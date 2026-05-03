<?php
/**
 * Block Renderer - Renders content blocks dynamically
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Block rendering system
 */
class BlockRenderer {

	/**
	 * Render a block based on its type
	 *
	 * @param array{type: string, data: mixed} $block
	 * @return string HTML
	 */
	public static function render( array $block ): string {
		$type = $block['type'] ?? '';
		$data = $block['data'] ?? [];

		switch ( $type ) {
			case 'intro':
				return self::render_intro( $data );
			case 'steps':
				return self::render_steps( $data );
			case 'table':
				return self::render_table( $data );
			case 'faq':
				return self::render_faq( $data );
			case 'comparison':
				return self::render_comparison( $data );
			case 'ranking':
				return self::render_ranking( $data );
			case 'calculator':
				return self::render_calculator( $data );
			case 'experts':
				return self::render_experts( $data );
			case 'lead_form':
				return self::render_lead_form( $data );
			case 'affiliate_box':
				return self::render_affiliate_box( $data );
			case 'related':
				return self::render_related( $data );
			case 'text':
				return self::render_text( $data );
			default:
				return '';
		}
	}

	private static function render_intro( array $data ): string {
		$html = '<div class="block block-intro">';
		$html .= '<div class="intro-content">';
		$html .= wp_kses_post( $data['content'] ?? '' );
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_steps( array $data ): string {
		$html = '<div class="block block-steps">';
		$html .= '<h2>' . esc_html( $data['title'] ?? 'Krok po kroku' ) . '</h2>';
		$html .= '<ol class="steps-list">';

		foreach ( $data['steps'] ?? [] as $step ) {
			$html .= '<li class="step-item">';
			$html .= '<h3>' . esc_html( $step['title'] ) . '</h3>';
			$html .= '<p>' . esc_html( $step['description'] ) . '</p>';
			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</div>';
		return $html;
	}

	private static function render_table( array $data ): string {
		$html = '<div class="block block-table">';
		$html .= '<table class="table">';

		// Header
		if ( ! empty( $data['headers'] ) ) {
			$html .= '<thead><tr>';
			foreach ( $data['headers'] as $header ) {
				$html .= '<th>' . esc_html( $header ) . '</th>';
			}
			$html .= '</tr></thead>';
		}

		// Body
		$html .= '<tbody>';
		foreach ( $data['rows'] ?? [] as $row ) {
			$html .= '<tr>';
			foreach ( $row as $cell ) {
				$html .= '<td>' . esc_html( $cell ) . '</td>';
			}
			$html .= '</tr>';
		}
		$html .= '</tbody>';

		$html .= '</table>';
		$html .= '</div>';
		return $html;
	}

	private static function render_faq( array $data ): string {
		$html = '<div class="block block-faq">';
		$html .= '<h2>' . esc_html( $data['title'] ?? 'Najczęściej zadawane pytania' ) . '</h2>';
		$html .= '<div class="faq-items">';

		foreach ( $data['items'] ?? [] as $index => $item ) {
			$html .= '<div class="faq-item">';
			$html .= '<button class="faq-question" data-index="' . $index . '">';
			$html .= esc_html( $item['question'] );
			$html .= '<span class="faq-icon">+</span>';
			$html .= '</button>';
			$html .= '<div class="faq-answer" id="faq-' . $index . '" style="display:none;">';
			$html .= '<p>' . esc_html( $item['answer'] ) . '</p>';
			$html .= '</div>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_comparison( array $data ): string {
		$comparison_id = $data['comparison_id'] ?? 0;
		if ( ! $comparison_id ) {
			return '';
		}

		$post = get_post( $comparison_id );
		if ( ! $post ) {
			return '';
		}

		$comparison = Comparison::from_post( $post );
		return '<div class="block block-comparison">' . $comparison->render_table() . '</div>';
	}

	private static function render_ranking( array $data ): string {
		$ranking_id = $data['ranking_id'] ?? 0;
		if ( ! $ranking_id ) {
			return '';
		}

		$post = get_post( $ranking_id );
		if ( ! $post ) {
			return '';
		}

		$ranking = Ranking::from_post( $post );
		return '<div class="block block-ranking">' . $ranking->render_list( $data['limit'] ?? 10 ) . '</div>';
	}

	private static function render_calculator( array $data ): string {
		$calculator_id = $data['calculator_id'] ?? 0;
		if ( ! $calculator_id ) {
			return '';
		}

		$post = get_post( $calculator_id );
		if ( ! $post ) {
			return '';
		}

		$calculator = Calculator::from_post( $post );
		return '<div class="block block-calculator">' . $calculator->render_form() . '</div>';
	}

	private static function render_experts( array $data ): string {
		$expert_ids = $data['expert_ids'] ?? [];
		if ( empty( $expert_ids ) ) {
			return '';
		}

		$html = '<div class="block block-experts">';
		$html .= '<h2>' . esc_html( $data['title'] ?? 'Polecani specjaliści' ) . '</h2>';
		$html .= '<div class="experts-grid">';

		foreach ( $expert_ids as $expert_id ) {
			$post = get_post( $expert_id );
			if ( ! $post ) {
				continue;
			}

			$expert = Expert::from_post( $post );
			$html .= $expert->render_card();
		}

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_lead_form( array $data ): string {
		$html = '<div class="block block-lead-form">';
		$html .= '<div class="lead-form-container">';
		$html .= '<h3>' . esc_html( $data['title'] ?? 'Otrzymaj bezpłatną wycenę' ) . '</h3>';
		$html .= '<p>' . esc_html( $data['description'] ?? 'Wypełnij formularz, a skontaktujemy Cię z najlepszymi specjalistami.' ) . '</p>';

		$html .= '<form class="lead-form" data-category="' . esc_attr( $data['category'] ?? '' ) . '">';
		$html .= '<input type="text" name="name" placeholder="Imię i nazwisko" required />';
		$html .= '<input type="email" name="email" placeholder="Email" required />';
		$html .= '<input type="tel" name="phone" placeholder="Telefon" required />';
		$html .= '<input type="text" name="city" placeholder="Miasto" required />';
		$html .= '<textarea name="message" placeholder="Opisz swoje potrzeby" rows="4"></textarea>';
		$html .= '<button type="submit" class="btn btn-primary">Wyślij zapytanie</button>';
		$html .= '</form>';

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_affiliate_box( array $data ): string {
		$html = '<div class="block block-affiliate">';
		$html .= '<div class="affiliate-box">';

		if ( ! empty( $data['image'] ) ) {
			$html .= '<img src="' . esc_url( $data['image'] ) . '" alt="' . esc_attr( $data['title'] ?? '' ) . '" />';
		}

		$html .= '<div class="affiliate-content">';
		$html .= '<h3>' . esc_html( $data['title'] ?? '' ) . '</h3>';
		$html .= '<p>' . esc_html( $data['description'] ?? '' ) . '</p>';

		if ( ! empty( $data['price'] ) ) {
			$html .= '<div class="affiliate-price">' . esc_html( $data['price'] ) . '</div>';
		}

		if ( ! empty( $data['url'] ) ) {
			$html .= '<a href="' . esc_url( $data['url'] ) . '" class="btn btn-primary" rel="nofollow sponsored" target="_blank">';
			$html .= esc_html( $data['cta'] ?? 'Sprawdź ofertę' );
			$html .= '</a>';
		}

		$html .= '</div>';
		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_related( array $data ): string {
		$post_ids = $data['post_ids'] ?? [];
		if ( empty( $post_ids ) ) {
			return '';
		}

		$html = '<div class="block block-related">';
		$html .= '<h2>' . esc_html( $data['title'] ?? 'Powiązane artykuły' ) . '</h2>';
		$html .= '<div class="related-posts">';

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$html .= '<div class="related-post">';
			$html .= '<h3><a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( $post->post_title ) . '</a></h3>';
			$html .= '<p>' . esc_html( wp_trim_words( $post->post_content, 20 ) ) . '</p>';
			$html .= '</div>';
		}

		$html .= '</div>';
		$html .= '</div>';
		return $html;
	}

	private static function render_text( array $data ): string {
		$html = '<div class="block block-text">';
		$html .= wp_kses_post( $data['content'] ?? '' );
		$html .= '</div>';
		return $html;
	}
}
