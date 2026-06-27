<?php
/**
 * Specific service + city wrapper template.
 *
 * @package PT24
 */

if (! defined('ABSPATH')) {
    exit;
}

global $wp_query;

$specific_service = sanitize_title((string) get_query_var('pt24_specific_service'));
$city_landing = sanitize_title((string) get_query_var('pt24_city_landing'));

$wp_query->query_vars['pt24_category'] = $specific_service;
$wp_query->query_vars['pt24_city'] = $city_landing;

require PT24_DIR . '/local-service-city.php';
