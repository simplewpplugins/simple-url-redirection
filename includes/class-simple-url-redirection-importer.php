<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUR_Importer {

	/**
	 * Normalize an old URL / path into the key we match incoming requests against.
	 * - strips scheme + host (the old site may have been on the same or a different domain)
	 * - url-decodes
	 * - lowercases
	 * - strips query string (kept out of matching on purpose - old query strings rarely matter)
	 * - strips trailing slash (except root)
	 */
	public static function normalize_path( $url_or_path ) {
		$url_or_path = trim( (string) $url_or_path );
		if ( '' === $url_or_path ) {
			return '';
		}

		// If it looks like a full URL, pull out just the path.
		$parts = wp_parse_url( $url_or_path );
		$path  = isset( $parts['path'] ) ? $parts['path'] : $url_or_path;

		$path = rawurldecode( $path );
		$path = strtolower( $path );
		$path = '/' . ltrim( $path, '/' );
		if ( strlen( $path ) > 1 ) {
			$path = rtrim( $path, '/' );
		}

		return $path;
	}

	/**
	 * Turn a "new url" CSV value into an absolute URL on the new site.
	 */
	public static function resolve_new_url( $new_url ) {
		$new_url = trim( (string) $new_url );
		if ( '' === $new_url ) {
			return '';
		}

		if ( 0 === stripos( $new_url, 'http://' ) || 0 === stripos( $new_url, 'https://' ) ) {
			return self::is_valid_destination( $new_url ) ? esc_url_raw( $new_url, array( 'http', 'https' ) ) : '';
		}
		if ( preg_match( '#^(//|[a-z][a-z0-9+.-]*:)#i', $new_url ) || preg_match( '/[\r\n]/', $new_url ) ) {
			return '';
		}
		$destination = home_url( '/' . ltrim( $new_url, '/' ) );
		return self::is_valid_destination( $destination ) ? esc_url_raw( $destination, array( 'http', 'https' ) ) : '';
	}

	public static function is_valid_destination( $url ) {
		$parts = wp_parse_url( $url );
		return is_array( $parts ) && ! empty( $parts['host'] ) && isset( $parts['scheme'] ) && in_array( strtolower( $parts['scheme'] ), array( 'http', 'https' ), true ) && ! isset( $parts['user'] ) && ! isset( $parts['pass'] ) && ! preg_match( '/[\r\n]/', $url );
	}

	/**
	 * Import one uploaded CSV file (from $_FILES tmp_name path).
	 * Returns array of counters: inserted, updated, skipped, total, errors[].
	 */
	public static function import_file( $tmp_path, $original_name = '' ) {
		$result = array(
			'inserted' => 0,
			'updated'  => 0,
			'skipped'  => 0,
			'total'    => 0,
			'errors'   => array(),
		);

		if ( ! is_readable( $tmp_path ) || filesize( $tmp_path ) > 5 * 1024 * 1024 ) {
			$result['errors'][] = 'CSV is missing or exceeds the 5 MB limit.';
			return $result;
		}
		$handle = fopen( $tmp_path, 'r' );
		if ( ! $handle ) {
			$result['errors'][] = sprintf( 'Could not open %s', $original_name );
			return $result;
		}

		// Peek at the first row to detect a header + delimiter.
		$first_line = fgets( $handle );
		rewind( $handle );

		$delimiter = ( substr_count( $first_line, ';' ) > substr_count( $first_line, ',' ) ) ? ';' : ',';

		$row_index      = 0;
		$old_col        = 0;
		$new_col        = 1;
		$header_skipped = false;

		while ( false !== ( $row = fgetcsv( $handle, 0, $delimiter ) ) ) {
			$row_index++;
			if ( $row_index > 20000 ) {
				$result['errors'][] = 'Stopped after 20,000 rows.';
				break;
			}

			// Skip fully blank rows.
			if ( 1 === count( $row ) && ( null === $row[0] || '' === trim( (string) $row[0] ) ) ) {
				continue;
			}

			if ( ! $header_skipped ) {
				$header_skipped = true;
				$maybe_header    = array_map( 'strtolower', array_map( 'trim', $row ) );

				$old_idx = self::find_column( $maybe_header, array( 'old_url', 'old url', 'oldurl', 'old', 'from', 'source', 'source_url', 'original' ) );
				$new_idx = self::find_column( $maybe_header, array( 'new_url', 'new url', 'newurl', 'new', 'to', 'destination', 'target', 'redirect_to' ) );

				if ( false !== $old_idx || false !== $new_idx ) {
					// It really was a header row - use detected columns and move to next row.
					$old_col = ( false !== $old_idx ) ? $old_idx : 0;
					$new_col = ( false !== $new_idx ) ? $new_idx : 1;
					continue;
				}
				// Not a header row (no recognizable column names) - fall through and process as data using default columns 0/1.
			}

			$result['total']++;

			$old_raw = isset( $row[ $old_col ] ) ? $row[ $old_col ] : '';
			$new_raw = isset( $row[ $new_col ] ) ? $row[ $new_col ] : '';

			$old_path = self::normalize_path( $old_raw );
			$new_url  = self::resolve_new_url( $new_raw );

			if ( '' === $old_path || '' === $new_url ) {
				$result['skipped']++;
				continue;
			}

			$outcome = SUR_DB::upsert_mapping( $old_path, $new_url, $original_name );
			if ( 'inserted' === $outcome ) {
				$result['inserted']++;
			} else {
				$result['updated']++;
			}
		}

		fclose( $handle );
		return $result;
	}

	private static function find_column( $header_row, $candidates ) {
		foreach ( $header_row as $idx => $col_name ) {
			$col_name = trim( $col_name );
			if ( in_array( $col_name, $candidates, true ) ) {
				return $idx;
			}
		}
		// Fuzzy contains-match, e.g. "old_url_path"
		foreach ( $header_row as $idx => $col_name ) {
			foreach ( $candidates as $candidate ) {
				if ( false !== strpos( $col_name, $candidate ) ) {
					return $idx;
				}
			}
		}
		return false;
	}
}
