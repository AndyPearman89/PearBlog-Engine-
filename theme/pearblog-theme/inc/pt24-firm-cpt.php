<?php
/**
 * PT24.PRO — company (firm) profile CPT.
 *
 * Registers the `pt24_firm` post type and routes /firma/{slug}/ to a profile
 * template. Mirrors the landing CPT's request-filter routing because generic
 * rewrite rules are unreliable on this host. Host-guarded (PT24 install only).
 *
 * @package PearBlog
 * @subpackage PT24
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PearBlog_PT24_Firm_CPT {

	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_post_type' ) );
		add_filter( 'request', array( __CLASS__, 'maybe_route_firm' ) );
		add_filter( 'template_include', array( __CLASS__, 'load_template' ) );
		add_filter( 'post_type_link', array( __CLASS__, 'custom_permalink' ), 10, 2 );
	}

	public static function register_post_type() {
		register_post_type( 'pt24_firm', array(
			'labels'             => array(
				'name'          => 'Firmy PT24',
				'singular_name' => 'Firma PT24',
				'menu_name'     => 'Firmy PT24',
			),
			'public'             => true,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'menu_icon'          => 'dashicons-building',
			'menu_position'      => 28,
			'supports'           => array( 'title', 'editor', 'custom-fields', 'thumbnail' ),
			'has_archive'        => false,
			'rewrite'            => array( 'slug' => 'firma', 'with_front' => false ),
			'capability_type'    => 'post',
			'show_in_rest'       => true,
		) );

		add_filter( 'query_vars', function ( $vars ) {
			$vars[] = 'pt24_firm';
			return $vars;
		} );
	}

	/**
	 * Route /firma/{slug}/ to the firm CPT via the request filter.
	 */
	public static function maybe_route_firm( $query_vars ) {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );

		$home_path = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		if ( $home_path && '/' !== $home_path ) {
			$home_path = untrailingslashit( $home_path );
			if ( 0 === strpos( $path, $home_path . '/' ) ) {
				$path = substr( $path, strlen( $home_path ) );
			}
		}

		$segments = array_values( array_filter( explode( '/', trim( $path, '/' ) ) ) );
		if ( 2 === count( $segments ) && 'firma' === strtolower( $segments[0] ) ) {
			$slug = sanitize_title( $segments[1] );
			return array(
				'post_type' => 'pt24_firm',
				'name'      => $slug,
				'pt24_firm' => $slug,
			);
		}

		return $query_vars;
	}

	public static function load_template( $template ) {
		$slug = (string) get_query_var( 'pt24_firm' );
		if ( '' !== $slug ) {
			$query = new WP_Query( array(
				'post_type'      => 'pt24_firm',
				'name'           => $slug,
				'posts_per_page' => 1,
			) );
			if ( $query->have_posts() ) {
				$query->the_post();
				global $wp_query;
				$wp_query = $query;
				$custom = locate_template( 'single-pt24_firm.php' );
				if ( $custom ) {
					return $custom;
				}
			}
		}

		if ( is_singular( 'pt24_firm' ) ) {
			$custom = locate_template( 'single-pt24_firm.php' );
			if ( $custom ) {
				return $custom;
			}
		}

		return $template;
	}

	public static function custom_permalink( $permalink, $post ) {
		if ( isset( $post->post_type ) && 'pt24_firm' === $post->post_type ) {
			return home_url( '/firma/' . $post->post_name . '/' );
		}
		return $permalink;
	}
}
