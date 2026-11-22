<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class SUR_Matcher {

	/**
	 * Turn a URL path like /old-category/some-product-name/ into a search phrase:
	 * "some product name" (uses the most specific / last segment).
	 */
	public static function path_to_phrase( $path ) {
		$path = trim( $path, '/' );
		if ( '' === $path ) {
			return '';
		}
		$segments = explode( '/', $path );
		$last     = end( $segments );
		$last     = preg_replace( '/\.(html?|php)$/i', '', $last );
		$phrase   = str_replace( array( '-', '_', '+' ), ' ', $last );
		$phrase   = preg_replace( '/\s+/', ' ', $phrase );
		return trim( $phrase );
	}

	/**
	 * Find the best matching published post/page/product for a given request path.
	 * Returns array( 'url' => ..., 'score' => 0-100 ) or null if nothing decent found.
	 */
	public static function find_best_match( $path, $threshold = 55 ) {
		$phrase = self::path_to_phrase( $path );
		if ( '' === $phrase || strlen( $phrase ) < 3 ) {
			return null;
		}

		$post_types = array( 'post', 'page' );
		if ( post_type_exists( 'product' ) ) {
			$post_types[] = 'product'; // WooCommerce, if present
		}

		$query = new WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				's'              => $phrase,
				'posts_per_page' => 8,
				'no_found_rows'  => true,
			)
		);

		if ( ! $query->have_posts() ) {
			return null;
		}

		$best_score = 0;
		$best_url   = null;

		foreach ( $query->posts as $candidate ) {
			$title_score = self::similarity( $phrase, $candidate->post_title );
			$slug_score  = self::similarity( $phrase, str_replace( array( '-', '_' ), ' ', $candidate->post_name ) );
			$score       = max( $title_score, $slug_score );

			if ( $score > $best_score ) {
				$best_score = $score;
				$best_url   = get_permalink( $candidate );
			}
		}

		wp_reset_postdata();

		if ( $best_url && $best_score >= $threshold ) {
			return array( 'url' => $best_url, 'score' => (int) $best_score );
		}

		return null;
	}

	/**
	 * Percentage similarity between two strings (case-insensitive).
	 */
	private static function similarity( $a, $b ) {
		$a = strtolower( trim( $a ) );
		$b = strtolower( trim( $b ) );
		if ( '' === $a || '' === $b ) {
			return 0;
		}
		similar_text( $a, $b, $percent );
		return $percent;
	}
}
