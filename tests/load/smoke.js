/**
 * PearBlog Engine – k6 load test: smoke (baseline connectivity)
 * Usage: k6 run smoke.js --env BASE_URL=https://your-site.com
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('error_rate');
const BASE_URL  = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  vus: 1,
  duration: '30s',
  thresholds: {
    http_req_duration: ['p(95)<500'],
    error_rate: ['rate<0.01'],
  },
};

export default function () {
  // Home page
  let res = http.get(`${BASE_URL}/`);
  check(res, { 'home 200': (r) => r.status === 200 });
  errorRate.add(res.status !== 200);

  // Health endpoint
  res = http.get(`${BASE_URL}/wp-json/pearblog/v1/health`);
  check(res, {
    'health 200': (r) => r.status === 200,
    'health body has status': (r) => r.json('status') !== undefined,
  });
  errorRate.add(res.status !== 200);

  sleep(1);
}
