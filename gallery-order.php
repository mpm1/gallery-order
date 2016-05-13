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
include 'gallery-post.php';
include 'gallery-paypal.php';

function order_link_short($atts, $content = null){
	$a = shortcode_atts(array(
		'id' => uniqid(),
        'order' => 0,
		'button_class' => '',
		'button_text' => 'Purchase',
		'popup_class' => '',
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

function create_order_window($order_post, $id, $class){
    $price = get_post_meta($order_post->ID, '_go_price', true);
    $fields = explode("\n", get_post_meta($order_post->ID, "_go_fields", true));

	$id_modal = $id . '_modal';
	?>
	<div id="<?php echo $id ?>" style="display: none; position: fixed; top: 0; left: 0; bottom: 0; right: 0; bottom: 0; z-index: 10000; background-color: rgba(0,0,0,0.4);">
		<div id="<?php echo $id_modal ?>" class="<?php echo $class ?>" style="width: 99%; max-width: 400px; margin-top: 10px; padding: 10px; border-radius: 5px; background-color: #fff; margin-left: auto; margin-right: auto; z-index: 10001">
		    <h2>Order <?php echo htmlspecialchars($order_post->post_title) ?>: $<?php echo htmlspecialchars($price) ?></h2>
            <form method="POST" action="">
                <?php foreach($fields as $key => $value){ 
                    $name = preg_replace("/[^A-Za-z0-9]/", "", $value);
                    $field_id = $id . '_' . $name; 
                    ?>
                    <div>
                        <label for="<?php echo $field_id ?>"><?php echo htmlspecialchars($value) ?></label>
                        <input type="text" id="<?php echo htmlentities($field_id) ?>" name="<?php echo htmlentities($name) ?>"/>
                    </div>
                <?php } ?>
                
                <input type="hidden" name="gallery_order_post_id_field" value="<?php echo $order_post->ID ?>" />

                <div>
                    <input type="submit" name="gallery_order_submit_button"/>
                    <button id="<?php echo $id ?>_cancel">Cancel</button>
                </div>
            </form>
		</div>
	</div>
	<script type="text/javascript">
		<?php add_jquery_variable('og') ?>
		og('#<?php echo $id ?>_cancel').click(function(){
			og('#<?php echo $id?>').hide();
            return false;
		})
	</script>
	<?php
}

function create_order_link($att){
	$id_window = $att['id'] . '_window';
    $order_post = get_post($att['order']);
	create_order_button($att['id'], $id_window, $att['button_class'], $att['button_text']);
	create_order_window($order_post, $id_window, $att['popup_class']);
}

function handle_submit(){
    if(isset($_POST['gallery_order_post_id_field']) && isset($_POST['gallery_order_submit_button'])){
        $gallery_options = get_option('order_gallery_settings');

        // Get the order information
        $order_post = get_post(intval($_POST['gallery_order_post_id_field']));
        $price = get_post_meta($order_post->ID, '_go_price', true);
        $fields = explode("\n", get_post_meta($order_post->ID, "_go_fields", true));

        $order_sku = uniqid();
        $order_name = $order_post->post_title;
        $order_description = $order_post->post_content;
        $order_price = floatval(price);
        $order_tax = round($order_price * (floatval(isset($gallery_options['tax_percent']) ? $gallery_options['tax_percent'] : "0.00")), 2, PHP_ROUND_HALF_EVEN);
        $order_fields = array();

        foreach($fields as $key => $value){
            $name = preg_replace("/[^A-Za-z0-9]/", "", $value);
            $order_fields[$name] = sanitize_text_field($_POST[$name]);
        }

        $returning_page = $_SERVER['HTTP_REFERER'];

        // Call paypal
        $result = create_payment($order_sku, $order_name, $order_description, $order_price, $order_tax, $order_fields, $returning_page);

        //TODO: Save the payment for later use

        //Navigate to the paypal page
        if($result['hasError']){
            
        }else{
            wp_redirect($result['approvalUrl']);
        }

        
    }else if(isset($_POST['find paypal variables'])){
        // Handle paypal results
    }
}
add_action('init', 'OrderGallery\handle_submit');