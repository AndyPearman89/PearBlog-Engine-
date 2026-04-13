<?php
/**
 * Unit tests for TopicResearchEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Content\TopicResearchEngine;
use PearBlogEngine\Content\TopicQueue;

/**
 * Minimal GA4Client stub — not configured, returns empty arrays.
 */
class StubGA4Client {
	public function is_configured(): bool { return false; }
	public function run_report( array $dims, array $mets, string $start, string $end ): array { return []; }
	public function extract_rows( array $report ): array { return []; }
}

/**
 * GA4Client stub that pretends to be configured and returns search terms.
 */
class ConfiguredGA4Client extends StubGA4Client {
	private array $terms;
	public function __construct( array $terms ) { $this->terms = $terms; }
	public function is_configured(): bool { return true; }
	public function run_report( array $dims, array $mets, string $start, string $end ): array { return [ 'rows' => [] ]; }
	public function extract_rows( array $report ): array { return $this->terms; }
}

/**
 * CompetitiveGapEngine stub.
 */
class StubGapEngine {
	private array $gap;
	public function __construct( array $gap = [] ) { $this->gap = $gap; }
	public function get_gap_topics( ?array $comp = null, ?array $pub = null ): array { return $this->gap; }
}

/**
 * KeywordClusterEngine stub with no clusters.
 */
class StubKCE {
	public function get_clusters(): array { return []; }
}

class TopicResearchEngineTest extends TestCase {

	private function make_engine(
		array $ga4_terms = [],
		array $gap_topics = []
	): TopicResearchEngine {
		$ga4  = empty( $ga4_terms )
			? new StubGA4Client()
			: new ConfiguredGA4Client( array_map( fn( $t ) => [ $t ], $ga4_terms ) );
		$gap  = new StubGapEngine( $gap_topics );
		$kce  = new StubKCE();

		// Use reflection to inject stubs.
		$engine = new TopicResearchEngine( null, null, null, null );
		$ref    = new \ReflectionClass( $engine );

		foreach ( [ 'ga4' => $ga4, 'gap' => $gap, 'kce' => $kce ] as $prop => $stub ) {
			$p = $ref->getProperty( $prop );
			$p->setAccessible( true );
			$p->setValue( $engine, $stub );
		}

		return $engine;
	}

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_transients']       = [];
		$GLOBALS['_current_user_can'] = false;
	}

	// -----------------------------------------------------------------------
	// is_enabled
	// -----------------------------------------------------------------------

	public function test_disabled_by_default(): void {
		$engine = $this->make_engine();
		$this->assertFalse( $engine->is_enabled() );
	}

	public function test_enabled_when_option_set(): void {
		update_option( TopicResearchEngine::OPTION_ENABLED, true );
		$engine = $this->make_engine();
		$this->assertTrue( $engine->is_enabled() );
	}

	// -----------------------------------------------------------------------
	// compute_score
	// -----------------------------------------------------------------------

	public function test_score_zero_when_no_sources(): void {
		$engine = $this->make_engine();
		$score  = $engine->compute_score( 'foo', [], [], [] );
		$this->assertSame( 0.0, $score );
	}

	public function test_score_ga4_only(): void {
		$engine = $this->make_engine();
		$score  = $engine->compute_score( 'foo', [ 'ga4' ], [], [] );
		$this->assertSame( 40.0, $score ); // WEIGHT_GA4 = 40
	}

	public function test_score_gap_adds_to_ga4(): void {
		$engine = $this->make_engine();
		$score  = $engine->compute_score( 'best php tips', [ 'ga4' ], [ 'best php tips' ], [] );
		$this->assertGreaterThan( 40.0, $score ); // GA4 (40) + gap (40) = 80
	}

	public function test_serp_source_gives_half_ga4_weight(): void {
		$engine      = $this->make_engine();
		$serp_score  = $engine->compute_score( 'foo', [ 'serp' ], [], [] );
		$this->assertSame( 20.0, $serp_score ); // half of WEIGHT_GA4 (40) = 20
	}

	public function test_cluster_bonus_added(): void {
		$engine = $this->make_engine();
		$score  = $engine->compute_score( 'best php tips', [ 'ga4' ], [], [ 'best php tips' => 0 ] );
		$this->assertSame( 60.0, $score ); // GA4 (40) + cluster (20)
	}

	// -----------------------------------------------------------------------
	// collect_candidates
	// -----------------------------------------------------------------------

	public function test_collect_candidates_from_ga4_terms(): void {
		$engine     = $this->make_engine( [ 'php frameworks', 'best seo tools' ] );
		$candidates = $engine->collect_candidates();
		$this->assertArrayHasKey( 'php frameworks', $candidates );
		$this->assertContains( 'ga4', $candidates['php frameworks'] );
	}

	public function test_collect_candidates_from_gap(): void {
		$engine     = $this->make_engine( [], [ 'react tutorial', 'node.js guide' ] );
		$candidates = $engine->collect_candidates();
		$this->assertArrayHasKey( 'react tutorial', $candidates );
		$this->assertContains( 'serp', $candidates['react tutorial'] );
	}

	public function test_collect_candidates_deduplicates_sources(): void {
		// Same topic from both GA4 and gap should be one entry with both sources.
		$engine     = $this->make_engine( [ 'php tips' ], [ 'php tips' ] );
		$candidates = $engine->collect_candidates();
		$this->assertArrayHasKey( 'php tips', $candidates );
		$sources = $candidates['php tips'];
		$this->assertContains( 'ga4', $sources );
		$this->assertContains( 'serp', $sources );
		// Same source should not appear twice.
		$this->assertSame( count( $sources ), count( array_unique( $sources ) ) );
	}

	// -----------------------------------------------------------------------
	// score_topics
	// -----------------------------------------------------------------------

	public function test_score_topics_returns_scored_array(): void {
		$engine  = $this->make_engine();
		$results = $engine->score_topics( [ 'best seo' => [ 'ga4' ], 'react tips' => [ 'serp' ] ] );
		$this->assertCount( 2, $results );
		$this->assertArrayHasKey( 'topic', $results[0] );
		$this->assertArrayHasKey( 'score', $results[0] );
		$this->assertArrayHasKey( 'sources', $results[0] );
	}

	public function test_score_topics_returns_empty_on_empty_input(): void {
		$engine = $this->make_engine();
		$this->assertSame( [], $engine->score_topics( [] ) );
	}

	// -----------------------------------------------------------------------
	// run
	// -----------------------------------------------------------------------

	public function test_run_returns_only_topics_above_min_score(): void {
		update_option( TopicResearchEngine::OPTION_MIN_SCORE, 50.0 );
		$engine = $this->make_engine( [ 'high value topic' ], [ 'high value topic' ] );
		$recs   = $engine->run();
		// 'high value topic' scores 40 (ga4) + 40 (gap) = 80 ≥ 50.
		$topics = array_column( $recs, 'topic' );
		$this->assertContains( 'high value topic', $topics );
	}

	public function test_run_persists_recommendations(): void {
		$engine = $this->make_engine( [ 'seo strategy' ], [ 'seo strategy' ] );
		$engine->run();
		$cached = $engine->get_recommendations();
		$this->assertNotEmpty( $cached );
	}

	public function test_run_sorts_descending_by_score(): void {
		update_option( TopicResearchEngine::OPTION_MIN_SCORE, 0.0 );
		$engine = $this->make_engine( [ 'high score topic' ], [ 'high score topic', 'gap only topic' ] );
		$recs   = $engine->run();
		if ( count( $recs ) >= 2 ) {
			$this->assertGreaterThanOrEqual( $recs[1]['score'], $recs[0]['score'] );
		}
		$this->assertNotEmpty( $recs );
	}

	// -----------------------------------------------------------------------
	// auto_queue
	// -----------------------------------------------------------------------

	public function test_auto_queue_pushes_topics_to_queue(): void {
		$recs = [
			[ 'topic' => 'PHP patterns', 'score' => 80.0, 'sources' => [ 'ga4' ] ],
			[ 'topic' => 'JavaScript tips', 'score' => 60.0, 'sources' => [ 'serp' ] ],
		];
		update_option( TopicResearchEngine::OPTION_MAX_TOPICS, 5 );
		$engine = $this->make_engine();
		$pushed = $engine->auto_queue( $recs, 1 );
		$this->assertSame( 2, $pushed );

		$queue = new TopicQueue( 1 );
		$this->assertContains( 'PHP patterns', $queue->all() );
	}

	public function test_auto_queue_skips_duplicates(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'Existing Topic' );

		$recs = [
			[ 'topic' => 'Existing Topic', 'score' => 80.0, 'sources' => [ 'ga4' ] ],
			[ 'topic' => 'New Topic',       'score' => 60.0, 'sources' => [ 'serp' ] ],
		];
		$engine = $this->make_engine();
		$pushed = $engine->auto_queue( $recs, 1 );
		$this->assertSame( 1, $pushed );
	}

	public function test_auto_queue_respects_max_topics(): void {
		update_option( TopicResearchEngine::OPTION_MAX_TOPICS, 2 );
		$recs = [
			[ 'topic' => 'Topic 1', 'score' => 90.0, 'sources' => [ 'ga4' ] ],
			[ 'topic' => 'Topic 2', 'score' => 80.0, 'sources' => [ 'ga4' ] ],
			[ 'topic' => 'Topic 3', 'score' => 70.0, 'sources' => [ 'serp' ] ],
		];
		$engine = $this->make_engine();
		$pushed = $engine->auto_queue( $recs, 1 );
		$this->assertSame( 2, $pushed );
	}
}

// Autoload check (no helper needed).
TopicResearchEngine::class;
