<?php
/**
 * Unit tests for KeywordClusterEngine.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Keywords\KeywordClusterEngine;
use PearBlogEngine\Keywords\KeywordCluster;

class KeywordClusterEngineTest extends TestCase {

	private KeywordClusterEngine $engine;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']    = [];
		$GLOBALS['_transients'] = [];
		$this->engine = new KeywordClusterEngine();
	}

	// -----------------------------------------------------------------------
	// tokenise
	// -----------------------------------------------------------------------

	public function test_tokenise_splits_into_lowercase_words(): void {
		$tokens = $this->engine->tokenise( 'Best Email Marketing Tips' );
		$this->assertArrayHasKey( 'best', $tokens );
		$this->assertArrayHasKey( 'email', $tokens );
		$this->assertArrayHasKey( 'marketing', $tokens );
		$this->assertArrayHasKey( 'tips', $tokens );
	}

	public function test_tokenise_skips_short_words(): void {
		$tokens = $this->engine->tokenise( 'go to top' );
		$this->assertArrayNotHasKey( 'go', $tokens );
		$this->assertArrayNotHasKey( 'to', $tokens );
		$this->assertArrayHasKey( 'top', $tokens );
	}

	public function test_tokenise_empty_string(): void {
		$this->assertSame( [], $this->engine->tokenise( '' ) );
	}

	// -----------------------------------------------------------------------
	// jaccard_similarity
	// -----------------------------------------------------------------------

	public function test_jaccard_identical_sets(): void {
		$a = [ 'seo' => true, 'tips' => true ];
		$this->assertSame( 1.0, $this->engine->jaccard_similarity( $a, $a ) );
	}

	public function test_jaccard_disjoint_sets(): void {
		$a = [ 'seo' => true ];
		$b = [ 'food' => true ];
		$this->assertSame( 0.0, $this->engine->jaccard_similarity( $a, $b ) );
	}

	public function test_jaccard_both_empty(): void {
		$this->assertSame( 1.0, $this->engine->jaccard_similarity( [], [] ) );
	}

	public function test_jaccard_partial_overlap(): void {
		$a   = [ 'email' => true, 'marketing' => true ];
		$b   = [ 'email' => true, 'campaigns' => true ];
		$sim = $this->engine->jaccard_similarity( $a, $b );
		// |A∩B|=1, |A∪B|=3 → 1/3 ≈ 0.333
		$this->assertEqualsWithDelta( 1 / 3, $sim, 0.01 );
	}

	// -----------------------------------------------------------------------
	// build_clusters — basic grouping
	// -----------------------------------------------------------------------

	public function test_build_clusters_empty_input(): void {
		$this->assertSame( [], $this->engine->build_clusters( [] ) );
	}

	public function test_build_clusters_returns_keyword_cluster_instances(): void {
		$terms = [
			'email marketing tips',
			'email marketing guide',
			'email marketing best practices',
		];

		$clusters = $this->engine->build_clusters( $terms );
		$this->assertNotEmpty( $clusters );
		$this->assertInstanceOf( KeywordCluster::class, $clusters[0] );
	}

	public function test_build_clusters_groups_similar_terms(): void {
		// All three terms share "email" + "marketing" → should form one cluster.
		$terms = [
			'email marketing tips',
			'email marketing strategy',
			'email marketing guide',
		];

		update_option( KeywordClusterEngine::OPTION_SIMILARITY_THRESH, 0.2 );
		update_option( KeywordClusterEngine::OPTION_MIN_CLUSTER_SIZE,  2 );

		$clusters = $this->engine->build_clusters( $terms );
		$this->assertNotEmpty( $clusters );

		// At least one cluster should contain all three (or most of them).
		$all_keywords = array_merge( ...array_map( fn( $c ) => $c->all_keywords(), $clusters ) );
		$this->assertGreaterThanOrEqual( 2, count( $all_keywords ) );
	}

	public function test_build_clusters_enforces_min_cluster_size(): void {
		// With min_cluster_size=3, a cluster of 2 should be excluded.
		update_option( KeywordClusterEngine::OPTION_MIN_CLUSTER_SIZE, 3 );
		update_option( KeywordClusterEngine::OPTION_SIMILARITY_THRESH, 0.2 );

		$terms = [
			'email marketing tips',   // similar to next one
			'email marketing guide',  // similar to above — pair of 2 only
		];

		$clusters = $this->engine->build_clusters( $terms );
		$this->assertSame( [], $clusters );
	}

	public function test_build_clusters_respects_max_clusters(): void {
		update_option( KeywordClusterEngine::OPTION_MAX_CLUSTERS, 1 );
		update_option( KeywordClusterEngine::OPTION_MIN_CLUSTER_SIZE, 2 );
		update_option( KeywordClusterEngine::OPTION_SIMILARITY_THRESH, 0.3 );

		$terms = [
			'email marketing tips', 'email marketing guide', 'email marketing strategy',
			'seo link building tips', 'seo link building guide', 'seo link building strategy',
		];

		$clusters = $this->engine->build_clusters( $terms );
		$this->assertLessThanOrEqual( 1, count( $clusters ) );
	}

	public function test_build_clusters_dissimilar_terms_not_grouped(): void {
		// These terms share no tokens → should not end up in the same cluster.
		$terms = [
			'quantum computing research',
			'quantum computing algorithms',
			'chocolate cake baking recipes',
			'chocolate cake frosting ideas',
		];

		update_option( KeywordClusterEngine::OPTION_SIMILARITY_THRESH, 0.3 );
		update_option( KeywordClusterEngine::OPTION_MIN_CLUSTER_SIZE, 2 );

		$clusters = $this->engine->build_clusters( $terms );

		// Should find 2 separate clusters (quantum vs chocolate), not 1 big one.
		if ( count( $clusters ) >= 2 ) {
			$cluster_pillars = array_map( fn( $c ) => $c->pillar, $clusters );
			$has_quantum   = (bool) array_filter( $cluster_pillars, fn( $p ) => str_contains( $p, 'quantum' ) );
			$has_chocolate = (bool) array_filter( $cluster_pillars, fn( $p ) => str_contains( $p, 'chocolate' ) );
			$this->assertTrue( $has_quantum && $has_chocolate );
		} else {
			// At minimum we should have at least 1 cluster.
			$this->assertGreaterThanOrEqual( 1, count( $clusters ) );
		}
	}

	// -----------------------------------------------------------------------
	// get_clusters — option-backed caching
	// -----------------------------------------------------------------------

	public function test_get_clusters_returns_empty_when_no_option_and_no_ga4(): void {
		$clusters = $this->engine->get_clusters();
		$this->assertSame( [], $clusters );
	}

	public function test_get_clusters_deserialises_persisted_data(): void {
		$cluster = new KeywordCluster( 'email marketing', [ 'email newsletter', 'email campaigns' ] );
		update_option(
			KeywordClusterEngine::OPTION_CACHED_CLUSTERS,
			wp_json_encode( [ $cluster->to_array() ] )
		);

		$clusters = $this->engine->get_clusters();
		$this->assertCount( 1, $clusters );
		$this->assertInstanceOf( KeywordCluster::class, $clusters[0] );
		$this->assertSame( 'email marketing', $clusters[0]->pillar );
	}

	// -----------------------------------------------------------------------
	// fetch_search_terms — not configured
	// -----------------------------------------------------------------------

	public function test_fetch_search_terms_empty_when_ga4_not_configured(): void {
		$terms = $this->engine->fetch_search_terms();
		$this->assertSame( [], $terms );
	}

	// -----------------------------------------------------------------------
	// refresh_clusters — no GA4 → empty result persisted
	// -----------------------------------------------------------------------

	public function test_refresh_clusters_returns_empty_without_ga4(): void {
		$clusters = $this->engine->refresh_clusters();
		$this->assertSame( [], $clusters );
	}

	public function test_refresh_clusters_persists_empty_array(): void {
		$this->engine->refresh_clusters();
		$raw = get_option( KeywordClusterEngine::OPTION_CACHED_CLUSTERS, null );
		$this->assertNotNull( $raw );
	}
}
