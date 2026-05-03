<?php
/**
 * Admin Dashboard for LeadAI Engine
 *
 * @package PearBlog\LeadAI\UI
 */

if (!defined('ABSPATH')) {
	exit;
}

// Get dashboard stats
global $wpdb;
$leads_table = $wpdb->prefix . 'pt24_leads';

$total_leads = $wpdb->get_var("SELECT COUNT(*) FROM {$leads_table}");
$leads_today = $wpdb->get_var($wpdb->prepare(
	"SELECT COUNT(*) FROM {$leads_table} WHERE created_at >= %d",
	strtotime('today')
));

$leads_by_status = $wpdb->get_results(
	"SELECT status, COUNT(*) as count FROM {$leads_table} GROUP BY status",
	ARRAY_A
);

$avg_score = $wpdb->get_var("SELECT AVG(score) FROM {$leads_table} WHERE score > 0");

// Recent leads
$recent_leads = $wpdb->get_results(
	"SELECT * FROM {$leads_table} ORDER BY created_at DESC LIMIT 10",
	ARRAY_A
);

?>
<div class="wrap">
	<h1>🤖 PT24 AI Lead Engine - Dashboard</h1>

	<div class="pt24-dashboard-stats" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 20px 0;">
		<div class="card" style="padding: 20px;">
			<h2 style="margin: 0; font-size: 36px; color: #2563eb;"><?php echo number_format($total_leads); ?></h2>
			<p style="margin: 5px 0 0; color: #6b7280;">Total Leads</p>
		</div>

		<div class="card" style="padding: 20px;">
			<h2 style="margin: 0; font-size: 36px; color: #10b981;"><?php echo number_format($leads_today); ?></h2>
			<p style="margin: 5px 0 0; color: #6b7280;">Leads Today</p>
		</div>

		<div class="card" style="padding: 20px;">
			<h2 style="margin: 0; font-size: 36px; color: #f59e0b;"><?php echo number_format($avg_score, 1); ?></h2>
			<p style="margin: 5px 0 0; color: #6b7280;">Avg Score</p>
		</div>

		<div class="card" style="padding: 20px;">
			<h2 style="margin: 0; font-size: 36px; color: #8b5cf6;">Active</h2>
			<p style="margin: 5px 0 0; color: #6b7280;">Status</p>
		</div>
	</div>

	<div style="display: grid; grid-template-columns: 1fr 2fr; gap: 20px; margin: 20px 0;">
		<div class="card" style="padding: 20px;">
			<h2>Leads by Status</h2>
			<table class="widefat">
				<thead>
					<tr>
						<th>Status</th>
						<th>Count</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($leads_by_status as $row): ?>
					<tr>
						<td><?php echo esc_html($row['status']); ?></td>
						<td><?php echo number_format($row['count']); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<div class="card" style="padding: 20px;">
			<h2>Recent Leads</h2>
			<table class="widefat">
				<thead>
					<tr>
						<th>ID</th>
						<th>Category</th>
						<th>Location</th>
						<th>Status</th>
						<th>Score</th>
						<th>Created</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($recent_leads as $lead): ?>
					<tr>
						<td><?php echo $lead['id']; ?></td>
						<td><?php echo esc_html($lead['category']); ?></td>
						<td><?php echo esc_html($lead['location']); ?></td>
						<td>
							<span class="badge" style="padding: 3px 8px; background: #e5e7eb; border-radius: 4px; font-size: 11px;">
								<?php echo esc_html($lead['status']); ?>
							</span>
						</td>
						<td><?php echo $lead['score']; ?></td>
						<td><?php echo date('Y-m-d H:i', $lead['created_at']); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>

	<div class="card" style="padding: 20px; margin: 20px 0;">
		<h2>System Information</h2>
		<p><strong>Version:</strong> 2.0.0</p>
		<p><strong>Status:</strong> ✅ Active</p>
		<p><strong>SLA Monitoring:</strong> Every 5 minutes</p>
		<p><strong>Database Tables:</strong> pt24_leads, pt24_contractors, pt24_sms_log, pt24_email_log</p>
	</div>

	<div class="card" style="padding: 20px; margin: 20px 0; background: #f0f9ff; border-left: 4px solid #2563eb;">
		<h2>🎯 AI Lead Engine Features</h2>
		<ul style="margin-left: 20px;">
			<li>✅ AI-powered lead analysis (intent detection + scoring)</li>
			<li>✅ Smart contractor routing (EXCLUSIVE / SHARED / OPEN)</li>
			<li>✅ SLA monitoring with automatic escalation</li>
			<li>✅ AI fallback responses (platform assistant)</li>
			<li>✅ SMS & Email notifications</li>
			<li>✅ Dynamic lead pricing (40 PLN / 25 PLN / 10 PLN)</li>
			<li>✅ Two-phase escalation system</li>
			<li>✅ Background queue processing</li>
		</ul>
	</div>
</div>

<style>
.pt24-dashboard-stats .card h2 {
	font-weight: 600;
}
.badge {
	display: inline-block;
}
</style>
