/**
 * Onboarding Wizard v2 JavaScript
 *
 * Handles step navigation, form validation, AJAX submissions,
 * and interactive UI elements.
 */

(function($) {
	'use strict';

	const Wizard = {
		currentStep: PearBlogWizard.currentStep,
		totalSteps: PearBlogWizard.totalSteps,

		init() {
			this.bindEvents();
			this.initStepSpecificFeatures();
		},

		bindEvents() {
			// Navigation
			$('#wizard-next').on('click', () => this.nextStep());
			$('#wizard-prev').on('click', () => this.prevStep());
			$('#wizard-finish').on('click', () => this.finishWizard());
			$('#wizard-skip').on('click', () => this.skipWizard());

			// Step 2: API Key
			$('#test-api-key').on('click', () => this.testAPIKey());

			// Step 3: Keywords
			$('#add-keyword').on('click', () => this.addKeyword());
			$('#keyword-input').on('keypress', (e) => {
				if (e.which === 13) {
					e.preventDefault();
					this.addKeyword();
				}
			});
			$(document).on('click', '.remove-keyword', function() {
				$(this).parent().remove();
			});

			// Step 4: Topics
			$('#generate-topics-btn').on('click', () => this.generateTopics());
			$('#add-topic-btn').on('click', () => this.addTopic());
			$('#manual-topic').on('keypress', (e) => {
				if (e.which === 13) {
					e.preventDefault();
					this.addTopic();
				}
			});
			$(document).on('click', '.remove-topic', function() {
				$(this).parent().remove();
				Wizard.updateEmptyState();
			});
			$(document).on('click', '.suggested-topic', function() {
				const topic = $(this).data('topic');
				Wizard.addTopicToQueue(topic);
				$(this).remove();
			});

			// Step 6: Monetization toggle
			$('#revenue-enabled').on('change', function() {
				$('#monetization-settings').toggle(this.checked);
			});
		},

		initStepSpecificFeatures() {
			// Auto-focus first input
			$('.wizard-step').find('input, textarea, select').first().focus();
		},

		nextStep() {
			if (!this.validateCurrentStep()) {
				return;
			}

			this.saveStepData().then(() => {
				this.currentStep++;
				if (this.currentStep > this.totalSteps) {
					this.finishWizard();
				} else {
					window.location.href = this.getStepURL(this.currentStep);
				}
			});
		},

		prevStep() {
			if (this.currentStep > 1) {
				this.currentStep--;
				window.location.href = this.getStepURL(this.currentStep);
			}
		},

		validateCurrentStep() {
			const step = this.currentStep;

			switch (step) {
				case 2:
					const apiKey = $('#openai-api-key').val().trim();
					if (apiKey === '') {
						this.showError('Please enter your OpenAI API key.');
						return false;
					}
					if (!apiKey.startsWith('sk-')) {
						this.showError('Invalid API key format. OpenAI keys start with "sk-".');
						return false;
					}
					break;

				case 3:
					const niche = $('#target-niche').val().trim();
					const audience = $('#target-audience').val().trim();
					const keywords = $('.keyword-tag').length;

					if (niche === '') {
						this.showError('Please enter your target niche.');
						return false;
					}
					if (audience === '') {
						this.showError('Please describe your target audience.');
						return false;
					}
					if (keywords === 0) {
						this.showError('Please add at least one keyword.');
						return false;
					}
					break;

				case 4:
					const topics = $('.topic-queue-item').length;
					if (topics === 0) {
						this.showError('Please add at least one topic to your queue.');
						return false;
					}
					break;
			}

			return true;
		},

		saveStepData() {
			const data = this.collectStepData();

			return $.ajax({
				url: PearBlogWizard.ajaxUrl,
				method: 'POST',
				data: {
					action: 'pearblog_wizard_save_step',
					nonce: PearBlogWizard.nonce,
					step: this.currentStep,
					data: data
				}
			}).fail((xhr) => {
				this.showError('Failed to save: ' + (xhr.responseJSON?.message || 'Unknown error'));
			});
		},

		collectStepData() {
			const step = this.currentStep;
			const data = {};

			switch (step) {
				case 2:
					data.openai_api_key = $('#openai-api-key').val().trim();
					data.openai_model = $('#openai-model').val();
					break;

				case 3:
					data.target_niche = $('#target-niche').val().trim();
					data.target_audience = $('#target-audience').val().trim();
					data.keywords = [];
					$('.keyword-tag').each(function() {
						const keyword = $(this).text().replace('×', '').trim();
						if (keyword) data.keywords.push(keyword);
					});
					break;

				case 4:
					data.topics = [];
					$('.topic-queue-item').each(function() {
						const topic = $(this).find('.topic-text').text().trim();
						if (topic) data.topics.push(topic);
					});
					break;

				case 5:
					data.auto_publish_enabled = $('#auto-publish-enabled').is(':checked');
					data.articles_per_day = $('#articles-per-day').val();
					data.publish_time = $('#publish-time').val();
					break;

				case 6:
					data.revenue_enabled = $('#revenue-enabled').is(':checked');
					data.adsense_publisher_id = $('#adsense-publisher-id').val().trim();
					data.adsense_strategy = $('#adsense-strategy').val();
					break;
			}

			return data;
		},

		getStepURL(step) {
			return window.location.pathname + '?page=pearblog-wizard-v2&step=' + step;
		},

		showError(message) {
			alert(message);
		},

		showSuccess(message) {
			// Could implement toast notifications here
			console.log('Success:', message);
		},

		// Step 2: Test API Key
		testAPIKey() {
			const apiKey = $('#openai-api-key').val().trim();
			const $button = $('#test-api-key');
			const $result = $('#api-test-result');

			if (apiKey === '') {
				$result.html('<p class="error">Please enter an API key first.</p>');
				return;
			}

			$button.prop('disabled', true).text('Testing...');
			$result.html('<p class="loading">Testing connection to OpenAI...</p>');

			$.ajax({
				url: PearBlogWizard.ajaxUrl,
				method: 'POST',
				data: {
					action: 'pearblog_wizard_test_api_key',
					nonce: PearBlogWizard.nonce,
					api_key: apiKey
				}
			}).done((response) => {
				if (response.success) {
					$result.html('<p class="success">' + response.data.message + '</p>');
				} else {
					$result.html('<p class="error">' + response.data.message + '</p>');
				}
			}).fail(() => {
				$result.html('<p class="error">Network error. Please try again.</p>');
			}).always(() => {
				$button.prop('disabled', false).text('Test Connection');
			});
		},

		// Step 3: Add Keyword
		addKeyword() {
			const keyword = $('#keyword-input').val().trim();

			if (keyword === '') {
				return;
			}

			// Check for duplicates
			let exists = false;
			$('.keyword-tag').each(function() {
				if ($(this).text().replace('×', '').trim().toLowerCase() === keyword.toLowerCase()) {
					exists = true;
				}
			});

			if (exists) {
				this.showError('Keyword already added.');
				return;
			}

			const $tag = $('<span class="keyword-tag">' + this.escapeHtml(keyword) + ' <button type="button" class="remove-keyword">×</button></span>');
			$('#keyword-list').append($tag);
			$('#keyword-input').val('').focus();
		},

		// Step 4: Generate Topics
		generateTopics() {
			const $button = $('#generate-topics-btn');
			const $container = $('#suggested-topics');
			const $list = $('#suggested-topics-list');

			$button.prop('disabled', true).text('Generating...');
			$list.html('<p class="loading">AI is generating topic ideas...</p>');
			$container.show();

			$.ajax({
				url: PearBlogWizard.ajaxUrl,
				method: 'POST',
				data: {
					action: 'pearblog_wizard_generate_topics',
					nonce: PearBlogWizard.nonce
				}
			}).done((response) => {
				if (response.success && response.data.topics) {
					$list.empty();
					response.data.topics.forEach((topic) => {
						const $topic = $('<div class="suggested-topic" data-topic="' + this.escapeHtml(topic) + '">' + this.escapeHtml(topic) + '</div>');
						$list.append($topic);
					});
				} else {
					$list.html('<p class="error">' + (response.data?.message || 'Failed to generate topics') + '</p>');
				}
			}).fail(() => {
				$list.html('<p class="error">Network error. Please try again.</p>');
			}).always(() => {
				$button.prop('disabled', false).text('✨ Generate Topic Ideas with AI');
			});
		},

		// Step 4: Add Topic
		addTopic() {
			const topic = $('#manual-topic').val().trim();
			if (topic === '') {
				return;
			}
			this.addTopicToQueue(topic);
			$('#manual-topic').val('').focus();
		},

		addTopicToQueue(topic) {
			// Check for duplicates
			let exists = false;
			$('.topic-queue-item .topic-text').each(function() {
				if ($(this).text().trim().toLowerCase() === topic.toLowerCase()) {
					exists = true;
				}
			});

			if (exists) {
				return;
			}

			const $item = $('<div class="topic-queue-item"><span class="topic-text">' + this.escapeHtml(topic) + '</span><button type="button" class="remove-topic">Remove</button></div>');
			$('#topic-queue-list').append($item);
			this.updateEmptyState();
		},

		updateEmptyState() {
			const $list = $('#topic-queue-list');
			const $items = $('.topic-queue-item');

			if ($items.length === 0) {
				$list.html('<p class="empty-state">No topics in queue yet. Add topics above.</p>');
			} else {
				$list.find('.empty-state').remove();
			}
		},

		// Finish wizard
		finishWizard() {
			this.saveStepData().then(() => {
				window.location.href = PearBlogWizard.ajaxUrl.replace('admin-ajax.php', 'admin.php?page=pearblog-engine-v7');
			});
		},

		// Skip wizard
		skipWizard() {
			if (!confirm('Are you sure you want to skip the setup wizard? You can configure these settings later in the admin panel.')) {
				return;
			}

			$.ajax({
				url: PearBlogWizard.ajaxUrl,
				method: 'POST',
				data: {
					action: 'pearblog_wizard_skip',
					nonce: PearBlogWizard.nonce
				}
			}).done((response) => {
				if (response.success && response.data.redirect_url) {
					window.location.href = response.data.redirect_url;
				}
			});
		},

		// Utility: Escape HTML
		escapeHtml(text) {
			const map = {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;'
			};
			return text.replace(/[&<>"']/g, (m) => map[m]);
		}
	};

	// Initialize on DOM ready
	$(document).ready(() => {
		Wizard.init();
	});

})(jQuery);
