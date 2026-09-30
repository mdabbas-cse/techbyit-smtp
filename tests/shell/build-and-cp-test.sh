#!/usr/bin/env bash

set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd -P)"
SCRIPT_UNDER_TEST="$PROJECT_ROOT/scripts/build-and-cp"
PASS_COUNT=0

fail() {
    printf 'FAIL: %s\n' "$1" >&2
    exit 1
}

assert_file_exists() {
    [[ -f "$1" ]] || fail "expected file to exist: $1"
}

assert_file_missing() {
    [[ ! -e "$1" ]] || fail "expected path to be absent: $1"
}

assert_file_contains() {
    grep -Fq -- "$2" "$1" || fail "expected $1 to contain: $2"
}

new_fixture() {
    FIXTURE_DIR="$(mktemp -d)"
    SOURCE_DIR="$FIXTURE_DIR/source"
    SVN_TRUNK="$FIXTURE_DIR/svn/trunk"
    FAKE_BIN="$FIXTURE_DIR/bin"
    COMMAND_LOG="$FIXTURE_DIR/commands.log"

    mkdir -p "$SOURCE_DIR/src" "$SOURCE_DIR/dist" "$SOURCE_DIR/vendor" \
        "$SOURCE_DIR/licenses" "$SOURCE_DIR/tests" "$SOURCE_DIR/.svn" \
        "$SVN_TRUNK" "$FAKE_BIN"

    printf '{}\n' > "$SOURCE_DIR/package.json"
    printf '{}\n' > "$SOURCE_DIR/composer.json"
    printf '{}\n' > "$SOURCE_DIR/composer.lock"
    printf 'plugin\n' > "$SOURCE_DIR/techbyit-smtp.php"
    printf 'readme\n' > "$SOURCE_DIR/readme.txt"
    printf 'license\n' > "$SOURCE_DIR/LICENSE"
    printf 'php\n' > "$SOURCE_DIR/src/Plugin.php"
    printf 'js\n' > "$SOURCE_DIR/dist/admin.js"
    printf 'autoload\n' > "$SOURCE_DIR/vendor/autoload.php"
    printf 'third-party\n' > "$SOURCE_DIR/licenses/react-mit.txt"
    printf 'development-only\n' > "$SOURCE_DIR/tests/test.php"
    printf 'metadata\n' > "$SOURCE_DIR/.svn/entries"
    printf 'old\n' > "$SVN_TRUNK/old-file.php"

    cat > "$FAKE_BIN/composer" <<'EOF'
#!/usr/bin/env bash
printf 'composer %s\n' "$*" >> "$BUILD_AND_CP_TEST_LOG"
exit "${BUILD_AND_CP_COMPOSER_EXIT:-0}"
EOF
    cat > "$FAKE_BIN/pnpm" <<'EOF'
#!/usr/bin/env bash
printf 'pnpm %s\n' "$*" >> "$BUILD_AND_CP_TEST_LOG"
exit "${BUILD_AND_CP_PNPM_EXIT:-0}"
EOF
    cat > "$FAKE_BIN/rsync" <<'EOF'
#!/usr/bin/env bash
if [[ -n "${BUILD_AND_CP_RSYNC_EXIT:-}" ]]; then
    exit "$BUILD_AND_CP_RSYNC_EXIT"
fi
exec /usr/bin/rsync "$@"
EOF
    chmod +x "$FAKE_BIN/composer" "$FAKE_BIN/pnpm" "$FAKE_BIN/rsync"
}

run_command() {
    PATH="$FAKE_BIN:$PATH" \
    BUILD_AND_CP_SOURCE_DIR="$SOURCE_DIR" \
    BUILD_AND_CP_SVN_TRUNK="$SVN_TRUNK" \
    BUILD_AND_CP_TEST_LOG="$COMMAND_LOG" \
        "$SCRIPT_UNDER_TEST" "$@"
}

test_success_copies_production_files_without_deleting_stale_files() {
    new_fixture

    run_command > "$FIXTURE_DIR/output"

    assert_file_exists "$SVN_TRUNK/techbyit-smtp.php"
    assert_file_exists "$SVN_TRUNK/src/Plugin.php"
    assert_file_exists "$SVN_TRUNK/dist/admin.js"
    assert_file_exists "$SVN_TRUNK/vendor/autoload.php"
    assert_file_exists "$SVN_TRUNK/old-file.php"
    assert_file_missing "$SVN_TRUNK/tests"
    assert_file_missing "$SVN_TRUNK/.svn"
    assert_file_contains "$COMMAND_LOG" 'composer install --no-dev --optimize-autoloader'
    assert_file_contains "$COMMAND_LOG" 'pnpm build'
    assert_file_contains "$FIXTURE_DIR/output" 'No SVN commands were executed.'
}

test_build_failure_preserves_exit_code_and_skips_copy() {
    new_fixture

    set +e
    BUILD_AND_CP_COMPOSER_EXIT=23 run_command > "$FIXTURE_DIR/output" 2>&1
    status=$?
    set -e

    [[ $status -eq 23 ]] || fail "expected build exit 23, got $status"
    assert_file_missing "$SVN_TRUNK/techbyit-smtp.php"
    assert_file_exists "$SVN_TRUNK/old-file.php"
    assert_file_contains "$FIXTURE_DIR/output" 'Build failed'
    assert_file_contains "$FIXTURE_DIR/output" 'SVN files were NOT copied.'
}

test_dry_run_reports_files_without_copying() {
    new_fixture

    run_command --dry-run > "$FIXTURE_DIR/output"

    assert_file_missing "$SVN_TRUNK/techbyit-smtp.php"
    assert_file_exists "$SVN_TRUNK/old-file.php"
    assert_file_contains "$FIXTURE_DIR/output" 'techbyit-smtp.php'
    assert_file_contains "$FIXTURE_DIR/output" 'Dry run completed. No files were copied.'
}

test_missing_build_configuration_stops_before_build() {
    new_fixture
    rm "$SOURCE_DIR/package.json"

    set +e
    run_command > "$FIXTURE_DIR/output" 2>&1
    status=$?
    set -e

    [[ $status -ne 0 ]] || fail 'expected missing package.json to fail'
    [[ ! -s "$COMMAND_LOG" ]] || fail 'expected no build commands to run'
    assert_file_missing "$SVN_TRUNK/techbyit-smtp.php"
    assert_file_contains "$FIXTURE_DIR/output" 'Build configuration does not exist'
}

test_copy_failure_preserves_rsync_exit_code() {
    new_fixture

    set +e
    BUILD_AND_CP_RSYNC_EXIT=31 run_command > "$FIXTURE_DIR/output" 2>&1
    status=$?
    set -e

    [[ $status -eq 31 ]] || fail "expected copy exit 31, got $status"
    assert_file_contains "$FIXTURE_DIR/output" 'Copy failed'
}

run_test() {
    local name="$1"
    "$name"
    PASS_COUNT=$((PASS_COUNT + 1))
    printf 'ok %d - %s\n' "$PASS_COUNT" "$name"
}

[[ -x "$SCRIPT_UNDER_TEST" ]] || fail "command is not installed in the repository: $SCRIPT_UNDER_TEST"

run_test test_success_copies_production_files_without_deleting_stale_files
run_test test_build_failure_preserves_exit_code_and_skips_copy
run_test test_dry_run_reports_files_without_copying
run_test test_missing_build_configuration_stops_before_build
run_test test_copy_failure_preserves_rsync_exit_code

printf '%d tests passed\n' "$PASS_COUNT"
