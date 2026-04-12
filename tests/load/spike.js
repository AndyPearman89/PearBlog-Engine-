/**
 * PearBlog Engine – k6 load test: spike (sudden 1000-user burst)
 * Usage: k6 run spike.js --env BASE_URL=https://your-site.com
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('error_rate');
const BASE_URL  = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  stages: [
    { duration: '10s', target: 100 },    // baseline
    { duration: '1m',  target: 100 },
    { duration: '10s', target: 1000 },   // spike!
    { duration: '3m',  target: 1000 },   // stay at spike
    { duration: '10s', target: 100 },    // scale back
    { duration: '3m',  target: 100 },    // recovery
    { duration: '10s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.20'],       // allow higher error rate during spike
    error_rate:      ['rate<0.20'],
  },
};

export default function () {
  const res = http.get(`${BASE_URL}/`);
  check(res, { 'status 2xx or 5xx': (r) => r.status < 600 });
  errorRate.add(res.status >= 500);
  sleep(0.5);
}
