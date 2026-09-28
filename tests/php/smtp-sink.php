<?php
/** Minimal local SMTP sink for integration tests. Accepts two messages, stores nothing. */
declare(strict_types=1);

$server = stream_socket_server( 'tcp://127.0.0.1:2525', $errno, $error );
if ( false === $server ) { exit( 1 ); }
stream_set_timeout( $server, 15 );
for ( $message = 0; $message < 2; $message++ ) {
	$client = stream_socket_accept( $server, 15 );
	if ( false === $client ) { exit( 2 ); }
	fwrite( $client, "220 localhost ready\r\n" );
	$in_data = false;
	while ( false !== ( $line = fgets( $client ) ) ) {
		$command = strtoupper( substr( $line, 0, 4 ) );
		if ( $in_data ) {
			if ( ".\r\n" === $line || ".\n" === $line ) {
				$in_data = false;
				fwrite( $client, "250 queued\r\n" );
			}
			continue;
		}
		if ( 'EHLO' === $command ) { fwrite( $client, "250-localhost\r\n250 SIZE 10000000\r\n" ); }
		elseif ( 'DATA' === $command ) { $in_data = true; fwrite( $client, "354 end with dot\r\n" ); }
		elseif ( 'QUIT' === $command ) { fwrite( $client, "221 bye\r\n" ); break; }
		else { fwrite( $client, "250 ok\r\n" ); }
	}
	fclose( $client );
}
fclose( $server );
