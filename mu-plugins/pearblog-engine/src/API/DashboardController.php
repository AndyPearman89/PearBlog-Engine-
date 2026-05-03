<?php
/**
 * Dashboard REST API Controller
 *
 * Provides REST endpoints for dashboard KPIs, charts, and analytics data.
 *
 * @package PearBlogEngine\API
 * @since 7.2.0
 */

declare(strict_types=1);

namespace PearBlogEngine\API;

use PearBlogEngine\Admin\DashboardTab;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

/**
 * Dashboard API controller.
 */
class DashboardController extends WP_REST_Controller {

	/**
	 * Namespace for API routes.
	 */
	protected $namespace = 'pearblog/v1';

	/**
	 * Register API routes.
	 */
	public function register_routes(): void {
		// Get dashboard KPIs
		register_rest_route(
			$this->namespace,
			'/dashboard/kpis',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_kpis' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'days' => [
						'description'       => 'Number of days to analyze',
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Get revenue chart data
		register_rest_route(
			$this->namespace,
			'/dashboard/revenue-chart',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_revenue_chart' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'days' => [
						'description'       => 'Number of days to display',
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Get top articles
		register_rest_route(
			$this->namespace,
			'/dashboard/top-articles',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_top_articles' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'limit' => [
						'description'       => 'Number of articles to return',
						'type'              => 'integer',
						'default'           => 10,
						'minimum'           => 1,
						'maximum'           => 100,
						'sanitize_callback' => 'absint',
					],
					'days'  => [
						'description'       => 'Period to analyze',
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);

		// Get revenue by source
		register_rest_route(
			$this->namespace,
			'/dashboard/revenue-by-source',
			[
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_revenue_by_source' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'args'                => [
					'days' => [
						'description'       => 'Period to analyze',
						'type'              => 'integer',
						'default'           => 30,
						'minimum'           => 1,
						'maximum'           => 365,
						'sanitize_callback' => 'absint',
					],
				],
			]
		);
	}

	/**
	 * Check if user has permission to access dashboard data.
	 *
	 * @return bool|WP_Error True if has permission, WP_Error otherwise.
	 */
	public function check_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new WP_Error(
				'rest_forbidden',
				__( 'You do not have permission to access dashboard data.', 'pearblog-engine' ),
				[ 'status' => 403 ]
			);
		}

		return true;
	}

	/**
	 * Get dashboard KPIs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_kpis( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		try {
			$kpis = DashboardTab::get_kpis( $days );

			return new WP_REST_Response( $kpis, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'dashboard_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get revenue chart data.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_revenue_chart( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		try {
			$chart_data = DashboardTab::get_revenue_chart( $days );

			return new WP_REST_Response( $chart_data, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'dashboard_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get top articles.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_top_articles( WP_REST_Request $request ) {
		$limit = $request->get_param( 'limit' );
		$days  = $request->get_param( 'days' );

		try {
			$articles = DashboardTab::get_top_articles( $limit, $days );

			return new WP_REST_Response( $articles, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'dashboard_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}

	/**
	 * Get revenue by source.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error Response object or error.
	 */
	public function get_revenue_by_source( WP_REST_Request $request ) {
		$days = $request->get_param( 'days' );

		try {
			$revenue_by_source = DashboardTab::get_revenue_by_source( $days );

			return new WP_REST_Response( $revenue_by_source, 200 );
		} catch ( \Exception $e ) {
			return new WP_Error(
				'dashboard_error',
				$e->getMessage(),
				[ 'status' => 500 ]
			);
		}
	}
}
