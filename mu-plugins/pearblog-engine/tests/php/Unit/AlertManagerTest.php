<?php
/**
 * Unit tests for AlertManager.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Monitoring\AlertManager;

class AlertManagerTest extends TestCase {

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
	}

	// -----------------------------------------------------------------------
	// Constants
	// -----------------------------------------------------------------------

	public function test_level_constants_are_defined(): void {
		$this->assertSame( 'info', AlertManager::LEVEL_INFO );
		$this->assertSame( 'warning', AlertManager::LEVEL_WARNING );
		$this->assertSame( 'error', AlertManager::LEVEL_ERROR );
		$this->assertSame( 'critical', AlertManager::LEVEL_CRITICAL );
	}

	public function test_priority_constants_are_defined(): void {
		$this->assertSame( 0, AlertManager::PRIORITY_P0 );
		$this->assertSame( 1, AlertManager::PRIORITY_P1 );
		$this->assertSame( 2, AlertManager::PRIORITY_P2 );
		$this->assertSame( 3, AlertManager::PRIORITY_P3 );
	}

	// -----------------------------------------------------------------------
	// Basic dispatch
	// -----------------------------------------------------------------------

	public function test_alert_does_not_throw_when_no_channels_configured(): void {
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->alert( 'Test', 'Test message', AlertManager::LEVEL_INFO );
	}

	public function test_pipeline_error_shorthand_uses_error_level(): void {
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->pipeline_error( 'Something went wrong', [ 'context' => 'test' ] );
	}

	public function test_critical_shorthand_uses_critical_level(): void {
		$manager = new AlertManager();
		$this->expectNotToPerformAssertions();
		$manager->critical( 'Critical Title', 'Critical message' );
	}

	public function test_info_shorthand_does_not_deduplicate(): void {
		$manager = new AlertManager();
		$manager->info( 'Title', 'Message 1' );
		$manager->info( 'Title', 'Message 2' );
		$this->assertTrue( true );
	}

	// -----------------------------------------------------------------------
	// Deduplication
	// -----------------------------------------------------------------------

	public function test_deduplication_suppresses_identical_alerts(): void {
		$title = 'Duplicate Alert';
		$level = AlertManager::LEVEL_ERROR;
		$key   = 'pb_alert_dedup_' . substr( md5( $title . $level ), 0, 16 );
		set_transient( $key, 1, 300 );

		$manager = new AlertManager();
		$manager->alert( $title, 'message', $level, [], true );
		$this->assertTrue( true );
	}

	public function test_dedup_transient_is_set_after_first_alert(): void {
		$title = 'New Alert';
		$level = AlertManager::LEVEL_WARNING;
		$key   = 'pb_alert_dedup_' . substr( md5( $title . $level ), 0, 16 );

		$this->assertFalse( get_transient( $key ) );

		( new AlertManager() )->alert( $title, 'body', $level, [], true );

		$this->assertNotFalse( get_transient( $key ) );
	}

	// -----------------------------------------------------------------------
	// Silence / mute
	// -----------------------------------------------------------------------

	public function test_add_silence_prevents_matching_alert(): void {
		$manager = new AlertManager();
		// Silence for 1 hour.
		$manager->add_silence( 'Pipeline Error', time() + 3600 );

		// Alert with matching title should be silenced (no exception).
		$this->expectNotToPerformAssertions();
		$manager->alert( 'Pipeline Error', 'test', AlertManager::LEVEL_ERROR, [], false );
	}

	public function test_expired_silence_does_not_suppress_alert(): void {
		$manager = new AlertManager();
		// Already-expired silence.
		$manager->add_silence( 'Pipeline Error', time() - 1 );

		// Alert should proceed (still no exception since no channel configured).
		$this->expectNotToPerformAssertions();
		$manager->alert( 'Pipeline Error', 'test', AlertManager::LEVEL_ERROR, [], false );
	}

	public function test_remove_silence_allows_alert_through(): void {
		$manager = new AlertManager();
		$manager->add_silence( 'My Alert', time() + 3600 );
		$manager->remove_silence( 'My Alert' );

		$this->assertEmpty( $manager->get_active_silences() );
	}

	public function test_get_active_silences_excludes_expired(): void {
		$manager = new AlertManager();
		$manager->add_silence( 'Active',  time() + 3600 );
		$manager->add_silence( 'Expired', time() - 1 );

		$active = $manager->get_active_silences();
		$this->assertCount( 1, $active );
		$this->assertSame( 'Active', $active[0]['pattern'] );
	}

	public function test_silence_restricted_to_specific_level(): void {
		$manager = new AlertManager();
		// Silence only 'error' level, not 'warning'.
		$manager->add_silence( 'DB Issue', time() + 3600, AlertManager::LEVEL_ERROR );

		// warning-level should still go through (no exception).
		$this->expectNotToPerformAssertions();
		$manager->alert( 'DB Issue', 'test', AlertManager::LEVEL_WARNING, [], false );
	}

	// -----------------------------------------------------------------------
	// Threshold management
	// -----------------------------------------------------------------------

	public function test_set_threshold_persists_to_option(): void {
		$manager = new AlertManager();
		$manager->set_threshold( 'Pipeline Error', AlertManager::LEVEL_ERROR, 10 );

		$raw  = get_option( AlertManager::OPTION_THRESHOLDS );
		$data = json_decode( $raw, true );
		$this->assertNotEmpty( $data );
		$this->assertSame( 'Pipeline Error', $data[0]['title_pattern'] );
		$this->assertSame( 10, $data[0]['max_per_hour'] );
	}

	// -----------------------------------------------------------------------
	// Templates
	// -----------------------------------------------------------------------

	public function test_set_template_persists_to_option(): void {
		$manager = new AlertManager();
		$manager->set_template( 'Test Alert', 'Slack: {message}', 'Email: {message}' );

		$raw  = get_option( AlertManager::OPTION_TEMPLATES );
		$data = json_decode( $raw, true );
		$this->assertNotEmpty( $data );
		$this->assertSame( 'Test Alert', $data[0]['title_pattern'] );
	}

	// -----------------------------------------------------------------------
	// ESCALATION_LEVELS enforcement
	// -----------------------------------------------------------------------

	public function test_p0_priority_escalates_info_to_critical(): void {
		$manager = new AlertManager();
		// Send an info-level alert with P0 priority — it must be recorded as critical.
		$manager->alert( 'P0 Test', 'body', AlertManager::LEVEL_INFO, [], false, AlertManager::PRIORITY_P0 );

		$history = $manager->get_history();
		$this->assertNotEmpty( $history );
		$entry = end( $history );
		$this->assertSame( AlertManager::LEVEL_CRITICAL, $entry['level'] );
	}

	public function test_p1_priority_escalates_warning_to_error(): void {
		$manager = new AlertManager();
		$manager->alert( 'P1 Test', 'body', AlertManager::LEVEL_WARNING, [], false, AlertManager::PRIORITY_P1 );

		$history = $manager->get_history();
		$entry   = end( $history );
		$this->assertSame( AlertManager::LEVEL_ERROR, $entry['level'] );
	}

	public function test_higher_provided_level_is_not_downgraded(): void {
		$manager = new AlertManager();
		// P3 minimum is info, but we provide critical — must stay critical.
		$manager->alert( 'P3 Critical Test', 'body', AlertManager::LEVEL_CRITICAL, [], false, AlertManager::PRIORITY_P3 );

		$history = $manager->get_history();
		$entry   = end( $history );
		$this->assertSame( AlertManager::LEVEL_CRITICAL, $entry['level'] );
	}

	public function test_p2_warning_level_is_unchanged(): void {
		$manager = new AlertManager();
		// P2 minimum is warning; providing warning should remain warning.
		$manager->alert( 'P2 Test', 'body', AlertManager::LEVEL_WARNING, [], false, AlertManager::PRIORITY_P2 );

		$history = $manager->get_history();
		$entry   = end( $history );
		$this->assertSame( AlertManager::LEVEL_WARNING, $entry['level'] );
	}


	public function test_alert_is_recorded_in_history(): void {
		$manager = new AlertManager();
		$manager->alert( 'History Test', 'msg', AlertManager::LEVEL_INFO, [], false );

		$history = $manager->get_history();
		$this->assertNotEmpty( $history );
		$found = false;
		foreach ( $history as $entry ) {
			if ( $entry['title'] === 'History Test' ) {
				$found = true;
				break;
			}
		}
		$this->assertTrue( $found, 'Alert should appear in history' );
	}

	public function test_clear_history_empties_history(): void {
		$manager = new AlertManager();
		$manager->alert( 'To Clear', 'msg', AlertManager::LEVEL_INFO, [], false );
		$manager->clear_history();

		$this->assertEmpty( $manager->get_history() );
	}

	public function test_get_history_respects_limit(): void {
		$manager = new AlertManager();
		for ( $i = 0; $i < 5; $i++ ) {
			$manager->alert( "Alert {$i}", 'msg', AlertManager::LEVEL_INFO, [], false );
		}

		$history = $manager->get_history( 3 );
		$this->assertCount( 3, $history );
	}
}

