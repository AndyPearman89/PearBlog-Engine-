<?php
/**
 * Autopilot runner – manages state and task execution for the enterprise autopilot.
 *
 * Stores execution state in WordPress options and provides methods to
 * start, pause, resume, and advance through the task list.
 *
 * @package PearBlogEngine\CLI
 */

declare(strict_types=1);

namespace PearBlogEngine\CLI;

/**
 * Manages autopilot task state and execution lifecycle.
 */
class AutopilotRunner {

	/** WordPress option key for autopilot state. */
	public const STATE_OPTION = 'pearblog_autopilot_state';

	/** WordPress option key for autopilot metrics. */
	public const METRICS_OPTION = 'pearblog_autopilot_metrics';

	/** Valid modes for the autopilot. */
	public const VALID_MODES = [ 'enterprise', 'standard' ];

	/** Valid statuses for the autopilot. */
	public const STATUS_IDLE    = 'idle';
	public const STATUS_RUNNING = 'running';
	public const STATUS_PAUSED  = 'paused';

	/** Maximum retries per task before marking as failed. */
	private const MAX_RETRIES = 3;

	/**
	 * Full ordered task list for enterprise autopilot.
	 *
	 * @var array<string, array{phase: string, name: string, priority: string}>
	 */
	private const TASKS = [
		'1.1' => [ 'phase' => 'Production Hardening', 'name' => 'Deployment Documentation',           'priority' => 'P0' ],
		'1.2' => [ 'phase' => 'Production Hardening', 'name' => 'Database Migrations Strategy',       'priority' => 'P0' ],
		'1.3' => [ 'phase' => 'Production Hardening', 'name' => 'Disaster Recovery Plan',             'priority' => 'P0' ],
		'1.4' => [ 'phase' => 'Production Hardening', 'name' => 'Performance Monitoring Dashboard',   'priority' => 'P1' ],
		'1.5' => [ 'phase' => 'Production Hardening', 'name' => 'Load Testing Suite',                 'priority' => 'P1' ],
		'1.6' => [ 'phase' => 'Production Hardening', 'name' => 'Security Audit (OWASP Top 10)',      'priority' => 'P0' ],
		'2.1' => [ 'phase' => 'Testing Expansion',    'name' => 'Expand Unit Test Coverage',          'priority' => 'P1' ],
		'2.2' => [ 'phase' => 'Testing Expansion',    'name' => 'Integration Test Suite',             'priority' => 'P1' ],
		'2.3' => [ 'phase' => 'Testing Expansion',    'name' => 'Performance Benchmarking',           'priority' => 'P2' ],
		'3.1' => [ 'phase' => 'Monitoring & Ops',     'name' => 'Enhanced Logging System',            'priority' => 'P1' ],
		'3.2' => [ 'phase' => 'Monitoring & Ops',     'name' => 'Advanced Alert Configuration',       'priority' => 'P2' ],
		'3.3' => [ 'phase' => 'Monitoring & Ops',     'name' => 'API Rate Limit Documentation',       'priority' => 'P2' ],
		'3.4' => [ 'phase' => 'Monitoring & Ops',     'name' => 'Monitoring Dashboard UI',            'priority' => 'P2' ],
		'4.1' => [ 'phase' => 'Documentation & UX',   'name' => 'Comprehensive Troubleshooting Guide','priority' => 'P1' ],
		'4.2' => [ 'phase' => 'Documentation & UX',   'name' => 'Video Tutorial Creation',           'priority' => 'P2' ],
		'4.3' => [ 'phase' => 'Documentation & UX',   'name' => 'User Onboarding Flow',              'priority' => 'P2' ],
		'5.1' => [ 'phase' => 'Advanced Features',    'name' => 'Content Caching Layer',              'priority' => 'P2' ],
		'5.2' => [ 'phase' => 'Advanced Features',    'name' => 'API Client Libraries',               'priority' => 'P2' ],
		'5.3' => [ 'phase' => 'Advanced Features',    'name' => 'CDN Integration Guide',              'priority' => 'P3' ],
		'5.4' => [ 'phase' => 'Advanced Features',    'name' => 'Advanced Prompt Templates',          'priority' => 'P2' ],
		'6.1' => [ 'phase' => 'Polish & Optimization','name' => 'Code Quality Improvements',          'priority' => 'P1' ],
		'6.2' => [ 'phase' => 'Polish & Optimization','name' => 'Performance Optimization',           'priority' => 'P1' ],
		'6.3' => [ 'phase' => 'Polish & Optimization','name' => 'Final Documentation Review',         'priority' => 'P2' ],
		'7.1' => [ 'phase' => 'Launch Preparation',   'name' => 'Pre-Launch Checklist',               'priority' => 'P0' ],
		'7.2' => [ 'phase' => 'Launch Preparation',   'name' => 'Beta Testing Program',               'priority' => 'P1' ],
		'7.3' => [ 'phase' => 'Launch Preparation',   'name' => 'Launch Day Preparation',             'priority' => 'P0' ],
	];

	/**
	 * Return the full task list definition.
	 *
	 * @return array<string, array{phase: string, name: string, priority: string}>
	 */
	public static function get_task_list(): array {
		return self::TASKS;
	}

	/**
	 * Return the total number of tasks.
	 */
	public static function get_task_count(): int {
		return count( self::TASKS );
	}

	/**
	 * Return task IDs, optionally filtered by a comma-separated filter.
	 *
	 * @param string $filter 'all' returns every task; otherwise a comma-separated
	 *                       list of task IDs (e.g. '1.1,1.2,2.1').
	 * @return string[]
	 */
	public static function resolve_tasks( string $filter ): array {
		if ( 'all' === strtolower( trim( $filter ) ) ) {
			return array_keys( self::TASKS );
		}

		$ids    = array_map( 'trim', explode( ',', $filter ) );
		$valid  = [];

		foreach ( $ids as $id ) {
			if ( isset( self::TASKS[ $id ] ) ) {
				$valid[] = $id;
			}
		}

		return $valid;
	}

	/**
	 * Get the current autopilot state from options.
	 *
	 * @return array{status: string, mode: string, current_task: string|null, tasks: string[], completed: string[], failed: string[], start_time: string|null, pause_time: string|null}
	 */
	public static function get_state(): array {
		$default = [
			'status'       => self::STATUS_IDLE,
			'mode'         => '',
			'current_task' => null,
			'tasks'        => [],
			'completed'    => [],
			'failed'       => [],
			'start_time'   => null,
			'pause_time'   => null,
		];

		$state = get_option( self::STATE_OPTION, $default );

		if ( ! is_array( $state ) ) {
			return $default;
		}

		return array_merge( $default, $state );
	}

	/**
	 * Save the autopilot state.
	 *
	 * @param array $state State array.
	 */
	public static function save_state( array $state ): void {
		update_option( self::STATE_OPTION, $state );
	}

	/**
	 * Start the autopilot with the given mode and tasks filter.
	 *
	 * @param string $mode   Autopilot mode ('enterprise' or 'standard').
	 * @param string $filter Task filter ('all' or comma-separated IDs).
	 * @return array{success: bool, message: string}
	 */
	public static function start( string $mode, string $filter ): array {
		$state = self::get_state();

		if ( self::STATUS_RUNNING === $state['status'] ) {
			return [
				'success' => false,
				'message' => 'Autopilot is already running. Use "pause" first, then "start" to restart.',
			];
		}

		if ( ! in_array( $mode, self::VALID_MODES, true ) ) {
			return [
				'success' => false,
				'message' => sprintf( 'Invalid mode "%s". Valid modes: %s', $mode, implode( ', ', self::VALID_MODES ) ),
			];
		}

		$task_ids = self::resolve_tasks( $filter );

		if ( empty( $task_ids ) ) {
			return [
				'success' => false,
				'message' => 'No valid tasks found for the given filter.',
			];
		}

		$now = gmdate( 'Y-m-d\TH:i:s\Z' );

		$new_state = [
			'status'       => self::STATUS_RUNNING,
			'mode'         => $mode,
			'current_task' => $task_ids[0],
			'tasks'        => $task_ids,
			'completed'    => [],
			'failed'       => [],
			'start_time'   => $now,
			'pause_time'   => null,
		];

		self::save_state( $new_state );
		self::log_event( $task_ids[0], 'STARTED', 'Autopilot started in ' . $mode . ' mode' );

		return [
			'success' => true,
			'message' => sprintf(
				'Autopilot started in %s mode with %d task(s). Current task: %s – %s',
				$mode,
				count( $task_ids ),
				$task_ids[0],
				self::TASKS[ $task_ids[0] ]['name']
			),
		];
	}

	/**
	 * Pause the running autopilot.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function pause(): array {
		$state = self::get_state();

		if ( self::STATUS_RUNNING !== $state['status'] ) {
			return [
				'success' => false,
				'message' => 'Autopilot is not running.',
			];
		}

		$state['status']     = self::STATUS_PAUSED;
		$state['pause_time'] = gmdate( 'Y-m-d\TH:i:s\Z' );

		self::save_state( $state );
		self::log_event( $state['current_task'] ?? 'N/A', 'PAUSED', 'Autopilot paused' );

		return [
			'success' => true,
			'message' => 'Autopilot paused.',
		];
	}

	/**
	 * Resume a paused autopilot.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function resume(): array {
		$state = self::get_state();

		if ( self::STATUS_PAUSED !== $state['status'] ) {
			return [
				'success' => false,
				'message' => 'Autopilot is not paused.',
			];
		}

		$state['status']     = self::STATUS_RUNNING;
		$state['pause_time'] = null;

		self::save_state( $state );
		self::log_event( $state['current_task'] ?? 'N/A', 'RESUMED', 'Autopilot resumed' );

		return [
			'success' => true,
			'message' => sprintf(
				'Autopilot resumed. Current task: %s – %s',
				$state['current_task'] ?? 'none',
				isset( self::TASKS[ $state['current_task'] ] ) ? self::TASKS[ $state['current_task'] ]['name'] : 'unknown'
			),
		];
	}

	/**
	 * Advance to the next task in the queue.
	 *
	 * Marks the current task as completed and advances to the next one.
	 * If no tasks remain, the autopilot status is set to idle.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function next(): array {
		$state = self::get_state();

		if ( self::STATUS_IDLE === $state['status'] ) {
			return [
				'success' => false,
				'message' => 'Autopilot is not active. Use "start" to begin.',
			];
		}

		$current = $state['current_task'];

		if ( null !== $current && ! in_array( $current, $state['completed'], true ) ) {
			$state['completed'][] = $current;
			self::log_event( $current, 'COMPLETED', 'Task completed' );
		}

		// Find the next uncompleted, non-failed task.
		$remaining = array_values( array_diff( $state['tasks'], $state['completed'], $state['failed'] ) );

		if ( empty( $remaining ) ) {
			$state['status']       = self::STATUS_IDLE;
			$state['current_task'] = null;

			self::save_state( $state );
			self::update_metrics( $state );
			self::log_event( 'ALL', 'COMPLETED', 'All tasks completed' );

			return [
				'success' => true,
				'message' => sprintf(
					'All tasks completed! %d succeeded, %d failed.',
					count( $state['completed'] ),
					count( $state['failed'] )
				),
			];
		}

		$next_task             = $remaining[0];
		$state['current_task'] = $next_task;

		self::save_state( $state );
		self::update_metrics( $state );
		self::log_event( $next_task, 'STARTED', 'Advancing to next task' );

		return [
			'success' => true,
			'message' => sprintf(
				'Advanced to task %s – %s (%d remaining)',
				$next_task,
				self::TASKS[ $next_task ]['name'],
				count( $remaining )
			),
		];
	}

	/**
	 * Mark the current task as failed and advance.
	 *
	 * @return array{success: bool, message: string}
	 */
	public static function fail_current(): array {
		$state   = self::get_state();
		$current = $state['current_task'];

		if ( null === $current ) {
			return [
				'success' => false,
				'message' => 'No current task to mark as failed.',
			];
		}

		$state['failed'][] = $current;
		self::save_state( $state );
		self::log_event( $current, 'FAILED', 'Task marked as failed' );

		return self::next();
	}

	/**
	 * Build a human-readable status summary.
	 *
	 * @return array{status: string, mode: string, current_task: string|null, current_task_name: string, total: int, completed: int, failed: int, remaining: int, progress_pct: float, start_time: string|null}
	 */
	public static function get_status_summary(): array {
		$state = self::get_state();

		$total     = count( $state['tasks'] );
		$completed = count( $state['completed'] );
		$failed    = count( $state['failed'] );
		$remaining = $total - $completed - $failed;

		return [
			'status'            => $state['status'],
			'mode'              => $state['mode'],
			'current_task'      => $state['current_task'],
			'current_task_name' => isset( self::TASKS[ $state['current_task'] ] )
				? self::TASKS[ $state['current_task'] ]['name']
				: '—',
			'total'             => $total,
			'completed'         => $completed,
			'failed'            => $failed,
			'remaining'         => max( 0, $remaining ),
			'progress_pct'      => $total > 0
				? round( ( ( $completed + $failed ) / $total ) * 100, 1 )
				: 0.0,
			'start_time'        => $state['start_time'],
		];
	}

	/**
	 * Reset the autopilot to idle state.
	 */
	public static function reset(): void {
		delete_option( self::STATE_OPTION );
		delete_option( self::METRICS_OPTION );
	}

	/**
	 * Append a log entry to the autopilot progress log option.
	 *
	 * @param string $task_id Task identifier.
	 * @param string $status  Event status (STARTED, COMPLETED, PAUSED, etc.).
	 * @param string $notes   Additional notes.
	 */
	public static function log_event( string $task_id, string $status, string $notes ): void {
		$log_entry = sprintf(
			'[%s] [TASK_%s] [%s] %s',
			gmdate( 'Y-m-d H:i:s' ),
			$task_id,
			$status,
			$notes
		);

		error_log( 'PearBlog Autopilot: ' . $log_entry );

		/**
		 * Action: pearblog_autopilot_event
		 *
		 * @param string $task_id Task identifier.
		 * @param string $status  Event status.
		 * @param string $notes   Additional notes.
		 */
		do_action( 'pearblog_autopilot_event', $task_id, $status, $notes );
	}

	/**
	 * Update stored metrics from current state.
	 *
	 * @param array $state Current autopilot state.
	 */
	private static function update_metrics( array $state ): void {
		$total     = count( $state['tasks'] );
		$completed = count( $state['completed'] );
		$failed    = count( $state['failed'] );

		$metrics = [
			'autopilot_run' => [
				'start_time'          => $state['start_time'],
				'current_task'        => $state['current_task'],
				'completed_tasks'     => $completed,
				'failed_tasks'        => $failed,
				'total_tasks'         => $total,
				'progress_percentage' => $total > 0 ? round( ( ( $completed + $failed ) / $total ) * 100, 1 ) : 0.0,
				'last_updated'        => gmdate( 'Y-m-d\TH:i:s\Z' ),
			],
		];

		update_option( self::METRICS_OPTION, $metrics );
	}
}
