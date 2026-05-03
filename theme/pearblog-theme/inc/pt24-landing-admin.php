<?php
/**
 * PT24 Landing Generator Admin Page
 *
 * Admin interface for bulk generation and management
 *
 * @package PearBlog
 * @version 2.0.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * PT24 Landing Admin Class
 */
class PearBlog_PT24_Landing_Admin {

    /**
     * Initialize
     */
    public static function init() {
        add_action('admin_menu', [__CLASS__, 'add_admin_page']);
        add_action('admin_post_pt24_bulk_generate', [__CLASS__, 'handle_bulk_generate']);
    }

    /**
     * Add admin page
     */
    public static function add_admin_page() {
        add_submenu_page(
            'edit.php?post_type=pt24_landing',
            'PT24 Generator',
            'Generator',
            'manage_options',
            'pt24-generator',
            [__CLASS__, 'render_admin_page']
        );
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        $services = PearBlog_PT24_Landing_CPT::get_services();
        $cities = PearBlog_PT24_Landing_CPT::get_cities();

        // Get counts
        $total_posts = wp_count_posts('pt24_landing');
        $published_count = $total_posts->publish ?? 0;

        ?>
        <div class="wrap">
            <h1>PT24 Landing Page Generator</h1>

            <?php if (isset($_GET['generated'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p><strong>Success!</strong> Generated <?php echo intval($_GET['generated']); ?> landing pages.</p>
                </div>
            <?php endif; ?>

            <div class="card" style="max-width: 800px;">
                <h2>Bulk Generation</h2>
                <p>Generate landing pages for all service/city combinations automatically.</p>

                <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                    <?php wp_nonce_field('pt24_bulk_generate', 'pt24_nonce'); ?>
                    <input type="hidden" name="action" value="pt24_bulk_generate">

                    <table class="form-table">
                        <tr>
                            <th scope="row">Services</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="select_all_services" checked>
                                    <strong>Select All (<?php echo count($services); ?>)</strong>
                                </label>
                                <br><br>
                                <?php foreach ($services as $slug => $name): ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" name="services[]" value="<?php echo esc_attr($slug); ?>" class="service-checkbox" checked>
                                        <?php echo esc_html($name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Cities</th>
                            <td>
                                <label>
                                    <input type="checkbox" id="select_all_cities" checked>
                                    <strong>Select All (<?php echo count($cities); ?>)</strong>
                                </label>
                                <br><br>
                                <?php foreach ($cities as $slug => $name): ?>
                                    <label style="display: block; margin-bottom: 5px;">
                                        <input type="checkbox" name="cities[]" value="<?php echo esc_attr($slug); ?>" class="city-checkbox" checked>
                                        <?php echo esc_html($name); ?>
                                    </label>
                                <?php endforeach; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Total Pages</th>
                            <td>
                                <p class="description">
                                    <strong id="total_combinations"><?php echo count($services) * count($cities); ?></strong> landing pages will be generated.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <?php submit_button('Generate Landing Pages', 'primary', 'submit', true, ['onclick' => 'return confirm("Generate landing pages for selected combinations?");']); ?>
                </form>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>Statistics</h2>
                <table class="widefat">
                    <tr>
                        <td><strong>Published Landing Pages:</strong></td>
                        <td><?php echo $published_count; ?></td>
                    </tr>
                    <tr>
                        <td><strong>Available Services:</strong></td>
                        <td><?php echo count($services); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Available Cities:</strong></td>
                        <td><?php echo count($cities); ?></td>
                    </tr>
                    <tr>
                        <td><strong>Max Combinations:</strong></td>
                        <td><?php echo count($services) * count($cities); ?></td>
                    </tr>
                </table>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>WP-CLI Commands</h2>
                <p>For bulk operations, use WP-CLI for better performance:</p>
                <pre style="background: #f1f1f1; padding: 15px; border-radius: 4px; overflow-x: auto;">
# Generate all landing pages
wp pt24 generate

# Generate specific combinations
wp pt24 generate --services=hydraulik,elektryk --cities=krakow,warszawa

# Import from CSV
wp pt24 import landings.csv

# List all landing pages
wp pt24 list

# Delete all landing pages
wp pt24 delete-all

# Flush rewrite rules
wp pt24 flush-rewrites
                </pre>
            </div>

            <div class="card" style="max-width: 800px; margin-top: 20px;">
                <h2>CSV Import Format</h2>
                <p>Create a CSV file with the following format:</p>
                <pre style="background: #f1f1f1; padding: 15px; border-radius: 4px;">service,city
hydraulik,krakow
elektryk,warszawa
pompa-ciepla,wroclaw</pre>
                <p>Then import via WP-CLI: <code>wp pt24 import your-file.csv</code></p>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            function updateTotal() {
                var serviceCount = $('.service-checkbox:checked').length;
                var cityCount = $('.city-checkbox:checked').length;
                $('#total_combinations').text(serviceCount * cityCount);
            }

            $('#select_all_services').change(function() {
                $('.service-checkbox').prop('checked', this.checked);
                updateTotal();
            });

            $('#select_all_cities').change(function() {
                $('.city-checkbox').prop('checked', this.checked);
                updateTotal();
            });

            $('.service-checkbox, .city-checkbox').change(function() {
                updateTotal();
            });
        });
        </script>
        <?php
    }

    /**
     * Handle bulk generation
     */
    public static function handle_bulk_generate() {
        // Verify nonce
        if (!isset($_POST['pt24_nonce']) || !wp_verify_nonce($_POST['pt24_nonce'], 'pt24_bulk_generate')) {
            wp_die('Security check failed');
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $services = $_POST['services'] ?? [];
        $cities = $_POST['cities'] ?? [];

        if (empty($services) || empty($cities)) {
            wp_redirect(admin_url('edit.php?post_type=pt24_landing&page=pt24-generator&error=1'));
            exit;
        }

        // Generate
        $result = PearBlog_PT24_Landing_CPT::bulk_generate($services, $cities);

        // Flush rewrite rules
        flush_rewrite_rules();

        // Redirect with success message
        wp_redirect(admin_url('edit.php?post_type=pt24_landing&page=pt24-generator&generated=' . $result['total']));
        exit;
    }
}

// Initialize
add_action('admin_init', ['PearBlog_PT24_Landing_Admin', 'init']);
