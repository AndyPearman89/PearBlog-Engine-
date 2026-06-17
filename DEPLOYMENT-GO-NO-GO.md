# ✅ Deployment Go/No-Go Checklist (Poradnik + PT24)

Use this single sign-off before declaring deployment finished.

## Target topology

- Host: `wordpress2614653.home.pl`
- Poradnik path: `/poradnik`
- PT24 path: `/pt24`

---

## 1) Poradnik checks

- [ ] `https://wordpress2614653.home.pl/poradnik/` returns 200/redirect-to-200
- [ ] `https://wordpress2614653.home.pl/poradnik/wp-admin/` redirects to login/session flow
- [ ] `https://wordpress2614653.home.pl/poradnik/wp-json/` returns 200
- [ ] `https://wordpress2614653.home.pl/poradnik/wp-json/pearblog/v1/health` is registered (`200/401/403`, not `rest_no_route`)
- [ ] Logged-in admin pages render without fatal errors:
  - [ ] `pearblog-enterprise-v8`
  - [ ] `poradnik-rpm-lead-fusion`
  - [ ] `poradnik-ads-layout-pro`
  - [ ] `poradnik-affiliate-copy-generator`
- [ ] One safe settings save persists after refresh
- [ ] One extra generation cycle completed successfully
- [ ] No new critical errors in Poradnik `debug.log`

---

## 2) PT24 checks

- [ ] `https://wordpress2614653.home.pl/pt24/` returns 200/redirect-to-200
- [ ] `https://wordpress2614653.home.pl/pt24/wp-admin/` redirects to login/session flow
- [ ] `https://wordpress2614653.home.pl/pt24/wp-json/` returns 200
- [ ] `https://wordpress2614653.home.pl/pt24/wp-json/pearblog/v1/health` is registered (`200/401/403`, not `rest_no_route`)
- [ ] `https://wordpress2614653.home.pl/pt24/wp-json/pt24/v1/businesses` responds
- [ ] One extra generation cycle completed successfully
- [ ] `wp pt24 stats` runs without fatal errors
- [ ] No new critical errors in PT24 `debug.log`

---

## 3) Monitoring and logs

- [ ] Monitoring widgets visible in authenticated admin session
- [ ] Last deployment run produced no unresolved CI/workflow errors
- [ ] Error logs reviewed after final cycle (Poradnik + PT24)

---

## 4) Final decision

- [ ] **GO** only when every checkbox above is `[x]`
- [ ] If any item is not complete, status remains **NO-GO**
