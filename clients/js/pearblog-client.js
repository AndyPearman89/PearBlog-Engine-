/**
 * PearBlog Engine JavaScript API Client
 * Version: 1.0.0
 *
 * Usage:
 *   import { PearBlogClient } from './pearblog-client.js';
 *   const client = new PearBlogClient('https://your-site.com', 'your-api-key');
 *   const health = await client.health();
 */

'use strict';

export class PearBlogClient {
  /**
   * @param {string} baseUrl  - WordPress site URL (e.g., 'https://example.com')
   * @param {string} apiKey   - PearBlog REST API key (from WP admin → Settings → API)
   * @param {Object} [options]
   * @param {number} [options.timeout=30000] - Request timeout in ms
   */
  constructor(baseUrl, apiKey, options = {}) {
    this.baseUrl  = baseUrl.replace(/\/$/, '');
    this.apiKey   = apiKey;
    this.timeout  = options.timeout ?? 30_000;
    this.namespace = '/wp-json/pearblog/v1';
  }

  // ---------------------------------------------------------------------------
  // Health
  // ---------------------------------------------------------------------------

  /**
   * GET /pearblog/v1/health
   * @returns {Promise<Object>} Health status object
   */
  async health() {
    return this._get('/health');
  }

  // ---------------------------------------------------------------------------
  // Topics
  // ---------------------------------------------------------------------------

  /**
   * GET /pearblog/v1/topics
   * @returns {Promise<string[]>} Array of queued topics
   */
  async getTopics() {
    return this._get('/topics');
  }

  /**
   * POST /pearblog/v1/topics
   * @param {string|string[]} topics - One or more topic strings
   * @returns {Promise<Object>} Created topic(s)
   */
  async addTopics(topics) {
    const payload = Array.isArray(topics) ? topics : [topics];
    return this._post('/topics', { topics: payload });
  }

  /**
   * DELETE /pearblog/v1/topics
   * Clear the entire topic queue.
   * @returns {Promise<Object>}
   */
  async clearTopics() {
    return this._delete('/topics');
  }

  // ---------------------------------------------------------------------------
  // Webhooks
  // ---------------------------------------------------------------------------

  /**
   * GET /pearblog/v1/webhooks
   * @returns {Promise<Array>} List of registered webhooks
   */
  async getWebhooks() {
    return this._get('/webhooks');
  }

  /**
   * POST /pearblog/v1/webhooks
   * @param {string}   url    - Endpoint URL
   * @param {string[]} events - Array of event names
   * @param {string}   [secret] - HMAC signing secret (optional)
   * @returns {Promise<Object>} Created webhook
   */
  async createWebhook(url, events, secret = '') {
    return this._post('/webhooks', { url, events, secret });
  }

  /**
   * DELETE /pearblog/v1/webhooks/{id}
   * @param {number|string} id
   * @returns {Promise<Object>}
   */
  async deleteWebhook(id) {
    return this._delete(`/webhooks/${id}`);
  }

  // ---------------------------------------------------------------------------
  // Content Calendar
  // ---------------------------------------------------------------------------

  /**
   * GET /pearblog/v1/calendar
   * @param {Object} [params] - Optional filters (e.g., { month: '2026-05' })
   * @returns {Promise<Array>} Calendar entries
   */
  async getCalendar(params = {}) {
    const qs = new URLSearchParams(params).toString();
    return this._get(`/calendar${qs ? '?' + qs : ''}`);
  }

  /**
   * POST /pearblog/v1/calendar
   * @param {string} date   - ISO date string 'YYYY-MM-DD'
   * @param {string} topic  - Topic string
   * @returns {Promise<Object>} Created calendar entry
   */
  async schedulePost(date, topic) {
    return this._post('/calendar', { date, topic });
  }

  /**
   * DELETE /pearblog/v1/calendar/{date}
   * @param {string} date - ISO date string 'YYYY-MM-DD'
   * @returns {Promise<Object>}
   */
  async removeScheduledPost(date) {
    return this._delete(`/calendar/${date}`);
  }

  // ---------------------------------------------------------------------------
  // Performance Metrics
  // ---------------------------------------------------------------------------

  /**
   * GET /pearblog/v1/performance/metrics
   * @returns {Promise<Object>} Recent pipeline performance metrics
   */
  async getMetrics() {
    return this._get('/performance/metrics');
  }

  // ---------------------------------------------------------------------------
  // Private HTTP helpers
  // ---------------------------------------------------------------------------

  _headers() {
    return {
      'Content-Type': 'application/json',
      'X-PearBlog-API-Key': this.apiKey,
    };
  }

  async _request(method, path, body = null) {
    const url        = `${this.baseUrl}${this.namespace}${path}`;
    const controller = new AbortController();
    const timerId    = setTimeout(() => controller.abort(), this.timeout);

    try {
      const opts = {
        method,
        headers: this._headers(),
        signal: controller.signal,
      };
      if (body !== null) {
        opts.body = JSON.stringify(body);
      }

      const response = await fetch(url, opts);
      const text     = await response.text();
      let data;
      try {
        data = JSON.parse(text);
      } catch {
        data = text;
      }

      if (!response.ok) {
        const err = new Error(
          `PearBlog API error ${response.status}: ${data?.message ?? text}`
        );
        err.status     = response.status;
        err.statusText = response.statusText;
        err.data       = data;
        throw err;
      }

      return data;
    } finally {
      clearTimeout(timerId);
    }
  }

  _get(path)          { return this._request('GET',    path); }
  _post(path, body)   { return this._request('POST',   path, body); }
  _delete(path)       { return this._request('DELETE', path); }
}

export default PearBlogClient;
