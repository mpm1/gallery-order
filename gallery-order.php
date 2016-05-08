<?php
/*
  Plugin Name: Gallery Wordpress Paypal
  Description: A library used to order image prints through paypal.
  Author: Mark McKellar
  Text Domain: gallery-order
 */
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

include 'gallery-options.php';

function add_jquery_variable($var_name){
	$gallery_options = get_options('order_gallery_settings');
	$var_result = empty($gallery_options['jquery_name']) ? '$' : $gallery_options['jquery_name'];
	?>
	var {$var_name} = {$var_result};
	<?php
}

function order_link(){
	
}

function order_link_short($atts, $content = null){
	ob_start();
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;
}
add_shortcode('order_gallery', 'OrderGallery\order_link');