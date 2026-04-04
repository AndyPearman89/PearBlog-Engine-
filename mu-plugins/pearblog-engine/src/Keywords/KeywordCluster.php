<?php
/**
 * Keyword cluster – value object pairing a pillar keyword with supporting keywords.
 *
 * A cluster represents a topical authority unit:
 *  - one pillar page that covers the broad topic in depth
 *  - several supporting articles that cover specific sub-topics
 *
 * Clusters are identified by a stable ID derived from the pillar keyword so
 * that they survive serialisation to WordPress options.
 *
 * @package PearBlogEngine\Keywords
 */

declare(strict_types=1);

namespace PearBlogEngine\Keywords;

/**
 * Immutable keyword cluster.
 */
class KeywordCluster {

	/** @var string Sanitised slug used as a stable identifier. */
	public readonly string $cluster_id;

	/** @var string The broad pillar keyword / topic. */
	public readonly string $pillar;

	/** @var string[] Supporting / long-tail keywords in this cluster. */
	public readonly array $supporting;

	/**
	 * @param string   $pillar     Pillar keyword (e.g. "email marketing").
	 * @param string[] $supporting Supporting / long-tail keywords.
	 */
	public function __construct( string $pillar, array $supporting = [] ) {
		$this->pillar     = trim( $pillar );
		$this->supporting = array_values( array_filter( array_map( 'trim', $supporting ) ) );
		$this->cluster_id = $this->derive_id( $this->pillar );
	}

	/**
	 * Return all keywords in the cluster (pillar first, then supporting).
	 *
	 * @return string[]
	 */
	public function all_keywords(): array {
		return array_merge( [ $this->pillar ], $this->supporting );
	}

	/**
	 * Return a comma-separated keyword list suitable for embedding in a prompt.
	 */
	public function keywords_as_string(): string {
		return implode( ', ', $this->all_keywords() );
	}

	// -----------------------------------------------------------------------
	// Serialization helpers
	// -----------------------------------------------------------------------

	/**
	 * Convert to a plain associative array for storage in WordPress options.
	 *
	 * @return array{cluster_id: string, pillar: string, supporting: string[]}
	 */
	public function to_array(): array {
		return [
			'cluster_id' => $this->cluster_id,
			'pillar'     => $this->pillar,
			'supporting' => $this->supporting,
		];
	}

	/**
	 * Reconstruct a KeywordCluster from a previously serialised array.
	 *
	 * @param array $data Associative array as returned by {@see to_array()}.
	 */
	public static function from_array( array $data ): self {
		return new self(
			(string) ( $data['pillar'] ?? '' ),
			(array)  ( $data['supporting'] ?? [] ),
		);
	}

	// -----------------------------------------------------------------------
	// Private helpers
	// -----------------------------------------------------------------------

	private function derive_id( string $pillar ): string {
		return substr( sanitize_title( $pillar ), 0, 64 );
	}
}
