<?php

/**
 * Plugin Name:  Bedrock Composer Store
 * Plugin URI:   https://github.com/CallMeLeon167/bedrock-composer-store
 * Description:  Adds a button to the plugin install screen to add plugins to composer.json.
 * Version:      1.0.0
 * Author:       CallMeLeon167
 * Author URI:   https://github.com/CallMeLeon167/
 * License:      GPL v3 or later
 * License URI:  https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:  bedrock-composer-store
 * Domain Path:  /languages
 * Requires PHP: 8.1
 * Requires at least: 6.0
 * 
 * @package CallMeLeon167\BedrockComposer
 */

namespace CallMeLeon167\BedrockComposer;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Additional security check for Bedrock environments
if (!defined('WP_ENV') || !in_array(WP_ENV, ['development', 'staging',])) {
    return;
}

/**
 * Main plugin class for Bedrock Composer Store
 * 
 * Adds functionality to install WordPress plugins via Composer
 * directly from the WordPress admin plugin install screen.
 */
class Store
{
    /**
     * Plugin version
     */
    public const VERSION = '1.0.0';

    /**
     * Plugin URL
     */
    private string $plugin_url;

    /**
     * Plugin path
     */
    private string $plugin_path;

    /**
     * Composer file path
     */
    private string $composer_path;

    /**
     * Initialize the plugin
     */
    public function __construct()
    {
        $this->plugin_url = plugin_dir_url(__FILE__);
        $this->plugin_path = plugin_dir_path(__FILE__);
        $this->composer_path = realpath(ABSPATH . '../../') . '/composer.json';

        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_ajax_bedrock_composer_install', [$this, 'handle_composer_install']);
        add_action('wp_ajax_bedrock_check_composer', [$this, 'check_composer_status']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);
    }

    /**
     * Load plugin textdomain for translations
     */
    public function load_textdomain(): void
    {
        load_plugin_textdomain(
            'bedrock-composer-store',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Enqueue admin CSS and JavaScript assets
     * 
     * @param string $hook Current admin page hook
     */
    public function enqueue_admin_assets(string $hook): void
    {
        // Only load on plugin install page
        if ($hook !== 'plugin-install.php') {
            return;
        }

        // Enqueue admin CSS
        wp_enqueue_style(
            'bedrock-composer-admin',
            $this->plugin_url . 'assets/css/admin.css',
            [],
            self::VERSION
        );

        // Enqueue admin JavaScript
        wp_enqueue_script(
            'bedrock-composer-admin',
            $this->plugin_url . 'assets/js/admin.js',
            ['jquery'],
            self::VERSION,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('bedrock-composer-admin', 'bedrock_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('bedrock_composer_nonce'),
            'add_text' => __('Add to Composer', 'bedrock-composer-store'),
            'adding_text' => __('Adding...', 'bedrock-composer-store'),
            'added_text' => __('Already in Composer', 'bedrock-composer-store'),
            'error_permission' => __('Permission denied', 'bedrock-composer-store'),
            'error_installation' => __('Installation failed', 'bedrock-composer-store'),
        ]);
    }

    /**
     * Check which plugins are already in composer.json
     * 
     * AJAX handler for checking existing plugins
     */
    public function check_composer_status(): void
    {
        // Verify nonce and permissions
        if (!$this->verify_request()) {
            wp_send_json_error([
                'message' => __('Permission denied', 'bedrock-composer-store')
            ]);
        }

        $slugs = $this->get_plugin_slugs_from_request();
        $existing_plugins = $this->get_existing_plugins($slugs);

        wp_send_json_success([
            'existing_plugins' => $existing_plugins
        ]);
    }

    /**
     * Handle adding a plugin to composer.json
     * 
     * AJAX handler for installing plugins via Composer
     */
    public function handle_composer_install(): void
    {
        // Verify nonce and permissions
        if (!$this->verify_request()) {
            wp_send_json_error([
                'message' => __('Permission denied', 'bedrock-composer-store')
            ]);
        }

        $slug = sanitize_text_field($_POST['plugin_slug'] ?? '');
        $name = sanitize_text_field($_POST['plugin_name'] ?? '');

        if (empty($slug) || empty($name)) {
            wp_send_json_error([
                'message' => __('Invalid plugin data', 'bedrock-composer-store')
            ]);
        }

        try {
            $this->add_plugin_to_composer($slug, $name);
        } catch (\Exception $e) {
            wp_send_json_error([
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add a plugin to composer.json
     * 
     * @param string $slug Plugin slug
     * @param string $name Plugin name
     * @throws \Exception If composer.json operations fail
     */
    private function add_plugin_to_composer(string $slug, string $name): void
    {
        if (!file_exists($this->composer_path)) {
            throw new \Exception(__('composer.json not found', 'bedrock-composer-store'));
        }

        $composer_data = $this->read_composer_file();
        $package = "wpackagist-plugin/{$slug}";

        // Check if plugin already exists
        if (isset($composer_data['require'][$package])) {
            throw new \Exception(__('Plugin already in composer.json', 'bedrock-composer-store'));
        }

        // Get plugin version and add to composer
        $version = $this->get_plugin_version($slug);
        $composer_data['require'][$package] = "^{$version}";

        // Sort packages alphabetically
        ksort($composer_data['require']);

        // Write back to composer.json
        $this->write_composer_file($composer_data);

        wp_send_json_success([
            'message' => sprintf(
                __("Plugin '%s' v%s added to composer.json!", 'bedrock-composer-store'),
                $name,
                $version
            )
        ]);
    }

    /**
     * Get plugin version from WordPress.org API
     * 
     * @param string $slug Plugin slug
     * @return string Plugin version or fallback
     */
    private function get_plugin_version(string $slug): string
    {
        $api_url = "https://api.wordpress.org/plugins/info/1.0/{$slug}.json";
        $response = wp_remote_get($api_url, [
            'timeout' => 10,
            'user-agent' => 'Bedrock Composer Store/' . self::VERSION
        ]);

        if (is_wp_error($response)) {
            return '1.0';
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return $data['version'] ?? '1.0';
    }

    /**
     * Verify AJAX request nonce and permissions
     * 
     * @return bool True if request is valid
     */
    private function verify_request(): bool
    {
        return check_ajax_referer('bedrock_composer_nonce', 'nonce', false)
            && current_user_can('install_plugins');
    }

    /**
     * Get plugin slugs from AJAX request
     * 
     * @return array Sanitized plugin slugs
     */
    private function get_plugin_slugs_from_request(): array
    {
        $slugs = $_POST['plugin_slugs'] ?? [$_POST['plugin_slug'] ?? ''];

        if (!is_array($slugs)) {
            $slugs = [$slugs];
        }

        return array_map('sanitize_text_field', array_filter($slugs));
    }

    /**
     * Get existing plugins from composer.json
     * 
     * @param array $slugs Plugin slugs to check
     * @return array Existing plugin slugs
     */
    private function get_existing_plugins(array $slugs): array
    {
        if (!file_exists($this->composer_path)) {
            return [];
        }

        try {
            $composer_data = $this->read_composer_file();
            $existing_plugins = [];

            foreach ($slugs as $slug) {
                $package = "wpackagist-plugin/{$slug}";
                if (isset($composer_data['require'][$package])) {
                    $existing_plugins[] = $slug;
                }
            }

            return $existing_plugins;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Read and decode composer.json file
     * 
     * @return array Composer data
     * @throws \Exception If file cannot be read or decoded
     */
    private function read_composer_file(): array
    {
        $content = file_get_contents($this->composer_path);

        if ($content === false) {
            throw new \Exception(__('Cannot read composer.json', 'bedrock-composer-store'));
        }

        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \Exception(__('Invalid JSON in composer.json', 'bedrock-composer-store'));
        }

        return $data;
    }

    /**
     * Write composer data to composer.json file
     * 
     * @param array $data Composer data
     * @throws \Exception If file cannot be written
     */
    private function write_composer_file(array $data): void
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new \Exception(__('Cannot encode JSON', 'bedrock-composer-store'));
        }

        $result = file_put_contents($this->composer_path, $json);

        if ($result === false) {
            throw new \Exception(__('Cannot write composer.json', 'bedrock-composer-store'));
        }
    }
}

// Initialize the plugin
new Store();
