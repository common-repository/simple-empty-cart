<?php
/*
Plugin Name: Simple Empty Cart
Description: Remove all items from the cart page with a single click - No bloat & Zero Impact code 🚀
Version: 1.1.2
Author: UX Heart
Author URL: http://uxheart.com
License: GPL3
Text Domain: simple-empty-cart
Domain Path: /languages
*/

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Load plugin textdomain
function uxh_sec_load_textdomain() {
    load_plugin_textdomain( 'simple-empty-cart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'plugins_loaded', 'uxh_sec_load_textdomain' );

// Add the settings page
function uxh_sec_add_settings_page() {
    add_options_page(
        __( 'Simple Empty Cart Settings', 'simple-empty-cart' ), 
        __( 'Simple Empty Cart', 'simple-empty-cart' ), 
        'manage_options', 
        'uxh_sec-settings', 
        'uxh_sec_render_settings_page'
    );
}
add_action( 'admin_menu', 'uxh_sec_add_settings_page' );

// Render the settings page
function uxh_sec_render_settings_page() {
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'Simple Empty Cart Settings', 'simple-empty-cart' ); ?></h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'uxh_sec_settings_group' );
            do_settings_sections( 'uxh_sec-settings' );
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// Register and define the settings
function uxh_sec_register_settings() {
    register_setting( 'uxh_sec_settings_group', 'uxh_sec_settings', 'uxh_sec_sanitize_settings' );

    add_settings_section(
        'uxh_sec_settings_section',
        __( 'Settings', 'simple-empty-cart' ),
        'uxh_sec_settings_section_callback',
        'uxh_sec-settings'
    );

    add_settings_field(
        'uxh_sec_hide_button_cart_page',
        __( 'Hide button in cart page', 'simple-empty-cart' ),
        'uxh_sec_checkbox_callback',
        'uxh_sec-settings',
        'uxh_sec_settings_section',
        array(
            'label_for' => 'uxh_sec_hide_button_cart_page',
            'name' => 'uxh_sec_hide_button_cart_page'
        )
    );

    add_settings_field(
        'uxh_sec_hide_button_mini_cart',
        __( 'Hide button in mini cart', 'simple-empty-cart' ),
        'uxh_sec_checkbox_callback',
        'uxh_sec-settings',
        'uxh_sec_settings_section',
        array(
            'label_for' => 'uxh_sec_hide_button_mini_cart',
            'name' => 'uxh_sec_hide_button_mini_cart'
        )
    );

    add_settings_field(
        'uxh_sec_admin_only_button_mini_cart',
        __( 'Make button in mini cart only visible to admins', 'simple-empty-cart' ),
        'uxh_sec_checkbox_callback',
        'uxh_sec-settings',
        'uxh_sec_settings_section',
        array(
            'label_for' => 'uxh_sec_admin_only_button_mini_cart',
            'name' => 'uxh_sec_admin_only_button_mini_cart'
        )
    );
}
add_action( 'admin_init', 'uxh_sec_register_settings' );

// Sanitize settings
function uxh_sec_sanitize_settings( $input ) {
    $new_input = array();
    foreach ($input as $key => $value) {
        $new_input[$key] = isset($value) ? absint($value) : '';
    }
    return $new_input;
}

// Section callback
function uxh_sec_settings_section_callback() {
    echo '<p>' . esc_html__( 'Configure the Simple Empty Cart plugin settings below.', 'simple-empty-cart' ) . '</p>';
}

// Checkbox callback
function uxh_sec_checkbox_callback($args) {
    $options = get_option('uxh_sec_settings');
    $checked = isset($options[$args['name']]) ? checked($options[$args['name']], 1, false) : '';
    echo '<input type="checkbox" id="' . esc_attr($args['name']) . '" name="uxh_sec_settings[' . esc_attr($args['name']) . ']" value="1" ' . esc_attr($checked) . ' />';
}

// Add "Empty Cart" button to the cart page
function uxh_sec_add_empty_cart_button() {
    $options = get_option('uxh_sec_settings');
    if (isset($options['uxh_sec_hide_button_cart_page']) && $options['uxh_sec_hide_button_cart_page']) {
        return;
    }
    echo '<button type="submit" name="uxh_sec_empty_cart" class="button">' . esc_html__( 'Empty Cart', 'simple-empty-cart' ) . '</button>';
    wp_nonce_field( 'uxh_sec_empty_cart_action', 'uxh_sec_empty_cart_nonce' );
}
add_action( 'woocommerce_cart_actions', 'uxh_sec_add_empty_cart_button' );

// Add "Empty Cart" button to the mini cart (slide-in cart)
function uxh_sec_add_empty_cart_button_to_mini_cart() {
    $options = get_option('uxh_sec_settings');
    if (isset($options['uxh_sec_hide_button_mini_cart']) && $options['uxh_sec_hide_button_mini_cart']) {
        return;
    }
    if (isset($options['uxh_sec_admin_only_button_mini_cart']) && $options['uxh_sec_admin_only_button_mini_cart'] && !current_user_can('administrator')) {
        return;
    }
    echo '<div class="uxh_sec-mini-cart-empty-cart">
            <form action="" method="post">
                <button type="submit" name="uxh_sec_empty_cart" class="button" style="margin-top: 10px; width:100%;">' . esc_html__( 'Empty Cart', 'simple-empty-cart' ) . '</button>';
    wp_nonce_field( 'uxh_sec_empty_cart_action', 'uxh_sec_empty_cart_nonce' );
    echo '</form>
          </div>';
}
add_action( 'woocommerce_after_mini_cart', 'uxh_sec_add_empty_cart_button_to_mini_cart', 20 ); // Place after mini cart contents

// Handle the empty cart button click
function uxh_sec_empty_cart() {
    if ( isset( $_POST['uxh_sec_empty_cart'] ) && isset( $_POST['uxh_sec_empty_cart_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['uxh_sec_empty_cart_nonce'] ) ), 'uxh_sec_empty_cart_action' ) ) {
        WC()->cart->empty_cart();
    }
}
add_action( 'init', 'uxh_sec_empty_cart' );
?>