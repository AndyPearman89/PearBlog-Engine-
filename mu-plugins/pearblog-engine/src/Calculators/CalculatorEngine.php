<?php
/**
 * Calculator Engine — core domain service.
 *
 * Responsibilities:
 * - CRUD for calculator definitions (fields + formula).
 * - Running a calculation against a set of user inputs.
 * - Generating specialist / guide recommendations after a calculation.
 * - Persisting results for analytics and lead generation.
 *
 * Field types supported:
 *   number, slider, select, radio, checkbox, text
 *
 * Formula evaluation:
 *   The formula is stored as a JSON object:
 *   { "type": "linear", "coefficients": {"field_key": value, …}, "constant": 0 }
 *   or { "type": "php_expression", "expression": "…" }
 *   PHP expression mode uses only whitelisted math functions.
 *
 * @package PearBlogEngine\Calculators
 */

declare( strict_types=1 );

namespace PearBlogEngine\Calculators;

use PearBlogEngine\AI\AIClient;

/**
 * Represents a single calculator field.
 */
final class CalculatorField {

	public string  $key;
	public string  $label;
	public string  $type;   // number|slider|select|radio|checkbox|text
	public mixed   $default;
	public ?float  $min;
	public ?float  $max;
	/** @var array<string,string> */
	public array   $options; // for select / radio

	/** @param array<string,mixed> $data */
	public function __construct( array $data ) {
		$this->key     = sanitize_key( $data['key'] );
		$this->label   = sanitize_text_field( $data['label'] ?? '' );
		$this->type    = in_array( $data['type'] ?? 'number', [ 'number', 'slider', 'select', 'radio', 'checkbox', 'text' ], true )
						? $data['type']
						: 'number';
		$this->default = $data['default'] ?? null;
		$this->min     = isset( $data['min'] ) ? (float) $data['min'] : null;
		$this->max     = isset( $data['max'] ) ? (float) $data['max'] : null;
		$this->options = is_array( $data['options'] ?? null ) ? $data['options'] : [];
	}
}

/**
 * Represents the result of a single calculation run.
 */
final class CalculatorResult {

	public float  $value;
	public string $label;
	/** @var array<string,mixed> */
	public array  $recommendations;
	public string $formatted;

	/**
	 * @param array<string,mixed> $recommendations
	 */
	public function __construct( float $value, string $label, array $recommendations = [], string $formatted = '' ) {
		$this->value           = $value;
		$this->label           = $label;
		$this->recommendations = $recommendations;
		$this->formatted       = $formatted ?: number_format( $value, 2, ',', ' ' ) . ' zł';
	}

	/** @return array<string,mixed> */
	public function to_array(): array {
		return [
			'value'           => $this->value,
			'label'           => $this->label,
			'formatted'       => $this->formatted,
			'recommendations' => $this->recommendations,
		];
	}
}

/**
 * Main Calculator Engine service.
 */
class CalculatorEngine {

	/** Whitelisted PHP math functions usable in expressions. */
	private const ALLOWED_FUNCTIONS = [ 'abs', 'ceil', 'floor', 'round', 'max', 'min', 'pow', 'sqrt', 'log', 'pi' ];

	private AIClient $ai;

	public function __construct( ?AIClient $ai = null ) {
		$this->ai = $ai ?? new AIClient();
	}

	// ── Public API ────────────────────────────────────────────────────────────

	/**
	 * Return a calculator definition by slug.
	 *
	 * @return array<string,mixed>|null
	 */
	public function get_by_slug( string $slug ): ?array {
		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}pb_calculators WHERE slug = %s AND status = 'publish' LIMIT 1",
				$slug
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		$row['fields']  = json_decode( $row['fields_json'], true ) ?: [];
		$row['formula'] = json_decode( $row['formula_json'], true ) ?: [];

		return $row;
	}

	/**
	 * List all published calculators, optionally by category.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public function list_calculators( string $category = '', int $limit = 20, int $offset = 0 ): array {
		global $wpdb;

		$where = "WHERE status = 'publish'";
		$args  = [];

		if ( $category !== '' ) {
			$where  .= ' AND category = %s';
			$args[]  = $category;
		}

		$args[] = $limit;
		$args[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, slug, title, category, use_count, created_at
				 FROM {$wpdb->prefix}pb_calculators
				 $where
				 ORDER BY use_count DESC
				 LIMIT %d OFFSET %d",
				...$args
			),
			ARRAY_A
		) ?: [];
	}

	/**
	 * Run a calculation and return the result.
	 *
	 * @param  array<string,mixed> $calculator  Fetched via get_by_slug().
	 * @param  array<string,mixed> $inputs       User-supplied field values.
	 */
	public function calculate( array $calculator, array $inputs ): CalculatorResult {
		$value = $this->evaluate_formula( $calculator['formula'], $inputs );

		// Build recommendations.
		$recs = $this->build_recommendations( $calculator, $value, $inputs );

		// Format output label.
		$template = $calculator['output_template'] ?? 'Szacowany koszt: {value} zł';
		$label    = str_replace(
			'{value}',
			number_format( $value, 0, ',', ' ' ),
			$template
		);

		// Persist result.
		$this->persist_result( (int) $calculator['id'], $inputs, $value, $label, $recs );

		// Increment use counter.
		global $wpdb;
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}pb_calculators SET use_count = use_count + 1 WHERE id = %d",
				(int) $calculator['id']
			)
		);

		return new CalculatorResult( $value, $label, $recs, number_format( $value, 0, ',', ' ' ) . ' zł' );
	}

	/**
	 * Upsert a calculator definition.
	 *
	 * @param array<string,mixed> $data
	 */
	public function upsert( array $data ): int {
		global $wpdb;

		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}pb_calculators WHERE slug = %s LIMIT 1",
				$data['slug']
			)
		);

		$row = [
			'slug'                 => sanitize_title( $data['slug'] ),
			'title'                => sanitize_text_field( $data['title'] ),
			'category'             => sanitize_text_field( $data['category'] ?? '' ),
			'fields_json'          => wp_json_encode( $data['fields'] ?? [] ),
			'formula_json'         => wp_json_encode( $data['formula'] ?? [] ),
			'output_template'      => sanitize_text_field( $data['output_template'] ?? '' ),
			'recommendation_rules' => wp_json_encode( $data['recommendation_rules'] ?? [] ),
			'status'               => in_array( $data['status'] ?? 'publish', [ 'publish', 'draft' ], true )
									  ? $data['status']
									  : 'publish',
		];

		if ( $existing ) {
			$wpdb->update( "{$wpdb->prefix}pb_calculators", $row, [ 'id' => (int) $existing ] );
			return (int) $existing;
		}

		$wpdb->insert( "{$wpdb->prefix}pb_calculators", $row );
		return (int) $wpdb->insert_id;
	}

	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Evaluate the formula against user inputs.
	 *
	 * @param  array<string,mixed> $formula
	 * @param  array<string,mixed> $inputs
	 */
	private function evaluate_formula( array $formula, array $inputs ): float {
		$type = $formula['type'] ?? 'linear';

		if ( $type === 'linear' ) {
			$result      = (float) ( $formula['constant'] ?? 0 );
			$coefficients = $formula['coefficients'] ?? [];

			foreach ( $coefficients as $key => $coef ) {
				$val     = isset( $inputs[ $key ] ) ? (float) $inputs[ $key ] : 0.0;
				$result += $val * (float) $coef;
			}

			return max( 0.0, $result );
		}

		if ( $type === 'lookup_table' ) {
			return $this->evaluate_lookup( $formula, $inputs );
		}

		// php_expression — restricted sandbox.
		if ( $type === 'php_expression' ) {
			return $this->evaluate_expression( (string) ( $formula['expression'] ?? '0' ), $inputs );
		}

		return 0.0;
	}

	/**
	 * Lookup-table evaluation: match a key field value to a price tier.
	 *
	 * @param  array<string,mixed> $formula
	 * @param  array<string,mixed> $inputs
	 */
	private function evaluate_lookup( array $formula, array $inputs ): float {
		$key_field = $formula['key_field'] ?? '';
		$table     = $formula['table'] ?? [];
		$value     = $inputs[ $key_field ] ?? '';

		return (float) ( $table[ $value ] ?? $formula['default'] ?? 0 );
	}

	/**
	 * Restricted PHP expression evaluator.
	 * Only numeric inputs and whitelisted functions are allowed.
	 *
	 * @param  array<string,mixed> $inputs
	 */
	private function evaluate_expression( string $expression, array $inputs ): float {
		// Build a safe variable map.
		$vars = [];
		foreach ( $inputs as $k => $v ) {
			if ( preg_match( '/^[a-z_][a-z0-9_]*$/i', $k ) ) {
				$vars[ $k ] = (float) $v;
			}
		}

		// Build function stub imports.
		$fn_imports = '';
		foreach ( self::ALLOWED_FUNCTIONS as $fn ) {
			$fn_imports .= "\${$fn} = '\\\\$fn';";
		}

		// Reject anything that looks like a PHP function call outside whitelist.
		if ( preg_match( '/\b(?!(?:' . implode( '|', self::ALLOWED_FUNCTIONS ) . ')\b)[a-z_]\w*\s*\(/i', $expression ) ) {
			return 0.0;
		}

		$code  = '<?php ';
		$code .= $fn_imports;
		foreach ( $vars as $k => $v ) {
			$code .= "\${$k} = {$v};";
		}
		$code .= "return (float)({$expression});";

		try {
			// phpcs:ignore Squiz.PHP.Eval.Discouraged
			return (float) eval( substr( $code, 5 ) );
		} catch ( \Throwable ) {
			return 0.0;
		}
	}

	/**
	 * Build specialist and guide recommendations from rules + AI.
	 *
	 * @param  array<string,mixed> $calculator
	 * @param  array<string,mixed> $inputs
	 * @return array<string,mixed>
	 */
	private function build_recommendations( array $calculator, float $value, array $inputs ): array {
		$rules = json_decode( $calculator['recommendation_rules'] ?? '[]', true ) ?: [];
		$recs  = [];

		foreach ( $rules as $rule ) {
			$threshold = (float) ( $rule['threshold'] ?? 0 );
			$op        = $rule['operator'] ?? '>=';

			$match = match ( $op ) {
				'>='    => $value >= $threshold,
				'<='    => $value <= $threshold,
				'>'     => $value > $threshold,
				'<'     => $value < $threshold,
				default => false,
			};

			if ( $match ) {
				$recs[] = [
					'type'  => sanitize_text_field( $rule['type'] ?? 'guide' ),
					'label' => sanitize_text_field( $rule['label'] ?? '' ),
					'url'   => esc_url_raw( $rule['url'] ?? '' ),
				];
			}
		}

		// Supplement with AI suggestion when value is non-trivial.
		if ( empty( $recs ) && $value > 0 ) {
			try {
				$ai_text = $this->ai->generate(
					sprintf(
						'Kalkulator: "%s". Wynik: %s zł. ' .
						'Zaproponuj 2-3 krótkie wskazówki dla użytkownika po polsku.',
						$calculator['title'],
						number_format( $value, 0, ',', ' ' )
					),
					[ 'max_tokens' => 150 ]
				);

				$recs[] = [
					'type'  => 'ai',
					'label' => $ai_text,
					'url'   => '',
				];
			} catch ( \Throwable ) {
				// AI unavailable — return rule-based recommendations only.
			}
		}

		return $recs;
	}

	/**
	 * Persist a calculation result for analytics and lead gen.
	 *
	 * @param  array<string,mixed> $inputs
	 * @param  array<string,mixed> $recommendations
	 */
	private function persist_result( int $calculator_id, array $inputs, float $value, string $label, array $recommendations ): void {
		global $wpdb;

		$session_hash = substr( hash( 'sha256', wp_json_encode( $inputs ) . (string) time() ), 0, 64 );

		$wpdb->insert(
			"{$wpdb->prefix}pb_calculator_results",
			[
				'calculator_id'   => $calculator_id,
				'session_hash'    => $session_hash,
				'inputs_json'     => wp_json_encode( $inputs ),
				'result_value'    => $value,
				'result_label'    => $label,
				'recommendations' => wp_json_encode( $recommendations ),
			]
		);
	}
}
