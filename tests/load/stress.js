/**
 * PearBlog Engine – k6 load test: stress (500 concurrent users)
 * Usage: k6 run stress.js --env BASE_URL=https://your-site.com
 */
import http from 'k6/http';
import { check, group, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('error_rate');
const BASE_URL  = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  stages: [
    { duration: '2m', target: 100 },
    { duration: '2m', target: 200 },
    { duration: '2m', target: 300 },
    { duration: '2m', target: 400 },
    { duration: '2m', target: 500 },
    { duration: '5m', target: 500 },   // peak stress
    { duration: '3m', target: 0 },     // ramp-down
  ],
  thresholds: {
    http_req_duration: ['p(95)<5000'],
    http_req_failed:   ['rate<0.10'],
    error_rate:        ['rate<0.10'],
  },
};

export default function () {
  group('Home page under stress', () => {
    const res = http.get(`${BASE_URL}/`);
    check(res, { 'status 200': (r) => r.status === 200 });
    errorRate.add(res.status !== 200);
  });

  group('REST API under stress', () => {
    const res = http.get(`${BASE_URL}/wp-json/pearblog/v1/health`);
    check(res, { 'health ok': (r) => [200, 503].includes(r.status) });
    errorRate.add(![200, 503].includes(res.status));
  });

  sleep(Math.random() * 2 + 0.5);
}
