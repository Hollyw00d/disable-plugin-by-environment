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
        add_action('admin_menu', array($this, PLUGIN_PREFIX . 'settings_page'));
        add_action('admin_post_' . PLUGIN_PREFIX . 'save_settings', array($this, PLUGIN_PREFIX . 'save_settings'));
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

        $options = get_option(PLUGIN_PREFIX . 'plugin_activation_status');
        ?>
        <div class="wrap">
         <h2><?php echo PLUGIN_NAME; ?></h2>
         <?php
         echo '<pre>';
         echo print_r($options);
         echo '</pre>';
         ?>

         <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
             <div id="message" class="updated notice notice-success is-dismissible">
                 <p><?php _e('Settings saved successfully.'); ?></p>
             </div>
         <?php endif; ?>
         <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
             <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />

             <input type="hidden" name="action" value="<?php echo PLUGIN_PREFIX . 'save_settings'; ?>">
             <?php wp_nonce_field(PLUGIN_PREFIX . 'save_settings_nonce'); ?>
             <?php $this->dpbe_plugin_activation_state($options); ?>

             <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
         </form>
        </div>
        <?php
    }

    public function dpbe_plugin_activation_state($options) {
        $deactivated_plugins_arr = get_plugins();

        foreach ($deactivated_plugins_arr as $plugin_file => $plugin_data) {
            $checked = isset($options['deactivated_plugins'][$plugin_file]) && $options['deactivated_plugins'][$plugin_file] ? 'checked="checked"' : '';
            echo "<p><label><input id='" . PLUGIN_PREFIX . "plugin_activation_status' name='" . PLUGIN_PREFIX . "plugin_activation_status[deactivated_plugins][" . esc_attr($plugin_file) . "]' type='checkbox' value='1' $checked /> " . esc_html($plugin_data['Name']) . "</label></p>";
        }
    }

    public function dpbe_save_settings() {
        if (!current_user_can('manage_options')) return;

        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], PLUGIN_PREFIX . 'save_settings_nonce')) {
            wp_die(__('Nonce verification failed.', 'disable-plugin-by-environment'));
        }

        $options = isset($_POST[PLUGIN_PREFIX . 'plugin_activation_status']) ? $_POST[PLUGIN_PREFIX . 'plugin_activation_status'] : array();

        // Sanitize the input
        foreach ($options['deactivated_plugins'] as $plugin_file => $value) {
            $options['deactivated_plugins'][$plugin_file] = $value ? 1 : 0;
        }

        // Update the options in the database
        update_option(PLUGIN_PREFIX . 'plugin_activation_status', $options);

        // Redirect back to the settings page with a success message
        $redirect_url = add_query_arg(
            array(
                'page' => PLUGIN_SLUG,
                'status' => 'success'
            ),
            admin_url('options-general.php')
        );

        wp_redirect($redirect_url);
        exit;
    }
}

$disable_plugin_by_env = new Disable_Plugin_By_Env();
$disable_plugin_by_env->init();

?>