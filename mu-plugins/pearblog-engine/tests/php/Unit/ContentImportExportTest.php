<?php
/**
 * Unit tests for ContentImportExport.
 *
 * @package PearBlogEngine\Tests\Unit
 */

declare(strict_types=1);

namespace PearBlogEngine\Tests\Unit;

use PHPUnit\Framework\TestCase;
use PearBlogEngine\Pipeline\ContentImportExport;
use PearBlogEngine\Content\TopicQueue;

class ContentImportExportTest extends TestCase {

	private ContentImportExport $ie;

	protected function setUp(): void {
		parent::setUp();
		$GLOBALS['_options']          = [];
		$GLOBALS['_transients']       = [];
		$GLOBALS['_post_meta']        = [];
		$GLOBALS['_post_fields']      = [];
		$GLOBALS['_current_user_can'] = true;
		$this->ie = new ContentImportExport();
	}

	protected function tearDown(): void {
		parent::tearDown();
		$GLOBALS['_current_user_can'] = false;
	}

	// -----------------------------------------------------------------------
	// import_topics_csv
	// -----------------------------------------------------------------------

	public function test_csv_import_basic(): void {
		$csv    = "topic\nBest PHP Frameworks\nJavaScript Tips";
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( 2, $result['imported'] );
		$this->assertSame( 0, $result['skipped'] );
		$this->assertEmpty( $result['errors'] );
	}

	public function test_csv_import_with_crlf_line_endings(): void {
		$csv    = "topic\r\nBest PHP Frameworks\r\nJavaScript Tips";
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( 2, $result['imported'] );
	}

	public function test_csv_import_skips_duplicates(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'Best PHP Frameworks' );

		$csv    = "topic\nBest PHP Frameworks\nJavaScript Tips";
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( 1, $result['imported'] );
		$this->assertSame( 1, $result['skipped'] );
	}

	public function test_csv_import_throws_on_missing_topic_header(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->ie->import_topics_csv( "title,description\nFoo,Bar", 1 );
	}

	public function test_csv_import_throws_on_empty_content(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->ie->import_topics_csv( '', 1 );
	}

	public function test_csv_import_with_priority_and_tags_columns(): void {
		$csv    = "topic,priority,tags\nBest PHP Frameworks,high,php,coding\nJavaScript Tips,low,js";
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( 2, $result['imported'] );
	}

	public function test_csv_import_skips_blank_topic_rows(): void {
		$csv    = "topic\nValid Topic\n   \n";
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( 1, $result['imported'] );
	}

	public function test_csv_import_respects_max_batch(): void {
		// Generate MAX_IMPORT_BATCH + 5 topics.
		$over = ContentImportExport::MAX_IMPORT_BATCH + 5;
		$rows = [ 'topic' ];
		for ( $i = 0; $i < $over; $i++ ) {
			$rows[] = "Topic {$i}";
		}
		$csv    = implode( "\n", $rows );
		$result = $this->ie->import_topics_csv( $csv, 1 );
		$this->assertSame( ContentImportExport::MAX_IMPORT_BATCH, $result['imported'] );
	}

	// -----------------------------------------------------------------------
	// import_topics_json
	// -----------------------------------------------------------------------

	public function test_json_import_array_of_strings(): void {
		$json   = json_encode( [ 'PHP Design Patterns', 'React Best Practices' ] );
		$result = $this->ie->import_topics_json( $json, 1 );
		$this->assertSame( 2, $result['imported'] );
		$this->assertEmpty( $result['errors'] );
	}

	public function test_json_import_array_of_objects(): void {
		$json = json_encode( [
			[ 'topic' => 'PHP Design Patterns', 'priority' => 'high' ],
			[ 'topic' => 'React Best Practices' ],
		] );
		$result = $this->ie->import_topics_json( $json, 1 );
		$this->assertSame( 2, $result['imported'] );
	}

	public function test_json_import_throws_on_invalid_json(): void {
		$this->expectException( \InvalidArgumentException::class );
		$this->ie->import_topics_json( '{ not valid json', 1 );
	}

	public function test_json_import_records_error_for_invalid_items(): void {
		$json   = json_encode( [ 'Valid Topic', 12345, null ] );
		$result = $this->ie->import_topics_json( $json, 1 );
		$this->assertSame( 1, $result['imported'] );
		$this->assertNotEmpty( $result['errors'] );
	}

	public function test_json_import_skips_duplicates(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'PHP Design Patterns' );

		$json   = json_encode( [ 'PHP Design Patterns', 'New Topic' ] );
		$result = $this->ie->import_topics_json( $json, 1 );
		$this->assertSame( 1, $result['imported'] );
		$this->assertSame( 1, $result['skipped'] );
	}

	public function test_json_import_dedup_is_case_insensitive(): void {
		$queue = new TopicQueue( 1 );
		$queue->push( 'php design patterns' );

		$json   = json_encode( [ 'PHP Design Patterns' ] );
		$result = $this->ie->import_topics_json( $json, 1 );
		$this->assertSame( 0, $result['imported'] );
		$this->assertSame( 1, $result['skipped'] );
	}

	// -----------------------------------------------------------------------
	// rows_to_csv
	// -----------------------------------------------------------------------

	public function test_rows_to_csv_returns_empty_on_no_rows(): void {
		$this->assertSame( '', $this->ie->rows_to_csv( [] ) );
	}

	public function test_rows_to_csv_includes_header_row(): void {
		$rows = [
			[ 'post_id' => 1, 'title' => 'Hello World', 'status' => 'publish' ],
		];
		$csv = $this->ie->rows_to_csv( $rows );
		$this->assertStringContainsString( 'post_id', $csv );
		$this->assertStringContainsString( 'title', $csv );
	}

	public function test_rows_to_csv_has_utf8_bom(): void {
		$rows = [ [ 'post_id' => 1, 'title' => 'Hello' ] ];
		$csv  = $this->ie->rows_to_csv( $rows );
		$this->assertStringStartsWith( "\xEF\xBB\xBF", $csv );
	}

	public function test_rows_to_csv_escapes_commas_in_values(): void {
		$rows = [
			[ 'post_id' => 1, 'title' => 'Hello, World', 'status' => 'publish' ],
		];
		$csv = $this->ie->rows_to_csv( $rows );
		$this->assertStringContainsString( '"Hello, World"', $csv );
	}

	public function test_rows_to_csv_escapes_quotes_in_values(): void {
		$rows = [
			[ 'post_id' => 1, 'title' => 'He said "hello"', 'status' => 'publish' ],
		];
		$csv = $this->ie->rows_to_csv( $rows );
		$this->assertStringContainsString( '"He said ""hello"""', $csv );
	}

	// -----------------------------------------------------------------------
	// export_articles_csv / export_articles_json
	// -----------------------------------------------------------------------

	public function test_export_articles_csv_returns_string(): void {
		$csv = $this->ie->export_articles_csv( [] );
		$this->assertIsString( $csv );
	}

	public function test_export_articles_json_returns_valid_json(): void {
		$json    = $this->ie->export_articles_json( [] );
		$decoded = json_decode( $json, true );
		$this->assertIsArray( $decoded );
	}

	// -----------------------------------------------------------------------
	// REST: permission callbacks
	// -----------------------------------------------------------------------

	public function test_rest_admin_permission_grants_manage_options(): void {
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->ie->rest_admin_permission( $request ) );
	}

	public function test_rest_permission_grants_manage_options(): void {
		$request = new \WP_REST_Request();
		$this->assertTrue( $this->ie->rest_permission( $request ) );
	}

	public function test_rest_permission_grants_valid_bearer_token(): void {
		$GLOBALS['_current_user_can'] = false;
		update_option( 'pearblog_api_key', 'my-token' );
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'Bearer my-token' );
		$this->assertTrue( $this->ie->rest_permission( $request ) );
	}

	public function test_rest_permission_denies_wrong_token(): void {
		$GLOBALS['_current_user_can'] = false;
		update_option( 'pearblog_api_key', 'correct-token' );
		$request = new \WP_REST_Request();
		$request->set_header( 'authorization', 'Bearer wrong-token' );
		$this->assertFalse( $this->ie->rest_permission( $request ) );
	}

	// -----------------------------------------------------------------------
	// REST: import callback
	// -----------------------------------------------------------------------

	public function test_rest_import_returns_400_when_data_empty(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'csv' );
		$request->set_param( 'data', '' );
		$response = $this->ie->rest_import_topics( $request );
		$this->assertSame( 400, $response->status );
	}

	public function test_rest_import_csv_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'csv' );
		$request->set_param( 'data', "topic\nNew Topic One\nNew Topic Two" );
		$request->set_param( 'site_id', 1 );
		$response = $this->ie->rest_import_topics( $request );
		$this->assertSame( 200, $response->status );
		$this->assertArrayHasKey( 'imported', $response->data );
		$this->assertSame( 2, $response->data['imported'] );
	}

	public function test_rest_import_json_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'json' );
		$request->set_param( 'data', json_encode( [ 'Topic A', 'Topic B' ] ) );
		$request->set_param( 'site_id', 1 );
		$response = $this->ie->rest_import_topics( $request );
		$this->assertSame( 200, $response->status );
		$this->assertSame( 2, $response->data['imported'] );
	}

	public function test_rest_import_bad_csv_returns_422(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'csv' );
		$request->set_param( 'data', "title,description\nFoo,Bar" );
		$response = $this->ie->rest_import_topics( $request );
		$this->assertSame( 422, $response->status );
	}

	// -----------------------------------------------------------------------
	// REST: export callback
	// -----------------------------------------------------------------------

	public function test_rest_export_returns_200(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'csv' );
		$request->set_param( 'limit', 10 );
		$response = $this->ie->rest_export_articles( $request );
		$this->assertSame( 200, $response->status );
		$this->assertArrayHasKey( 'csv', $response->data );
	}

	public function test_rest_export_json_format_returns_articles_key(): void {
		$request = new \WP_REST_Request();
		$request->set_param( 'format', 'json' );
		$request->set_param( 'limit', 10 );
		$response = $this->ie->rest_export_articles( $request );
		$this->assertSame( 200, $response->status );
		$this->assertArrayHasKey( 'articles', $response->data );
	}
}
