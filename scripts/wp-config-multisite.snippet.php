<?php
/**
 * PearBlog Engine — Multisite constants for wp-config.php
 *
 * Manual fallback for `scripts/setup-multisite.sh`. Paste the block below into
 * wp-config.php ABOVE the line:  /* That's all, stop editing! */
 *
 * Step 1 — add only this, then visit Tools → Network Setup in wp-admin:
 *
 *     define( 'WP_ALLOW_MULTISITE', true );
 *
 * Step 2 — after WordPress generates the network, replace the line above with
 * the full block below (subdirectory mode, peartree.pro as the network host):
 */

define( 'WP_ALLOW_MULTISITE', true );
define( 'MULTISITE', true );
define( 'SUBDOMAIN_INSTALL', false );          // subdirectory mode + domain mapping
define( 'DOMAIN_CURRENT_SITE', 'peartree.pro' );
define( 'PATH_CURRENT_SITE', '/' );
define( 'SITE_ID_CURRENT_SITE', 1 );
define( 'BLOG_ID_CURRENT_SITE', 1 );

/**
 * Domain mapping is handled by WordPress core (>= 4.5): each subsite row in the
 * wp_blogs table stores its own apex domain. No sunrise.php / mapping plugin is
 * required. Subsite domains registered by setup-multisite.sh:
 *
 *   poradnik.pro · pt24.pro · elektryk-pt24.pro ·
 *   mucharski.pl · zalew-mucharski.pl · po-beskidzku.pl
 *
 * Each of these must:
 *   - resolve (DNS A/AAAA) to this server,
 *   - be accepted by the web server (ServerAlias / server_name),
 *   - have a valid TLS certificate.
 */
