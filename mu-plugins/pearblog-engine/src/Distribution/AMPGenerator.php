<?php
/**
 * AMP Generator – creates AMP (Accelerated Mobile Pages) versions of articles.
 *
 * Generates valid AMP HTML from WordPress post content and serves it at
 * `?amp=1` or the `/amp/` URL variant.  Also outputs a `<link rel="amphtml">`
 * tag in the standard page head for discoverability.
 *
 * Configuration (WP options):
 *   pearblog_amp_enabled   – (bool) enable AMP generation
 *   pearblog_amp_analytics – Google Analytics measurement ID for AMP
 *   pearblog_amp_adsense   – AdSense publisher ID for AMP ads
 *
 * @package PearBlogEngine\Distribution
 */

declare(strict_types=1);

namespace PearBlogEngine\Distribution;

/**
 * Generates and serves AMP versions of published articles.
 */
class AMPGenerator {

	/** WP option keys. */
	public const OPTION_ENABLED   = 'pearblog_amp_enabled';
	public const OPTION_ANALYTICS = 'pearblog_amp_analytics';
	public const OPTION_ADSENSE   = 'pearblog_amp_adsense';

	/** AMP HTML boilerplate style. */
	private const AMP_BOILERPLATE = 'body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}';
	private const AMP_BOILERPLATE_NOSCRIPT = 'body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}';

	// -----------------------------------------------------------------------
	// Registration
	// -----------------------------------------------------------------------

	/**
	 * Hook into WordPress.
	 */
	public function register(): void {
		if ( ! (bool) get_option( self::OPTION_ENABLED, false ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'serve_amp' ] );
		add_action( 'wp_head', [ $this, 'output_amphtml_link' ] );
		add_filter( 'query_vars', [ $this, 'add_query_var' ] );
	}

	/**
	 * Add `amp` to recognized query vars.
	 *
	 * @param string[] $vars Existing query vars.
	 * @return string[]
	 */
	public function add_query_var( array $vars ): array {
		$vars[] = 'amp';
		return $vars;
	}

	// -----------------------------------------------------------------------
	// AMP serving
	// -----------------------------------------------------------------------

	/**
	 * Check if this is an AMP request and serve AMP HTML.
	 */
	public function serve_amp(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		if ( ! get_query_var( 'amp' ) ) {
			return;
		}

		$post = get_queried_object();
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$this->output_amp_page( $post );
		exit;
	}

	/**
	 * Output the amphtml link tag in the page <head>.
	 */
	public function output_amphtml_link(): void {
		if ( ! is_singular( 'post' ) ) {
			return;
		}

		$amp_url = add_query_arg( 'amp', '1', get_permalink() );
		echo '<link rel="amphtml" href="' . esc_url( $amp_url ) . '">' . "\n";
	}

	// -----------------------------------------------------------------------
	// AMP page generation
	// -----------------------------------------------------------------------

	/**
	 * Build and output a full AMP HTML page.
	 *
	 * @param \WP_Post $post WordPress post object.
	 */
	public function output_amp_page( \WP_Post $post ): void {
		$title         = get_the_title( $post );
		$content       = $this->convert_to_amp_content( $post->post_content );
		$meta_desc     = get_post_meta( $post->ID, 'pearblog_meta_description', true );
		$published     = get_the_date( 'c', $post );
		$modified      = get_the_modified_date( 'c', $post );
		$author        = get_the_author_meta( 'display_name', $post->post_author );
		$image_url     = get_the_post_thumbnail_url( $post->ID, 'large' );
		$analytics_id  = (string) get_option( self::OPTION_ANALYTICS, '' );
		$adsense_id    = (string) get_option( self::OPTION_ADSENSE, '' );
		$canonical_url = get_permalink( $post->ID );

		header( 'Content-Type: text/html; charset=utf-8' );

		echo '<!doctype html>' . "\n";
		echo '<html ⚡ lang="' . esc_attr( get_bloginfo( 'language' ) ) . '">' . "\n";
		echo '<head>' . "\n";
		echo '<meta charset="utf-8">' . "\n";
		echo '<meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">' . "\n";
		echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '">' . "\n";

		if ( ! empty( $meta_desc ) ) {
			echo '<meta name="description" content="' . esc_attr( $meta_desc ) . '">' . "\n";
		}

		// AMP boilerplate.
		echo '<style amp-boilerplate>' . self::AMP_BOILERPLATE . '</style>' . "\n";
		echo '<noscript><style amp-boilerplate>' . self::AMP_BOILERPLATE_NOSCRIPT . '</style></noscript>' . "\n";
		echo '<script async src="https://cdn.ampproject.org/v0.js"></script>' . "\n";

		// Optional AMP components.
		if ( $image_url ) {
			echo '<script async custom-element="amp-img" src="https://cdn.ampproject.org/v0/amp-img-0.1.js"></script>' . "\n";
		}

		if ( '' !== $analytics_id ) {
			echo '<script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>' . "\n";
		}

		if ( '' !== $adsense_id ) {
			echo '<script async custom-element="amp-ad" src="https://cdn.ampproject.org/v0/amp-ad-0.1.js"></script>' . "\n";
		}

		// Custom styles.
		echo '<style amp-custom>' . $this->get_amp_styles() . '</style>' . "\n";
		echo '</head>' . "\n";

		// Body.
		echo '<body>' . "\n";
		echo '<article class="amp-article">' . "\n";
		echo '<header class="amp-header">' . "\n";
		echo '<h1>' . esc_html( $title ) . '</h1>' . "\n";
		echo '<p class="amp-meta">By ' . esc_html( $author ) . ' · ' . esc_html( get_the_date( 'F j, Y', $post ) ) . '</p>' . "\n";
		echo '</header>' . "\n";

		if ( $image_url ) {
			echo '<amp-img src="' . esc_url( $image_url ) . '" width="800" height="450" layout="responsive" alt="' . esc_attr( $title ) . '"></amp-img>' . "\n";
		}

		echo '<div class="amp-content">' . "\n";
		echo $content . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput -- content is processed by convert_to_amp_content
		echo '</div>' . "\n";

		// Analytics tracking.
		if ( '' !== $analytics_id ) {
			echo '<amp-analytics type="gtag" data-credentials="include">' . "\n";
			echo '<script type="application/json">{"vars":{"gtag_id":"' . esc_js( $analytics_id ) . '","config":{"' . esc_js( $analytics_id ) . '":{"groups":"default"}}}}</script>' . "\n";
			echo '</amp-analytics>' . "\n";
		}

		echo '</article>' . "\n";
		echo '</body>' . "\n";
		echo '</html>' . "\n";
	}

	// -----------------------------------------------------------------------
	// Content conversion
	// -----------------------------------------------------------------------

	/**
	 * Convert WordPress post content to valid AMP HTML.
	 *
	 * Replaces standard HTML elements with their AMP equivalents.
	 *
	 * @param string $content Raw WordPress post content.
	 * @return string AMP-compliant HTML.
	 */
	public function convert_to_amp_content( string $content ): string {
		// Apply WordPress content filters.
		$content = apply_filters( 'the_content', $content );

		// Replace <img> with <amp-img>.
		$content = preg_replace_callback(
			'/<img([^>]*)>/i',
			function ( $matches ) {
				$attrs = $matches[1];
				preg_match( '/src=["\']([^"\']+)["\']/', $attrs, $src );
				preg_match( '/alt=["\']([^"\']*)["\']/', $attrs, $alt );
				preg_match( '/width=["\']?(\d+)["\']?/', $attrs, $width );
				preg_match( '/height=["\']?(\d+)["\']?/', $attrs, $height );

				$w   = ! empty( $width[1] ) ? $width[1] : '800';
				$h   = ! empty( $height[1] ) ? $height[1] : '450';
				$src = ! empty( $src[1] ) ? $src[1] : '';
				$alt = ! empty( $alt[1] ) ? $alt[1] : '';

				if ( '' === $src ) {
					return '';
				}

				return sprintf(
					'<amp-img src="%s" width="%s" height="%s" layout="responsive" alt="%s"></amp-img>',
					esc_url( $src ),
					esc_attr( $w ),
					esc_attr( $h ),
					esc_attr( $alt )
				);
			},
			$content
		) ?? $content;

		// Remove scripts and iframes (not allowed in AMP).
		$content = preg_replace( '/<script[^>]*>.*?<\/script>/is', '', $content ) ?? $content;
		$content = preg_replace( '/<iframe[^>]*>.*?<\/iframe>/is', '', $content ) ?? $content;

		// Remove style attributes.
		$content = preg_replace( '/\sstyle=["\'][^"\']*["\']/', '', $content ) ?? $content;

		return $content;
	}

	/**
	 * Get base AMP CSS styles.
	 *
	 * @return string CSS rules (max 75KB for AMP compliance).
	 */
	private function get_amp_styles(): string {
		return '
body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; color: #333; margin: 0; padding: 0; }
.amp-article { max-width: 800px; margin: 0 auto; padding: 16px; }
.amp-header h1 { font-size: 28px; line-height: 1.3; margin-bottom: 8px; }
.amp-meta { color: #666; font-size: 14px; margin-bottom: 16px; }
.amp-content { font-size: 17px; line-height: 1.7; }
.amp-content h2 { font-size: 22px; margin-top: 32px; }
.amp-content h3 { font-size: 18px; margin-top: 24px; }
.amp-content p { margin-bottom: 16px; }
.amp-content a { color: #0073aa; text-decoration: none; }
.amp-content ul, .amp-content ol { padding-left: 24px; }
amp-img { margin: 16px 0; }
		';
	}
}
