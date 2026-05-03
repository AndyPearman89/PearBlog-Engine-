/**
 * Admin Panel v7.0 - JavaScript
 *
 * Client-side functionality for PearBlog Engine v7.0 SaaS Control Center
 *
 * @package PearBlogEngine
 * @since 7.1.0
 */

(function($) {
	'use strict';

	/**
	 * PearBlog Admin V7 Controller
	 */
	const PearBlogAdminV7 = {

		/**
		 * Initialize admin v7
		 */
		init: function() {
			this.initDashboard();
			this.initTabSwitching();
			this.initCharts();
			console.log('[PearBlog v7] Admin interface initialized');
		},

		/**
		 * Initialize dashboard widgets
		 */
		initDashboard: function() {
			// Load dashboard data via AJAX
			if ($('.pearblog-v7-dashboard').length) {
				this.initPeriodSelector();
				this.loadDashboardData(30); // Default 30 days
			}
		},

		/**
		 * Initialize period selector
		 */
		initPeriodSelector: function() {
			const self = this;
			$('.dashboard-period-select').on('change', function() {
				const days = parseInt($(this).val());
				self.loadDashboardData(days);
			});
		},

		/**
		 * Load dashboard KPI data
		 */
		loadDashboardData: function(days) {
			const self = this;

			$.ajax({
				url: pearblogAdminV7.restUrl + 'dashboard/kpis?days=' + days,
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', pearblogAdminV7.restNonce);
				},
				success: function(data) {
					// KPIs are already rendered server-side, just need to update chart
					self.loadRevenueChart(days);
				},
				error: function(xhr, status, error) {
					console.warn('[PearBlog v7] Dashboard data not yet available:', error);
					// Gracefully degrade - show placeholder data
				}
			});
		},

		/**
		 * Initialize tab switching
		 */
		initTabSwitching: function() {
			$('.pearblog-v7-tab').on('click', function(e) {
				const tab = $(this).data('tab');

				// Update URL without page reload
				const url = new URL(window.location);
				url.searchParams.set('tab', tab);
				window.history.pushState({}, '', url);
			});

			// Handle browser back/forward
			$(window).on('popstate', function() {
				location.reload();
			});
		},

		/**
		 * Load revenue chart data
		 */
		loadRevenueChart: function(days) {
			const self = this;

			$.ajax({
				url: pearblogAdminV7.restUrl + 'dashboard/revenue-chart?days=' + days,
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', pearblogAdminV7.restNonce);
				},
				success: function(data) {
					self.renderRevenueChart(data.labels, data.data);
				},
				error: function(xhr, status, error) {
					console.warn('[PearBlog v7] Revenue chart data not available:', error);
				}
			});
		},

		/**
		 * Initialize Chart.js visualizations
		 */
		initCharts: function() {
			if (typeof Chart === 'undefined') {
				console.warn('[PearBlog v7] Chart.js not loaded');
				return;
			}

			// Chart will be loaded via AJAX in initDashboard
		},

		/**
		 * Render revenue over time chart
		 */
		renderRevenueChart: function(labels, data) {
			if (typeof Chart === 'undefined') {
				console.warn('[PearBlog v7] Chart.js not loaded');
				return;
			}

			const canvas = document.getElementById('revenueChart');
			if (!canvas) return;

			const ctx = canvas.getContext('2d');

			// Destroy existing chart if any
			if (window.pearblogRevenueChart) {
				window.pearblogRevenueChart.destroy();
			}

			// Use real data from API or fallback to empty
			const chartLabels = labels && labels.length > 0 ? labels : [];
			const chartData = data && data.length > 0 ? data : [];

			window.pearblogRevenueChart = new Chart(ctx, {
				type: 'line',
				data: {
					labels: chartLabels,
					datasets: [{
						label: 'Daily Revenue ($)',
						data: chartData,
						borderColor: '#2563eb',
						backgroundColor: 'rgba(37, 99, 235, 0.1)',
						borderWidth: 2,
						fill: true,
						tension: 0.4
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							mode: 'index',
							intersect: false,
							callbacks: {
								label: function(context) {
									return '$' + context.parsed.y.toFixed(2);
								}
							}
						}
					},
					scales: {
						y: {
							beginAtZero: true,
							ticks: {
								callback: function(value) {
									return '$' + value;
								}
							}
						}
					},
					interaction: {
						mode: 'nearest',
						axis: 'x',
						intersect: false
					}
				}
			});
		},

		/**
		 * Show success notification
		 */
		showSuccess: function(message) {
			this.showNotice(message, 'success');
		},

		/**
		 * Show error notification
		 */
		showError: function(message) {
			this.showNotice(message, 'danger');
		},

		/**
		 * Show notification
		 */
		showNotice: function(message, type) {
			const notice = $('<div>')
				.addClass('pearblog-notice pearblog-notice-' + type)
				.html('<p>' + message + '</p>')
				.hide()
				.prependTo('.pearblog-v7-content')
				.slideDown(300);

			setTimeout(function() {
				notice.slideUp(300, function() {
					$(this).remove();
				});
			}, 5000);
		}
	};

	/**
	 * Initialize on document ready
	 */
	$(document).ready(function() {
		if ($('.pearblog-admin-v7').length) {
			PearBlogAdminV7.init();
		}
	});

	// Expose to global scope for external access
	window.PearBlogAdminV7 = PearBlogAdminV7;

})(jQuery);
