#!/usr/bin/env php
<?php
/**
 * Security Audit Runner
 *
 * Generates comprehensive OWASP Top 10 security audit report
 *
 * Usage: php scripts/run-security-audit.php
 */

// Define constants
define( 'ABSPATH', dirname( __DIR__ ) . '/' );
define( 'PEARBLOG_ENGINE_DIR', ABSPATH . 'mu-plugins/pearblog-engine' );

// Stub WordPress functions
function current_time( $type ) {
	return date( 'Y-m-d H:i:s' );
}

function wp_json_encode( $data, $options = 0 ) {
	return json_encode( $data, $options );
}

// Load SecurityAuditor
require_once PEARBLOG_ENGINE_DIR . '/src/Security/SecurityAuditor.php';

// Run audit
echo "🔒 Starting OWASP Top 10 Security Audit...\n";
echo "=========================================\n\n";

$auditor = new \PearBlogEngine\Security\SecurityAuditor();
$results = $auditor->run_full_audit();

// Display summary
$summary = $results['summary'] ?? [];
echo "📊 Summary:\n";
echo sprintf( "   Total Checks: %d\n", $summary['total_checks'] ?? 0 );
echo sprintf( "   Passed: %d\n", $summary['passed'] ?? 0 );
echo sprintf( "   Failed: %d\n", $summary['failed'] ?? 0 );
echo sprintf( "   Warnings: %d\n", $summary['warnings'] ?? 0 );
echo sprintf( "   Risk Score: %d/100\n", $summary['risk_score'] ?? 0 );
echo sprintf( "   Status: %s\n\n", $summary['overall_status'] ?? 'UNKNOWN' );

// Generate markdown report
$report = generate_markdown_report( $results );

// Save report
$output_file = ABSPATH . 'SECURITY-AUDIT-REPORT-DETAILED.md';
file_put_contents( $output_file, $report );

echo "✅ Detailed report saved to: SECURITY-AUDIT-REPORT-DETAILED.md\n\n";

// Show warnings
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
	echo "⚠️  Found {$critical_count} CRITICAL and {$high_count} HIGH severity issues!\n";
	echo "Review the report and address critical issues before production deployment.\n";
} else {
	echo "🎉 No critical or high severity issues found!\n";
	echo "Platform is secure for production deployment.\n";
}

/**
 * Generate comprehensive markdown report
 *
 * @param array $results Audit results.
 * @return string Markdown report
 */
function generate_markdown_report( array $results ): string {
	$report = "# 🔒 OWASP Top 10 Security Audit Report - Detailed\n\n";
	$report .= "**Platform:** PearBlog Engine v8.0.0\n";
	$report .= sprintf( "**Audit Date:** %s\n", date( 'Y-m-d H:i:s' ) );
	$report .= "**Auditor:** Security Auditor v1.0 (Automated)\n";
	$report .= "**Standard:** OWASP Top 10 2021\n\n";
	$report .= "---\n\n";

	// Executive Summary
	$report .= "## Executive Summary\n\n";
	$summary = $results['summary'] ?? [];
	$report .= sprintf( "**Overall Security Status:** %s\n\n", get_status_emoji( $summary['overall_status'] ?? 'UNKNOWN' ) );

	$report .= "### Summary Metrics\n\n";
	$report .= "| Metric | Count | Status |\n";
	$report .= "|--------|-------|--------|\n";
	$report .= sprintf( "| **Total Checks** | %d | Complete |\n", $summary['total_checks'] ?? 0 );
	$report .= sprintf( "| **Passed** | %d | ✅ |\n", $summary['passed'] ?? 0 );
	$report .= sprintf( "| **Failed** | %d | %s |\n", $summary['failed'] ?? 0, ( $summary['failed'] ?? 0 ) > 0 ? '❌' : '✅' );
	$report .= sprintf( "| **Warnings** | %d | %s |\n", $summary['warnings'] ?? 0, ( $summary['warnings'] ?? 0 ) > 0 ? '⚠️' : '✅' );
	$report .= sprintf( "| **Total Vulnerabilities** | %d | %s |\n", $summary['total_vulnerabilities'] ?? 0, ( $summary['total_vulnerabilities'] ?? 0 ) > 0 ? '⚠️' : '✅' );
	$report .= "\n";
	$report .= sprintf( "**Risk Score:** %d/100 %s\n\n", $summary['risk_score'] ?? 0, get_risk_emoji( $summary['risk_score'] ?? 0 ) );
	$report .= "---\n\n";

	// Detailed Findings
	$report .= "## Detailed Findings\n\n";

	foreach ( $results['checks'] ?? [] as $check ) {
		$report .= sprintf( "### %s: %s\n\n", $check['id'] ?? '', $check['name'] ?? '' );
		$report .= sprintf( "**Status:** %s %s\n", get_status_emoji( $check['status'] ?? '' ), $check['status'] ?? 'UNKNOWN' );
		$report .= sprintf( "**Description:** %s\n\n", $check['description'] ?? '' );

		$issues = $check['issues'] ?? [];

		if ( ! empty( $issues ) ) {
			$report .= "#### Issues Found:\n\n";

			foreach ( $issues as $issue ) {
				$severity_emoji = get_severity_emoji( $issue['severity'] ?? '' );
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

	$report .= "\n### Priority 3: Medium Severity Issues\n\n";
	$medium_found = false;

	foreach ( $results['checks'] ?? [] as $check ) {
		foreach ( $check['issues'] ?? [] as $issue ) {
			if ( 'MEDIUM' === $issue['severity'] ) {
				$medium_found = true;
				$report .= sprintf( "- [ ] **%s** in `%s`: %s\n", $check['name'] ?? '', $issue['file'] ?? '', $issue['finding'] ?? '' );
			}
		}
	}

	if ( ! $medium_found ) {
		$report .= "✅ No medium severity issues found!\n";
	}

	$report .= "\n---\n\n";

	// Best Practices
	$report .= "## 📋 Security Best Practices\n\n";
	$report .= "### Recommended Actions\n\n";
	$report .= "1. **Regular Audits:** Run security audits quarterly or after major updates\n";
	$report .= "2. **Dependency Updates:** Keep all dependencies and WordPress core up to date\n";
	$report .= "3. **Security Headers:** Implement security headers (CSP, X-Frame-Options, etc.)\n";
	$report .= "4. **Input Validation:** Always validate and sanitize user input\n";
	$report .= "5. **Output Escaping:** Escape all output appropriately (esc_html, esc_attr, esc_url)\n";
	$report .= "6. **Authentication:** Use strong authentication and authorization checks\n";
	$report .= "7. **Logging:** Implement comprehensive security event logging\n";
	$report .= "8. **Monitoring:** Set up real-time security monitoring and alerts\n";
	$report .= "9. **Rate Limiting:** Implement rate limiting for API endpoints\n";
	$report .= "10. **SSL/TLS:** Always use HTTPS in production\n\n";

	$report .= "### Security Resources\n\n";
	$report .= "- [OWASP Top 10 2021](https://owasp.org/Top10/)\n";
	$report .= "- [WordPress Security](https://wordpress.org/support/article/hardening-wordpress/)\n";
	$report .= "- [PHP Security Best Practices](https://cheatsheetseries.owasp.org/cheatsheets/PHP_Configuration_Cheat_Sheet.html)\n";
	$report .= "- [OWASP Cheat Sheet Series](https://cheatsheetseries.owasp.org/)\n\n";

	$report .= "### Security Tools\n\n";
	$report .= "**WP-CLI Commands (requires WordPress environment):**\n\n";
	$report .= "```bash\n";
	$report .= "# Run full security audit\n";
	$report .= "wp pearblog security audit\n\n";
	$report .= "# Quick security scan\n";
	$report .= "wp pearblog security scan\n\n";
	$report .= "# Generate JSON report\n";
	$report .= "wp pearblog security audit --format=json\n\n";
	$report .= "# Filter by severity\n";
	$report .= "wp pearblog security audit --severity=high\n";
	$report .= "```\n\n";

	$report .= "---\n\n";
	$report .= sprintf( "**Report Generated:** %s\n", date( 'Y-m-d H:i:s' ) );
	$report .= "**Next Audit Due:** " . date( 'Y-m-d', strtotime( '+3 months' ) ) . " (Quarterly)\n";
	$report .= "**Audit Tool:** PearBlog Security Auditor v1.0\n\n";

	$report .= "---\n\n";
	$report .= "*This is an automated security audit report. Manual review by security professionals is recommended for production deployments.*\n";

	return $report;
}

function get_status_emoji( string $status ): string {
	$emojis = [
		'PASS'     => '✅ PASS',
		'WARNING'  => '⚠️ WARNING',
		'CRITICAL' => '❌ CRITICAL',
		'FAIL'     => '❌ FAIL',
	];
	return $emojis[ $status ] ?? '❓ UNKNOWN';
}

function get_severity_emoji( string $severity ): string {
	$emojis = [
		'CRITICAL' => '🔴',
		'HIGH'     => '🟠',
		'MEDIUM'   => '🟡',
		'LOW'      => '🟢',
		'INFO'     => 'ℹ️',
	];
	return $emojis[ $severity ] ?? '❓';
}

function get_risk_emoji( int $score ): string {
	if ( $score >= 50 ) {
		return '🔴 (High Risk)';
	} elseif ( $score >= 20 ) {
		return '🟠 (Medium Risk)';
	} elseif ( $score >= 5 ) {
		return '🟡 (Low Risk)';
	}
	return '🟢 (Very Low Risk)';
}
