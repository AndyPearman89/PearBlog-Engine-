<?php
/**
 * Calculator Model - Cost and ROI calculators
 *
 * @package PearBlogEngine\DecisionPlatform
 */

declare(strict_types=1);

namespace PearBlogEngine\DecisionPlatform;

/**
 * Calculator model with inputs and formulas
 */
class Calculator {

	/** @var int */
	public int $id;

	/** @var string */
	public string $name;

	/** @var string */
	public string $slug;

	/** @var string */
	public string $description;

	/** @var array<array{name: string, label: string, type: string, default: mixed, min: float|null, max: float|null, options: array|null}> */
	public array $inputs;

	/** @var string Formula in PHP expression format */
	public string $formula;

	/** @var array{label: string, unit: string, format: string} */
	public array $output;

	/** @var string ISO 8601 timestamp */
	public string $created_at;

	/** @var string ISO 8601 timestamp */
	public string $updated_at;

	/**
	 * Create from custom post type
	 *
	 * @param \WP_Post $post
	 * @return self
	 */
	public static function from_post( \WP_Post $post ): self {
		$calculator = new self();
		$calculator->id = $post->ID;
		$calculator->name = $post->post_title;
		$calculator->slug = $post->post_name;
		$calculator->description = $post->post_content;

		$inputs_data = get_post_meta( $post->ID, 'pearblog_calculator_inputs', true );
		$calculator->inputs = $inputs_data ? json_decode( $inputs_data, true ) : [];

		$calculator->formula = get_post_meta( $post->ID, 'pearblog_calculator_formula', true ) ?: '';

		$output_data = get_post_meta( $post->ID, 'pearblog_calculator_output', true );
		$calculator->output = $output_data ? json_decode( $output_data, true ) : [
			'label' => 'Wynik',
			'unit' => '',
			'format' => 'number',
		];

		$calculator->created_at = $post->post_date;
		$calculator->updated_at = $post->post_modified;

		return $calculator;
	}

	/**
	 * Save to WordPress
	 *
	 * @return int Post ID
	 */
	public function save(): int {
		if ( $this->id > 0 ) {
			wp_update_post( [
				'ID' => $this->id,
				'post_title' => $this->name,
				'post_name' => $this->slug,
				'post_content' => $this->description,
			] );
		} else {
			$this->id = wp_insert_post( [
				'post_title' => $this->name,
				'post_name' => $this->slug,
				'post_content' => $this->description,
				'post_status' => 'publish',
				'post_type' => 'pearblog_calculator',
			] );
		}

		update_post_meta( $this->id, 'pearblog_calculator_inputs', wp_json_encode( $this->inputs ) );
		update_post_meta( $this->id, 'pearblog_calculator_formula', $this->formula );
		update_post_meta( $this->id, 'pearblog_calculator_output', wp_json_encode( $this->output ) );

		return $this->id;
	}

	/**
	 * Calculate result from input values
	 *
	 * @param array<string, mixed> $values Input values keyed by input name
	 * @return float|null Result or null on error
	 */
	public function calculate( array $values ): ?float {
		// Validate inputs
		foreach ( $this->inputs as $input ) {
			if ( ! isset( $values[ $input['name'] ] ) ) {
				return null;
			}

			// Type validation
			$value = $values[ $input['name'] ];
			if ( 'number' === $input['type'] ) {
				$value = (float) $value;

				// Range validation
				if ( null !== $input['min'] && $value < $input['min'] ) {
					return null;
				}
				if ( null !== $input['max'] && $value > $input['max'] ) {
					return null;
				}

				$values[ $input['name'] ] = $value;
			}
		}

		// Build safe calculation context
		$context = $values;

		// Parse and execute formula safely
		try {
			$result = $this->evaluate_formula( $this->formula, $context );
			return is_numeric( $result ) ? (float) $result : null;
		} catch ( \Exception $e ) {
			error_log( sprintf(
				'Calculator formula error (ID %d): %s',
				$this->id,
				$e->getMessage()
			) );
			return null;
		}
	}

	/**
	 * Safely evaluate formula with given context
	 *
	 * @param string $formula
	 * @param array<string, mixed> $context
	 * @return mixed
	 */
	private function evaluate_formula( string $formula, array $context ) {
		// Extract variables for eval context
		extract( $context, EXTR_SKIP );

		// Basic safety check - only allow math operations and variables
		if ( preg_match( '/[^0-9a-zA-Z_+\-*\/().\s]/', $formula ) ) {
			throw new \RuntimeException( 'Invalid formula: unsafe characters detected' );
		}

		// Evaluate
		return eval( "return {$formula};" );
	}

	/**
	 * Format result according to output configuration
	 *
	 * @param float $value
	 * @return string
	 */
	public function format_result( float $value ): string {
		$formatted = '';

		switch ( $this->output['format'] ) {
			case 'currency':
				$formatted = number_format( $value, 2, ',', ' ' ) . ' ' . $this->output['unit'];
				break;
			case 'percentage':
				$formatted = number_format( $value, 1 ) . '%';
				break;
			case 'number':
			default:
				$formatted = number_format( $value, 2 ) . ( $this->output['unit'] ? ' ' . $this->output['unit'] : '' );
				break;
		}

		return $formatted;
	}

	/**
	 * Render calculator form
	 *
	 * @return string HTML
	 */
	public function render_form(): string {
		$html = '<div class="calculator-form" data-calculator-id="' . $this->id . '">';
		$html .= '<form class="calculator-inputs">';

		foreach ( $this->inputs as $input ) {
			$html .= '<div class="form-group">';
			$html .= '<label for="calc_' . esc_attr( $input['name'] ) . '">';
			$html .= esc_html( $input['label'] );
			$html .= '</label>';

			if ( 'number' === $input['type'] ) {
				$html .= sprintf(
					'<input type="number" id="calc_%s" name="%s" value="%s" min="%s" max="%s" step="0.01" class="form-control" />',
					esc_attr( $input['name'] ),
					esc_attr( $input['name'] ),
					esc_attr( (string) ( $input['default'] ?? '' ) ),
					esc_attr( (string) ( $input['min'] ?? '' ) ),
					esc_attr( (string) ( $input['max'] ?? '' ) )
				);
			} elseif ( 'select' === $input['type'] ) {
				$html .= '<select id="calc_' . esc_attr( $input['name'] ) . '" name="' . esc_attr( $input['name'] ) . '" class="form-control">';
				foreach ( $input['options'] ?? [] as $option ) {
					$html .= '<option value="' . esc_attr( $option['value'] ) . '">' . esc_html( $option['label'] ) . '</option>';
				}
				$html .= '</select>';
			}

			$html .= '</div>';
		}

		$html .= '<button type="button" class="btn btn-primary calculator-submit">Oblicz</button>';
		$html .= '</form>';

		$html .= '<div class="calculator-result" style="display:none;">';
		$html .= '<h3>' . esc_html( $this->output['label'] ) . ':</h3>';
		$html .= '<div class="result-value"></div>';
		$html .= '</div>';

		$html .= '</div>';

		return $html;
	}
}
