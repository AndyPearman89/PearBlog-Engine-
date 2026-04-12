<?php
/**
 * Exception thrown when an AI provider returns a rate-limit response.
 *
 * Used by AIClient to trigger exponential-backoff retries.
 *
 * @package PearBlogEngine\AI
 */

declare(strict_types=1);

namespace PearBlogEngine\AI;

/**
 * Signals an HTTP 429 (or provider-equivalent) rate-limit response.
 */
class RateLimitException extends \RuntimeException {}
