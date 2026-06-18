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
	}

	// -----------------------------------------------------------------------
	// Input validation (returns null)
	// -----------------------------------------------------------------------

	public function test_calculate_returns_null_when_metraz_missing(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_below_10(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [ 'metraz' => 5 ] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_when_metraz_above_1000(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [ 'metraz' => 1001 ] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_standard(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'   => 100,
			'standard' => 'super_luksusowy',
		] );
		$this->assertNull( $result );
	}

	public function test_calculate_returns_null_for_invalid_lokalizacja(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 100,
			'lokalizacja' => 'kosmos',
		] );
		$this->assertNull( $result );
	}

	// -----------------------------------------------------------------------
	// Valid calculations
	// -----------------------------------------------------------------------

	public function test_calculate_returns_result_for_valid_inputs(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 100,
			'standard'    => 'sredni',
			'lokalizacja' => 'miasto',
		] );
		$this->assertNotNull( $result );
	}

	public function test_calculate_min_cost_less_than_max_cost(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz'      => 50,
			'standard'    => 'sredni',
			'lokalizacja' => 'miasto',
		] );
		$this->assertLessThan( $result['max_cost'], $result['min_cost'] );
	}

	public function test_calculate_returns_avg_cost(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 100,
		] );
		$this->assertNotNull( $result );
		$expected_avg = ( $result['min_cost'] + $result['max_cost'] ) / 2;
		$this->assertEqualsWithDelta( $expected_avg, $result['avg_cost'], 0.01 );
	}

	public function test_calculate_returns_cost_per_unit(): void {
		$metraz = 100;
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => $metraz,
		] );
		$this->assertNotNull( $result );
		$expected = $result['avg_cost'] / $metraz;
		$this->assertEqualsWithDelta( $expected, $result['cost_per_unit'], 0.01 );
	}

	public function test_calculate_returns_breakdown(): void {
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', [
			'metraz' => 80,
		] );
		$this->assertNotNull( $result );
		$this->assertArrayHasKey( 'breakdown', $result );
	}

	public function test_calculate_returns_inputs_in_result(): void {
		$inputs = [ 'metraz' => 75, 'standard' => 'premium' ];
		$result = SmartCalculatorEngine::calculate( 'remont-mieszkania', $inputs );
		$this->assertNotNull( $result );
		$this->assertSame( $inputs, $result['inputs'] );
	}

	public function test_calculate_returns_service_in_result(): void {
		$result = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 120 ] );
		$this->assertNotNull( $result );
		$this->assertSame( 'budowa-domu', $result['service'] );
	}

	public function test_standard_premium_costs_more_than_podstawowy(): void {
		$base = [ 'metraz' => 100, 'lokalizacja' => 'miasto' ];
		$premium   = SmartCalculatorEngine::calculate( 'remont-mieszkania', array_merge( $base, [ 'standard' => 'premium' ] ) );
		$podstawowy = SmartCalculatorEngine::calculate( 'remont-mieszkania', array_merge( $base, [ 'standard' => 'podstawowy' ] ) );
		$this->assertGreaterThan( $podstawowy['avg_cost'], $premium['avg_cost'] );
	}

	public function test_lokalizacja_miasto_costs_more_than_wies(): void {
		$base = [ 'metraz' => 100, 'standard' => 'sredni' ];
		$miasto = SmartCalculatorEngine::calculate( 'remont-mieszkania', array_merge( $base, [ 'lokalizacja' => 'miasto' ] ) );
		$wies   = SmartCalculatorEngine::calculate( 'remont-mieszkania', array_merge( $base, [ 'lokalizacja' => 'wies' ] ) );
		$this->assertGreaterThan( $wies['avg_cost'], $miasto['avg_cost'] );
	}

	public function test_calculate_unknown_service_uses_fallback_prices(): void {
		$result = SmartCalculatorEngine::calculate( 'unknown-service-xyz', [ 'metraz' => 100 ] );
		$this->assertNotNull( $result );
		$this->assertGreaterThan( 0, $result['avg_cost'] );
	}

	public function test_metraz_boundary_10_is_valid(): void {
		$result = SmartCalculatorEngine::calculate( 'dach', [ 'metraz' => 10 ] );
		$this->assertNotNull( $result );
	}

	public function test_metraz_boundary_1000_is_valid(): void {
		$result = SmartCalculatorEngine::calculate( 'dach', [ 'metraz' => 1000 ] );
		$this->assertNotNull( $result );
	}

	public function test_calculate_budowa_domu_uses_higher_base_price(): void {
		$budowa  = SmartCalculatorEngine::calculate( 'budowa-domu', [ 'metraz' => 100 ] );
		$remont  = SmartCalculatorEngine::calculate( 'remont-mieszkania', [ 'metraz' => 100 ] );
		$this->assertGreaterThan( $remont['avg_cost'], $budowa['avg_cost'] );
	}

	public function test_calculate_all_standards_return_non_null(): void {
		foreach ( [ 'podstawowy', 'sredni', 'premium' ] as $standard ) {
			$result = SmartCalculatorEngine::calculate( 'dach', [ 'metraz' => 50, 'standard' => $standard ] );
			$this->assertNotNull( $result, "Failed for standard: {$standard}" );
		}
	}

	public function test_calculate_all_lokalizacje_return_non_null(): void {
		foreach ( [ 'miasto', 'przedmiescia', 'wies' ] as $lok ) {
			$result = SmartCalculatorEngine::calculate( 'dach', [ 'metraz' => 50, 'lokalizacja' => $lok ] );
			$this->assertNotNull( $result, "Failed for lokalizacja: {$lok}" );
		}
	}
}
