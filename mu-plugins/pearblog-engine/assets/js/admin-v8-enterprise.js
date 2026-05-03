/**
 * Admin Panel v8.0 ENTERPRISE MAX - JavaScript
 *
 * Real-time updates, dark mode, notifications, and advanced interactions
 *
 * @package PearBlogEngine
 * @since 8.0.0
 */

(function($) {
	'use strict';

	const PearBlogV8 = {
		charts: {},
		realtimeInterval: null,
		currentTheme: 'light',
		currentLang: 'en',

		/**
		 * Initialize on DOM ready
		 */
		init() {
			this.setupEventListeners();
			this.initializeCharts();
			this.loadSavedTheme();
			this.startRealtimeUpdates();
			console.log('🚀 PearBlog Enterprise v8.0 initialized');
		},

		/**
		 * Setup event listeners
		 */
		setupEventListeners() {
			// Tab switching
			$(document).on('click', '.pb-v8-tab', function() {
				const tabId = $(this).data('tab');
				PearBlogV8.switchTab(tabId);
			});

			// Handle browser back/forward
			window.addEventListener('popstate', (e) => {
				if (e.state && e.state.tab) {
					PearBlogV8.switchTab(e.state.tab, false);
				}
			});
		},

		/**
		 * Switch between tabs
		 */
		switchTab(tabId, updateHistory = true) {
			// Update tab buttons
			$('.pb-v8-tab').removeClass('is-active');
			$(`.pb-v8-tab[data-tab="${tabId}"]`).addClass('is-active');

			// Update tab panels
			$('.pb-v8-tab-panel').removeClass('is-active');
			$(`.pb-v8-tab-panel[data-tab="${tabId}"]`).addClass('is-active');

			// Update URL
			if (updateHistory) {
				const url = new URL(window.location);
				url.searchParams.set('tab', tabId);
				history.pushState({ tab: tabId }, '', url);
			}

			// Initialize tab-specific features
			this.initializeTabFeatures(tabId);
		},

		/**
		 * Initialize tab-specific features
		 */
		initializeTabFeatures(tabId) {
			switch(tabId) {
				case 'dashboard':
					this.initializeDashboardCharts();
					break;
				case 'realtime':
					this.startRealtimeMonitoring();
					break;
			}
		},

		/**
		 * Initialize all charts
		 */
		initializeCharts() {
			if (typeof Chart === 'undefined') {
				console.warn('Chart.js not loaded');
				return;
			}

			// Set default chart options
			Chart.defaults.font.family = 'var(--pb-v8-font-sans)';
			Chart.defaults.color = getComputedStyle(document.documentElement)
				.getPropertyValue('--pb-v8-text-secondary');
		},

		/**
		 * Initialize dashboard charts
		 */
		initializeDashboardCharts() {
			this.initializeRevenueChart();
			this.initializeContentChart();
		},

		/**
		 * Initialize revenue trend chart
		 */
		initializeRevenueChart() {
			const canvas = document.getElementById('revenueChart');
			if (!canvas) return;

			// Generate mock data
			const days = 30;
			const labels = [];
			const data = [];

			for (let i = days; i >= 0; i--) {
				const date = new Date();
				date.setDate(date.getDate() - i);
				labels.push(date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }));
				data.push(Math.random() * 500 + 500);
			}

			const ctx = canvas.getContext('2d');
			this.charts.revenue = new Chart(ctx, {
				type: 'line',
				data: {
					labels: labels,
					datasets: [{
						label: 'Revenue ($)',
						data: data,
						borderColor: '#0066ff',
						backgroundColor: 'rgba(0, 102, 255, 0.1)',
						borderWidth: 3,
						fill: true,
						tension: 0.4,
						pointRadius: 0,
						pointHoverRadius: 6,
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							mode: 'index',
							intersect: false,
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							borderRadius: 8,
							callbacks: {
								label: function(context) {
									return '$' + context.parsed.y.toFixed(2);
								}
							}
						}
					},
					scales: {
						x: {
							grid: {
								display: false
							}
						},
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
		 * Initialize content distribution chart
		 */
		initializeContentChart() {
			const canvas = document.getElementById('contentChart');
			if (!canvas) return;

			const ctx = canvas.getContext('2d');
			this.charts.content = new Chart(ctx, {
				type: 'doughnut',
				data: {
					labels: ['Published', 'Draft', 'Scheduled', 'In Progress'],
					datasets: [{
						data: [65, 20, 10, 5],
						backgroundColor: [
							'#00c853',
							'#ffa726',
							'#0066ff',
							'#ff3d00'
						],
						borderWidth: 0,
						hoverOffset: 8
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					plugins: {
						legend: {
							position: 'bottom',
							labels: {
								padding: 20,
								usePointStyle: true,
								font: {
									size: 13,
									weight: '600'
								}
							}
						},
						tooltip: {
							backgroundColor: 'rgba(0, 0, 0, 0.8)',
							padding: 12,
							borderRadius: 8,
							callbacks: {
								label: function(context) {
									const total = context.dataset.data.reduce((a, b) => a + b, 0);
									const percentage = ((context.parsed / total) * 100).toFixed(1);
									return context.label + ': ' + percentage + '%';
								}
							}
						}
					}
				}
			});
		},

		/**
		 * Start real-time monitoring
		 */
		startRealtimeMonitoring() {
			if (this.realtimeInterval) {
				clearInterval(this.realtimeInterval);
			}

			this.updateRealtimeData();
			this.realtimeInterval = setInterval(() => {
				this.updateRealtimeData();
			}, 5000); // Update every 5 seconds
		},

		/**
		 * Update real-time data
		 */
		updateRealtimeData() {
			if (typeof pbV8Data === 'undefined' || !pbV8Data.realtimeEnabled) {
				return;
			}

			$.ajax({
				url: pbV8Data.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pb_v8_get_realtime_stats',
					nonce: pbV8Data.nonce
				},
				success: (response) => {
					if (response.success) {
						this.displayRealtimeStats(response.data);
					}
				}
			});
		},

		/**
		 * Display real-time stats
		 */
		displayRealtimeStats(data) {
			$('#liveVisitors').text(data.visitors);
			$('#liveRevenue').text('$' + data.revenue.toFixed(2));
			$('#liveConversions').text(data.conversions);
			$('#liveErrors').text(data.errors + '%');

			// Add activity to stream
			this.addActivityToStream(data);
		},

		/**
		 * Add activity to live stream
		 */
		addActivityToStream(data) {
			const stream = $('#liveActivityStream');
			const time = new Date().toLocaleTimeString();

			const activityHtml = `
				<div class="pb-v8-activity-item" style="padding: 12px; border-bottom: 1px solid var(--pb-v8-border); animation: pb-v8-fadeIn 0.3s;">
					<div style="display: flex; justify-content: space-between; align-items: center;">
						<span><strong>${data.visitors}</strong> visitors online</span>
						<span style="color: var(--pb-v8-text-tertiary); font-size: 12px;">${time}</span>
					</div>
				</div>
			`;

			stream.prepend(activityHtml);

			// Keep only last 20 items
			stream.children().slice(20).remove();
		},

		/**
		 * Stop real-time updates
		 */
		startRealtimeUpdates() {
			// Only start if on dashboard or realtime tab
			const currentTab = new URLSearchParams(window.location.search).get('tab') || 'dashboard';
			if (currentTab === 'dashboard' || currentTab === 'realtime') {
				this.updateRealtimeData();
			}
		},

		/**
		 * Toggle theme (dark/light)
		 */
		toggleTheme() {
			const newTheme = this.currentTheme === 'dark' ? 'light' : 'dark';
			this.setTheme(newTheme);

			// Save to backend
			$.ajax({
				url: pbV8Data.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pb_v8_toggle_theme',
					nonce: pbV8Data.nonce
				},
				success: (response) => {
					if (response.success) {
						console.log('Theme saved:', response.data.theme);
					}
				}
			});
		},

		/**
		 * Set theme
		 */
		setTheme(theme) {
			this.currentTheme = theme;
			$('.pearblog-admin-v8').attr('data-theme', theme);
			localStorage.setItem('pbV8Theme', theme);

			// Update charts if they exist
			this.updateChartsTheme();
		},

		/**
		 * Load saved theme
		 */
		loadSavedTheme() {
			const savedTheme = localStorage.getItem('pbV8Theme') || pbV8Data.theme || 'light';
			this.setTheme(savedTheme);
		},

		/**
		 * Update charts theme
		 */
		updateChartsTheme() {
			// Update chart colors based on theme
			Object.values(this.charts).forEach(chart => {
				if (chart) {
					chart.update();
				}
			});
		},

		/**
		 * Toggle language (EN/PL)
		 */
		toggleLanguage() {
			const newLang = this.currentLang === 'en' ? 'pl' : 'en';
			this.currentLang = newLang;

			// Reload page with new language
			const url = new URL(window.location);
			url.searchParams.set('lang', newLang);
			window.location.href = url.toString();
		},

		/**
		 * Toggle notifications panel
		 */
		toggleNotifications() {
			const panel = $('#pbV8NotificationCenter');

			if (panel.is(':visible')) {
				panel.fadeOut(200);
			} else {
				this.loadNotifications();
				panel.fadeIn(200);
			}
		},

		/**
		 * Load notifications
		 */
		loadNotifications() {
			$.ajax({
				url: pbV8Data.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pb_v8_get_notifications',
					nonce: pbV8Data.nonce
				},
				success: (response) => {
					if (response.success) {
						this.displayNotifications(response.data);
					}
				}
			});
		},

		/**
		 * Display notifications
		 */
		displayNotifications(notifications) {
			const list = $('#pbV8NotificationList');
			list.empty();

			if (notifications.length === 0) {
				list.html('<p style="padding: 20px; text-align: center; color: var(--pb-v8-text-secondary);">No new notifications</p>');
				return;
			}

			notifications.forEach(notif => {
				const time = this.formatTime(notif.time);
				const icon = notif.type === 'success' ? '✅' : notif.type === 'warning' ? '⚠️' : 'ℹ️';

				const html = `
					<div class="pb-v8-notification-item" style="padding: 16px; border-bottom: 1px solid var(--pb-v8-border);">
						<div style="display: flex; gap: 12px;">
							<span style="font-size: 24px;">${icon}</span>
							<div style="flex: 1;">
								<strong>${notif.title}</strong>
								<p style="margin: 4px 0 0; font-size: 14px; color: var(--pb-v8-text-secondary);">
									${notif.message}
								</p>
								<span style="font-size: 12px; color: var(--pb-v8-text-tertiary);">${time}</span>
							</div>
						</div>
					</div>
				`;

				list.append(html);
			});
		},

		/**
		 * Format timestamp
		 */
		formatTime(timestamp) {
			const now = Math.floor(Date.now() / 1000);
			const diff = now - timestamp;

			if (diff < 60) return 'Just now';
			if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
			if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
			return Math.floor(diff / 86400) + ' days ago';
		},

		/**
		 * Refresh activity
		 */
		refreshActivity() {
			location.reload();
		},

		/**
		 * Generate report
		 */
		generateReport(type) {
			alert('Generating ' + type + ' report...');
			// Implement actual report generation
		},

		/**
		 * Export data
		 */
		exportData(format) {
			$.ajax({
				url: pbV8Data.ajaxUrl,
				type: 'POST',
				data: {
					action: 'pb_v8_export_report',
					nonce: pbV8Data.nonce,
					format: format
				},
				success: (response) => {
					if (response.success) {
						alert(response.data.message);
					}
				}
			});
		},

		/**
		 * Export audit log
		 */
		exportAuditLog() {
			this.exportData('csv');
		}
	};

	// Global functions for inline onclick handlers
	window.pbV8SwitchTab = (tabId) => PearBlogV8.switchTab(tabId);
	window.pbV8ToggleTheme = () => PearBlogV8.toggleTheme();
	window.pbV8ToggleLanguage = () => PearBlogV8.toggleLanguage();
	window.pbV8ToggleNotifications = () => PearBlogV8.toggleNotifications();
	window.pbV8RefreshActivity = () => PearBlogV8.refreshActivity();
	window.pbV8GenerateReport = (type) => PearBlogV8.generateReport(type);
	window.pbV8Export = (format) => PearBlogV8.exportData(format);
	window.pbV8ExportAuditLog = () => PearBlogV8.exportAuditLog();
	window.pbV8InitRealtime = () => PearBlogV8.startRealtimeMonitoring();

	// Initialize when DOM is ready
	$(document).ready(() => {
		PearBlogV8.init();
	});

	// Cleanup on page unload
	$(window).on('beforeunload', () => {
		if (PearBlogV8.realtimeInterval) {
			clearInterval(PearBlogV8.realtimeInterval);
		}
	});

})(jQuery);
