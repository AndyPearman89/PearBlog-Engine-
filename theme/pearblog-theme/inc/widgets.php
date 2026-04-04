<?php
/**
 * PearBlog PRO Custom Widgets
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recent Posts Widget with thumbnails and reading time.
 */
class PearBlog_Recent_Posts_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pearblog_recent_posts',
			__( 'PearBlog: Recent Posts', 'pearblog-theme' ),
			array(
				'description' => __( 'Displays recent posts with thumbnails and reading time.', 'pearblog-theme' ),
				'classname'   => 'pb-widget-recent-posts',
			)
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 */
	public function widget( $args, $instance ) {
		$title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Recent Posts', 'pearblog-theme' );
		$count       = ! empty( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$show_thumb  = ! empty( $instance['show_thumbnail'] );
		$show_date   = ! empty( $instance['show_date'] );
		$category_id = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;

		$query_args = array(
			'posts_per_page'      => $count,
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		);

		if ( $category_id ) {
			$query_args['cat'] = $category_id;
		}

		$posts = get_posts( $query_args );

		if ( empty( $posts ) ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $title ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<ul class="pb-recent-posts-list">';

		foreach ( $posts as $post ) {
			echo '<li class="pb-recent-post-item">';

			if ( $show_thumb && has_post_thumbnail( $post ) ) {
				echo '<div class="pb-recent-post-thumb">';
				echo '<a href="' . esc_url( get_permalink( $post ) ) . '">';
				echo get_the_post_thumbnail( $post, 'pearblog-thumbnail', array( 'loading' => 'lazy' ) );
				echo '</a>';
				echo '</div>';
			}

			echo '<div class="pb-recent-post-info">';
			echo '<a href="' . esc_url( get_permalink( $post ) ) . '" class="pb-recent-post-title">';
			echo esc_html( get_the_title( $post ) );
			echo '</a>';

			if ( $show_date ) {
				echo '<span class="pb-recent-post-date">' . esc_html( get_the_date( '', $post ) ) . '</span>';
			}

			if ( function_exists( 'pearblog_get_reading_time' ) ) {
				echo '<span class="pb-recent-post-reading-time">' . esc_html( pearblog_get_reading_time( $post->ID ) ) . '</span>';
			}

			echo '</div>';
			echo '</li>';
		}

		echo '</ul>';

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Back-end form.
	 *
	 * @param array $instance Widget settings.
	 */
	public function form( $instance ) {
		$title          = isset( $instance['title'] ) ? $instance['title'] : __( 'Recent Posts', 'pearblog-theme' );
		$count          = isset( $instance['count'] ) ? absint( $instance['count'] ) : 5;
		$show_thumbnail = isset( $instance['show_thumbnail'] ) ? (bool) $instance['show_thumbnail'] : true;
		$show_date      = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : true;
		$category       = isset( $instance['category'] ) ? absint( $instance['category'] ) : 0;
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>"><?php esc_html_e( 'Number of posts:', 'pearblog-theme' ); ?></label>
			<input class="tiny-text" id="<?php echo esc_attr( $this->get_field_id( 'count' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'count' ) ); ?>" type="number" min="1" max="20" value="<?php echo esc_attr( $count ); ?>">
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_thumbnail ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_thumbnail' ) ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_thumbnail' ) ); ?>"><?php esc_html_e( 'Show thumbnails', 'pearblog-theme' ); ?></label>
		</p>
		<p>
			<input class="checkbox" type="checkbox" <?php checked( $show_date ); ?> id="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'show_date' ) ); ?>">
			<label for="<?php echo esc_attr( $this->get_field_id( 'show_date' ) ); ?>"><?php esc_html_e( 'Show post date', 'pearblog-theme' ); ?></label>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'category' ) ); ?>"><?php esc_html_e( 'Category:', 'pearblog-theme' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'show_option_all' => __( 'All Categories', 'pearblog-theme' ),
					'name'            => $this->get_field_name( 'category' ),
					'id'              => $this->get_field_id( 'category' ),
					'selected'        => $category,
					'class'           => 'widefat',
				)
			);
			?>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array Sanitized values.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                   = array();
		$instance['title']          = sanitize_text_field( $new_instance['title'] );
		$instance['count']          = absint( $new_instance['count'] );
		$instance['show_thumbnail'] = isset( $new_instance['show_thumbnail'] ) ? 1 : 0;
		$instance['show_date']      = isset( $new_instance['show_date'] ) ? 1 : 0;
		$instance['category']       = absint( $new_instance['category'] );
		return $instance;
	}
}

/**
 * CTA Widget for sidebar or footer.
 */
class PearBlog_CTA_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pearblog_cta',
			__( 'PearBlog: Call to Action', 'pearblog-theme' ),
			array(
				'description' => __( 'Displays a call-to-action box with title, text, and button.', 'pearblog-theme' ),
				'classname'   => 'pb-widget-cta',
			)
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 */
	public function widget( $args, $instance ) {
		$title       = ! empty( $instance['title'] ) ? $instance['title'] : '';
		$text        = ! empty( $instance['text'] ) ? $instance['text'] : '';
		$button_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Learn More', 'pearblog-theme' );
		$button_url  = ! empty( $instance['button_url'] ) ? $instance['button_url'] : '#';
		$style       = ! empty( $instance['style'] ) ? $instance['style'] : 'gradient';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
		<div class="pb-widget-cta-inner pb-cta-style-<?php echo esc_attr( $style ); ?>">
			<?php if ( $title ) : ?>
				<h3 class="pb-widget-cta-title"><?php echo esc_html( $title ); ?></h3>
			<?php endif; ?>

			<?php if ( $text ) : ?>
				<p class="pb-widget-cta-text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>

			<?php if ( $button_text && $button_url ) : ?>
				<a href="<?php echo esc_url( $button_url ); ?>" class="pb-widget-cta-button">
					<?php echo esc_html( $button_text ); ?>
				</a>
			<?php endif; ?>
		</div>
		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Back-end form.
	 *
	 * @param array $instance Widget settings.
	 */
	public function form( $instance ) {
		$title       = isset( $instance['title'] ) ? $instance['title'] : __( 'Get Started', 'pearblog-theme' );
		$text        = isset( $instance['text'] ) ? $instance['text'] : '';
		$button_text = isset( $instance['button_text'] ) ? $instance['button_text'] : __( 'Learn More', 'pearblog-theme' );
		$button_url  = isset( $instance['button_url'] ) ? $instance['button_url'] : '';
		$style       = isset( $instance['style'] ) ? $instance['style'] : 'gradient';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>"><?php esc_html_e( 'Text:', 'pearblog-theme' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'text' ) ); ?>" rows="4"><?php echo esc_textarea( $text ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>"><?php esc_html_e( 'Button Text:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_text' ) ); ?>" type="text" value="<?php echo esc_attr( $button_text ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'button_url' ) ); ?>"><?php esc_html_e( 'Button URL:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'button_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'button_url' ) ); ?>" type="url" value="<?php echo esc_url( $button_url ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>"><?php esc_html_e( 'Style:', 'pearblog-theme' ); ?></label>
			<select class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'style' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'style' ) ); ?>">
				<option value="gradient" <?php selected( $style, 'gradient' ); ?>><?php esc_html_e( 'Gradient', 'pearblog-theme' ); ?></option>
				<option value="solid" <?php selected( $style, 'solid' ); ?>><?php esc_html_e( 'Solid', 'pearblog-theme' ); ?></option>
				<option value="outline" <?php selected( $style, 'outline' ); ?>><?php esc_html_e( 'Outline', 'pearblog-theme' ); ?></option>
				<option value="minimal" <?php selected( $style, 'minimal' ); ?>><?php esc_html_e( 'Minimal', 'pearblog-theme' ); ?></option>
			</select>
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array Sanitized values.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                = array();
		$instance['title']       = sanitize_text_field( $new_instance['title'] );
		$instance['text']        = sanitize_textarea_field( $new_instance['text'] );
		$instance['button_text'] = sanitize_text_field( $new_instance['button_text'] );
		$instance['button_url']  = esc_url_raw( $new_instance['button_url'] );
		$instance['style']       = sanitize_text_field( $new_instance['style'] );
		return $instance;
	}
}

/**
 * Social Follow Widget.
 */
class PearBlog_Social_Follow_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pearblog_social_follow',
			__( 'PearBlog: Social Follow', 'pearblog-theme' ),
			array(
				'description' => __( 'Displays social media follow links.', 'pearblog-theme' ),
				'classname'   => 'pb-widget-social-follow',
			)
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 */
	public function widget( $args, $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Follow Us', 'pearblog-theme' );

		$networks = array(
			'twitter'   => array( 'label' => 'Twitter / X', 'icon' => '𝕏' ),
			'facebook'  => array( 'label' => 'Facebook', 'icon' => 'f' ),
			'instagram' => array( 'label' => 'Instagram', 'icon' => '📷' ),
			'linkedin'  => array( 'label' => 'LinkedIn', 'icon' => 'in' ),
			'youtube'   => array( 'label' => 'YouTube', 'icon' => '▶' ),
		);

		$has_links = false;
		foreach ( $networks as $key => $network ) {
			if ( ! empty( $instance[ $key ] ) ) {
				$has_links = true;
				break;
			}
		}

		if ( ! $has_links ) {
			return;
		}

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $title ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}

		echo '<div class="pb-social-follow-links">';
		foreach ( $networks as $key => $network ) {
			if ( ! empty( $instance[ $key ] ) ) {
				echo '<a href="' . esc_url( $instance[ $key ] ) . '" class="pb-social-link pb-social-' . esc_attr( $key ) . '" target="_blank" rel="noopener noreferrer" aria-label="' . esc_attr( $network['label'] ) . '">';
				echo '<span class="pb-social-icon">' . esc_html( $network['icon'] ) . '</span>';
				echo '<span class="pb-social-label">' . esc_html( $network['label'] ) . '</span>';
				echo '</a>';
			}
		}
		echo '</div>';

		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Back-end form.
	 *
	 * @param array $instance Widget settings.
	 */
	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? $instance['title'] : __( 'Follow Us', 'pearblog-theme' );
		$networks  = array( 'twitter', 'facebook', 'instagram', 'linkedin', 'youtube' );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<?php foreach ( $networks as $network ) : ?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( $network ) ); ?>"><?php echo esc_html( ucfirst( $network ) . ' URL:' ); ?></label>
				<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( $network ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( $network ) ); ?>" type="url" value="<?php echo esc_url( $instance[ $network ] ?? '' ); ?>">
			</p>
		<?php endforeach; ?>
		<?php
	}

	/**
	 * Sanitize widget form values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array Sanitized values.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance          = array();
		$instance['title'] = sanitize_text_field( $new_instance['title'] );
		$networks          = array( 'twitter', 'facebook', 'instagram', 'linkedin', 'youtube' );

		foreach ( $networks as $network ) {
			$instance[ $network ] = esc_url_raw( $new_instance[ $network ] ?? '' );
		}

		return $instance;
	}
}

/**
 * About / Author Widget.
 */
class PearBlog_About_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct(
			'pearblog_about',
			__( 'PearBlog: About / Author', 'pearblog-theme' ),
			array(
				'description' => __( 'Displays an about section with image and bio.', 'pearblog-theme' ),
				'classname'   => 'pb-widget-about',
			)
		);
	}

	/**
	 * Front-end display.
	 *
	 * @param array $args     Widget display arguments.
	 * @param array $instance Widget settings.
	 */
	public function widget( $args, $instance ) {
		$title     = ! empty( $instance['title'] ) ? $instance['title'] : __( 'About', 'pearblog-theme' );
		$image_url = ! empty( $instance['image_url'] ) ? $instance['image_url'] : '';
		$bio       = ! empty( $instance['bio'] ) ? $instance['bio'] : '';
		$link_text = ! empty( $instance['link_text'] ) ? $instance['link_text'] : '';
		$link_url  = ! empty( $instance['link_url'] ) ? $instance['link_url'] : '';

		echo $args['before_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		if ( $title ) {
			echo $args['before_title'] . esc_html( apply_filters( 'widget_title', $title ) ) . $args['after_title']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
		?>
		<div class="pb-about-inner">
			<?php if ( $image_url ) : ?>
				<div class="pb-about-image">
					<img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( $title ); ?>" loading="lazy">
				</div>
			<?php endif; ?>

			<?php if ( $bio ) : ?>
				<p class="pb-about-bio"><?php echo esc_html( $bio ); ?></p>
			<?php endif; ?>

			<?php if ( $link_text && $link_url ) : ?>
				<a href="<?php echo esc_url( $link_url ); ?>" class="pb-about-link"><?php echo esc_html( $link_text ); ?></a>
			<?php endif; ?>
		</div>
		<?php
		echo $args['after_widget']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Back-end form.
	 *
	 * @param array $instance Widget settings.
	 */
	public function form( $instance ) {
		$title     = isset( $instance['title'] ) ? $instance['title'] : __( 'About', 'pearblog-theme' );
		$image_url = isset( $instance['image_url'] ) ? $instance['image_url'] : '';
		$bio       = isset( $instance['bio'] ) ? $instance['bio'] : '';
		$link_text = isset( $instance['link_text'] ) ? $instance['link_text'] : '';
		$link_url  = isset( $instance['link_url'] ) ? $instance['link_url'] : '';
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'image_url' ) ); ?>"><?php esc_html_e( 'Image URL:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'image_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'image_url' ) ); ?>" type="url" value="<?php echo esc_url( $image_url ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'bio' ) ); ?>"><?php esc_html_e( 'Bio:', 'pearblog-theme' ); ?></label>
			<textarea class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'bio' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'bio' ) ); ?>" rows="4"><?php echo esc_textarea( $bio ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>"><?php esc_html_e( 'Link Text:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_text' ) ); ?>" type="text" value="<?php echo esc_attr( $link_text ); ?>">
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'link_url' ) ); ?>"><?php esc_html_e( 'Link URL:', 'pearblog-theme' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'link_url' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'link_url' ) ); ?>" type="url" value="<?php echo esc_url( $link_url ); ?>">
		</p>
		<?php
	}

	/**
	 * Sanitize widget form values.
	 *
	 * @param array $new_instance New values.
	 * @param array $old_instance Old values.
	 * @return array Sanitized values.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance              = array();
		$instance['title']     = sanitize_text_field( $new_instance['title'] );
		$instance['image_url'] = esc_url_raw( $new_instance['image_url'] );
		$instance['bio']       = sanitize_textarea_field( $new_instance['bio'] );
		$instance['link_text'] = sanitize_text_field( $new_instance['link_text'] );
		$instance['link_url']  = esc_url_raw( $new_instance['link_url'] );
		return $instance;
	}
}

/**
 * Register all custom widgets.
 */
function pearblog_register_pro_widgets() {
	register_widget( 'PearBlog_Recent_Posts_Widget' );
	register_widget( 'PearBlog_CTA_Widget' );
	register_widget( 'PearBlog_Social_Follow_Widget' );
	register_widget( 'PearBlog_About_Widget' );
}
add_action( 'widgets_init', 'pearblog_register_pro_widgets' );
