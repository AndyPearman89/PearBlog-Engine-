<?php
/**
 * Security Auditor - OWASP Top 10 Automated Scanner
 *
 * Performs automated security audits against OWASP Top 10 vulnerabilities:
 *  A01:2021 – Broken Access Control
 *  A02:2021 – Cryptographic Failures
 *  A03:2021 – Injection
 *  A04:2021 – Insecure Design
 *  A05:2021 – Security Misconfiguration
 *  A06:2021 – Vulnerable and Outdated Components
 *  A07:2021 – Identification and Authentication Failures
 *  A08:2021 – Software and Data Integrity Failures
 *  A09:2021 – Security Logging and Monitoring Failures
 *  A10:2021 – Server-Side Request Forgery (SSRF)
 *
 * @package PearBlogEngine\Security
 * @since 7.1.0
 */

declare(strict_types=1);

namespace PearBlogEngine\Security;

/**
 * Automated security auditor for PearBlog Engine
 */
class SecurityAuditor {

	/** @var array<string, mixed> Audit results */
	private array $results = [];

	/** @var int Total vulnerabilities found */
	private int $vulnerability_count = 0;

	/** @var string[] Files to scan */
	private array $scan_paths = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->scan_paths = [
			PEARBLOG_ENGINE_DIR . '/src/API/',
			PEARBLOG_ENGINE_DIR . '/src/Admin/',
			PEARBLOG_ENGINE_DIR . '/src/Monitoring/',
		];
	}

	/**
	 * Run full security audit
	 *
	 * @return array<string, mixed> Audit results
	 */
	public function run_full_audit(): array {
		$this->results = [
			'timestamp'     => current_time( 'mysql' ),
			'auditor'       => 'PearBlog Security Auditor v1.0',
			'owasp_version' => 'OWASP Top 10 2021',
			'checks'        => [],
			'summary'       => [],
		];

		// Run all OWASP Top 10 checks
		$this->check_a01_broken_access_control();
		$this->check_a02_cryptographic_failures();
		$this->check_a03_injection();
		$this->check_a04_insecure_design();
		$this->check_a05_security_misconfiguration();
		$this->check_a06_vulnerable_components();
		$this->check_a07_auth_failures();
		$this->check_a08_integrity_failures();
		$this->check_a09_logging_failures();
		$this->check_a10_ssrf();

		// Generate summary
		$this->generate_summary();

		return $this->results;
	}

	/**
	 * A01:2021 – Broken Access Control
	 * Check for missing authorization, capability checks
	 */
	private function check_a01_broken_access_control(): void {
		$issues = [];

		// Scan REST endpoints for permission callbacks
		$api_files = $this->get_php_files( $this->scan_paths[0] ?? '' );

		foreach ( $api_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for register_rest_route without permission_callback
			if ( preg_match_all( '/register_rest_route\s*\(/', $content, $matches ) ) {
				$route_count = count( $matches[0] );

				// Check if permission_callback is present
				$permission_count = substr_count( $content, 'permission_callback' );

				if ( $permission_count < $route_count ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'HIGH',
						'finding'  => 'REST route registered without permission_callback',
						'line'     => $this->find_line_number( $content, 'register_rest_route' ),
						'recommendation' => 'Add permission_callback to all REST routes',
					];
					$this->vulnerability_count++;
				}
			}

			// Check for current_user_can() usage
			if ( false === strpos( $content, 'current_user_can' ) && false !== strpos( $content, 'class ' ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'MEDIUM',
					'finding'  => 'No capability checks found in controller',
					'recommendation' => 'Add current_user_can() checks for sensitive operations',
				];
				$this->vulnerability_count++;
			}
		}

		// Check Admin pages for capability checks
		$admin_files = $this->get_php_files( $this->scan_paths[1] ?? '' );

		foreach ( $admin_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for render methods without capability checks
			if ( preg_match( '/public\s+(?:static\s+)?function\s+render\s*\(/', $content ) ) {
				if ( false === strpos( $content, 'current_user_can' ) && false === strpos( $content, 'manage_options' ) ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'HIGH',
						'finding'  => 'Admin render method without capability check',
						'line'     => $this->find_line_number( $content, 'function render' ),
						'recommendation' => 'Add current_user_can("manage_options") check at start of render method',
					];
					$this->vulnerability_count++;
				}
			}
		}

		$this->results['checks']['A01_Broken_Access_Control'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'CRITICAL',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A02:2021 – Cryptographic Failures
	 * Check for weak encryption, exposed secrets
	 */
	private function check_a02_cryptographic_failures(): void {
		$issues = [];

		$all_files = [];
		foreach ( $this->scan_paths as $path ) {
			$all_files = array_merge( $all_files, $this->get_php_files( $path ) );
		}

		foreach ( $all_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for weak hashing algorithms
			if ( preg_match( '/\b(md5|sha1)\s*\(/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'HIGH',
					'finding'  => 'Weak hashing algorithm detected (MD5/SHA1)',
					'line'     => $this->find_line_number( $content, 'md5|sha1' ),
					'recommendation' => 'Use password_hash() or wp_hash_password() for passwords, hash("sha256") for data',
				];
				$this->vulnerability_count++;
			}

			// Check for hardcoded API keys or passwords
			if ( preg_match( '/(api[_-]?key|password|secret)\s*=\s*["\'][^"\']{20,}["\']/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'Possible hardcoded credential detected',
					'recommendation' => 'Move credentials to WordPress options or environment variables',
				];
				$this->vulnerability_count++;
			}

			// Check for unsafe $_GET['api_key'] usage
			if ( preg_match( '/\$_GET\s*\[\s*["\'](?:api[-_]?key|secret|password)/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'HIGH',
					'finding'  => 'Sensitive data passed via GET parameter',
					'recommendation' => 'Use POST, headers, or WordPress options for sensitive data',
				];
				$this->vulnerability_count++;
			}
		}

		$this->results['checks']['A02_Cryptographic_Failures'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'CRITICAL',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A03:2021 – Injection
	 * Check for SQL injection, XSS, command injection
	 */
	private function check_a03_injection(): void {
		$issues = [];

		$all_files = [];
		foreach ( $this->scan_paths as $path ) {
			$all_files = array_merge( $all_files, $this->get_php_files( $path ) );
		}

		foreach ( $all_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for direct SQL queries without prepare()
			if ( preg_match( '/\$wpdb->query\s*\(\s*["\']/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'Direct SQL query without prepared statement',
					'line'     => $this->find_line_number( $content, '$wpdb->query' ),
					'recommendation' => 'Use $wpdb->prepare() for all dynamic SQL queries',
				];
				$this->vulnerability_count++;
			}

			// Check for $_GET/$_POST used directly in output
			if ( preg_match( '/echo\s+\$_(?:GET|POST|REQUEST)\[/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'HIGH',
					'finding'  => 'User input echoed without sanitization (XSS risk)',
					'line'     => $this->find_line_number( $content, 'echo $_' ),
					'recommendation' => 'Use esc_html(), esc_attr(), or esc_url() for all output',
				];
				$this->vulnerability_count++;
			}

			// Check for exec/shell_exec/system with user input
			if ( preg_match( '/\b(exec|shell_exec|system|passthru)\s*\(.*\$_(?:GET|POST|REQUEST)/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'Command injection risk - user input in shell command',
					'recommendation' => 'Never pass user input to shell commands; use WordPress functions instead',
				];
				$this->vulnerability_count++;
			}

			// Check for unescaped output in admin pages
			if ( preg_match( '/echo\s+\$[a-zA-Z_]/', $content ) && false === strpos( $content, 'esc_html' ) ) {
				// Count echo statements
				$echo_count = substr_count( $content, 'echo ' );
				$esc_count = substr_count( $content, 'esc_html' ) + substr_count( $content, 'esc_attr' );

				if ( $echo_count > $esc_count + 5 ) { // Allow some tolerance for static strings
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'MEDIUM',
						'finding'  => 'Multiple echo statements without escaping functions',
						'recommendation' => 'Ensure all dynamic output uses esc_html(), esc_attr(), or esc_url()',
					];
					$this->vulnerability_count++;
				}
			}
		}

		$this->results['checks']['A03_Injection'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'CRITICAL',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A04:2021 – Insecure Design
	 * Check for missing security patterns
	 */
	private function check_a04_insecure_design(): void {
		$issues = [];

		// Check for CSRF protection in forms
		$admin_files = $this->get_php_files( $this->scan_paths[1] ?? '' );

		foreach ( $admin_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for forms without nonce fields
			if ( preg_match( '/<form[^>]*method=["\']post["\']/', $content ) ) {
				if ( false === strpos( $content, 'wp_nonce_field' ) && false === strpos( $content, 'wp_create_nonce' ) ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'HIGH',
						'finding'  => 'POST form without CSRF protection (nonce)',
						'line'     => $this->find_line_number( $content, '<form' ),
						'recommendation' => 'Add wp_nonce_field() to all forms',
					];
					$this->vulnerability_count++;
				}
			}
		}

		// Check for POST handlers without nonce verification
		foreach ( $admin_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			if ( preg_match( '/function\s+handle_[a-z_]+\s*\(/', $content ) ) {
				if ( false === strpos( $content, 'check_admin_referer' ) && false === strpos( $content, 'wp_verify_nonce' ) ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'HIGH',
						'finding'  => 'POST handler without nonce verification',
						'recommendation' => 'Add check_admin_referer() or wp_verify_nonce() at start of handler',
					];
					$this->vulnerability_count++;
				}
			}
		}

		$this->results['checks']['A04_Insecure_Design'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'HIGH',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A05:2021 – Security Misconfiguration
	 * Check for security settings
	 */
	private function check_a05_security_misconfiguration(): void {
		$issues = [];

		// Check if WP_DEBUG is enabled in production (if we can determine)
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG === true ) {
			$issues[] = [
				'file'     => 'wp-config.php',
				'severity' => 'MEDIUM',
				'finding'  => 'WP_DEBUG is enabled',
				'recommendation' => 'Disable WP_DEBUG in production environments',
			];
		}

		// Check for exposed error messages
		$all_files = [];
		foreach ( $this->scan_paths as $path ) {
			$all_files = array_merge( $all_files, $this->get_php_files( $path ) );
		}

		foreach ( $all_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for error_reporting() usage
			if ( preg_match( '/error_reporting\s*\(\s*E_ALL/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'LOW',
					'finding'  => 'Error reporting configured in code',
					'recommendation' => 'Configure error reporting in wp-config.php only',
				];
			}

			// Check for phpinfo() calls
			if ( preg_match( '/\bphpinfo\s*\(/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'HIGH',
					'finding'  => 'phpinfo() call found - information disclosure risk',
					'recommendation' => 'Remove phpinfo() calls from production code',
				];
				$this->vulnerability_count++;
			}
		}

		$this->results['checks']['A05_Security_Misconfiguration'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'MEDIUM',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A06:2021 – Vulnerable and Outdated Components
	 * Check composer.json dependencies
	 */
	private function check_a06_vulnerable_components(): void {
		$issues = [];

		$composer_file = PEARBLOG_ENGINE_DIR . '/composer.json';

		if ( file_exists( $composer_file ) ) {
			$composer = json_decode( file_get_contents( $composer_file ), true );

			if ( isset( $composer['require'] ) ) {
				// Check PHP version requirement
				$php_version = $composer['require']['php'] ?? '';
				if ( version_compare( PHP_VERSION, '8.1.0', '<' ) ) {
					$issues[] = [
						'file'     => 'composer.json',
						'severity' => 'MEDIUM',
						'finding'  => 'PHP version below 8.1 (current: ' . PHP_VERSION . ')',
						'recommendation' => 'Upgrade to PHP 8.1 or higher for security patches',
					];
				}

				// Note: Full dependency scanning requires external tools like Snyk or Dependabot
				$issues[] = [
					'file'     => 'composer.json',
					'severity' => 'INFO',
					'finding'  => 'Dependency scanning recommended',
					'recommendation' => 'Use Snyk, Dependabot, or composer audit to scan for vulnerable dependencies',
				];
			}
		}

		$this->results['checks']['A06_Vulnerable_Components'] = [
			'status' => 'INFO',
			'risk_level' => 'MEDIUM',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A07:2021 – Identification and Authentication Failures
	 * Check auth implementation
	 */
	private function check_a07_auth_failures(): void {
		$issues = [];

		$api_files = $this->get_php_files( $this->scan_paths[0] ?? '' );

		foreach ( $api_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for Bearer token auth without proper validation
			if ( preg_match( '/Authorization.*Bearer/', $content ) ) {
				if ( false === strpos( $content, 'hash_equals' ) ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'MEDIUM',
						'finding'  => 'Token comparison without timing-safe function',
						'recommendation' => 'Use hash_equals() for token comparison to prevent timing attacks',
					];
					$this->vulnerability_count++;
				}
			}

			// Check for password handling
			if ( preg_match( '/password/', $content ) ) {
				if ( false === strpos( $content, 'password_hash' ) && false === strpos( $content, 'wp_hash_password' ) ) {
					$issues[] = [
						'file'     => basename( $file ),
						'severity' => 'INFO',
						'finding'  => 'Password handling detected - verify using secure functions',
						'recommendation' => 'Ensure using password_hash() or wp_hash_password()',
					];
				}
			}
		}

		$this->results['checks']['A07_Auth_Failures'] = [
			'status' => empty( $issues ) ? 'PASS' : 'WARNING',
			'risk_level' => 'HIGH',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A08:2021 – Software and Data Integrity Failures
	 * Check for unsigned updates, insecure deserialization
	 */
	private function check_a08_integrity_failures(): void {
		$issues = [];

		$all_files = [];
		foreach ( $this->scan_paths as $path ) {
			$all_files = array_merge( $all_files, $this->get_php_files( $path ) );
		}

		foreach ( $all_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for unserialize() with user input
			if ( preg_match( '/unserialize\s*\(\s*\$_/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'Insecure deserialization - unserialize() with user input',
					'recommendation' => 'Use JSON or verified data sources only; never unserialize user input',
				];
				$this->vulnerability_count++;
			}

			// Check for eval() usage
			if ( preg_match( '/\beval\s*\(/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'eval() usage detected - code injection risk',
					'recommendation' => 'Remove eval() and use alternative approaches',
				];
				$this->vulnerability_count++;
			}
		}

		$this->results['checks']['A08_Integrity_Failures'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'CRITICAL',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A09:2021 – Security Logging and Monitoring Failures
	 * Check logging implementation
	 */
	private function check_a09_logging_failures(): void {
		$issues = [];

		// Check if Logger is used for sensitive operations
		$api_files = $this->get_php_files( $this->scan_paths[0] ?? '' );

		$files_with_logging = 0;
		foreach ( $api_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			if ( preg_match( '/Logger::(error|warning|info)/', $content ) ) {
				$files_with_logging++;
			}
		}

		if ( count( $api_files ) > 0 && $files_with_logging < count( $api_files ) / 2 ) {
			$issues[] = [
				'file'     => 'API Controllers',
				'severity' => 'MEDIUM',
				'finding'  => 'Insufficient logging in API controllers',
				'recommendation' => 'Add Logger calls for authentication failures, errors, and sensitive operations',
			];
		}

		$this->results['checks']['A09_Logging_Failures'] = [
			'status' => empty( $issues ) ? 'PASS' : 'WARNING',
			'risk_level' => 'MEDIUM',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * A10:2021 – Server-Side Request Forgery (SSRF)
	 * Check for unvalidated URL requests
	 */
	private function check_a10_ssrf(): void {
		$issues = [];

		$all_files = [];
		foreach ( $this->scan_paths as $path ) {
			$all_files = array_merge( $all_files, $this->get_php_files( $path ) );
		}

		foreach ( $all_files as $file ) {
			$content = file_get_contents( $file );
			if ( false === $content ) {
				continue;
			}

			// Check for wp_remote_get/post with user-supplied URLs
			if ( preg_match( '/wp_remote_(?:get|post)\s*\(\s*\$_(?:GET|POST|REQUEST)/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'HIGH',
					'finding'  => 'SSRF risk - HTTP request with user-supplied URL',
					'line'     => $this->find_line_number( $content, 'wp_remote_' ),
					'recommendation' => 'Validate and whitelist URLs before making requests',
				];
				$this->vulnerability_count++;
			}

			// Check for file_get_contents with user input
			if ( preg_match( '/file_get_contents\s*\(\s*\$_/', $content ) ) {
				$issues[] = [
					'file'     => basename( $file ),
					'severity' => 'CRITICAL',
					'finding'  => 'SSRF/LFI risk - file_get_contents with user input',
					'recommendation' => 'Use wp_remote_get() with URL validation, or whitelist file paths',
				];
				$this->vulnerability_count++;
			}
		}

		$this->results['checks']['A10_SSRF'] = [
			'status' => empty( $issues ) ? 'PASS' : 'FAIL',
			'risk_level' => 'HIGH',
			'issues_found' => count( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * Generate audit summary
	 */
	private function generate_summary(): void {
		$total_checks = count( $this->results['checks'] );
		$passed = 0;
		$failed = 0;
		$warnings = 0;

		foreach ( $this->results['checks'] as $check ) {
			if ( 'PASS' === $check['status'] ) {
				$passed++;
			} elseif ( 'FAIL' === $check['status'] ) {
				$failed++;
			} elseif ( 'WARNING' === $check['status'] ) {
				$warnings++;
			}
		}

		$this->results['summary'] = [
			'total_checks' => $total_checks,
			'passed' => $passed,
			'failed' => $failed,
			'warnings' => $warnings,
			'info' => $total_checks - $passed - $failed - $warnings,
			'total_vulnerabilities' => $this->vulnerability_count,
			'risk_score' => $this->calculate_risk_score(),
			'overall_status' => $failed > 0 ? 'CRITICAL' : ( $warnings > 0 ? 'WARNING' : 'PASS' ),
		];
	}

	/**
	 * Calculate overall risk score (0-100)
	 *
	 * @return int Risk score
	 */
	private function calculate_risk_score(): int {
		$critical = 0;
		$high = 0;
		$medium = 0;

		foreach ( $this->results['checks'] as $check ) {
			foreach ( $check['issues'] ?? [] as $issue ) {
				if ( 'CRITICAL' === $issue['severity'] ) {
					$critical++;
				} elseif ( 'HIGH' === $issue['severity'] ) {
					$high++;
				} elseif ( 'MEDIUM' === $issue['severity'] ) {
					$medium++;
				}
			}
		}

		// Calculate weighted score
		$score = ( $critical * 10 ) + ( $high * 5 ) + ( $medium * 2 );

		return min( 100, $score );
	}

	/**
	 * Get all PHP files in directory
	 *
	 * @param string $dir Directory path.
	 * @return string[] Array of file paths
	 */
	private function get_php_files( string $dir ): array {
		if ( ! is_dir( $dir ) ) {
			return [];
		}

		$files = [];
		$iterator = new \RecursiveIteratorIterator(
			new \RecursiveDirectoryIterator( $dir )
		);

		foreach ( $iterator as $file ) {
			if ( $file->isFile() && 'php' === $file->getExtension() ) {
				$files[] = $file->getPathname();
			}
		}

		return $files;
	}

	/**
	 * Find line number of pattern in content
	 *
	 * @param string $content File content.
	 * @param string $pattern Search pattern.
	 * @return int Line number (1-indexed)
	 */
	private function find_line_number( string $content, string $pattern ): int {
		$lines = explode( "\n", $content );
		foreach ( $lines as $num => $line ) {
			if ( preg_match( '/' . preg_quote( $pattern, '/' ) . '/', $line ) ) {
				return $num + 1;
			}
		}
		return 0;
	}

	/**
	 * Export results as JSON
	 *
	 * @return string JSON encoded results
	 */
	public function export_json(): string {
		return wp_json_encode( $this->results, JSON_PRETTY_PRINT );
	}

	/**
	 * Get results array
	 *
	 * @return array<string, mixed>
	 */
	public function get_results(): array {
		return $this->results;
	}
}
