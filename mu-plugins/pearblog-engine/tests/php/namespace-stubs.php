<?php
/**
 * Namespace-level function stubs for unit tests.
 *
 * Defines no-op overrides for PHP built-in functions (setcookie, etc.) in
 * the PearBlogEngine sub-namespaces.  Because PHP resolves unqualified
 * function calls by checking the current namespace first, these stubs
 * intercept calls made by production code without a leading backslash,
 * preventing "headers already sent" errors during PHPUnit runs.
 *
 * This file must NOT have declare(strict_types=1) so that it can freely
 * declare named namespaces.
 */

namespace PearBlogEngine\Monetization {
	if ( ! function_exists( 'PearBlogEngine\\Monetization\\setcookie' ) ) {
		function setcookie( string $name, string $value = '', ...$args ): bool {
			return true; // no-op in unit tests
		}
	}
}
