<?php
/**
 * Plugin Name:     Disable Plugin by Environment
 * Description:     Disable plugins by environment. For example, if you have a user tracking plugin that you want enabled only on the PROD environment website then it can be disabled in other environment websites (STAGING, DEV, LOCAL, etc.).
 * Author:          Matt Jennings
 * Author URI:      https://www.mattjennings.net/
 * Text Domain:     disable-plugin-by-environment
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Disable_Plugin_By_Environment
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Global vars
define('PLUGIN_NAME', 'Disable Plugin by Environment');
define('PLUGIN_SLUG', 'disable-plugin-by-environment');
define('PLUGIN_PREFIX', 'dpbe_');

class Disable_Plugin_By_Env {

    public function init() {
        add_action('admin_init', array($this, PLUGIN_PREFIX . 'register_settings'));
        add_action('admin_menu', array($this, PLUGIN_PREFIX . 'settings_page'));
    }

    public function dpbe_register_settings() {
        // Register the setting and the validation callback
        register_setting(
            PLUGIN_PREFIX . 'example_plugin_options',    // Option group
            PLUGIN_PREFIX . 'example_plugin_options',    // Option name in the database
            array($this, PLUGIN_PREFIX . 'example_plugin_options_validate') // Validation callback
        );

        // Add the settings section
        add_settings_section(
            'api_settings',                    // Section ID
            'API Settings',                    // Title of the section
            array($this, PLUGIN_PREFIX . 'plugin_section_text'),  // Callback to output the description
            PLUGIN_PREFIX . 'example_plugin'               // Page on which the section appears
        );

        // Add the settings field
        add_settings_field(
            PLUGIN_PREFIX . 'plugin_setting_api_key',      // Field ID
            'API Key',                         // Field title
            array($this, PLUGIN_PREFIX . 'plugin_setting_api_key'),  // Callback to output the form field
            PLUGIN_PREFIX . 'example_plugin',              // Page on which the field appears
            'api_settings'                     // Section in which the field appears
        );
    }

    public function dpbe_settings_page() {
        add_options_page(
            PLUGIN_NAME,
            PLUGIN_NAME,
            'manage_options',
            PLUGIN_SLUG,
            array($this, PLUGIN_PREFIX . 'settings_page_html')
        );
    }

    public function dpbe_settings_page_html() {
        if (!current_user_can('manage_options')) return;

        $options = get_option(PLUGIN_PREFIX . 'example_plugin_options');

        print_r($options);
        ?>
        <div class="wrap">


            <h2><?php echo PLUGIN_NAME; ?></h2>
            <form action="options.php" method="post">
                <?php 
                settings_fields(PLUGIN_PREFIX . 'example_plugin_options');
                do_settings_sections(PLUGIN_PREFIX . 'example_plugin');
                ?>
                <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
            </form>
        </div>
        <?php
    }

    public function dpbe_plugin_section_text() {
        echo '<p>Here you can set all the options for using the API.</p>';
    }

    public function dpbe_plugin_setting_api_key() {
        $options = get_option(PLUGIN_PREFIX . 'example_plugin_options');
        $api_key = isset($options['api_key']) ? $options['api_key'] : '';
        echo "<input id='" . PLUGIN_PREFIX . "plugin_setting_api_key' name='"  . PLUGIN_PREFIX .  "example_plugin_options[api_key]' type='text' value='" . esc_attr($api_key) . "' />";
    }

    public function dpbe_example_plugin_options_validate($input) {
        $newinput = array();
        $newinput['api_key'] = trim($input['api_key']);

        // Validate the API key (for example, a 32-character alphanumeric string)
        if (!preg_match('/^[a-z0-9]{4}$/i', $newinput['api_key'])) {
            $newinput['api_key'] = ''; // Clear the input if it's not valid
        }

        return $newinput;
    }
}

$disable_plugin_by_env = new Disable_Plugin_By_Env();
$disable_plugin_by_env->init();

?>