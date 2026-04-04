<?php
/**
 * PearBlog A/B Testing Meta Box
 *
 * Adds a meta box on the post editor for configuring headline A/B tests
 * and viewing test results.
 *
 * @package PearBlog
 * @version 2.1.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the A/B testing meta box for posts.
 */
function pearblog_ab_test_add_meta_box() {
	add_meta_box(
		'pearblog_ab_test',
		__( 'PearBlog A/B Headline Test', 'pearblog-theme' ),
		'pearblog_ab_test_meta_box_render',
		'post',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'pearblog_ab_test_add_meta_box' );

/**
 * Render the A/B testing meta box.
 *
 * @param WP_Post $post Current post object.
 */
function pearblog_ab_test_meta_box_render( $post ) {
	wp_nonce_field( 'pearblog_ab_test_nonce_action', 'pearblog_ab_test_nonce' );

	$enabled   = get_post_meta( $post->ID, 'pb_ab_test_enabled', true );
	$variant_a = get_post_meta( $post->ID, 'pb_headline_variant_a', true );
	$variant_b = get_post_meta( $post->ID, 'pb_headline_variant_b', true );
	$winner    = get_post_meta( $post->ID, 'pb_ab_test_winner', true );
	$completed = get_post_meta( $post->ID, 'pb_ab_test_completed', true );

	// Get test results if the test is active.
	$results = null;
	if ( $enabled && function_exists( 'pb_get_ab_test_results' ) ) {
		$results = pb_get_ab_test_results( $post->ID );
	}

	?>
	<style>
		.pb-ab-field { margin-bottom: 10px; }
		.pb-ab-field label { display: block; font-weight: 600; margin-bottom: 4px; font-size: 12px; }
		.pb-ab-field input[type="text"] { width: 100%; }
		.pb-ab-results { background: #f0f0f1; border-radius: 4px; padding: 10px; margin-top: 10px; }
		.pb-ab-results h4 { margin: 0 0 8px; font-size: 12px; text-transform: uppercase; color: #646970; }
		.pb-ab-variant-row { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; border-bottom: 1px solid #ddd; }
		.pb-ab-variant-row:last-child { border-bottom: none; }
		.pb-ab-winner-badge { background: #00a32a; color: #fff; padding: 2px 6px; border-radius: 3px; font-size: 11px; }
		.pb-ab-completed { color: #00a32a; font-weight: 600; margin-top: 8px; font-size: 12px; }
		.pb-ab-generate-btn { margin-top: 6px; }
	</style>

	<div class="pb-ab-field">
		<label>
			<input type="checkbox" name="pb_ab_test_enabled" value="1" <?php checked( $enabled, '1' ); ?> <?php echo $completed ? 'disabled' : ''; ?>>
			<?php esc_html_e( 'Enable A/B test for this post', 'pearblog-theme' ); ?>
		</label>
	</div>

	<?php if ( $completed ) : ?>
		<p class="pb-ab-completed">
			<?php
			$completed_ts = strtotime( $completed );
			$formatted_date = $completed_ts ? date_i18n( get_option( 'date_format' ), $completed_ts ) : esc_html( $completed );
			printf(
				/* translators: 1: winning variant letter, 2: completion date */
				esc_html__( '✅ Test completed — Variant %1$s won on %2$s', 'pearblog-theme' ),
				esc_html( strtoupper( $winner ) ),
				esc_html( $formatted_date )
			);
			?>
		</p>
	<?php endif; ?>

	<div class="pb-ab-field">
		<label for="pb_headline_variant_a"><?php esc_html_e( 'Variant A (headline)', 'pearblog-theme' ); ?></label>
		<input type="text" id="pb_headline_variant_a" name="pb_headline_variant_a"
			value="<?php echo esc_attr( $variant_a ); ?>"
			placeholder="<?php echo esc_attr( get_the_title( $post ) ); ?>">
	</div>

	<div class="pb-ab-field">
		<label for="pb_headline_variant_b"><?php esc_html_e( 'Variant B (headline)', 'pearblog-theme' ); ?></label>
		<input type="text" id="pb_headline_variant_b" name="pb_headline_variant_b"
			value="<?php echo esc_attr( $variant_b ); ?>"
			placeholder="<?php esc_attr_e( 'Alternative headline…', 'pearblog-theme' ); ?>">
	</div>

	<?php if ( function_exists( 'pb_generate_headline_variations' ) && empty( $variant_b ) && ! $completed ) : ?>
		<button type="button" class="button pb-ab-generate-btn" id="pb-ab-generate">
			<?php esc_html_e( 'Auto-generate variant B', 'pearblog-theme' ); ?>
		</button>
		<script>
		document.getElementById('pb-ab-generate').addEventListener('click', function() {
			var title = document.getElementById('pb_headline_variant_a').value || document.getElementById('title').value || '';
			if (!title) { this.textContent = 'Enter Variant A first'; setTimeout(function(){ document.getElementById('pb-ab-generate').textContent = 'Auto-generate variant B'; }, 2000); return; }
			// Simple client-side variation: prepend power word.
			var words = ['Ultimate', 'Complete', 'Essential', 'Proven', 'Expert'];
			var word = words[Math.floor(Math.random() * words.length)];
			document.getElementById('pb_headline_variant_b').value = word + ' Guide: ' + title;
		});
		</script>
	<?php endif; ?>

	<?php if ( $results && $enabled && ! $completed ) : ?>
		<div class="pb-ab-results">
			<h4><?php esc_html_e( 'Live Results', 'pearblog-theme' ); ?></h4>
			<?php
			$variant_a_data = $results['a'] ?? array( 'impressions' => 0, 'clicks' => 0, 'ctr' => 0 );
			$variant_b_data = $results['b'] ?? array( 'impressions' => 0, 'clicks' => 0, 'ctr' => 0 );
			?>
			<div class="pb-ab-variant-row">
				<span><strong>A</strong></span>
				<span><?php echo esc_html( $variant_a_data['impressions'] ?? 0 ); ?> <?php esc_html_e( 'imp', 'pearblog-theme' ); ?></span>
				<span><?php echo esc_html( $variant_a_data['clicks'] ?? 0 ); ?> <?php esc_html_e( 'clicks', 'pearblog-theme' ); ?></span>
				<span><?php echo esc_html( number_format( (float) ( $variant_a_data['ctr'] ?? 0 ), 1 ) ); ?>%</span>
			</div>
			<div class="pb-ab-variant-row">
				<span><strong>B</strong></span>
				<span><?php echo esc_html( $variant_b_data['impressions'] ?? 0 ); ?> <?php esc_html_e( 'imp', 'pearblog-theme' ); ?></span>
				<span><?php echo esc_html( $variant_b_data['clicks'] ?? 0 ); ?> <?php esc_html_e( 'clicks', 'pearblog-theme' ); ?></span>
				<span><?php echo esc_html( number_format( (float) ( $variant_b_data['ctr'] ?? 0 ), 1 ) ); ?>%</span>
			</div>
			<?php if ( ! empty( $results['winner'] ) ) : ?>
				<p style="margin: 8px 0 0; font-size: 12px;">
					<span class="pb-ab-winner-badge">
						<?php
						printf(
							/* translators: %s: winning variant letter */
							esc_html__( 'Winner: %s', 'pearblog-theme' ),
							esc_html( strtoupper( $results['winner'] ) )
						);
						?>
					</span>
				</p>
			<?php else : ?>
				<p style="margin: 8px 0 0; font-size: 11px; color: #646970;">
					<?php esc_html_e( 'Need 100+ impressions per variant to determine a winner.', 'pearblog-theme' ); ?>
				</p>
			<?php endif; ?>
		</div>
	<?php endif; ?>
	<?php
}

/**
 * Save the A/B testing meta box data.
 *
 * @param int $post_id Post ID.
 */
function pearblog_ab_test_save_meta( $post_id ) {
	// Verify nonce.
	if ( ! isset( $_POST['pearblog_ab_test_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pearblog_ab_test_nonce'] ) ), 'pearblog_ab_test_nonce_action' ) ) {
		return;
	}

	// Check autosave.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Don't overwrite completed tests.
	$completed = get_post_meta( $post_id, 'pb_ab_test_completed', true );
	if ( $completed ) {
		return;
	}

	$enabled   = isset( $_POST['pb_ab_test_enabled'] ) ? '1' : '';
	$variant_a = sanitize_text_field( $_POST['pb_headline_variant_a'] ?? '' );
	$variant_b = sanitize_text_field( $_POST['pb_headline_variant_b'] ?? '' );

	update_post_meta( $post_id, 'pb_ab_test_enabled', $enabled );
	update_post_meta( $post_id, 'pb_headline_variant_a', $variant_a );
	update_post_meta( $post_id, 'pb_headline_variant_b', $variant_b );
}
add_action( 'save_post', 'pearblog_ab_test_save_meta' );
