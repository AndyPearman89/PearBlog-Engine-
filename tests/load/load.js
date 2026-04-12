/**
 * PearBlog Engine – k6 load test: average load (100 concurrent users)
 * Usage: k6 run load.js --env BASE_URL=https://your-site.com
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate    = new Rate('error_rate');
const homeLoadTime = new Trend('home_load_time');
const apiLatency   = new Trend('api_latency');

const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  stages: [
    { duration: '2m', target: 100 },   // ramp-up to 100 VUs
    { duration: '5m', target: 100 },   // steady at 100 VUs
    { duration: '2m', target: 0 },     // ramp-down
  ],
  thresholds: {
    http_req_duration:   ['p(95)<2000', 'p(99)<5000'],
    http_req_failed:     ['rate<0.05'],
    error_rate:          ['rate<0.05'],
    home_load_time:      ['p(95)<1500'],
    api_latency:         ['p(95)<500'],
  },
};

export default function () {
  group('Public pages', () => {
    // Home page
    let res = http.get(`${BASE_URL}/`);
    check(res, { 'home 200': (r) => r.status === 200 });
    homeLoadTime.add(res.timings.duration);
    errorRate.add(res.status !== 200);

    // Single post (adjust slug as needed)
    res = http.get(`${BASE_URL}/?p=1`);
    check(res, { 'post 200 or 404': (r) => [200, 404].includes(r.status) });
    homeLoadTime.add(res.timings.duration);

    // Category archive
    res = http.get(`${BASE_URL}/?cat=1`);
    check(res, { 'category page ok': (r) => r.status === 200 });
  });

  group('REST API', () => {
    // Health check
    let res = http.get(`${BASE_URL}/wp-json/pearblog/v1/health`);
    check(res, { 'health 200': (r) => r.status === 200 });
    apiLatency.add(res.timings.duration);
    errorRate.add(res.status !== 200);

    // Performance metrics endpoint
    res = http.get(`${BASE_URL}/wp-json/pearblog/v1/performance/metrics`);
    check(res, { 'metrics 200 or 401': (r) => [200, 401, 403].includes(r.status) });
    apiLatency.add(res.timings.duration);
  });

  sleep(Math.random() * 3 + 1);
}
