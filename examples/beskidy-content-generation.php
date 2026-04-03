<?php
/**
 * Example: Using the Beskidy Travel Content Engine
 *
 * This file demonstrates how to use the new travel content generation system.
 *
 * @package PearBlogEngine\Examples
 */

// ============================================================================
// EXAMPLE 1: Basic Setup for Beskidy Blog
// ============================================================================

// Configure site for Beskidy content
update_option( 'pearblog_industry', 'Beskidy mountains travel and hiking' );
update_option( 'pearblog_language', 'pl' );
update_option( 'pearblog_tone', 'friendly' );
update_option( 'pearblog_monetization', 'affiliate' );
update_option( 'pearblog_publish_rate', 3 );

// Add topics to the queue
use PearBlogEngine\Content\TopicQueue;

$queue = new TopicQueue( get_current_blog_id() );

// Add various topic types
$queue->push( 'Babia Góra szlaki' );              // Hiking trails (informational)
$queue->push( 'Turbacz jak dojechać' );           // Navigation (navigational)
$queue->push( 'Pilsko noclegi w okolicy' );       // Accommodation (transactional)
$queue->push( 'Beskid Wyspowy atrakcje zimą' );   // Seasonal content
$queue->push( 'Szlak Orłowej Perci trudność' );   // Difficulty query

// ============================================================================
// EXAMPLE 2: Manual Content Generation
// ============================================================================

use PearBlogEngine\Pipeline\ContentPipeline;
use PearBlogEngine\Tenant\TenantContext;

// Get tenant context
$context = new TenantContext( get_current_blog_id() );

// Create and run pipeline
$pipeline = new ContentPipeline( $context );
$result = $pipeline->run();

if ( $result ) {
    echo "✓ Published post ID: {$result['post_id']}\n";
    echo "  Topic: {$result['topic']}\n";
    echo "  Status: {$result['status']}\n";
}

// ============================================================================
// EXAMPLE 3: Validate Generated Content
// ============================================================================

use PearBlogEngine\Content\ContentValidator;

// Get post content
$post_id = $result['post_id'];
$content = get_post_field( 'post_content', $post_id );

// Validate as Beskidy content
$validator = new ContentValidator();
$validation = $validator->validate( $content, 'beskidy' );

if ( $validation['valid'] ) {
    echo "✓ Content is valid!\n";
} else {
    echo "✗ Content validation failed:\n";
    echo $validator->format_report( $validation );
}

// ============================================================================
// EXAMPLE 4: Multi-Language Content Generation
// ============================================================================

// Generate Polish version
update_option( 'pearblog_language', 'pl' );
$queue->push( 'Babia Góra szlaki' );
$result_pl = $pipeline->run();
echo "Polish post: {$result_pl['post_id']}\n";

// Generate English version
update_option( 'pearblog_language', 'en' );
$queue->push( 'Babia Gora hiking trails' );
$result_en = $pipeline->run();
echo "English post: {$result_en['post_id']}\n";

// Generate German version
update_option( 'pearblog_language', 'de' );
$queue->push( 'Babia Gora Wanderwege' );
$result_de = $pipeline->run();
echo "German post: {$result_de['post_id']}\n";

// ============================================================================
// EXAMPLE 5: Custom Prompt Builder Selection
// ============================================================================

// Force use of BeskidyPromptBuilder for all content
add_filter( 'pearblog_prompt_builder_class', function( $class, $profile ) {
    return 'PearBlogEngine\Content\BeskidyPromptBuilder';
}, 10, 2 );

// Or enable Beskidy mode conditionally
add_filter( 'pearblog_is_beskidy_content', function( $is_beskidy, $industry, $profile ) {
    // Enable for specific domain
    if ( $_SERVER['HTTP_HOST'] === 'po.beskidzku.pl' ) {
        return true;
    }
    return $is_beskidy;
}, 10, 3 );

// ============================================================================
// EXAMPLE 6: Customize Beskidy Prompt
// ============================================================================

add_filter( 'pearblog_beskidy_prompt', function( $prompt, $topic, $profile ) {
    // Add custom requirement
    $prompt .= "\nDODATKOWE WYMAGANIA:\n";
    $prompt .= "- Zawsze podaj współrzędne GPS\n";
    $prompt .= "- Uwzględnij dostępność dla niepełnosprawnych\n";
    $prompt .= "- Wspomnij o punktach gastronomicznych na trasie\n\n";

    return $prompt;
}, 10, 3 );

// ============================================================================
// EXAMPLE 7: Add Custom Validation Rules
// ============================================================================

add_filter( 'pearblog_content_validated', function( $validation, $content, $type ) {
    // Add custom check for GPS coordinates
    if ( $type === 'beskidy' ) {
        if ( ! preg_match( '/GPS|współrzędne|koordynaty/i', $content ) ) {
            $validation['warnings'][] = 'Consider adding GPS coordinates';
        }
    }

    return $validation;
}, 10, 3 );

// ============================================================================
// EXAMPLE 8: Hook into Pipeline Events
// ============================================================================

// Log when content generation starts
add_action( 'pearblog_pipeline_started', function( $topic, $context ) {
    error_log( "Starting content generation for: {$topic}" );
    error_log( "Profile: {$context->profile->summary()}" );
}, 10, 2 );

// Process content after generation
add_action( 'pearblog_pipeline_completed', function( $post_id, $topic, $context ) {
    error_log( "Content published: Post #{$post_id}" );

    // Validate the content
    $content = get_post_field( 'post_content', $post_id );
    $validator = new ContentValidator();
    $result = $validator->validate( $content, 'beskidy' );

    if ( ! $result['valid'] ) {
        // Flag for manual review
        update_post_meta( $post_id, '_validation_issues', $result['errors'] );
        wp_update_post( [
            'ID' => $post_id,
            'post_status' => 'draft', // Revert to draft if invalid
        ] );
    }
}, 10, 3 );

// ============================================================================
// EXAMPLE 9: Batch Generate Content with Validation
// ============================================================================

function generate_beskidy_content_batch( array $topics ): array {
    $queue = new TopicQueue( get_current_blog_id() );
    $context = new TenantContext( get_current_blog_id() );
    $pipeline = new ContentPipeline( $context );
    $validator = new ContentValidator();

    $results = [];

    // Add all topics to queue
    foreach ( $topics as $topic ) {
        $queue->push( $topic );
    }

    // Process each topic
    while ( $result = $pipeline->run() ) {
        // Validate content
        $content = get_post_field( 'post_content', $result['post_id'] );
        $validation = $validator->validate( $content, 'beskidy' );

        $results[] = [
            'post_id'    => $result['post_id'],
            'topic'      => $result['topic'],
            'valid'      => $validation['valid'],
            'errors'     => $validation['errors'],
            'warnings'   => $validation['warnings'],
        ];
    }

    return $results;
}

// Usage
$topics = [
    'Babia Góra szlaki',
    'Turbacz wycieczka',
    'Pilsko trasy narciarskie',
    'Szlak Orłowej Perci opis',
    'Beskid Wyspowy noclegi',
];

$batch_results = generate_beskidy_content_batch( $topics );

// Report results
foreach ( $batch_results as $result ) {
    $status = $result['valid'] ? '✓' : '✗';
    echo "{$status} {$result['topic']} (Post #{$result['post_id']})\n";

    if ( ! empty( $result['errors'] ) ) {
        foreach ( $result['errors'] as $error ) {
            echo "  ERROR: {$error}\n";
        }
    }

    if ( ! empty( $result['warnings'] ) ) {
        foreach ( $result['warnings'] as $warning ) {
            echo "  WARNING: {$warning}\n";
        }
    }
}

// ============================================================================
// EXAMPLE 10: Create Travel Content for Different Regions
// ============================================================================

// Generic travel content (not Beskidy)
update_option( 'pearblog_industry', 'travel and tourism' );
$queue->push( 'Best beaches in Croatia' );
$pipeline->run(); // Uses TravelPromptBuilder

// Switch back to Beskidy
update_option( 'pearblog_industry', 'Beskidy mountains travel' );
$queue->push( 'Pilsko zimą' );
$pipeline->run(); // Uses BeskidyPromptBuilder

// ============================================================================
// EXAMPLE 11: Debug Builder Selection
// ============================================================================

use PearBlogEngine\Content\PromptBuilderFactory;
use PearBlogEngine\Tenant\SiteProfile;

function debug_builder_selection( string $industry, string $language = 'pl' ): void {
    $profile = new SiteProfile(
        industry: $industry,
        tone: 'friendly',
        monetization: 'affiliate',
        publish_rate: 3,
        language: $language
    );

    $builder = PromptBuilderFactory::create( $profile );
    $class_name = get_class( $builder );

    echo "Industry: {$industry}\n";
    echo "Language: {$language}\n";
    echo "Selected: {$class_name}\n";
    echo str_repeat( '-', 50 ) . "\n";
}

// Test different configurations
debug_builder_selection( 'Beskidy mountains travel', 'pl' );
// Selected: PearBlogEngine\Content\MultiLanguageTravelBuilder

debug_builder_selection( 'Beskidy mountains travel', 'en' );
// Selected: PearBlogEngine\Content\MultiLanguageTravelBuilder

debug_builder_selection( 'travel and tourism', 'pl' );
// Selected: PearBlogEngine\Content\TravelPromptBuilder

debug_builder_selection( 'technology', 'en' );
// Selected: PearBlogEngine\Content\PromptBuilder

// ============================================================================
// EXAMPLE 12: Generate Sample Prompt
// ============================================================================

function show_generated_prompt( string $topic ): void {
    $context = new TenantContext( get_current_blog_id() );
    $builder = PromptBuilderFactory::create( $context->profile );
    $prompt = $builder->build( $topic );

    echo "=== GENERATED PROMPT ===\n";
    echo $prompt;
    echo "\n=== END PROMPT ===\n";
}

// Show what prompt would be generated for a topic
show_generated_prompt( 'Babia Góra szlaki' );
