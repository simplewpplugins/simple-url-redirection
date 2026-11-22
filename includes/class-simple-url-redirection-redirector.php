<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUR_Redirector {

	public function __construct() {
		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
	}

	private function get_settings() {
		$defaults = array(
			'canonical_host'        => '',
			'force_https'           => 0,
			'enable_fuzzy'          => 0,
			'fuzzy_threshold'       => 55,
			'fuzzy_redirect_status' => 302,
		);
		return wp_parse_args( get_option( 'sur_redirects_settings', array() ), $defaults );
	}

	/**
	 * The canonical host we want every request to end up on, e.g. "www.example.com".
	 */
	private function canonical_host( $settings ) {
		if ( ! empty( $settings['canonical_host'] ) ) {
			return strtolower( $settings['canonical_host'] );
		}
		$home = wp_parse_url( home_url() );
		return isset( $home['host'] ) ? strtolower( $home['host'] ) : '';
	}

	public function maybe_redirect() {
		if ( is_admin() ) {
			return;
		}

		$settings   = $this->get_settings();
		$want_host  = $this->canonical_host( $settings );
		$host       = isset( $_SERVER['HTTP_HOST'] ) ? strtolower( sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ) ) ) : '';
		$host       = preg_replace( '/:[0-9]+$/', '', $host );
		$is_https   = is_ssl();
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		if ( ! is_string( $request_uri ) || '/' !== substr( $request_uri, 0, 1 ) || '/' === substr( $request_uri, 0, 2 ) || preg_match( '/[\r\n]/', $request_uri ) ) {
			return;
		}
		$request_uri = esc_url_raw( $request_uri );

		// 1. Canonical host / scheme enforcement - runs for every request (with or without www),
		//    so this fixes the www vs non-www problem site-wide, independent of the redirect map.
		$needs_host_fix   = ( $want_host && $host && $host !== $want_host );
		// Do not force a redirect when the configured host is invalid.
		if ( ! preg_match( '/^[a-z0-9.-]+$/i', $want_host ) ) {
			return;
		}
		$needs_scheme_fix = ( ! empty( $settings['force_https'] ) && ! $is_https );

		if ( ( $needs_host_fix || $needs_scheme_fix ) && $want_host ) {
			$scheme = $needs_scheme_fix ? 'https' : ( $is_https ? 'https' : 'http' );
			$target = $scheme . '://' . $want_host . $request_uri;
			// Use a validated destination; canonical host may intentionally differ from home_url().
			$target = esc_url_raw( $target, array( 'http', 'https' ) );
			wp_redirect( $target, 301, 'Simple URL Redirection' );
			exit;
		}

		// 2. Only intervene further if WordPress couldn't find anything for this URL.
		if ( ! is_404() ) {
			return;
		}

		$path       = wp_parse_url( $request_uri, PHP_URL_PATH );
		$normalized = SUR_Importer::normalize_path( $path );

		// 2a. Exact match from an imported CSV.
		$mapping = SUR_DB::find_by_path( $normalized );
		if ( $mapping ) {
			if ( ! SUR_Importer::is_valid_destination( $mapping->new_url ) || untrailingslashit( $mapping->new_url ) === untrailingslashit( home_url( $request_uri ) ) ) {
				return;
			}
			SUR_DB::increment_hit( $mapping->id );
			wp_redirect( esc_url_raw( $mapping->new_url, array( 'http', 'https' ) ), 301, 'Simple URL Redirection' ); // External destinations are supported for administrator-created mappings.
			exit;
		}

		// 2b. No exact mapping - try to suggest the closest existing page.
		$suggestion = null;
		if ( ! empty( $settings['enable_fuzzy'] ) ) {
			$suggestion = SUR_Matcher::find_best_match( $path, (int) $settings['fuzzy_threshold'] );
		}

		if ( $suggestion ) {
			SUR_DB::log_notfound( $normalized, $host, $suggestion['url'], $suggestion['score'] );
			$status = (int) $settings['fuzzy_redirect_status'];
			wp_safe_redirect( $suggestion['url'], $status ? $status : 302 );
			exit;
		}

		// 2c. Nothing found at all - log it so the admin can see what's still 404ing
		//     and add a manual redirect or approve a future suggestion.
		SUR_DB::log_notfound( $normalized, $host, null, 0 );
		// Let WordPress render its normal 404 page.
	}
}
