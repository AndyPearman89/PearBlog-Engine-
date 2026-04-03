<?php
/**
 * Cluster engine – manages topical authority clusters across WordPress posts.
 *
 * A cluster consists of:
 *  - one pillar post   (covers the broad topic comprehensively)
 *  - N supporting posts (cover specific sub-topics in depth)
 *
 * Cluster data is stored as:
 *  - Site option `pearblog_clusters`               → registry of all clusters.
 *  - Post meta  `pearblog_cluster_id`              → which cluster a post belongs to.
 *  - Post meta  `pearblog_cluster_role` (pillar|supporting) → role within cluster.
 *
 * @package PearBlogEngine\SEO
 */

declare(strict_types=1);

namespace PearBlogEngine\SEO;

use PearBlogEngine\Keywords\KeywordCluster;

/**
 * Cluster registry and post-association manager.
 */
class ClusterEngine {

	private const REGISTRY_OPTION = 'pearblog_clusters';
	private const META_CLUSTER_ID = 'pearblog_cluster_id';
	private const META_ROLE       = 'pearblog_cluster_role';

	/** @var int WordPress site ID. */
	private int $site_id;

	public function __construct( int $site_id ) {
		$this->site_id = $site_id;
	}

	// -----------------------------------------------------------------------
	// Cluster registry
	// -----------------------------------------------------------------------

	/**
	 * Persist a {@see KeywordCluster} to the site registry.
	 *
	 * @param KeywordCluster $cluster Cluster to store.
	 */
	public function save_cluster( KeywordCluster $cluster ): void {
		$registry = $this->load_registry();
		$registry[ $cluster->cluster_id ] = $cluster->to_array();
		$this->save_registry( $registry );
	}

	/**
	 * Find a stored cluster by its ID, or return null.
	 *
	 * @param string $cluster_id Cluster ID (derived from pillar keyword).
	 */
	public function find_cluster( string $cluster_id ): ?KeywordCluster {
		$registry = $this->load_registry();
		if ( ! isset( $registry[ $cluster_id ] ) ) {
			return null;
		}
		return KeywordCluster::from_array( $registry[ $cluster_id ] );
	}

	/**
	 * Find a cluster whose pillar (or any supporting keyword) matches a topic.
	 *
	 * Returns the first matching cluster, or null.
	 *
	 * @param string $topic Topic / keyword to search for.
	 */
	public function find_cluster_for_topic( string $topic ): ?KeywordCluster {
		$topic    = mb_strtolower( trim( $topic ) );
		$registry = $this->load_registry();

		foreach ( $registry as $data ) {
			$cluster = KeywordCluster::from_array( $data );
			foreach ( $cluster->all_keywords() as $kw ) {
				if ( mb_strtolower( $kw ) === $topic ) {
					return $cluster;
				}
			}
		}
		return null;
	}

	/**
	 * Return all stored clusters.
	 *
	 * @return KeywordCluster[]
	 */
	public function all_clusters(): array {
		return array_map(
			[ KeywordCluster::class, 'from_array' ],
			array_values( $this->load_registry() )
		);
	}

	/**
	 * Delete a cluster from the registry.
	 *
	 * @param string $cluster_id Cluster ID.
	 */
	public function delete_cluster( string $cluster_id ): void {
		$registry = $this->load_registry();
		unset( $registry[ $cluster_id ] );
		$this->save_registry( $registry );
	}

	// -----------------------------------------------------------------------
	// Post association
	// -----------------------------------------------------------------------

	/**
	 * Tag a post as the pillar page for a cluster.
	 *
	 * @param int            $post_id WordPress post ID.
	 * @param KeywordCluster $cluster The cluster.
	 */
	public function mark_as_pillar( int $post_id, KeywordCluster $cluster ): void {
		update_post_meta( $post_id, self::META_CLUSTER_ID, $cluster->cluster_id );
		update_post_meta( $post_id, self::META_ROLE, 'pillar' );
	}

	/**
	 * Tag a post as a supporting page in a cluster.
	 *
	 * @param int            $post_id WordPress post ID.
	 * @param KeywordCluster $cluster The cluster.
	 */
	public function mark_as_supporting( int $post_id, KeywordCluster $cluster ): void {
		update_post_meta( $post_id, self::META_CLUSTER_ID, $cluster->cluster_id );
		update_post_meta( $post_id, self::META_ROLE, 'supporting' );
	}

	/**
	 * Return all published post IDs that belong to a given cluster.
	 *
	 * @param string $cluster_id Cluster ID.
	 * @return int[]
	 */
	public function get_posts_in_cluster( string $cluster_id ): array {
		$query = new \WP_Query( [
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_query'     => [ [
				'key'   => self::META_CLUSTER_ID,
				'value' => $cluster_id,
			] ],
		] );

		return array_map( 'intval', $query->posts );
	}

	/**
	 * Return the pillar post ID for a cluster, or null if none exists yet.
	 *
	 * @param string $cluster_id Cluster ID.
	 */
	public function get_pillar_post( string $cluster_id ): ?int {
		$query = new \WP_Query( [
			'post_status'    => [ 'publish', 'draft', 'pending' ],
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'   => self::META_CLUSTER_ID,
					'value' => $cluster_id,
				],
				[
					'key'   => self::META_ROLE,
					'value' => 'pillar',
				],
			],
		] );

		return ! empty( $query->posts ) ? (int) $query->posts[0] : null;
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function load_registry(): array {
		$raw = get_blog_option( $this->site_id, self::REGISTRY_OPTION, [] );
		return is_array( $raw ) ? $raw : [];
	}

	private function save_registry( array $registry ): void {
		update_blog_option( $this->site_id, self::REGISTRY_OPTION, $registry );
	}
}
