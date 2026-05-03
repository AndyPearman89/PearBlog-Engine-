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
				this.loadDashboardData();
			}
		},

		/**
		 * Load dashboard KPI data
		 */
		loadDashboardData: function() {
			const self = this;

			$.ajax({
				url: pearblogAdminV7.restUrl + 'dashboard/kpis',
				method: 'GET',
				beforeSend: function(xhr) {
					xhr.setRequestHeader('X-WP-Nonce', pearblogAdminV7.restNonce);
				},
				success: function(data) {
					self.updateKPIs(data);
				},
				error: function(xhr, status, error) {
					console.warn('[PearBlog v7] Dashboard data not yet available:', error);
					// Gracefully degrade - show placeholder data
				}
			});
		},

		/**
		 * Update KPI card values
		 */
		updateKPIs: function(data) {
			if (data.revenue) {
				$('.kpi-value').eq(0).text('$' + data.revenue.toFixed(2));
			}
			if (data.articles) {
				$('.kpi-value').eq(1).text(data.articles);
			}
			if (data.views) {
				$('.kpi-value').eq(2).text(data.views.toLocaleString());
			}
			if (data.rpm) {
				$('.kpi-value').eq(3).text('$' + data.rpm.toFixed(2));
			}
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
		 * Initialize Chart.js visualizations
		 */
		initCharts: function() {
			if (typeof Chart === 'undefined') {
				console.warn('[PearBlog v7] Chart.js not loaded');
				return;
			}

			this.renderRevenueChart();
		},

		/**
		 * Render revenue over time chart
		 */
		renderRevenueChart: function() {
			const canvas = document.getElementById('revenueChart');
			if (!canvas) return;

			const ctx = canvas.getContext('2d');

			// Generate sample data for last 30 days
			const labels = [];
			const data = [];
			const today = new Date();

			for (let i = 29; i >= 0; i--) {
				const date = new Date(today);
				date.setDate(date.getDate() - i);
				labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
				data.push(Math.random() * 10 + 5); // Random $5-$15
			}

			new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: 'Daily Revenue ($)',
						data: data,
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
