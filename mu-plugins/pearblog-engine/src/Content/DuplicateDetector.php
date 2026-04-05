<?php
/**
 * Duplicate detector – blocks publication of articles too similar to existing content.
 *
 * Uses a bag-of-words TF-IDF cosine similarity comparison:
 *  1. Strip HTML and tokenise the candidate content.
 *  2. Build a TF vector for the candidate.
 *  3. Load TF vectors for all published posts (cached as post meta).
 *  4. Compute cosine similarity.
 *  5. Return the highest-similarity match.
 *
 * @package PearBlogEngine\Content
 */

declare(strict_types=1);

namespace PearBlogEngine\Content;

/**
 * Detects near-duplicate article content using TF-IDF cosine similarity.
 */
class DuplicateDetector {

	/** Similarity threshold above which content is considered a duplicate. */
	public const DEFAULT_THRESHOLD = 0.80;

	/** Meta key for the stored TF vector of a post. */
	private const META_TF_VECTOR = '_pearblog_tf_vector';

	/** Stop words to exclude from TF computation (English + Polish basics). */
	private const STOP_WORDS = [
		'the','and','for','are','was','that','this','with','have','from','not',
		'but','they','will','one','all','can','her','his','has','its','our',
		'we','you','he','she','it','be','an','or','in','on','at','to','is',
		'do','by','so','of','as','up','if','go',
		// Polish
		'się','jak','nie','tak','czy','ale','więc','to','jest','są','być','na',
		'do','ze','się','po','za','przed','przez','przy','z','w','i','a',
	];

	/**
	 * Check whether $content is a near-duplicate of any existing published post.
	 *
	 * @param string $content     Candidate content (HTML or Markdown).
	 * @param int    $exclude_id  Post ID to exclude (the post being updated, if any).
	 * @param float  $threshold   Similarity score above which a match is flagged.
	 * @return array{is_duplicate: bool, similarity: float, matched_post_id: int|null, matched_title: string|null}
	 */
	public function check( string $content, int $exclude_id = 0, float $threshold = self::DEFAULT_THRESHOLD ): array {
		$candidate_tf  = $this->build_tf_vector( $content );

		if ( empty( $candidate_tf ) ) {
			return $this->no_match();
		}

		$post_ids = get_posts( [
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'post__not_in'   => $exclude_id > 0 ? [ $exclude_id ] : [],
		] );

		$best_similarity   = 0.0;
		$best_post_id      = null;

		foreach ( $post_ids as $pid ) {
			$stored_tf = get_post_meta( $pid, self::META_TF_VECTOR, true );

			if ( ! is_array( $stored_tf ) || empty( $stored_tf ) ) {
				// Build and cache TF vector for this post.
				$post      = get_post( $pid );
				$stored_tf = $post ? $this->build_tf_vector( $post->post_content ) : [];
				if ( ! empty( $stored_tf ) ) {
					update_post_meta( $pid, self::META_TF_VECTOR, $stored_tf );
				}
			}

			if ( empty( $stored_tf ) ) {
				continue;
			}

			$sim = $this->cosine_similarity( $candidate_tf, $stored_tf );

			if ( $sim > $best_similarity ) {
				$best_similarity = $sim;
				$best_post_id    = $pid;
			}

			if ( $best_similarity >= $threshold ) {
				break; // No need to keep scanning once we hit the threshold.
			}
		}

		$is_dup = $best_similarity >= $threshold;

		return [
			'is_duplicate'    => $is_dup,
			'similarity'      => round( $best_similarity, 4 ),
			'matched_post_id' => $best_post_id,
			'matched_title'   => $best_post_id ? get_the_title( $best_post_id ) : null,
		];
	}

	/**
	 * Pre-compute and store the TF vector for a post (call after publish).
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Post content (HTML).
	 */
	public function index( int $post_id, string $content ): void {
		$tf = $this->build_tf_vector( $content );
		if ( ! empty( $tf ) ) {
			update_post_meta( $post_id, self::META_TF_VECTOR, $tf );
		}
	}

	// -----------------------------------------------------------------------
	// Private implementation
	// -----------------------------------------------------------------------

	/**
	 * Tokenise content and compute normalised term-frequency vector.
	 *
	 * @return array<string, float> word → TF (0–1)
	 */
	private function build_tf_vector( string $content ): array {
		$text  = mb_strtolower( wp_strip_all_tags( $content ) );
		// Extract word tokens (minimum 3 chars, letters only).
		preg_match_all( '/\b[a-ząćęłńóśźż]{3,}\b/u', $text, $matches );
		$tokens = $matches[0] ?? [];

		if ( empty( $tokens ) ) {
			return [];
		}

		// Remove stop words.
		$tokens = array_filter( $tokens, fn( $t ) => ! in_array( $t, self::STOP_WORDS, true ) );

		$total = count( $tokens );
		if ( 0 === $total ) {
			return [];
		}

		$counts = array_count_values( $tokens );
		$tf     = [];
		foreach ( $counts as $word => $count ) {
			$tf[ $word ] = $count / $total;
		}

		return $tf;
	}

	/**
	 * Cosine similarity between two TF vectors.
	 *
	 * @param array<string, float> $a
	 * @param array<string, float> $b
	 * @return float 0.0 (orthogonal) – 1.0 (identical)
	 */
	private function cosine_similarity( array $a, array $b ): float {
		$dot      = 0.0;
		$mag_a    = 0.0;
		$mag_b    = 0.0;

		foreach ( $a as $word => $weight ) {
			$dot   += $weight * ( $b[ $word ] ?? 0.0 );
			$mag_a += $weight ** 2;
		}

		foreach ( $b as $weight ) {
			$mag_b += $weight ** 2;
		}

		$denominator = sqrt( $mag_a ) * sqrt( $mag_b );
		if ( $denominator < 1e-10 ) {
			return 0.0;
		}

		return $dot / $denominator;
	}

	private function no_match(): array {
		return [
			'is_duplicate'    => false,
			'similarity'      => 0.0,
			'matched_post_id' => null,
			'matched_title'   => null,
		];
	}
}
