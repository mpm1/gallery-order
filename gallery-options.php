<?php
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

function create_settings_window(){
	add_menu_page(
		'Order Gallery Settings',
		'Order Gallery Settings',
		'manage_options',
		'order_gallery_settings_page',
		'OrderGallery\options_html'
	);
	
	add_action('admin_init', 'OrderGallery\register_settings');
}
add_action('admin_menu', 'OrderGallery\create_settings_window');

function register_settings(){
	register_setting('order_gallery_settings', 'order_gallery_settings');
	
	add_settings_section('basic_settings', 'Basic Settings', '', 'gallery_options_section');
	add_settings_field('jquery_name', 'JQuery Variable Name', 'OrderGallery\add_text_field', 'gallery_options_section', 'basic_settings', array('id' => 'jquery_name'));
    add_settings_field('tax_percent', 'Tax(%)', 'OrderGallery\add_decimal_field', 'gallery_options_section', 'basic_settings', array('id' => 'tax_percent'));
    register_setting('order_gallery_settings', 'basic_settings');	

    add_settings_section('paypal_settings', 'Paypal Settings', '', 'gallery_options_section');
	add_settings_field('paypal_client_id', 'Client Id', 'OrderGallery\add_text_field', 'gallery_options_section', 'paypal_settings', array('id' => 'paypal_client_id'));
    add_settings_field('paypal_secret', 'Client Secret', 'OrderGallery\add_text_field', 'gallery_options_section', 'paypal_settings', array('id' => 'paypal_secret'));
	register_setting('order_gallery_settings', 'paypal_settings');	
}

function add_decimal_field($args){
    $id = $args['id'];
    $options = get_option('order_gallery_settings');
	$value = isset($options[$id]) == "1" ? $options[$id] : "";
    echo "<input id='{$id}' name='order_gallery_settings[{$id}]' type='number' step='any' value='{$value}' />";
}

function add_text_field($args){
    $id = $args['id'];
    $options = get_option('order_gallery_settings');
	$value = isset($options[$id]) == "1" ? $options[$id] : "";
    echo "<input id='{$id}' name='order_gallery_settings[{$id}]' type='text' value='{$value}' />";
}

function options_html(){
	?>
	<div class="wrap">
		<h2>Order Gallery Settings</h2>
		
		<form method="post" action="options.php">
		<?php settings_fields('order_gallery_settings'); ?>
		<?php do_settings_sections('gallery_options_section'); ?>
		<?php submit_button() ?>
		</form>
	</div>
	<?php
}