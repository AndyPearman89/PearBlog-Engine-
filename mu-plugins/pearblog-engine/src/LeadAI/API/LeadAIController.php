<?php
/**
 * LeadAI REST API Controller
 *
 * REST API endpoints for lead management and monitoring.
 *
 * @package PearBlog\LeadAI\API
 */

declare(strict_types=1);

namespace PearBlog\LeadAI\API;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use PearBlog\LeadAI\Application\LeadOrchestrator;

/**
 * Lead AI API Controller
 *
 * Manages lead lifecycle via REST API.
 */
class LeadAIController extends WP_REST_Controller {
	protected $namespace = 'pt24/v1';
	private LeadOrchestrator $orchestrator;

	public function __construct() {
		$this->orchestrator = new LeadOrchestrator();
	}

	/**
	 * Register REST API routes.
	 */
	public function register_routes(): void {
		// POST /pt24/v1/leads - Create new lead
		register_rest_route($this->namespace, '/leads', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'create_lead'],
			'permission_callback' => '__return_true', // Public endpoint
			'args'                => [
				'category' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'location' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
				],
				'message' => [
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_textarea_field',
				],
				'package_type' => [
					'required' => false,
					'type'     => 'string',
					'default'  => 'FREE',
				],
			],
		]);

		// GET /pt24/v1/leads/{id} - Get lead details
		register_rest_route($this->namespace, '/leads/(?P<id>\d+)', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'get_lead'],
			'permission_callback' => [$this, 'check_permission'],
		]);

		// POST /pt24/v1/leads/{id}/respond - Mark lead as responded
		register_rest_route($this->namespace, '/leads/(?P<id>\d+)/respond', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'mark_responded'],
			'permission_callback' => [$this, 'check_permission'],
		]);

		// POST /pt24/v1/leads/{id}/close - Close lead
		register_rest_route($this->namespace, '/leads/(?P<id>\d+)/close', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'close_lead'],
			'permission_callback' => [$this, 'check_permission'],
		]);

		// GET /pt24/v1/leads - List leads with filters
		register_rest_route($this->namespace, '/leads', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'list_leads'],
			'permission_callback' => [$this, 'check_permission'],
			'args'                => [
				'status' => [
					'required' => false,
					'type'     => 'string',
				],
				'package_type' => [
					'required' => false,
					'type'     => 'string',
				],
				'page' => [
					'required' => false,
					'type'     => 'integer',
					'default'  => 1,
				],
				'per_page' => [
					'required' => false,
					'type'     => 'integer',
					'default'  => 20,
				],
			],
		]);

		// POST /pt24/v1/sla/monitor - Run SLA monitoring cycle
		register_rest_route($this->namespace, '/sla/monitor', [
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => [$this, 'run_sla_monitoring'],
			'permission_callback' => [$this, 'check_permission'],
		]);

		// GET /pt24/v1/stats/dashboard - Get dashboard statistics
		register_rest_route($this->namespace, '/stats/dashboard', [
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => [$this, 'get_dashboard_stats'],
			'permission_callback' => [$this, 'check_permission'],
		]);
	}

	/**
	 * Create new lead.
	 */
	public function create_lead(WP_REST_Request $request): WP_REST_Response {
		$data = [
			'category'     => $request->get_param('category'),
			'location'     => $request->get_param('location'),
			'message'      => $request->get_param('message'),
			'package_type' => $request->get_param('package_type') ?? 'FREE',
		];

		$result = $this->orchestrator->processNewLead($data);

		if (!$result['success']) {
			return new WP_REST_Response([
				'success' => false,
				'error'   => $result['error'],
			], 400);
		}

		return new WP_REST_Response([
			'success'       => true,
			'lead_id'       => $result['lead_id'],
			'status'        => $result['status'],
			'score'         => $result['score'],
			'intent'        => $result['intent'],
			'sla_deadline'  => $result['sla_deadline'],
		], 201);
	}

	/**
	 * Get lead details.
	 */
	public function get_lead(WP_REST_Request $request): WP_REST_Response {
		$lead_id = (int) $request->get_param('id');

		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_leads';

		$lead = $wpdb->get_row(
			$wpdb->prepare("SELECT * FROM {$table_name} WHERE id = %d", $lead_id),
			ARRAY_A
		);

		if (!$lead) {
			return new WP_REST_Response([
				'success' => false,
				'error'   => 'Lead not found',
			], 404);
		}

		// Decode JSON fields
		$lead['metadata'] = json_decode($lead['metadata'] ?? '{}', true);
		$lead['score_breakdown'] = json_decode($lead['score_breakdown'] ?? '{}', true);

		return new WP_REST_Response([
			'success' => true,
			'lead'    => $lead,
		], 200);
	}

	/**
	 * Mark lead as responded.
	 */
	public function mark_responded(WP_REST_Request $request): WP_REST_Response {
		$lead_id = (int) $request->get_param('id');

		$result = $this->orchestrator->markAsResponded($lead_id);

		if (!$result) {
			return new WP_REST_Response([
				'success' => false,
				'error'   => 'Failed to mark lead as responded',
			], 400);
		}

		return new WP_REST_Response([
			'success' => true,
			'message' => 'Lead marked as responded',
		], 200);
	}

	/**
	 * Close lead.
	 */
	public function close_lead(WP_REST_Request $request): WP_REST_Response {
		$lead_id = (int) $request->get_param('id');

		$result = $this->orchestrator->closeLead($lead_id);

		if (!$result) {
			return new WP_REST_Response([
				'success' => false,
				'error'   => 'Failed to close lead',
			], 400);
		}

		return new WP_REST_Response([
			'success' => true,
			'message' => 'Lead closed',
		], 200);
	}

	/**
	 * List leads with filters.
	 */
	public function list_leads(WP_REST_Request $request): WP_REST_Response {
		$status       = $request->get_param('status');
		$package_type = $request->get_param('package_type');
		$page         = (int) $request->get_param('page');
		$per_page     = (int) $request->get_param('per_page');

		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_leads';

		$where = ['1=1'];
		$params = [];

		if ($status) {
			$where[] = 'status = %s';
			$params[] = $status;
		}

		if ($package_type) {
			$where[] = 'package_type = %s';
			$params[] = $package_type;
		}

		$where_clause = implode(' AND ', $where);
		$offset = ($page - 1) * $per_page;

		$query = "SELECT * FROM {$table_name} WHERE {$where_clause} ORDER BY created_at DESC LIMIT %d OFFSET %d";
		$params[] = $per_page;
		$params[] = $offset;

		$leads = $wpdb->get_results(
			$wpdb->prepare($query, ...$params),
			ARRAY_A
		);

		// Decode JSON fields
		foreach ($leads as &$lead) {
			$lead['metadata'] = json_decode($lead['metadata'] ?? '{}', true);
			$lead['score_breakdown'] = json_decode($lead['score_breakdown'] ?? '{}', true);
		}

		// Get total count
		$count_query = "SELECT COUNT(*) FROM {$table_name} WHERE {$where_clause}";
		$total = $wpdb->get_var(
			empty($params) ? $count_query : $wpdb->prepare($count_query, ...array_slice($params, 0, -2))
		);

		return new WP_REST_Response([
			'success'   => true,
			'leads'     => $leads,
			'total'     => (int) $total,
			'page'      => $page,
			'per_page'  => $per_page,
			'total_pages' => ceil($total / $per_page),
		], 200);
	}

	/**
	 * Run SLA monitoring.
	 */
	public function run_sla_monitoring(WP_REST_Request $request): WP_REST_Response {
		$result = $this->orchestrator->runSLAMonitoring();

		return new WP_REST_Response([
			'success'           => true,
			'checked_at'        => $result['checked_at'],
			'breached_count'    => $result['breached_count'],
			'approaching_count' => $result['approaching_count'],
			'escalations'       => $result['escalations'],
		], 200);
	}

	/**
	 * Get dashboard statistics.
	 */
	public function get_dashboard_stats(WP_REST_Request $request): WP_REST_Response {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pt24_leads';

		// Total leads by status
		$leads_by_status = $wpdb->get_results(
			"SELECT status, COUNT(*) as count FROM {$table_name} GROUP BY status",
			ARRAY_A
		);

		// Average score by package type
		$avg_score = $wpdb->get_results(
			"SELECT package_type, AVG(score) as avg_score FROM {$table_name} GROUP BY package_type",
			ARRAY_A
		);

		// Leads created today
		$today_start = strtotime('today');
		$leads_today = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table_name} WHERE created_at >= %d",
				$today_start
			)
		);

		// SLA compliance rate
		$total_premium = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE package_type IN ('PREMIUM', 'PREMIUM+')"
		);

		$breached = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name}
			WHERE package_type IN ('PREMIUM', 'PREMIUM+')
			AND status IN ('ESCALATED', 'REDISTRIBUTED')"
		);

		$sla_compliance = $total_premium > 0 ? (1 - ($breached / $total_premium)) * 100 : 100;

		return new WP_REST_Response([
			'success'         => true,
			'leads_by_status' => $leads_by_status,
			'avg_score'       => $avg_score,
			'leads_today'     => (int) $leads_today,
			'sla_compliance'  => round($sla_compliance, 2),
			'total_leads'     => (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}"),
		], 200);
	}

	/**
	 * Check permissions.
	 */
	public function check_permission(): bool {
		return current_user_can('manage_options');
	}
}
