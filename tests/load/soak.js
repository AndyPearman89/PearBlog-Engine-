/**
 * PearBlog Engine – k6 load test: soak / endurance (2-hour sustained load)
 * Usage: k6 run soak.js --env BASE_URL=https://your-site.com
 */
import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate   = new Rate('error_rate');
const pageLatency = new Trend('page_latency');
const BASE_URL    = __ENV.BASE_URL || 'http://localhost:8080';

export const options = {
  stages: [
    { duration: '5m',   target: 100 },   // ramp-up
    { duration: '110m', target: 100 },   // soak at 100 VUs for ~2 hours
    { duration: '5m',   target: 0 },     // ramp-down
  ],
  thresholds: {
    http_req_duration: ['p(95)<2000'],
    http_req_failed:   ['rate<0.01'],
    error_rate:        ['rate<0.01'],
    page_latency:      ['p(99)<3000'],
  },
};

export default function () {
  // Rotate through several pages to simulate real browsing.
  const pages = ['/', '/?page_id=2', '/?cat=1'];
  const url   = BASE_URL + pages[Math.floor(Math.random() * pages.length)];

  const res = http.get(url);
  check(res, { 'status 200': (r) => r.status === 200 });
  pageLatency.add(res.timings.duration);
  errorRate.add(res.status !== 200);

  sleep(Math.random() * 5 + 1);
}
