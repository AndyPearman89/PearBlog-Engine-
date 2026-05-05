<?php
/**
 * Security Command
 *
 * WP-CLI commands for security auditing and vulnerability scanning
 *
 * @package PearBlogEngine
 * @subpackage CLI
 */

namespace PearBlogEngine\CLI;

use PearBlogEngine\Security\SecurityAuditor;

class SecurityCommand {

	/**
	 * Run OWASP Top 10 security audit
	 *
	 * Performs comprehensive security audit against OWASP Top 10 2021 vulnerabilities.
	 * Generates detailed report with findings, severity levels, and remediation steps.
	 *
	 * ## OPTIONS
	 *
	 * [--format=<format>]
	 * : Output format (text, json, markdown)
	 * ---
	 * default: markdown
	 * options:
	 *   - text
	 *   - json
	 *   - markdown
	 * ---
	 *
	 * [--output=<file>]
	 * : Save report to file (default: SECURITY-AUDIT-REPORT-DETAILED.md)
	 *
	 * [--severity=<level>]
	 * : Filter by minimum severity level
	 * ---
	 * default: all
	 * options:
	 *   - all
	 *   - critical
	 *   - high
	 *   - medium
	 *   - low
	 * ---
	 *
	 * ## EXAMPLES
	 *
	 *     # Run full audit and generate markdown report
	 *     wp pearblog security audit
	 *
	 *     # Generate JSON report
	 *     wp pearblog security audit --format=json
	 *
	 *     # Save to custom file
	 *     wp pearblog security audit --output=my-audit.md
	 *
	 *     # Show only critical and high severity issues
	 *     wp pearblog security audit --severity=high
	 *
	 * @when after_wp_load
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function audit( $args, $assoc_args ): void {
		$format   = $assoc_args['format'] ?? 'markdown';
		$output   = $assoc_args['output'] ?? 'SECURITY-AUDIT-REPORT-DETAILED.md';
		$severity = $assoc_args['severity'] ?? 'all';

		\WP_CLI::log( '🔒 Starting OWASP Top 10 Security Audit...' );
		\WP_CLI::log( '' );

		// Initialize auditor
		$auditor = new SecurityAuditor();

		// Run full audit
		\WP_CLI::log( '⚡ Scanning codebase for vulnerabilities...' );
		$results = $auditor->run_full_audit();

		\WP_CLI::log( '✅ Scan complete!' );
		\WP_CLI::log( '' );

		// Display summary
		$summary = $results['summary'] ?? [];
		\WP_CLI::log( '📊 Summary:' );
		\WP_CLI::log( sprintf( '   Total Checks: %d', $summary['total_checks'] ?? 0 ) );
		\WP_CLI::log( sprintf( '   Passed: %d', $summary['passed'] ?? 0 ) );
		\WP_CLI::log( sprintf( '   Failed: %d', $summary['failed'] ?? 0 ) );
		\WP_CLI::log( sprintf( '   Warnings: %d', $summary['warnings'] ?? 0 ) );
		\WP_CLI::log( sprintf( '   Risk Score: %d/100', $summary['risk_score'] ?? 0 ) );
		\WP_CLI::log( sprintf( '   Status: %s', $summary['overall_status'] ?? 'UNKNOWN' ) );
		\WP_CLI::log( '' );

		// Generate report based on format
		$report = '';
		switch ( $format ) {
			case 'json':
				$report = $auditor->export_json();
				break;

			case 'text':
				$report = $this->generate_text_report( $results, $severity );
				break;

			case 'markdown':
			default:
				$report = $this->generate_markdown_report( $results, $severity );
				break;
		}

		// Save to file
		$output_path = ABSPATH . $output;
		file_put_contents( $output_path, $report );

		\WP_CLI::success( sprintf( 'Report saved to: %s', $output ) );

		// Show critical/high severity warnings
		$critical_count = 0;
		$high_count     = 0;

		foreach ( $results['checks'] ?? [] as $check ) {
			foreach ( $check['issues'] ?? [] as $issue ) {
				if ( 'CRITICAL' === $issue['severity'] ) {
					$critical_count++;
				} elseif ( 'HIGH' === $issue['severity'] ) {
					$high_count++;
				}
			}
		}

		if ( $critical_count > 0 || $high_count > 0 ) {
			\WP_CLI::warning( sprintf( '⚠️  Found %d CRITICAL and %d HIGH severity issues!', $critical_count, $high_count ) );
			\WP_CLI::log( 'Review the report and address critical issues before production deployment.' );
		}
	}

	/**
	 * Generate markdown format report
	 *
	 * @param array  $results  Audit results.
	 * @param string $severity Severity filter.
	 * @return string Markdown report
	 */
	private function generate_markdown_report( array $results, string $severity ): string {
		$report = "# 🔒 OWASP Top 10 Security Audit Report - Detailed\n\n";
		$report .= "**Platform:** PearBlog Engine v8.0.0\n";
		$report .= sprintf( "**Audit Date:** %s\n", date( 'Y-m-d H:i:s' ) );
		$report .= "**Auditor:** Security Auditor v1.0 (Automated)\n";
		$report .= "**Standard:** OWASP Top 10 2021\n\n";
		$report .= "---\n\n";

		// Executive Summary
		$report .= "## Executive Summary\n\n";
		$summary = $results['summary'] ?? [];
		$report .= sprintf( "**Overall Security Status:** %s\n\n", $this->get_status_emoji( $summary['overall_status'] ?? 'UNKNOWN' ) );

		$report .= "### Summary Metrics\n\n";
		$report .= "| Metric | Count | Status |\n";
		$report .= "|--------|-------|--------|\n";
		$report .= sprintf( "| **Total Checks** | %d | Complete |\n", $summary['total_checks'] ?? 0 );
		$report .= sprintf( "| **Passed** | %d | ✅ |\n", $summary['passed'] ?? 0 );
		$report .= sprintf( "| **Failed** | %d | %s |\n", $summary['failed'] ?? 0, ( $summary['failed'] ?? 0 ) > 0 ? '❌' : '✅' );
		$report .= sprintf( "| **Warnings** | %d | %s |\n", $summary['warnings'] ?? 0, ( $summary['warnings'] ?? 0 ) > 0 ? '⚠️' : '✅' );
		$report .= sprintf( "| **Total Vulnerabilities** | %d | %s |\n", $summary['total_vulnerabilities'] ?? 0, ( $summary['total_vulnerabilities'] ?? 0 ) > 0 ? '⚠️' : '✅' );
		$report .= "\n";
		$report .= sprintf( "**Risk Score:** %d/100 %s\n\n", $summary['risk_score'] ?? 0, $this->get_risk_emoji( $summary['risk_score'] ?? 0 ) );
		$report .= "---\n\n";

		// Detailed Findings
		$report .= "## Detailed Findings\n\n";

		$severity_levels = [
			'all'      => [ 'CRITICAL', 'HIGH', 'MEDIUM', 'LOW', 'INFO' ],
			'critical' => [ 'CRITICAL' ],
			'high'     => [ 'CRITICAL', 'HIGH' ],
			'medium'   => [ 'CRITICAL', 'HIGH', 'MEDIUM' ],
			'low'      => [ 'CRITICAL', 'HIGH', 'MEDIUM', 'LOW' ],
		];

		$filter_levels = $severity_levels[ $severity ] ?? $severity_levels['all'];

		foreach ( $results['checks'] ?? [] as $check ) {
			$report .= sprintf( "### %s: %s\n\n", $check['id'] ?? '', $check['name'] ?? '' );
			$report .= sprintf( "**Status:** %s %s\n", $this->get_status_emoji( $check['status'] ?? '' ), $check['status'] ?? 'UNKNOWN' );
			$report .= sprintf( "**Description:** %s\n\n", $check['description'] ?? '' );

			$issues = $check['issues'] ?? [];
			$filtered_issues = array_filter(
				$issues,
				function( $issue ) use ( $filter_levels ) {
					return in_array( $issue['severity'] ?? '', $filter_levels, true );
				}
			);

			if ( ! empty( $filtered_issues ) ) {
				$report .= "#### Issues Found:\n\n";

				foreach ( $filtered_issues as $issue ) {
					$severity_emoji = $this->get_severity_emoji( $issue['severity'] ?? '' );
					$report .= sprintf( "**%s %s**\n", $severity_emoji, $issue['severity'] ?? 'UNKNOWN' );
					$report .= sprintf( "- **File:** `%s`\n", $issue['file'] ?? 'Unknown' );

					if ( isset( $issue['line'] ) && $issue['line'] > 0 ) {
						$report .= sprintf( "- **Line:** %d\n", $issue['line'] );
					}

					$report .= sprintf( "- **Finding:** %s\n", $issue['finding'] ?? 'No description' );

					if ( isset( $issue['code'] ) ) {
						$report .= sprintf( "- **Code:**\n  ```php\n  %s\n  ```\n", $issue['code'] );
					}

					$report .= sprintf( "- **Recommendation:** %s\n\n", $issue['recommendation'] ?? 'No recommendation' );
				}
			} else {
				$report .= "✅ **No issues found**\n\n";
			}

			$report .= "---\n\n";
		}

		// Remediation Plan
		$report .= "## 🛠️ Remediation Plan\n\n";
		$report .= "### Priority 1: Critical Issues\n\n";
		$critical_found = false;

		foreach ( $results['checks'] ?? [] as $check ) {
			foreach ( $check['issues'] ?? [] as $issue ) {
				if ( 'CRITICAL' === $issue['severity'] ) {
					$critical_found = true;
					$report .= sprintf( "- [ ] **%s** in `%s`: %s\n", $check['name'] ?? '', $issue['file'] ?? '', $issue['finding'] ?? '' );
				}
			}
		}

		if ( ! $critical_found ) {
			$report .= "✅ No critical issues found!\n";
		}

		$report .= "\n### Priority 2: High Severity Issues\n\n";
		$high_found = false;

		foreach ( $results['checks'] ?? [] as $check ) {
			foreach ( $check['issues'] ?? [] as $issue ) {
				if ( 'HIGH' === $issue['severity'] ) {
					$high_found = true;
					$report .= sprintf( "- [ ] **%s** in `%s`: %s\n", $check['name'] ?? '', $issue['file'] ?? '', $issue['finding'] ?? '' );
				}
			}
		}

		if ( ! $high_found ) {
			$report .= "✅ No high severity issues found!\n";
		}

		$report .= "\n---\n\n";

		// Best Practices
		$report .= "## 📋 Security Best Practices\n\n";
		$report .= "### Recommended Actions\n\n";
		$report .= "1. **Regular Audits:** Run security audits quarterly or after major updates\n";
		$report .= "2. **Dependency Updates:** Keep all dependencies and WordPress core up to date\n";
		$report .= "3. **Security Headers:** Implement security headers (CSP, X-Frame-Options, etc.)\n";
		$report .= "4. **Input Validation:** Always validate and sanitize user input\n";
		$report .= "5. **Output Escaping:** Escape all output appropriately\n";
		$report .= "6. **Authentication:** Use strong authentication and authorization checks\n";
		$report .= "7. **Logging:** Implement comprehensive security event logging\n";
		$report .= "8. **Monitoring:** Set up real-time security monitoring and alerts\n\n";

		$report .= "### Security Resources\n\n";
		$report .= "- [OWASP Top 10](https://owasp.org/Top10/)\n";
		$report .= "- [WordPress Security](https://wordpress.org/support/article/hardening-wordpress/)\n";
		$report .= "- [PHP Security Best Practices](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)\n\n";

		$report .= "---\n\n";
		$report .= sprintf( "**Report Generated:** %s\n", date( 'Y-m-d H:i:s' ) );
		$report .= "**Next Audit Due:** " . date( 'Y-m-d', strtotime( '+3 months' ) ) . " (Quarterly)\n\n";

		return $report;
	}

	/**
	 * Generate text format report
	 *
	 * @param array  $results  Audit results.
	 * @param string $severity Severity filter.
	 * @return string Text report
	 */
	private function generate_text_report( array $results, string $severity ): string {
		$report = "OWASP TOP 10 SECURITY AUDIT REPORT\n";
		$report .= "==================================\n\n";
		$report .= sprintf( "Audit Date: %s\n", date( 'Y-m-d H:i:s' ) );
		$report .= "Platform: PearBlog Engine v8.0.0\n";
		$report .= "Standard: OWASP Top 10 2021\n\n";

		$summary = $results['summary'] ?? [];
		$report .= "SUMMARY\n";
		$report .= "-------\n";
		$report .= sprintf( "Total Checks: %d\n", $summary['total_checks'] ?? 0 );
		$report .= sprintf( "Passed: %d\n", $summary['passed'] ?? 0 );
		$report .= sprintf( "Failed: %d\n", $summary['failed'] ?? 0 );
		$report .= sprintf( "Warnings: %d\n", $summary['warnings'] ?? 0 );
		$report .= sprintf( "Risk Score: %d/100\n", $summary['risk_score'] ?? 0 );
		$report .= sprintf( "Status: %s\n\n", $summary['overall_status'] ?? 'UNKNOWN' );

		foreach ( $results['checks'] ?? [] as $check ) {
			$report .= sprintf( "\n%s: %s\n", $check['id'] ?? '', $check['name'] ?? '' );
			$report .= str_repeat( '-', 60 ) . "\n";
			$report .= sprintf( "Status: %s\n", $check['status'] ?? 'UNKNOWN' );
			$report .= sprintf( "Description: %s\n\n", $check['description'] ?? '' );

			$issues = $check['issues'] ?? [];
			if ( ! empty( $issues ) ) {
				$report .= "Issues:\n";
				foreach ( $issues as $issue ) {
					$report .= sprintf( "  [%s] %s\n", $issue['severity'] ?? '', $issue['finding'] ?? '' );
					$report .= sprintf( "  File: %s\n", $issue['file'] ?? '' );
					$report .= sprintf( "  Recommendation: %s\n\n", $issue['recommendation'] ?? '' );
				}
			} else {
				$report .= "No issues found.\n";
			}
		}

		return $report;
	}

	/**
	 * Get status emoji
	 *
	 * @param string $status Status string.
	 * @return string Emoji
	 */
	private function get_status_emoji( string $status ): string {
		$emojis = [
			'PASS'     => '✅ PASS',
			'WARNING'  => '⚠️ WARNING',
			'CRITICAL' => '❌ CRITICAL',
			'FAIL'     => '❌ FAIL',
		];

		return $emojis[ $status ] ?? '❓ UNKNOWN';
	}

	/**
	 * Get severity emoji
	 *
	 * @param string $severity Severity level.
	 * @return string Emoji
	 */
	private function get_severity_emoji( string $severity ): string {
		$emojis = [
			'CRITICAL' => '🔴',
			'HIGH'     => '🟠',
			'MEDIUM'   => '🟡',
			'LOW'      => '🟢',
			'INFO'     => 'ℹ️',
		];

		return $emojis[ $severity ] ?? '❓';
	}

	/**
	 * Get risk emoji
	 *
	 * @param int $score Risk score (0-100).
	 * @return string Emoji
	 */
	private function get_risk_emoji( int $score ): string {
		if ( $score >= 50 ) {
			return '🔴 (High Risk)';
		} elseif ( $score >= 20 ) {
			return '🟠 (Medium Risk)';
		} elseif ( $score >= 5 ) {
			return '🟡 (Low Risk)';
		}
		return '🟢 (Very Low Risk)';
	}

	/**
	 * Quick security scan (simplified)
	 *
	 * Performs a quick security scan and displays results in terminal.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pearblog security scan
	 *
	 * @when after_wp_load
	 */
	public function scan(): void {
		\WP_CLI::log( '🔍 Running quick security scan...' );
		\WP_CLI::log( '' );

		$auditor = new SecurityAuditor();
		$results = $auditor->run_full_audit();

		$summary = $results['summary'] ?? [];

		// Display results
		$table_data = [
			[
				'Metric'    => 'Total Checks',
				'Value'     => $summary['total_checks'] ?? 0,
				'Status'    => '✓',
			],
			[
				'Metric'    => 'Passed',
				'Value'     => $summary['passed'] ?? 0,
				'Status'    => '✓',
			],
			[
				'Metric'    => 'Failed',
				'Value'     => $summary['failed'] ?? 0,
				'Status'    => ( $summary['failed'] ?? 0 ) > 0 ? '✗' : '✓',
			],
			[
				'Metric'    => 'Warnings',
				'Value'     => $summary['warnings'] ?? 0,
				'Status'    => ( $summary['warnings'] ?? 0 ) > 0 ? '⚠' : '✓',
			],
			[
				'Metric'    => 'Risk Score',
				'Value'     => sprintf( '%d/100', $summary['risk_score'] ?? 0 ),
				'Status'    => ( $summary['risk_score'] ?? 0 ) < 20 ? '✓' : '⚠',
			],
		];

		\WP_CLI\Utils\format_items( 'table', $table_data, [ 'Metric', 'Value', 'Status' ] );

		\WP_CLI::log( '' );
		\WP_CLI::log( sprintf( 'Overall Status: %s', $summary['overall_status'] ?? 'UNKNOWN' ) );

		if ( ( $summary['failed'] ?? 0 ) > 0 || ( $summary['warnings'] ?? 0 ) > 0 ) {
			\WP_CLI::log( '' );
			\WP_CLI::warning( 'Run "wp pearblog security audit" for detailed report' );
		} else {
			\WP_CLI::success( 'No security issues found!' );
		}
	}
}
