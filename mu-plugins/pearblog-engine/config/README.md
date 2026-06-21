# PearBlog Engine – wp-config files

This directory contains WordPress configuration snippets required to
launch and operate **PearBlog Engine v8 Enterprise**.

## Files

| File | Purpose |
|------|---------|
| `wp-config-pearblog-v8.php` | Production-ready snippet with safe defaults. Include it from your `wp-config.php` or copy the constants you need. |
| `wp-config-pearblog-v8-sample.php` | Fully commented example showing **all** configurable constants with placeholder values. |

## Quick start

Add one line to your `wp-config.php` (before *"That's all, stop editing!"*):

```php
require_once __DIR__ . '/wp-content/mu-plugins/pearblog-engine/config/wp-config-pearblog-v8.php';
```

That's it – the v8 Enterprise dashboard will be activated automatically.

## Overriding defaults

Every constant in `wp-config-pearblog-v8.php` is guarded by `defined()`,
so you can set any value **before** the `require_once` line in your
`wp-config.php` and it will take precedence.

```php
// Example: enable database logging before loading the snippet
define( 'PEARBLOG_DATABASE_LOGGING', true );
require_once __DIR__ . '/wp-content/mu-plugins/pearblog-engine/config/wp-config-pearblog-v8.php';
```
