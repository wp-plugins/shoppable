<?php
  /*
    Plugin Name: Shoppable.co Frames
    Plugin URI: http://docs.72lux.com/wordpress-installation.html
    Description: Makes it easy to setup Shoppable and display product and cart frames.  <strong>Permalink settings must be changed from default.  For the cart to display properly, your theme must contain Widget Areas.</strong>
    Version: 1.2.4
    Author: 72Lux
    Author URI: http://www.shoppable.com/
  */

// ------------------------------------------------------------------------
// REQUIRE MINIMUM VERSION OF WORDPRESS:
// ------------------------------------------------------------------------
// THIS IS USEFUL IF YOU REQUIRE A MINIMUM VERSION OF WORDPRESS TO RUN YOUR
// PLUGIN. IN THIS PLUGIN THE WP_EDITOR() FUNCTION REQUIRES WORDPRESS 3.3
// OR ABOVE. ANYTHING LESS SHOWS A WARNING AND THE PLUGIN IS DEACTIVATED.
// ------------------------------------------------------------------------

function shple_requires_wordpress_version () {
  global $wp_version;
  $plugin = plugin_basename( __FILE__ );
  $plugin_data = get_plugin_data( __FILE__, false );

  if ( version_compare($wp_version, "3.3", "<" ) ) {
    if( is_plugin_active($plugin) ) {
      deactivate_plugins( $plugin );
      wp_die( "'".$plugin_data['Name']."' requires WordPress 3.3 or higher, and has been deactivated! Please upgrade WordPress and try again.<br /><br />Back to <a href='".admin_url()."'>WordPress admin</a>." );
    }
  }
}
add_action( 'admin_init', 'shple_requires_wordpress_version' );

// ------------------------------------------------------------------------
// PLUGIN PREFIX:
// ------------------------------------------------------------------------
// 'shple_' prefix is derived from Shoppable

// ------------------------------------------------------------------------
// REGISTER HOOKS & CALLBACK FUNCTIONS:
// ------------------------------------------------------------------------

// Set-up Action and Filter Hooks
register_activation_hook( __FILE__, 'shple_add_defaults' );
register_uninstall_hook( __FILE__, 'shple_delete_plugin_options' );

add_action( 'admin_init', 'shple_init' );
add_action( 'admin_menu', 'shple_add_settings_page' );
add_action( 'widgets_init','shple_widget_init' );
add_action('init', 'shple_shortcode_button_init');
add_action('wp_head','shple_inject_shopjs_script', 2);
add_action('wp_head','shple_inject_css', 2);

add_filter( 'plugin_action_links', 'shple_plugin_action_links', 10, 2 );

// --------------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_uninstall_hook(__FILE__, 'shple_delete_plugin_options')
// --------------------------------------------------------------------------------------

// Delete options table entries ONLY when plugin deactivated AND deleted
function shple_delete_plugin_options () {
  delete_option( 'shple_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: register_activation_hook(__FILE__, 'shple_add_defaults')
// ------------------------------------------------------------------------------

// Define default option settings
function shple_add_defaults () {
  $tmp = get_option('shple_options');
  // added the isset() because of unset array keys on activation
  if( isset( $tmp['chk_default_options_db'] ) && ( $tmp['chk_default_options_db']=='1' ) || ( !is_array($tmp) ) ) {
    delete_option('shple_options'); // so we don't have to reset all the 'off' checkboxes too! (don't think this is needed but leave for now)
    $arr = array(
      "token" => "",
      "flag_poweredby" => false,
      "chk_default_options_db" => ""
    );
    update_option('shple_options', $arr);
  }
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_init', 'shple_init' )
// ------------------------------------------------------------------------------

function shple_init (){
  register_setting( 'shple_plugin_options', 'shple_options', 'shple_validate_options' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('widgets_init' , 'shple_widget_init')
// ------------------------------------------------------------------------------

function shple_widget_init (){
  register_widget( 'shoppable_frame_widget' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION FOR: add_action('admin_menu', 'shple_add_settings_page');
// ------------------------------------------------------------------------------

// Add menu page
function shple_add_settings_page() {
  add_options_page( 'Shoppable Frames Configurations Page', 'Shoppable', 'manage_options', 'shoppable', 'shple_render_help_settings' );
}

// ------------------------------------------------------------------------------
// CALLBACK FUNCTION SPECIFIED IN: add_options_page()
// ------------------------------------------------------------------------------
function shple_render_help_settings () {
  echo '<div class="icon32" id="icon-options-general"><br></div>';
  echo '<h2>Shoppable Frames Settings Menu</h2>';

  $currentTab = ( isset( $_GET['tab'] ) ) ? $_GET['tab'] : 'settings';
  shple_render_admin_tabs($currentTab);

  echo '<div class="wrap">';
  switch($currentTab) {
    case 'help':
      echo shple_render_helper_copy();
    break;

    case 'settings':
    default:
      echo shple_render_form();
    break;
  }
  echo '</div>';
}

// render helper copy
function shple_render_helper_copy () {
?>

  <h3>Requirements</h3>
  <ul>
    <li>Non-default Permalink settings.</li>
    <li>Widget areas for shopping bag placement.</li>
    <li>Your Shoppable Token.</li>
  </ul>

  <h3>How can I change my Permalink settings?</h3>
  <p>Check steps #7 and #8 in our <a href="http://docs.72lux.com/wordpress-installation.html" target="_blank">WordPress Integration Docs</a>.</p>

  <h3>How do I place my shopping bag?</h3>
  <p>You can place the shopping bag as a widget. Navigate to your <strong>Widgets</strong> page and place the Shoppable Shopping Bag in any widget area.</p>

  <h3>My theme contains no widget areas for the shopping bag.</h3>
  <p>In these cases, a publisher will have to manually edit the theme files and include the proper Shopping Bag HTML.</p>
  <p>Please refer to the Shoppable Documentation about <a href="http://docs.72lux.com/shopping-bag-link.html" target="_blank">Adding a Shopping Bag Link</a></p>

  <h3>Where can I find my Shoppable Token?</h3>
  <p>They can be located <a href="https://home.shoppable.co/site_settings/tokens">here</a> when viewing your Shoppable Settings.</p>

  <h3>More resources</h3>
  <ul>
    <li><a href="https://home.shoppable.co" target="_blank">Shoppable Home</a></li>
    <li><a href="http://docs.72lux.com" target="_blank">Shoppable Documentation</a></li>
    <li><a href="http://docs.72lux.com/wordpress-installation.html" target="_blank">WordPress Integration Documentation</a></li>
  </ul>
<?php
}

// render the form
function shple_render_form () {
  $options = get_option('shple_options');
  ?>

    <form method="post" action="options.php">
      <? settings_fields('shple_plugin_options'); ?>
      <table class="form-table">
        <tr>
          <th scope="row">Paste your token here.</th>
          <td>
            <input style="width:240px;" name="shple_options[token]" type="text" value="<?php echo $options['token'] ?>" />
            <br /><span style="color:#666666;margin-left:2px;">You can retrieve your API Token <a target="_blank" href="https://home.shoppable.co/site_settings/tokens">here</a></span>
          </td>
        </tr>
        <tr>
          <th scope="row">Display a PoweredBy logo.</th>
          <td>
            <input name="shple_options[flag_poweredby]" type="checkbox" <? echo $options['flag_poweredby'] ? 'checked="checked"' : ''; ?> value="1" />
          </td>
        </tr>
      </table>
      <p class="submit">
      <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
      </p>
    </form>

  <?php
}

// Sanitize and validate input. Accepts an array, return a sanitized array.
function shple_validate_options ( $input ) {
  return $input;
}

// Display a Settings link on the main Plugins page
function shple_plugin_action_links ( $links, $file ) {

  if ( $file == plugin_basename( __FILE__ ) ) {
    $shple_links = '<a href="'.get_admin_url().'options-general.php?page=shoppable">'.__('Settings').'</a>';
    // make the 'Settings' link appear first
    array_unshift( $links, $shple_links );
  }

  return $links;
}

// setup Help section tabs
function shple_render_admin_tabs ( $current = 'settings' ) {
  $tabs = array(
    'settings' => 'Settings',
    'help' => 'Help'
    );

  echo '<h2 class="nav-tab-wrapper">';

  foreach( $tabs as $tab => $name ) {
    $class = ( $tab === $current ) ? ' nav-tab-active' : '';
    echo '<a class="nav-tab'.$class.'"" href="'.get_admin_url().'options-general.php?page=shoppable/shoppable.php&tab='.$tab.'">'.$name.'</a>';
  }

  echo '</h2>';
}

// ------------------------------------------------------------------------------
// Adding custom buttons to add/edit post field
// ------------------------------------------------------------------------------

// init process for registering our button
function shple_shortcode_button_init() {
  //Abort early if the user will never see TinyMCE
  if ( ! current_user_can('edit_posts') && ! current_user_can('edit_pages') && get_user_option('rich_editing') == 'true')
    return;

  //Add a callback to regiser our tinymce plugin
  add_filter("mce_external_plugins", "shple_register_tinymce_plugin");

  // Add a callback to add our button to the TinyMCE toolbar
  add_filter('mce_buttons', 'shple_add_tinymce_button');
}


//This callback registers our plug-in
function shple_register_tinymce_plugin($plugin_array) {
  # JOE: Not sure if this is the best way to do dis
  $plugin_array['shple_button'] = plugin_dir_url('') . 'shoppable/assets/js/shortcode.js';
  return $plugin_array;
}

//This callback adds our button to the toolbar
function shple_add_tinymce_button($buttons) {
  //Add the button ID to the $button array
  $buttons[] = "shple_button";
  return $buttons;
}


// ------------------------------------------------------------------------------
// Shortcodes:
// ------------------------------------------------------------------------------

function shple_shoppable_frame_shortcode($atts) {
  $options = get_option('shple_options');
  $checked = $options['flag_poweredby'] ? ' data-show-powered-by="1" ' : 'data-show-powered-by="0"';
  if (isset($atts['id'])) {
    return '<div class="lux-product-frame" data-hosted="1" data-id="' . $atts['id'] . '"'. $checked .'></div>';
  }
  else {
    return '<div class="lux-product-frame" data-hosted="1"' . $checked . '></div>';
  }
}
add_shortcode( 'shoppable_frame', 'shple_shoppable_frame_shortcode' );


// ------------------------------------------------------------------------------
// Hooks to inject proper Javascript and CSS assets:
// ------------------------------------------------------------------------------

// Inject frame embed script into head for shopping bag/product frame use
function shple_inject_shopjs_script () {
  if(!is_admin()) {
    $options = get_option('shple_options');
    $src = plugin_dir_url('') . 'shoppable/assets/js/shop.min.js';
    $string = "<script id='lux-shop-loader' src='" . $src . "?token=" . $options['token'] . "'></script>";
    echo $string;
  }
}

// inject styles
function shple_inject_css() {
//  wp_enqueue_style('shoppable_styles', plugin_dir_url('') . 'shoppable/assets/css/shop.css');
  wp_enqueue_style('frame_responsive_styles', plugin_dir_url('') . 'shoppable/assets/css/frame.responsive.css');

}


// ------------------------------------------------------------------------------
// Plugin Widget Class
// ------------------------------------------------------------------------------

class shoppable_frame_widget extends WP_Widget{
  function shoppable_frame_widget() {
    parent::WP_Widget(false, $name = 'Shoppable shopping bag');
  }

  function widget($args, $instance) {
    $shopping_bag_title = isset( $instance['shopping_bag_title'] ) ? $instance['shopping_bag_title'] : 'SHOPPING BAG';

    echo "\n<aside class=\"widget shopping-cart\">";
    echo "\n\t<a href=\"#\" class=\"lux-cart-frame\" data-hosted=\"1\">" . $shopping_bag_title . "</a> <span class=\"lux-cart-label\"></span>";
    echo "\n</aside>";
  }

  function update ( $new_instance, $old_instance ) {
    $isntance = $old_instance;
    $instance['shopping_bag_title'] = $new_instance['shopping_bag_title'];
    return $instance;
  }

  function form ( $instance ) {
    $shopping_bag_title = isset( $instance['shopping_bag_title'] ) ? $instance['shopping_bag_title'] : 'SHOPPING BAG';
    ?>
      <p>
        <label for="<?php echo $this->get_field_id('shopping_bag_title'); ?>"><?php _e('Cart Title:'); ?></label>
        <input type="text" name="<?php echo $this->get_field_name('shopping_bag_title'); ?>" value="<?php echo $shopping_bag_title; ?>" />
      </p>
    <?php
  }

}
