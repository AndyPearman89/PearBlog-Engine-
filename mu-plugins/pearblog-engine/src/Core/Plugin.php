<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package PearBlogEngine\Core
 */

declare(strict_types=1);

namespace PearBlogEngine\Core;

use PearBlogEngine\Scheduler\CronManager;
use PearBlogEngine\Admin\AdminPage;

/**
 * Plugin class – boots all sub-systems exactly once.
 */
class Plugin {

	/** @var self|null */
	private static ?self $instance = null;

	private function __construct() {}

	/**
	 * Return the singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Attach WordPress hooks and initialise sub-systems.
	 */
	public function boot(): void {
		( new CronManager() )->register();
		( new AdminPage() )->register();
	}
}
