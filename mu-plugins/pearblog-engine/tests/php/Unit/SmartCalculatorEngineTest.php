<?php
/**
 * Unit tests for SmartCalculatorEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\SmartCalculatorEngine;

class SmartCalculatorEngineTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options'] = [];
		$GLOBALS['_filters'] = [];
	}

	// -----------------------------------------------------------------------
	// calculate — invalid inputs
	// -----------------------------------------------------------------------

	public function test_calculate_returns_null_without_metraz(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [] );

		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_too_small(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 5 ] );

		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_too_large(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 5000 ] );

		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_standard(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'   => 100,
			'standard' => 'luksusowy',
		] );

		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_lokalizacja(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'      => 100,
			'lokalizacja' => 'zagranica',
		] );

		$this->assertNull( $result );
	}

	// -----------------------------------------------------------------------
	// calculate — valid inputs
	// -----------------------------------------------------------------------

	public function test_calculate_returns_array_for_valid_inputs(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'      => 100,
			'standard'    => 'sredni',
			'lokalizacja' => 'miasto',
		] );

		$this->assertIsArray( $result );
	}

	public function test_calculate_result_has_required_keys(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 100 ] );

		$this->assertArrayHasKey( 'min_cost', $result );
		$this->assertArrayHasKey( 'max_cost', $result );
		$this->assertArrayHasKey( 'avg_cost', $result );
		$this->assertArrayHasKey( 'cost_per_unit', $result );
		$this->assertArrayHasKey( 'breakdown', $result );
		$this->assertArrayHasKey( 'inputs', $result );
		$this->assertArrayHasKey( 'service', $result );
	}

	public function test_calculate_min_cost_less_than_max_cost(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz' => 100,
		] );

		$this->assertLessThan( $result['max_cost'], $result['min_cost'] );
	}

	public function test_calculate_avg_is_between_min_and_max(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz' => 100,
		] );

		$this->assertGreaterThanOrEqual( $result['min_cost'], $result['avg_cost'] );
		$this->assertLessThanOrEqual( $result['max_cost'], $result['avg_cost'] );
	}

	public function test_calculate_service_is_preserved_in_result(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [ 'metraz' => 60 ] );

		$this->assertSame( 'remont-mieszkania', $result['service'] );
	}

	// -----------------------------------------------------------------------
	// Standard multipliers
	// -----------------------------------------------------------------------

	public function test_premium_standard_more_expensive_than_basic(): void {
		$basic   = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'   => 100,
			'standard' => 'podstawowy',
		] );
		$premium = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'   => 100,
			'standard' => 'premium',
		] );

		$this->assertGreaterThan( $basic['avg_cost'], $premium['avg_cost'] );
	}

	// -----------------------------------------------------------------------
	// Location multipliers
	// -----------------------------------------------------------------------

	public function test_city_more_expensive_than_rural(): void {
		$city  = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'      => 100,
			'lokalizacja' => 'miasto',
		] );
		$rural = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz'      => 100,
			'lokalizacja' => 'wies',
		] );

		$this->assertGreaterThan( $rural['avg_cost'], $city['avg_cost'] );
	}

	// -----------------------------------------------------------------------
	// Different services
	// -----------------------------------------------------------------------

	public function test_calculate_works_for_remont_domu(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-domu', [ 'metraz' => 100 ] );

		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['avg_cost'] );
	}

	public function test_calculate_works_for_unknown_service_with_fallback(): void {
		$result = SmartCalculatorEngine::calculate( 'unknown-service', [ 'metraz' => 100 ] );

		$this->assertIsArray( $result );
		$this->assertGreaterThan( 0, $result['avg_cost'] );
	}

	// -----------------------------------------------------------------------
	// Breakdown
	// -----------------------------------------------------------------------

	public function test_breakdown_is_array(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 100 ] );

		$this->assertIsArray( $result['breakdown'] );
		$this->assertNotEmpty( $result['breakdown'] );
	}

	public function test_breakdown_values_are_numeric(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 100 ] );

		foreach ( $result['breakdown'] as $value ) {
			$this->assertIsFloat( $value );
			$this->assertGreaterThan( 0.0, $value );
		}
	}

	// -----------------------------------------------------------------------
	// render
	// -----------------------------------------------------------------------

	public function test_render_returns_string(): void {
		$html = SmartCalculatorEngine::render( 'budowa-domu' );

		$this->assertIsString( $html );
		$this->assertNotEmpty( $html );
	}

	public function test_render_contains_form(): void {
		$html = SmartCalculatorEngine::render( 'budowa-domu' );

		$this->assertStringContainsString( '<form', $html );
	}

	public function test_render_contains_service_data_attribute(): void {
		$html = SmartCalculatorEngine::render( 'budowa-domu' );

		$this->assertStringContainsString( 'data-service="budowa-domu"', $html );
	}

	public function test_render_contains_calculator_submit_button(): void {
		$html = SmartCalculatorEngine::render( 'budowa-domu' );

		$this->assertStringContainsString( 'calculator-submit', $html );
	}
}
