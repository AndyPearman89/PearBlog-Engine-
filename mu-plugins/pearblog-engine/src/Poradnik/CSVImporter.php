<?php
/**
 * CSV Import System
 *
 * Handles batch content generation from CSV files.
 *
 * @package PearBlog\Poradnik
 */

namespace PearBlog\Poradnik;

/**
 * Class CSVImporter
 *
 * Import and process CSV files for content generation.
 */
class CSVImporter {
	/**
	 * Required CSV columns.
	 *
	 * @var array
	 */
	private $required_columns = array( 'topic', 'category', 'city', 'intent' );

	/**
	 * Valid intent types.
	 *
	 * @var array
	 */
	private $valid_intents = array( 'cost', 'service', 'problem', 'comparison', 'diy' );

	/**
	 * Import CSV file and return rows.
	 *
	 * @param string $file_path Path to CSV file.
	 * @return array|WP_Error Array of rows or error.
	 */
	public function import( string $file_path ) {
		if ( ! file_exists( $file_path ) ) {
			return new \WP_Error( 'file_not_found', 'CSV file not found' );
		}

		$handle = fopen( $file_path, 'r' );
		if ( ! $handle ) {
			return new \WP_Error( 'file_open_failed', 'Could not open CSV file' );
		}

		// Read header row
		$header = fgetcsv( $handle );
		if ( ! $header ) {
			fclose( $handle );
			return new \WP_Error( 'invalid_csv', 'CSV file is empty or invalid' );
		}

		// Validate columns
		$validation = $this->validate_columns( $header );
		if ( is_wp_error( $validation ) ) {
			fclose( $handle );
			return $validation;
		}

		// Read data rows
		$rows  = array();
		$line_number = 1;

		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$line_number++;
			$row_data = array_combine( $header, $row );

			// Validate row
			$validated = $this->validate_row( $row_data, $line_number );
			if ( is_wp_error( $validated ) ) {
				// Skip invalid rows but log error
				error_log( '[CSVImporter] Line ' . $line_number . ': ' . $validated->get_error_message() );
				continue;
			}

			$rows[] = $validated;
		}

		fclose( $handle );

		return $rows;
	}

	/**
	 * Validate CSV columns.
	 *
	 * @param array $columns Column names from CSV.
	 * @return true|WP_Error True if valid, WP_Error otherwise.
	 */
	private function validate_columns( array $columns ) {
		$missing = array_diff( $this->required_columns, $columns );

		if ( ! empty( $missing ) ) {
			return new \WP_Error(
				'missing_columns',
				sprintf( 'Missing required columns: %s', implode( ', ', $missing ) )
			);
		}

		return true;
	}

	/**
	 * Validate and clean a CSV row.
	 *
	 * @param array $row Row data.
	 * @param int   $line_number Line number for error reporting.
	 * @return array|WP_Error Validated row or error.
	 */
	private function validate_row( array $row, int $line_number ) {
		// Check required fields
		foreach ( $this->required_columns as $column ) {
			if ( empty( $row[ $column ] ) ) {
				return new \WP_Error(
					'missing_field',
					sprintf( 'Missing required field: %s', $column )
				);
			}
		}

		// Validate intent
		if ( ! in_array( $row['intent'], $this->valid_intents, true ) ) {
			return new \WP_Error(
				'invalid_intent',
				sprintf(
					'Invalid intent "%s". Must be one of: %s',
					$row['intent'],
					implode( ', ', $this->valid_intents )
				)
			);
		}

		// Clean and return
		return array(
			'topic'    => trim( $row['topic'] ),
			'category' => trim( $row['category'] ),
			'city'     => trim( $row['city'] ),
			'intent'   => trim( $row['intent'] ),
		);
	}

	/**
	 * Generate topic combinations for batch generation.
	 *
	 * Example: 10 topics × 100 cities = 1,000 articles
	 *
	 * @param array $topics Array of topics.
	 * @param array $cities Array of cities.
	 * @param string $intent Content intent.
	 * @return array Array of topic combinations.
	 */
	public function generate_combinations( array $topics, array $cities, string $intent = 'cost' ): array {
		$combinations = array();

		foreach ( $topics as $topic ) {
			foreach ( $cities as $city ) {
				$combinations[] = array(
					'topic'    => $topic['name'],
					'category' => $topic['category'],
					'city'     => $city,
					'intent'   => $intent,
				);
			}
		}

		return $combinations;
	}

	/**
	 * Export rows to CSV file.
	 *
	 * @param array  $rows Rows to export.
	 * @param string $file_path Path to output file.
	 * @return bool|WP_Error True on success, WP_Error on failure.
	 */
	public function export( array $rows, string $file_path ) {
		$handle = fopen( $file_path, 'w' );
		if ( ! $handle ) {
			return new \WP_Error( 'file_open_failed', 'Could not create CSV file' );
		}

		// Write header
		fputcsv( $handle, $this->required_columns );

		// Write rows
		foreach ( $rows as $row ) {
			$csv_row = array(
				$row['topic'],
				$row['category'],
				$row['city'],
				$row['intent'],
			);
			fputcsv( $handle, $csv_row );
		}

		fclose( $handle );

		return true;
	}

	/**
	 * Get sample CSV template.
	 *
	 * @return array Sample rows.
	 */
	public function get_sample_template(): array {
		return array(
			array(
				'topic'    => 'Remont łazienki',
				'category' => 'remont',
				'city'     => 'Warszawa',
				'intent'   => 'cost',
			),
			array(
				'topic'    => 'Malowanie mieszkania',
				'category' => 'remont',
				'city'     => 'Kraków',
				'intent'   => 'cost',
			),
			array(
				'topic'    => 'Wymiana okien',
				'category' => 'budowa',
				'city'     => 'Wrocław',
				'intent'   => 'service',
			),
		);
	}
}
