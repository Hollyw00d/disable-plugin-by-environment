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
 public static function init() {
     $self = new self();
     add_action('init', array($self, PLUGIN_PREFIX . 'deactivate_plugins'));
     add_action('admin_menu', array($self, PLUGIN_PREFIX . 'settings_page'));
     add_action('admin_post_' . PLUGIN_PREFIX . 'save_settings', array($self, PLUGIN_PREFIX . 'save_settings'));
 }

public function dpbe_deactivate_plugins() {
 $options = get_option(PLUGIN_PREFIX . 'plugin_activation_status');
 $deactivated_plugins_keys = isset($options['deactivated_plugins']) ? array_keys($options['deactivated_plugins']) : array();
 $all_plugins = get_plugins();
 $all_plugins_keys = array_keys($all_plugins);

 foreach ($all_plugins_keys as $plugin_key) {
  if (in_array($plugin_key, $deactivated_plugins_keys) && is_plugin_active($plugin_key)) {
      deactivate_plugins($plugin_key, false, is_network_admin());
  } elseif (!in_array($plugin_key, $deactivated_plugins_keys) && !is_plugin_active($plugin_key)) {
      activate_plugin($plugin_key, '', is_network_admin());
  }
  }
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
  if (!current_user_can('manage_options')) {
   return;
  }

  $options = get_option(PLUGIN_PREFIX . 'plugin_activation_status');

  echo '<pre>';
  print_r($options);
  echo '</pre>';
  ?>
  <div class="wrap">
   <h2><?php echo PLUGIN_NAME; ?></h2>

   <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
       <div id="message" class="updated notice notice-success is-dismissible">
           <p><?php _e('Settings saved successfully.'); ?></p>
       </div>
   <?php endif; ?>

   <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post">
    <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
    <input type="hidden" name="action" value="<?php echo PLUGIN_PREFIX . 'save_settings'; ?>">

    <?php 
    wp_nonce_field(PLUGIN_PREFIX . 'save_settings_nonce'); 
    $this->dpbe_environments($options);
    $this->dpbe_plugin_activation_state($options);
    ?>

    <input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e('Save'); ?>" />
   </form>
  </div>
  <?php
 }

 public function dpbe_environments($options) {
  $environments = isset($options['environments']) ? esc_textarea($options['environments']) : ''; // Retrieve saved environments
  ?>
  <h3>Environments Where Plugins will be Deactivated</h3>
  
  <div>
   <p>Please enter:</p>
   <ol>
    <li>A full URL of an environment you want to disable plugins on</li>
    <li>Entering multiple URLs are OK, but please add only one URL per line</li>
    <li>Each URL must have <code>https:</code> or <code>http:</code> in the URL! Examples are below:<br />
    <code>https://example.local/</code><br />
    <code>https://dev.example.com/</code><br />
    <code>https://staging.example.com/</code>
    </li>
   </ol>
  </div>

  <textarea name="<?php echo PLUGIN_PREFIX; ?>environments" id="<?php echo PLUGIN_PREFIX; ?>environments" cols="50" rows="6"><?php echo $environments; ?></textarea>
  <?php
 }

 public function dpbe_plugin_activation_state($options) {
  $all_plugins = get_plugins();
  ?>
   <h3>Deactivated Plugins</h3>
   <div>
    <p>Deactivated plugins have <strong>checkboxes</strong> checked. <strong>Please be careful when using this plugin to NOT deactivate an important plugin!</strong></p>
   </div>
  <?php
  foreach ($all_plugins as $plugin_file => $plugin_data) {
   $checked = isset($options['deactivated_plugins'][$plugin_file]) && $options['deactivated_plugins'][$plugin_file] ? 'checked="checked"' : '';
   echo "<p><label><input id='" . PLUGIN_PREFIX . "plugin_activation_status[deactivated_plugins][" . esc_attr($plugin_file) . "]' name='" . PLUGIN_PREFIX . "plugin_activation_status[deactivated_plugins][" . esc_attr($plugin_file) . "]' type='checkbox' value='1' $checked /> " . esc_html($plugin_data['Name']) . "</label></p>";
  }
 }

 public function dpbe_save_settings() {
  if (!current_user_can('manage_options')) {
      return;
  }

  // Verify nonce
  if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], PLUGIN_PREFIX . 'save_settings_nonce')) {
      wp_die(__('Nonce verification failed.', 'disable-plugin-by-environment'));
  }

  $options = isset($_POST[PLUGIN_PREFIX . 'plugin_activation_status']) ? $_POST[PLUGIN_PREFIX . 'plugin_activation_status'] : array();

  // Sanitize the input
  $environments = isset($_POST[PLUGIN_PREFIX . 'environments']) ? sanitize_textarea_field($_POST[PLUGIN_PREFIX . 'environments']) : '';
  $options['environments'] = $environments;

  if (isset($options['deactivated_plugins'])) {
      foreach ($options['deactivated_plugins'] as $plugin_file => $value) {
          $options['deactivated_plugins'][$plugin_file] = $value ? 1 : 0;
      }
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

Disable_Plugin_By_Env::init();
?>