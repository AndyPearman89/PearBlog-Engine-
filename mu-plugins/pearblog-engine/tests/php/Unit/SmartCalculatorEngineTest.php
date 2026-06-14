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
		$GLOBALS['_options']     = [];
		$GLOBALS['_db_results']  = []; // get_var returns null → table absent → store skipped
		$GLOBALS['_db_inserts']  = [];
	}

	// -----------------------------------------------------------------------
	// calculate — happy path
	// -----------------------------------------------------------------------

	public function test_calculate_returns_array_for_known_service(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'    => 50,
			'standard'  => 'sredni',
			'lokalizacja' => 'miasto',
		] );

		$this->assertIsArray( $result );
	}

	public function test_calculate_returns_required_keys(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 80,
		] );

		$this->assertArrayHasKey( 'min_cost', $result );
		$this->assertArrayHasKey( 'max_cost', $result );
		$this->assertArrayHasKey( 'avg_cost', $result );
		$this->assertArrayHasKey( 'cost_per_unit', $result );
		$this->assertArrayHasKey( 'breakdown', $result );
		$this->assertArrayHasKey( 'service', $result );
	}

	public function test_calculate_min_cost_less_than_max_cost(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [
			'metraz' => 100,
		] );

		$this->assertLessThan( $result['max_cost'], $result['min_cost'] );
	}

	public function test_calculate_avg_cost_between_min_and_max(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-domu', [
			'metraz' => 120,
		] );

		$this->assertGreaterThanOrEqual( $result['min_cost'], $result['avg_cost'] );
		$this->assertLessThanOrEqual( $result['max_cost'], $result['avg_cost'] );
	}

	public function test_calculate_cost_per_unit_equals_avg_divided_by_area(): void {
		$metraz = 100;
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => $metraz,
		] );

		$expected_cpu = round( $result['avg_cost'] / $metraz, 2 );
		$this->assertSame( $expected_cpu, $result['cost_per_unit'] );
	}

	public function test_calculate_stores_service_slug_in_result(): void {
		$result = SmartCalculatorEngine::calculate( 'dach', [
			'metraz' => 60,
		] );

		$this->assertSame( 'dach', $result['service'] );
	}

	// -----------------------------------------------------------------------
	// calculate — standard multiplier effect
	// -----------------------------------------------------------------------

	public function test_premium_standard_costs_more_than_sredni(): void {
		$base = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 100,
			'standard' => 'sredni',
		] );
		$premium = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 100,
			'standard' => 'premium',
		] );

		$this->assertGreaterThan( $base['avg_cost'], $premium['avg_cost'] );
	}

	public function test_podstawowy_standard_costs_less_than_sredni(): void {
		$base = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 100,
			'standard' => 'sredni',
		] );
		$basic = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 100,
			'standard' => 'podstawowy',
		] );

		$this->assertLessThan( $base['avg_cost'], $basic['avg_cost'] );
	}

	// -----------------------------------------------------------------------
	// calculate — location multiplier effect
	// -----------------------------------------------------------------------

	public function test_miasto_lokalizacja_costs_more_than_wies(): void {
		$city = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 100,
			'lokalizacja' => 'miasto',
		] );
		$rural = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 100,
			'lokalizacja' => 'wies',
		] );

		$this->assertGreaterThan( $rural['avg_cost'], $city['avg_cost'] );
	}

	public function test_przedmiescia_lokalizacja_is_baseline(): void {
		$suburban = SmartCalculatorEngine::calculate( 'dach', [
			'metraz'      => 50,
			'standard'    => 'sredni',
			'lokalizacja' => 'przedmiescia',
		] );

		// Baseline: multiplier 1.0 — no change from raw base
		$this->assertGreaterThan( 0, $suburban['avg_cost'] );
	}

	// -----------------------------------------------------------------------
	// calculate — unknown service uses fallback prices
	// -----------------------------------------------------------------------

	public function test_calculate_returns_result_for_unknown_service(): void {
		$result = SmartCalculatorEngine::calculate( 'unknown-service-xyz', [
			'metraz' => 50,
		] );

		$this->assertIsArray( $result );
		$this->assertSame( 'unknown-service-xyz', $result['service'] );
	}

	// -----------------------------------------------------------------------
	// validate_inputs — invalid cases (calculate returns null)
	// -----------------------------------------------------------------------

	public function test_calculate_returns_null_when_metraz_missing(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_too_small(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 5, // below minimum of 10
		] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_too_large(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 1001, // above maximum of 1000
		] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_standard(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 50,
			'standard' => 'luxury', // not in allowed list
		] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_lokalizacja(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 50,
			'lokalizacja' => 'europa', // not in allowed list
		] );
		$this->assertNull( $result );
	}

	// -----------------------------------------------------------------------
	// validate_inputs — edge values accepted
	// -----------------------------------------------------------------------

	public function test_calculate_accepts_metraz_at_minimum_boundary(): void {
		$result = SmartCalculatorEngine::calculate( 'dach', [
			'metraz' => 10,
		] );
		$this->assertIsArray( $result );
	}

	public function test_calculate_accepts_metraz_at_maximum_boundary(): void {
		$result = SmartCalculatorEngine::calculate( 'dach', [
			'metraz' => 1000,
		] );
		$this->assertIsArray( $result );
	}

	// -----------------------------------------------------------------------
	// cost scales with area
	// -----------------------------------------------------------------------

	public function test_larger_area_produces_higher_cost(): void {
		$small = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 30,
		] );
		$large = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 150,
		] );

		$this->assertGreaterThan( $small['avg_cost'], $large['avg_cost'] );
	}
}
