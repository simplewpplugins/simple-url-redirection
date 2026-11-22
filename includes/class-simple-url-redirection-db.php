<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUR_DB {

	public static function redirects_table() {
		global $wpdb;
		return $wpdb->prefix . 'sur_redirects';
	}

	public static function notfound_table() {
		global $wpdb;
		return $wpdb->prefix . 'sur_notfound';
	}

	public static function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$redirects_table = self::redirects_table();
		$notfound_table  = self::notfound_table();

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$sql1 = "CREATE TABLE {$redirects_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			old_path VARCHAR(500) NOT NULL,
			old_path_hash CHAR(32) NOT NULL,
			new_url TEXT NOT NULL,
			hits BIGINT UNSIGNED NOT NULL DEFAULT 0,
			source VARCHAR(255) DEFAULT '',
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY old_path_hash (old_path_hash)
		) {$charset_collate};";
		dbDelta( $sql1 );

		$sql2 = "CREATE TABLE {$notfound_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			requested_path VARCHAR(500) NOT NULL,
			requested_path_hash CHAR(32) NOT NULL,
			host VARCHAR(255) DEFAULT '',
			hits BIGINT UNSIGNED NOT NULL DEFAULT 1,
			suggested_url TEXT,
			suggested_score INT DEFAULT 0,
			resolved TINYINT(1) NOT NULL DEFAULT 0,
			first_seen DATETIME NOT NULL,
			last_seen DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY requested_path_hash (requested_path_hash)
		) {$charset_collate};";
		dbDelta( $sql2 );
	}

	/**
	 * Look up an exact redirect by normalized path.
	 */
	public static function find_by_path( $normalized_path ) {
		global $wpdb;
		$table = self::redirects_table();
		$hash  = md5( $normalized_path );
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE old_path_hash = %s", $hash ) );
		return $row;
	}

	public static function increment_hit( $id ) {
		global $wpdb;
		$table = self::redirects_table();
		$wpdb->query( $wpdb->prepare( "UPDATE {$table} SET hits = hits + 1 WHERE id = %d", $id ) );
	}

	/**
	 * Insert or update a mapping. Returns 'inserted' | 'updated' | 'skipped'.
	 */
	public static function upsert_mapping( $old_path, $new_url, $source = '' ) {
		global $wpdb;
		$table = self::redirects_table();
		$hash  = md5( $old_path );
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id, new_url FROM {$table} WHERE old_path_hash = %s", $hash ) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'new_url'    => $new_url,
					'source'     => $source,
					'updated_at' => $now,
				),
				array( 'id' => $existing->id )
			);
			return 'updated';
		}

		$wpdb->insert(
			$table,
			array(
				'old_path'      => $old_path,
				'old_path_hash' => $hash,
				'new_url'       => $new_url,
				'source'        => $source,
				'hits'          => 0,
				'created_at'    => $now,
				'updated_at'    => $now,
			)
		);
		return 'inserted';
	}

	public static function delete_mapping( $id ) {
		global $wpdb;
		$table = self::redirects_table();
		$wpdb->delete( $table, array( 'id' => (int) $id ) );
	}

	public static function get_mappings( $search = '', $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$table  = self::redirects_table();
		$offset = ( max( 1, $paged ) - 1 ) * $per_page;

		if ( $search ) {
			$like = '%' . $wpdb->esc_like( $search ) . '%';
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} WHERE old_path LIKE %s OR new_url LIKE %s ORDER BY updated_at DESC LIMIT %d OFFSET %d",
				$like, $like, $per_page, $offset
			) );
			$total = (int) $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE old_path LIKE %s OR new_url LIKE %s",
				$like, $like
			) );
		} else {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT * FROM {$table} ORDER BY updated_at DESC LIMIT %d OFFSET %d",
				$per_page, $offset
			) );
			$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		return array( 'rows' => $rows, 'total' => $total );
	}

	public static function count_mappings() {
		global $wpdb;
		$table = self::redirects_table();
		return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
	}

	/* ---------------- 404 log ---------------- */

	public static function log_notfound( $path, $host, $suggested_url = null, $score = 0 ) {
		global $wpdb;
		$table = self::notfound_table();
		$hash  = md5( $path );
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT id FROM {$table} WHERE requested_path_hash = %s", $hash ) );

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'hits'            => $wpdb->get_var( $wpdb->prepare( "SELECT hits FROM {$table} WHERE id = %d", $existing->id ) ) + 1,
					'last_seen'       => $now,
					'suggested_url'   => $suggested_url,
					'suggested_score' => (int) $score,
				),
				array( 'id' => $existing->id )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'requested_path'      => $path,
					'requested_path_hash' => $hash,
					'host'                => $host,
					'hits'                => 1,
					'suggested_url'       => $suggested_url,
					'suggested_score'     => (int) $score,
					'resolved'            => 0,
					'first_seen'          => $now,
					'last_seen'           => $now,
				)
			);
		}
	}

	public static function get_notfound_log( $only_unresolved = true, $paged = 1, $per_page = 20 ) {
		global $wpdb;
		$table  = self::notfound_table();
		$offset = ( max( 1, $paged ) - 1 ) * $per_page;
		$where  = $only_unresolved ? 'WHERE resolved = 0' : '';

		$rows  = $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$table} {$where} ORDER BY hits DESC, last_seen DESC LIMIT %d OFFSET %d",
			$per_page, $offset
		) );
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} {$where}" );

		return array( 'rows' => $rows, 'total' => $total );
	}

	public static function mark_notfound_resolved( $id ) {
		global $wpdb;
		$table = self::notfound_table();
		$wpdb->update( $table, array( 'resolved' => 1 ), array( 'id' => (int) $id ) );
	}

	public static function delete_notfound( $id ) {
		global $wpdb;
		$table = self::notfound_table();
		$wpdb->delete( $table, array( 'id' => (int) $id ) );
	}
}
