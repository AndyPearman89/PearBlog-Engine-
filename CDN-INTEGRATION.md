# CDN Integration Guide — PearBlog Engine

> **Version:** 6.0  
> **Purpose:** Configure a CDN to serve static assets and cache HTML pages for PearBlog-powered sites.

---

## Overview

A CDN (Content Delivery Network) dramatically improves PearBlog site performance by:
- Caching static assets (CSS, JS, images) at edge locations worldwide
- Optionally caching full HTML pages (full-page cache)
- Handling SSL termination at the edge
- Absorbing traffic spikes and DDoS attacks

This guide covers three popular CDN options: **Cloudflare**, **AWS CloudFront**, and **BunnyCDN**.

---

## 1. Cloudflare Integration

### Setup

1. **Add your site to Cloudflare:**
   - Create account at [cloudflare.com](https://cloudflare.com)
   - Add site → Update DNS nameservers to Cloudflare's
   - Wait for propagation (up to 48 hrs, usually < 1 hr)

2. **SSL/TLS Settings:**
   - Set mode to **Full (Strict)** (requires valid origin certificate)
   - Enable **Always Use HTTPS** and **Automatic HTTPS Rewrites**

3. **Cache Rules (Page Rules or Cache Rules):**
   ```
   # Cache static assets aggressively
   URL: *yourdomain.com/*.{css,js,woff2,woff,ttf,eot}
   Cache Level: Cache Everything
   Edge Cache TTL: 1 month

   # Cache images
   URL: *yourdomain.com/wp-content/uploads/*
   Cache Level: Cache Everything
   Edge Cache TTL: 7 days

   # Bypass cache for admin and dynamic pages
   URL: *yourdomain.com/wp-admin/*
   Cache Level: Bypass

   URL: *yourdomain.com/wp-login.php
   Cache Level: Bypass
   ```

4. **WordPress Plugin Integration:**
   Install the official **Cloudflare** WordPress plugin:
   ```bash
   wp plugin install cloudflare --activate
   ```
   Configure with your API Token (Zone: Cache Purge permission).

5. **Automatic Cache Purge:**
   Add to `mu-plugins/pearblog-engine/src/Core/Plugin.php`:
   ```php
   add_action( 'save_post', function( $post_id ) {
       if ( function_exists( 'cloudflare_plugin_purge_by_url' ) ) {
           cloudflare_plugin_purge_by_url( get_permalink( $post_id ) );
       }
   }, 10 );
   ```

### Performance Settings (Cloudflare)
- Enable **Auto Minify** for HTML, CSS, JS
- Enable **Brotli** compression
- Enable **Rocket Loader** (test first — may conflict with WP scripts)
- Enable **Polish** (WebP conversion for images) — Pro plan+
- Enable **Mirage** (mobile image optimization) — Pro plan+

### Cost Analysis
| Plan | Price | Features |
|------|-------|---------|
| Free | $0/mo | Basic CDN, 3 Page Rules, WAF (limited) |
| Pro | $20/mo | WAF, image optimization, 20 Page Rules |
| Business | $200/mo | Custom WAF rules, PCI compliance |

---

## 2. AWS CloudFront Integration

### Setup

1. **Create CloudFront Distribution:**
   - Go to AWS CloudFront → Create Distribution
   - **Origin Domain:** `your-wordpress-server.com`
   - **Protocol:** HTTPS only
   - **Origin Shield:** Enable (reduces origin load) — recommended

2. **Cache Behaviors:**
   ```
   # Default behavior (HTML pages)
   Path: /*
   Cache Policy: Custom (TTL: 0–86400 s, forward all cookies: no, forward query strings: whitelist)
   Origin Request Policy: Forward Viewer Protocol, Host header

   # Static assets
   Path: /wp-content/*
   Cache Policy: CachingOptimized (TTL: 86400 s)
   Compress objects: yes

   # WordPress admin bypass
   Path: /wp-admin/*
   Cache Policy: CachingDisabled
   Origin Request Policy: AllViewer
   ```

3. **Custom Domain + ACM Certificate:**
   ```bash
   # Request certificate in ACM (us-east-1 required for CloudFront)
   aws acm request-certificate \
     --domain-name yourdomain.com \
     --validation-method DNS \
     --region us-east-1
   ```

4. **Cache Invalidation after Publish:**
   Add to WordPress (via mu-plugin or functions.php):
   ```php
   add_action( 'save_post', 'pearblog_cloudfront_invalidate' );
   function pearblog_cloudfront_invalidate( int $post_id ): void {
       // Requires AWS SDK or WP Offload Media plugin.
       $cf_distribution_id = get_option( 'pearblog_cloudfront_distribution_id' );
       if ( ! $cf_distribution_id ) return;

       $client = new \Aws\CloudFront\CloudFrontClient([
           'region'  => 'us-east-1',
           'version' => 'latest',
       ]);
       $client->createInvalidation([
           'DistributionId' => $cf_distribution_id,
           'InvalidationBatch' => [
               'Paths' => [ 'Quantity' => 1, 'Items' => [ get_permalink( $post_id ) ] ],
               'CallerReference' => 'pearblog-' . time(),
           ],
       ]);
   }
   ```

5. **WordPress `WP_HOME` / `WP_SITEURL`:**
   In `wp-config.php`:
   ```php
   define( 'WP_HOME',    'https://yourdomain.cloudfront.net' );
   define( 'WP_SITEURL', 'https://yourdomain.cloudfront.net' );
   ```

### Cost Analysis
| Usage (monthly) | Estimated Cost |
|-----------------|---------------|
| 1 TB transfer (US/EU) | ~$85 |
| 10 M requests | ~$8 |
| Origin Shield (1 TB) | ~$10 |
| **Total (moderate site)** | **~$100/mo** |

---

## 3. BunnyCDN Integration

BunnyCDN is the most cost-effective option — ideal for budget-conscious deployments.

### Setup

1. **Create a Pull Zone:**
   - Log in to [bunny.net](https://bunny.net) → CDN → Add Pull Zone
   - **Origin URL:** `https://your-wordpress-server.com`
   - **CDN Hostname:** `yourdomain-cdn.b-cdn.net` (or custom hostname)

2. **Configure Caching:**
   In the Pull Zone settings:
   - **Cache Expiration Time:** 30 days (for assets)
   - **Vary Cache by Cookie:** `wordpress_logged_in_*` (bypass for logged-in users)
   - **Disable Cache for Query Strings:** Off (WordPress uses `?ver=` query strings for assets)
   - **Browser Cache Expiration:** 30 days

3. **Rewrite URLs (WordPress):**
   Install **BunnyCDN** WordPress plugin or add to `functions.php`:
   ```php
   function pearblog_bunny_rewrite( string $url ): string {
       $cdn_hostname = get_option( 'pearblog_bunny_cdn_hostname', '' );
       if ( empty( $cdn_hostname ) ) return $url;

       $wp_uploads_url = wp_get_upload_dir()['baseurl'];
       if ( str_starts_with( $url, $wp_uploads_url ) ) {
           return str_replace( $wp_uploads_url, "https://{$cdn_hostname}", $url );
       }
       return $url;
   }
   add_filter( 'wp_get_attachment_url', 'pearblog_bunny_rewrite' );
   add_filter( 'the_content',           fn( $c ) => pearblog_bunny_rewrite( $c ) );
   ```

4. **Cache Purge after Publish:**
   ```php
   add_action( 'save_post', function( int $post_id ): void {
       $cdn_key      = get_option( 'pearblog_bunny_api_key', '' );
       $pull_zone_id = get_option( 'pearblog_bunny_pull_zone_id', '' );
       if ( ! $cdn_key || ! $pull_zone_id ) return;

       $url = sprintf(
           'https://api.bunny.net/pullzone/%s/purgeCache',
           $pull_zone_id
       );
       wp_remote_post( $url, [
           'headers' => [ 'AccessKey' => $cdn_key, 'Content-Type' => 'application/json' ],
           'body'    => wp_json_encode( [ 'async' => true ] ),
       ] );
   }, 10 );
   ```

### Cost Analysis
| Usage (monthly) | Price |
|-----------------|-------|
| 0–500 GB transfer | $0.005/GB = **$2.50** |
| 500 GB–1 TB | $0.01/GB = **$5** |
| **Typical blog (100 GB/mo)** | **$0.50/mo** |

---

## 4. WordPress Configuration for CDN

### `wp-config.php` additions

```php
// Force HTTPS (required when CDN handles SSL)
define( 'FORCE_SSL_ADMIN', true );

// Detect HTTPS behind CDN/reverse proxy
if ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) &&
     'https' === $_SERVER['HTTP_X_FORWARDED_PROTO'] ) {
    $_SERVER['HTTPS'] = 'on';
}

// Disable concatenation (CDN handles it better)
define( 'CONCATENATE_SCRIPTS', false );
```

### Recommended WordPress Plugins

| Plugin | Purpose |
|--------|---------|
| [W3 Total Cache](https://wordpress.org/plugins/w3-total-cache/) | Full-page cache + CDN integration |
| [WP Super Cache](https://wordpress.org/plugins/wp-super-cache/) | Full-page cache (lighter alternative) |
| [Autoptimize](https://wordpress.org/plugins/autoptimize/) | CSS/JS minification + defer |
| [WP Offload Media](https://wordpress.org/plugins/amazon-s3-and-cloudfront/) | Serve uploads from S3 + CloudFront |
| [Cloudflare](https://wordpress.org/plugins/cloudflare/) | Official Cloudflare WP plugin |

---

## 5. Cache Purging Strategy

### Purge events
| WordPress event | Action |
|-----------------|--------|
| Post published/updated | Purge that URL + home page |
| Menu updated | Purge all pages with that menu |
| Plugin/theme updated | Purge everything |
| New comment approved | Purge that post URL |

### Manual purge (WP-CLI)
```bash
# Purge single URL (Cloudflare plugin required)
wp cf-purge --url=https://yourdomain.com/my-post/

# Purge all (use sparingly)
wp cache flush
```

---

## 6. Performance Comparison

| CDN | Avg global TTFB | Static asset delivery | Cost | Ease of setup |
|-----|-----------------|-----------------------|------|---------------|
| Cloudflare (Free) | 45 ms | ✅ Excellent | Free | ⭐⭐⭐⭐⭐ |
| CloudFront | 38 ms | ✅ Excellent | $$$ | ⭐⭐⭐ |
| BunnyCDN | 52 ms | ✅ Excellent | $ | ⭐⭐⭐⭐ |
| No CDN (origin only) | 85–250 ms | — | — | — |

**Recommendation:** Start with **Cloudflare Free** — zero cost, easy setup, excellent performance. Upgrade to **BunnyCDN** for cost-effective scale or **CloudFront** for AWS-native stacks.

---

## 7. Verifying CDN Is Active

```bash
# Check response headers — CDN should add its own header
curl -I https://yourdomain.com/ | grep -i "cf-cache\|x-cache\|via"

# Cloudflare: CF-Cache-Status: HIT
# CloudFront: X-Cache: Hit from cloudfront
# BunnyCDN: x-cache: HIT
```

If you see `MISS`, wait for the first request to populate the edge cache.
