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

function order_link_short($atts, $content = null){
	$a = shortcode_atts(array(
		'id' => uniqid(),
		'button_class' => '',
		'button_text' => 'Purchase',
		'popup_class' => '',
		'popup_feilds' => 'Name,Email,Affiliation'
	), $atts, 'order_gallery');
	
	ob_start();
	create_order_link($a);
	$output = ob_get_contents();
	ob_end_clean();
	
	return $output;
}
add_shortcode('order_gallery', 'OrderGallery\order_link_short');

function add_jquery_variable($var_name){
	$gallery_options = get_option('order_gallery_settings');
	$var_result = empty($gallery_options['jquery_name']) ? '$' : $gallery_options['jquery_name'];
	?>
	var <?php echo $var_name ?> = <?php echo $var_result ?>;
	<?php
}

function create_order_button($id, $id_window, $class, $text){
	?>
	<button id='<?php echo $id ?>' class='<?php echo $class ?>'><?php echo $text ?></button>
	<script type="text/javascript">
	{
		<?php add_jquery_variable('og') ?>
		og('#<?php echo $id ?>').click(function(){
			og('#<?php echo $id_window?>').show();
		})
	}
	</script>
	<?php
}

function create_order_window($id, $class){
	$id_modal = $id . '_modal';
	?>
	<div id="<?php echo $id ?>" style="display: none; position: fixed; top: 0; left: 0; down: 0; right: 0; bottom: 0; z-index: 10000; background-color: rgba(0,0,0,0.4);">
		<div id="<?php echo $id_modal ?>" class="<?php echo $class ?>" style="width: 99%; max-width: 400px; margin-top: 10px; padding: 10px; border-radius: 5px; background-color: #fff; margin-left: auto; margin-right: auto;">
		HERE IS STUFF
		</div>
	</div>
	<script type="text/javascript">
		<?php add_jquery_variable('og') ?>
		og('#<?php echo $id ?>').click(function(){
			og('#<?php echo $id?>').hide();
		})
	</script>
	<?php
}

function create_order_link($att){
	$id_window = $att['id'] . '_window';
	create_order_button($att['id'], $id_window, $att['button_class'], $att['button_text']);
	create_order_window($id_window, $att['popup_class']);
}