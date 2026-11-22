<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUR_Admin {

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'admin_post_sur_import_csv', array( $this, 'handle_csv_import' ) );
		add_action( 'admin_post_sur_add_manual', array( $this, 'handle_add_manual' ) );
		add_action( 'admin_post_sur_delete_redirect', array( $this, 'handle_delete_redirect' ) );
		add_action( 'admin_post_sur_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_sur_approve_suggestion', array( $this, 'handle_approve_suggestion' ) );
		add_action( 'admin_post_sur_dismiss_notfound', array( $this, 'handle_dismiss_notfound' ) );
		add_action( 'admin_post_sur_export_csv', array( $this, 'handle_export_csv' ) );
	}

	public function enqueue_assets( $hook ) {
		if ( false === strpos( $hook, 'simple-url-redirection' ) ) {
			return;
		}
		wp_enqueue_style( 'simple-url-redirection-admin', SIMPLE_URL_REDIRECTION_URL . 'assets/admin.css', array(), SIMPLE_URL_REDIRECTION_VERSION );
	}

	public function add_menu() {
		add_menu_page(
			'Simple URL Redirection',
			'Simple URL Redirection',
			'manage_options',
			'simple-url-redirection',
			array( $this, 'render_import_page' ),
			'dashicons-randomize',
			80
		);
		add_submenu_page( 'simple-url-redirection', 'Import CSV', 'Import CSV', 'manage_options', 'simple-url-redirection', array( $this, 'render_import_page' ) );
		add_submenu_page( 'simple-url-redirection', 'All Redirects', 'All Redirects', 'manage_options', 'simple-url-redirection-list', array( $this, 'render_list_page' ) );
		add_submenu_page( 'simple-url-redirection', '404 Suggestions', '404 Suggestions', 'manage_options', 'simple-url-redirection-404', array( $this, 'render_404_page' ) );
		add_submenu_page( 'simple-url-redirection', 'Settings', 'Settings', 'manage_options', 'simple-url-redirection-settings', array( $this, 'render_settings_page' ) );
	}

	private function nav_tabs( $active ) {
		$tabs = array(
			'simple-url-redirection'         => 'Import CSV',
			'simple-url-redirection-list'    => 'All Redirects (' . SUR_DB::count_mappings() . ')',
			'simple-url-redirection-404'     => '404 Suggestions',
			'simple-url-redirection-settings' => 'Settings',
		);
		echo '<h2 class="nav-tab-wrapper">';
		foreach ( $tabs as $slug => $label ) {
			$class = ( $slug === $active ) ? 'nav-tab nav-tab-active' : 'nav-tab';
			printf(
				'<a href="%s" class="%s">%s</a>',
				esc_url( admin_url( 'admin.php?page=' . $slug ) ),
				esc_attr( $class ),
				esc_html( $label )
			);
		}
		echo '</h2>';
	}

	/* ---------------------------------------------------------------------
	 * Import CSV page
	 * ------------------------------------------------------------------ */

	public function render_import_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		?>
		<div class="wrap">
			<h1>Simple URL Redirection</h1>
			<?php $this->nav_tabs( 'simple-url-redirection' ); ?>

			<?php if ( isset( $_GET['imported'] ) ) : ?>
				<?php $this->render_import_results(); ?>
			<?php endif; ?>

			<div class="card" style="max-width:700px;margin-top:20px;">
				<h2>Upload CSV file(s)</h2>
				<p>Each CSV should have an old URL column and a new URL column (any of the header names <code>old_url</code>, <code>from</code>, <code>source_url</code> / <code>new_url</code>, <code>to</code>, <code>destination</code> are auto-detected - or just leave the first two columns as old, new). Old URLs can be full URLs or just paths; new URLs can be a full <?php echo esc_html( home_url() ); ?> URL or a relative path. You can select multiple files at once.</p>

				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'sur_import_csv' ); ?>
					<input type="hidden" name="action" value="sur_import_csv" />
					<p>
						<input type="file" name="sur_csv_files[]" accept=".csv" multiple required />
					</p>
					<p class="submit">
						<button type="submit" class="button button-primary">Upload &amp; Import</button>
					</p>
				</form>
			</div>

			<div class="card" style="max-width:700px;margin-top:20px;">
				<h2>Export current redirects</h2>
				<p>Download every redirect currently stored (useful for backup or editing in bulk).</p>
				<a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=sur_export_csv' ), 'sur_export_csv' ) ); ?>">Export CSV</a>
			</div>
		</div>
		<?php
	}

	private function render_import_results() {
		$data = get_transient( 'sur_last_import_' . get_current_user_id() );
		if ( ! $data ) {
			return;
		}
		echo '<div class="notice notice-success"><p><strong>Import complete.</strong></p><ul style="margin-left:20px;list-style:disc;">';
		foreach ( $data as $file => $r ) {
			printf(
				'<li><strong>%s</strong>: %d row(s) - %d added, %d updated, %d skipped%s</li>',
				esc_html( $file ),
				(int) $r['total'],
				(int) $r['inserted'],
				(int) $r['updated'],
				(int) $r['skipped'],
				$r['errors'] ? ' - <span style="color:#b32d2e;">' . esc_html( implode( '; ', $r['errors'] ) ) . '</span>' : ''
			);
		}
		echo '</ul></div>';
		delete_transient( 'sur_last_import_' . get_current_user_id() );
	}

	public function handle_csv_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_import_csv' );

		$results = array();

		if ( ! empty( $_FILES['sur_csv_files'] ) && is_array( $_FILES['sur_csv_files']['tmp_name'] ) ) {
			$files = $_FILES['sur_csv_files'];
			foreach ( $files['tmp_name'] as $i => $tmp_name ) {
				if ( empty( $tmp_name ) || UPLOAD_ERR_OK !== $files['error'][ $i ] ) {
					continue;
				}
				$name              = sanitize_file_name( $files['name'][ $i ] );
				if ( 'csv' !== strtolower( pathinfo( $name, PATHINFO_EXTENSION ) ) || ! is_uploaded_file( $tmp_name ) ) {
					continue;
				}
				$results[ $name ]  = SUR_Importer::import_file( $tmp_name, $name );
			}
		}

		set_transient( 'sur_last_import_' . get_current_user_id(), $results, 60 );

		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection&imported=1' ) );
		exit;
	}

	private function csv_safe_cell( $value ) {
		return preg_match( '/^[\s]*[=+@\-\t\r]/', (string) $value ) ? "'" . $value : $value;
	}

	public function handle_export_csv() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_export_csv' );

		global $wpdb;
		$table = SUR_DB::redirects_table();
		$rows  = $wpdb->get_results( "SELECT old_path, new_url FROM {$table} ORDER BY old_path ASC" );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=simple-url-redirection-export.csv' );

		$out = fopen( 'php://output', 'w' );
		fputcsv( $out, array( 'old_url', 'new_url' ) );
		foreach ( $rows as $row ) {
			fputcsv( $out, array( $this->csv_safe_cell( $row->old_path ), $this->csv_safe_cell( $row->new_url ) ) );
		}
		fclose( $out );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * All Redirects list page
	 * ------------------------------------------------------------------ */

	public function render_list_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per_page = 20;
		$data     = SUR_DB::get_mappings( $search, $paged, $per_page );
		?>
		<div class="wrap">
			<h1>Simple URL Redirection</h1>
			<?php $this->nav_tabs( 'simple-url-redirection-list' ); ?>

			<div class="card" style="max-width:500px;margin-top:20px;">
				<h2>Add a redirect manually</h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'sur_add_manual' ); ?>
					<input type="hidden" name="action" value="sur_add_manual" />
					<p>
						<label>Old URL / path<br/>
						<input type="text" name="old_path" class="regular-text" placeholder="/old-page/" required /></label>
					</p>
					<p>
						<label>New URL / path<br/>
						<input type="text" name="new_url" class="regular-text" placeholder="/new-page/ or full URL" required /></label>
					</p>
					<p class="submit"><button class="button button-primary">Add redirect</button></p>
				</form>
			</div>

			<form method="get" style="margin-top:20px;">
				<input type="hidden" name="page" value="simple-url-redirection-list" />
				<p class="search-box">
					<input type="search" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="Search old or new URL" />
					<button class="button">Search</button>
				</p>
			</form>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Old path</th>
						<th>New URL</th>
						<th>Hits</th>
						<th>Source</th>
						<th>Updated</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $data['rows'] ) ) : ?>
					<tr><td colspan="6">No redirects yet - import a CSV to get started.</td></tr>
				<?php else : ?>
					<?php foreach ( $data['rows'] as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row->old_path ); ?></code></td>
							<td><a href="<?php echo esc_url( $row->new_url ); ?>" target="_blank"><?php echo esc_html( $row->new_url ); ?></a></td>
							<td><?php echo (int) $row->hits; ?></td>
							<td><?php echo esc_html( $row->source ); ?></td>
							<td><?php echo esc_html( $row->updated_at ); ?></td>
							<td>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="return confirm('Delete this redirect?');">
									<?php wp_nonce_field( 'sur_delete_redirect' ); ?>
									<input type="hidden" name="action" value="sur_delete_redirect" />
									<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>" />
									<button class="button-link-delete">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php
			$total_pages = max( 1, ceil( $data['total'] / $per_page ) );
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				) );
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	public function handle_add_manual() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_add_manual' );

		$old_raw = isset( $_POST['old_path'] ) ? wp_unslash( $_POST['old_path'] ) : '';
		$new_raw = isset( $_POST['new_url'] ) ? wp_unslash( $_POST['new_url'] ) : '';

		$old_path = SUR_Importer::normalize_path( $old_raw );
		$new_url  = SUR_Importer::resolve_new_url( $new_raw );

		if ( $old_path && $new_url ) {
			SUR_DB::upsert_mapping( $old_path, $new_url, 'manual' );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection-list' ) );
		exit;
	}

	public function handle_delete_redirect() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_delete_redirect' );
		SUR_DB::delete_mapping( isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection-list' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * 404 suggestions page
	 * ------------------------------------------------------------------ */

	public function render_404_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
		$per_page = 20;
		$data     = SUR_DB::get_notfound_log( true, $paged, $per_page );
		?>
		<div class="wrap">
			<h1>Simple URL Redirection</h1>
			<?php $this->nav_tabs( 'simple-url-redirection-404' ); ?>

			<p style="margin-top:15px;">These are old links that still 404 (or 404'd until a closest-match guess kicked in). Approve a suggestion to make it a permanent 301 redirect, or dismiss it if it's not worth redirecting.</p>

			<table class="wp-list-table widefat fixed striped">
				<thead>
					<tr>
						<th>Requested path</th>
						<th>Hits</th>
						<th>Suggested match</th>
						<th>Confidence</th>
						<th>Last seen</th>
						<th></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( empty( $data['rows'] ) ) : ?>
					<tr><td colspan="6">Nothing to review right now.</td></tr>
				<?php else : ?>
					<?php foreach ( $data['rows'] as $row ) : ?>
						<tr>
							<td><code><?php echo esc_html( $row->requested_path ); ?></code></td>
							<td><?php echo (int) $row->hits; ?></td>
							<td>
								<?php if ( $row->suggested_url ) : ?>
									<a href="<?php echo esc_url( $row->suggested_url ); ?>" target="_blank"><?php echo esc_html( $row->suggested_url ); ?></a>
								<?php else : ?>
									<em>no match found</em>
								<?php endif; ?>
							</td>
							<td><?php echo $row->suggested_score ? (int) $row->suggested_score . '%' : '-'; ?></td>
							<td><?php echo esc_html( $row->last_seen ); ?></td>
							<td style="white-space:nowrap;">
								<?php if ( $row->suggested_url ) : ?>
									<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
										<?php wp_nonce_field( 'sur_approve_suggestion' ); ?>
										<input type="hidden" name="action" value="sur_approve_suggestion" />
										<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>" />
										<button class="button button-small button-primary">Approve as 301</button>
									</form>
								<?php endif; ?>
								<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
									<?php wp_nonce_field( 'sur_dismiss_notfound' ); ?>
									<input type="hidden" name="action" value="sur_dismiss_notfound" />
									<input type="hidden" name="id" value="<?php echo (int) $row->id; ?>" />
									<button class="button button-small">Dismiss</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				<?php endif; ?>
				</tbody>
			</table>

			<?php
			$total_pages = max( 1, ceil( $data['total'] / $per_page ) );
			if ( $total_pages > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">';
				echo paginate_links( array(
					'base'      => add_query_arg( 'paged', '%#%' ),
					'format'    => '',
					'current'   => $paged,
					'total'     => $total_pages,
					'prev_text' => '&laquo;',
					'next_text' => '&raquo;',
				) );
				echo '</div></div>';
			}
			?>
		</div>
		<?php
	}

	public function handle_approve_suggestion() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_approve_suggestion' );

		global $wpdb;
		$id    = isset( $_POST['id'] ) ? (int) $_POST['id'] : 0;
		$table = SUR_DB::notfound_table();
		$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

		if ( $row && $row->suggested_url ) {
			SUR_DB::upsert_mapping( $row->requested_path, $row->suggested_url, 'approved-suggestion' );
			SUR_DB::mark_notfound_resolved( $id );
		}

		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection-404' ) );
		exit;
	}

	public function handle_dismiss_notfound() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_dismiss_notfound' );
		SUR_DB::mark_notfound_resolved( isset( $_POST['id'] ) ? (int) $_POST['id'] : 0 );
		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection-404' ) );
		exit;
	}

	/* ---------------------------------------------------------------------
	 * Settings page
	 * ------------------------------------------------------------------ */

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings   = wp_parse_args( get_option( 'sur_redirects_settings', array() ), array(
			'canonical_host'        => '',
			'force_https'           => 0,
			'enable_fuzzy'          => 0,
			'fuzzy_threshold'       => 55,
			'fuzzy_redirect_status' => 302,
		) );
		$home_host  = wp_parse_url( home_url(), PHP_URL_HOST );
		?>
		<div class="wrap">
			<h1>Simple URL Redirection</h1>
			<?php $this->nav_tabs( 'simple-url-redirection-settings' ); ?>

			<?php if ( isset( $_GET['saved'] ) ) : ?>
				<div class="notice notice-success"><p>Settings saved.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="max-width:700px;margin-top:20px;">
				<?php wp_nonce_field( 'sur_save_settings' ); ?>
				<input type="hidden" name="action" value="sur_save_settings" />

				<table class="form-table">
					<tr>
						<th scope="row">Canonical domain</th>
						<td>
							<input type="text" name="canonical_host" value="<?php echo esc_attr( $settings['canonical_host'] ); ?>" class="regular-text" placeholder="<?php echo esc_attr( $home_host ); ?>" />
							<p class="description">Leave blank to auto-use your WordPress Site Address (<code><?php echo esc_html( $home_host ); ?></code>). Every request - with or without <code>www</code> - will be 301 redirected to this exact host, so both variants always resolve correctly.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Force HTTPS</th>
						<td>
							<label><input type="checkbox" name="force_https" value="1" <?php checked( $settings['force_https'], 1 ); ?> /> Redirect any plain-HTTP request to HTTPS</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Suggest closest match on 404</th>
						<td>
							<label><input type="checkbox" name="enable_fuzzy" value="1" <?php checked( $settings['enable_fuzzy'], 1 ); ?> /> When an old URL has no CSV mapping, try to find the closest matching page/post/product automatically</label>
						</td>
					</tr>
					<tr>
						<th scope="row">Match confidence threshold</th>
						<td>
							<input type="number" min="0" max="100" name="fuzzy_threshold" value="<?php echo esc_attr( $settings['fuzzy_threshold'] ); ?>" style="width:80px;" /> %
							<p class="description">Lower = more suggestions but less accurate. 55% is a reasonable default.</p>
						</td>
					</tr>
					<tr>
						<th scope="row">Suggested match redirect type</th>
						<td>
							<select name="fuzzy_redirect_status">
								<option value="302" <?php selected( $settings['fuzzy_redirect_status'], 302 ); ?>>302 Temporary (recommended until reviewed)</option>
								<option value="301" <?php selected( $settings['fuzzy_redirect_status'], 301 ); ?>>301 Permanent</option>
							</select>
							<p class="description">Suggestions are also logged under "404 Suggestions" so you can approve them into permanent 301s from a CSV-style mapping.</p>
						</td>
					</tr>
				</table>

				<p class="submit"><button class="button button-primary">Save settings</button></p>
			</form>
		</div>
		<?php
	}

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Not allowed' );
		}
		check_admin_referer( 'sur_save_settings' );

		$settings = array(
			'canonical_host'        => isset( $_POST['canonical_host'] ) ? strtolower( sanitize_text_field( wp_unslash( $_POST['canonical_host'] ) ) ) : '',
			'force_https'           => isset( $_POST['force_https'] ) ? 1 : 0,
			'enable_fuzzy'          => isset( $_POST['enable_fuzzy'] ) ? 1 : 0,
			'fuzzy_threshold'       => isset( $_POST['fuzzy_threshold'] ) ? max( 0, min( 100, (int) $_POST['fuzzy_threshold'] ) ) : 55,
			'fuzzy_redirect_status' => isset( $_POST['fuzzy_redirect_status'] ) && '301' === $_POST['fuzzy_redirect_status'] ? 301 : 302,
		);

		if ( '' !== $settings['canonical_host'] && ! preg_match( '/^[a-z0-9.-]+$/', $settings['canonical_host'] ) ) {
			$settings['canonical_host'] = '';
		}
		update_option( 'sur_redirects_settings', $settings );

		wp_safe_redirect( admin_url( 'admin.php?page=simple-url-redirection-settings&saved=1' ) );
		exit;
	}
}
