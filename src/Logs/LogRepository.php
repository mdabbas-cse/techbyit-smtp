<?php
namespace TechByIt\SMTP\Logs;

use TechByIt\SMTP\Support\ConfigurationException;

/** Reads and writes mail log rows. Times are stored in UTC and returned as ISO 8601. */
final class LogRepository {
	public const STATUSES     = array( 'sending', 'sent', 'failed' );
	public const PER_PAGE_MAX = 100;
	private const PURGE_BATCH = 1000;

	public function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'mailflow_smtp_logs';
	}

	public function create( array $entry ): int {
		global $wpdb;
		// Log rows are written once per message; there is nothing to cache.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		return $wpdb->insert( $this->table(), $entry ) ? (int) $wpdb->insert_id : 0;
	}

	public function update( int $id, array $fields ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return false !== $wpdb->update( $this->table(), $fields, array( 'id' => $id ) );
	}

	/** Validates REST query parameters; unknown keys are ignored. */
	public function normalize_filters( array $input ): array {
		$filters = array(
			'search'    => '',
			'status'    => '',
			'provider'  => '',
			'date_from' => '',
			'date_to'   => '',
			'page'      => 1,
			'per_page'  => 20,
		);
		$errors  = array();
		foreach ( $filters as $key => $default ) {
			if ( ! isset( $input[ $key ] ) ) {
				continue;
			}
			$value = $input[ $key ];
			if ( ! is_string( $value ) && ! is_int( $value ) ) {
				$errors[ $key ] = 'Invalid value.';
				continue;
			}
			$value = trim( (string) $value );
			if ( '' === $value ) {
				continue;
			}
			if ( 'search' === $key ) {
				if ( strlen( $value ) > 200 ) {
					$errors[ $key ] = 'Search text is too long.';
				} else {
					$filters[ $key ] = sanitize_text_field( $value );
				}
			} elseif ( 'status' === $key ) {
				if ( in_array( $value, self::STATUSES, true ) ) {
					$filters[ $key ] = $value;
				} else {
					$errors[ $key ] = 'Unknown status.';
				}
			} elseif ( 'provider' === $key ) {
				if ( preg_match( '/^[a-z0-9_]{1,64}$/', $value ) ) {
					$filters[ $key ] = $value;
				} else {
					$errors[ $key ] = 'Unknown provider.';
				}
			} elseif ( 'date_from' === $key || 'date_to' === $key ) {
				if ( preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $parts ) && checkdate( (int) $parts[2], (int) $parts[3], (int) $parts[1] ) ) {
					$filters[ $key ] = $value;
				} else {
					$errors[ $key ] = 'Use a YYYY-MM-DD date.';
				}
			} else {
				$max = 'per_page' === $key ? self::PER_PAGE_MAX : 100000;
				if ( preg_match( '/^\d{1,6}$/', $value ) && (int) $value >= 1 && (int) $value <= $max ) {
					$filters[ $key ] = (int) $value;
				} else {
					$errors[ $key ] = 'Enter a number from 1 to ' . $max . '.';
				}
			}
		}
		if ( ! $errors && '' !== $filters['date_from'] && '' !== $filters['date_to'] && $filters['date_from'] > $filters['date_to'] ) {
			$errors['date_to'] = 'The end date must not be before the start date.';
		}
		if ( $errors ) {
			// Field errors are returned as REST data, not rendered as HTML here.
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new ConfigurationException( 'Please correct the log filters.', $errors );
		}
		return $filters;
	}

	public function query( array $filters ): array {
		global $wpdb;
		$where  = array( '1 = 1' );
		$values = array( $this->table() );
		foreach ( array( 'status', 'provider' ) as $column ) {
			if ( '' !== $filters[ $column ] ) {
				$where[]  = "{$column} = %s";
				$values[] = $filters[ $column ];
			}
		}
		if ( '' !== $filters['search'] ) {
			$like     = '%' . $wpdb->esc_like( $filters['search'] ) . '%';
			$where[]  = '(subject LIKE %s OR recipients LIKE %s OR from_email LIKE %s)';
			$values[] = $like;
			$values[] = $like;
			$values[] = $like;
		}
		if ( '' !== $filters['date_from'] ) {
			$where[]  = 'created_at >= %s';
			$values[] = $this->utc_day_start( $filters['date_from'], false );
		}
		if ( '' !== $filters['date_to'] ) {
			$where[]  = 'created_at < %s';
			$values[] = $this->utc_day_start( $filters['date_to'], true );
		}
		$clause = implode( ' AND ', $where );
		// The clause holds only fixed SQL with placeholders; all values are passed to prepare().
		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$total = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM %i WHERE {$clause}", $values ) );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, provider, from_email, from_name, recipients, subject, status, error_code, error_message, created_at, sent_at FROM %i WHERE {$clause} ORDER BY id DESC LIMIT %d OFFSET %d",
				array_merge( $values, array( $filters['per_page'], ( $filters['page'] - 1 ) * $filters['per_page'] ) )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$this->assert_no_error();
		return array(
			'items'       => array_map( array( $this, 'summary' ), is_array( $rows ) ? $rows : array() ),
			'total'       => $total,
			'page'        => $filters['page'],
			'per_page'    => $filters['per_page'],
			'total_pages' => max( 1, (int) ceil( $total / $filters['per_page'] ) ),
		);
	}

	public function find( int $id ): ?array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM %i WHERE id = %d', $this->table(), $id ), ARRAY_A );
		$this->assert_no_error();
		return is_array( $row ) ? $this->details( $row ) : null;
	}

	/** Counts messages per status created in the last $days days. */
	public function stats( int $days ): array {
		global $wpdb;
		$since = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i WHERE created_at >= %s GROUP BY status', $this->table(), $since ), ARRAY_A );
		$this->assert_no_error();
		$counts = array(
			'total'   => 0,
			'sent'    => 0,
			'failed'  => 0,
			'sending' => 0,
		);
		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( in_array( $row['status'], self::STATUSES, true ) ) {
				$counts[ $row['status'] ] = (int) $row['total'];
				$counts['total']         += (int) $row['total'];
			}
		}
		return $counts;
	}

	public function recent_failures( int $limit ): array {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT id, provider, from_email, from_name, recipients, subject, status, error_code, error_message, created_at, sent_at FROM %i WHERE status = %s ORDER BY id DESC LIMIT %d', $this->table(), 'failed', $limit ), ARRAY_A );
		$this->assert_no_error();
		return array_map( array( $this, 'summary' ), is_array( $rows ) ? $rows : array() );
	}

	public function delete( int $id ): bool {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->delete( $this->table(), array( 'id' => $id ), array( '%d' ) );
		$this->assert_no_error();
		return $deleted > 0;
	}

	public function clear(): int {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query( $wpdb->prepare( 'DELETE FROM %i', $this->table() ) );
		$this->assert_no_error();
		return (int) $deleted;
	}

	/** Deletes rows older than the retention window in bounded batches. */
	public function purge_older_than( int $days ): int {
		global $wpdb;
		$before  = gmdate( 'Y-m-d H:i:s', time() - $days * DAY_IN_SECONDS );
		$deleted = 0;
		for ( $batch = 0; $batch < 50; $batch++ ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$count    = (int) $wpdb->query( $wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s ORDER BY id LIMIT %d', $this->table(), $before, self::PURGE_BATCH ) );
			$deleted += $count;
			if ( $count < self::PURGE_BATCH ) {
				break;
			}
		}
		return $deleted;
	}

	private function summary( array $row ): array {
		$recipients = $this->decode( $row['recipients'] ?? null );
		return array(
			'id'            => (int) $row['id'],
			'provider'      => (string) $row['provider'],
			'from_email'    => (string) $row['from_email'],
			'from_name'     => (string) $row['from_name'],
			'to'            => $this->strings( $recipients['to'] ?? array() ),
			'subject'       => (string) $row['subject'],
			'status'        => (string) $row['status'],
			'error_code'    => (string) $row['error_code'],
			'error_message' => (string) $row['error_message'],
			'created_at'    => $this->iso( $row['created_at'] ),
			'sent_at'       => $this->iso( $row['sent_at'] ),
		);
	}

	private function details( array $row ): array {
		$recipients = $this->decode( $row['recipients'] ?? null );
		return array_merge(
			$this->summary( $row ),
			array(
				'cc'            => $this->strings( $recipients['cc'] ?? array() ),
				'bcc'           => $this->strings( $recipients['bcc'] ?? array() ),
				'reply_to'      => $this->strings( $recipients['reply_to'] ?? array() ),
				'headers'       => $this->strings( $this->decode( $row['headers'] ?? null ) ),
				'attachments'   => $this->strings( $this->decode( $row['attachments'] ?? null ) ),
				'content_type'  => (string) $row['content_type'],
				'body'          => null === $row['body'] ? null : (string) $row['body'],
				'message_id'    => (string) $row['message_id'],
				'attempt_count' => (int) $row['attempt_count'],
				'updated_at'    => $this->iso( $row['updated_at'] ),
			)
		);
	}

	private function utc_day_start( string $date, bool $next_day ): string {
		$local = new \DateTimeImmutable( $date . ' 00:00:00', wp_timezone() );
		if ( $next_day ) {
			$local = $local->modify( '+1 day' );
		}
		return $local->setTimezone( new \DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i:s' );
	}

	private function iso( $value ): ?string {
		$time = is_string( $value ) && '' !== $value && '0000-00-00 00:00:00' !== $value ? strtotime( $value . ' UTC' ) : false;
		return false === $time ? null : gmdate( 'c', $time );
	}

	private function decode( $json ): array {
		$value = is_string( $json ) ? json_decode( $json, true ) : null;
		return is_array( $value ) ? $value : array();
	}

	private function strings( $values ): array {
		return is_array( $values ) ? array_values( array_filter( $values, 'is_string' ) ) : array();
	}

	private function assert_no_error(): void {
		global $wpdb;
		if ( '' !== (string) $wpdb->last_error ) {
			throw new \RuntimeException( 'Mail logs are unavailable.' );
		}
	}
}
