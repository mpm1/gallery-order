<?php
/*
  Plugin Name: Gallery Wordpress Paypal
  Description: A library used to order image prints through paypal.
  Author: Mark McKellar
  Text Domain: gallery-order
 */
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

function create_posttype(){
	// Create the Order Galler Taxonomy
	$labels = array(
		'name' => __('Order Galleries'),
		'singular_name' => __('Order Gallery'),
		'search_items' => __('Search Galleries'),
		'all_items' => __('All Galleries'),
		'parent_item' => __('Parent Gallery'),
		'parent_item_colon' => __('Parent Gallery:'),
		'edit_item' => __('Edit Gallery'),
		'update_item' => __('Update Gallery'),
		'add_new_item' => __('Add New Gallery'),
		'new_item_name' => __('New Gallery Name'),
		'menu_name' => __('Galleries')
	);
	
	register_taxonomy('order-gallery', array('gallery-order'), array(
			'hierarchical' => true,
			'labels' => $labels,
			'public' => false,
			'show_ui' => true,
			'show_admin_column' => true,
			'query_var' => true,
			'rewrite' => array('slug' => 'order-gallery')
		));
	
	// Create the Order Gallery Post Type
	$args = array(
		'labels' => array(
			'name' => __('Order Galleries'),
			'singular_name' => __('Order Gallery')
		),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'has_archive' => true,
		'taxonomies' => 'order-gallery',
		'rewrite' => array('slug' => 'gallery-order')
	);
	
	register_post_type('gallery-order', $args);
}
add_action('init', 'OrderGallery\create_posttype');

function meta_box_add() {
	$screens = array('gallery-order');
	
	foreach ($screens as $screen){
		add_meta_box('go_meta_data', 'Order Gallery Options', 'OrderGallery\meta_box_callback', $screen);
	}
}
add_action('add_meta_boxes', 'OrderGallery\meta_box_add');

function meta_box_add_field($post, $id, $label, $type, $options){
	$value = get_post_meta($post->ID, $id, true);
	$field = $id . "_field";
	
	echo '<p>';
	echo '<strong>' . $label . '</strong>';
	echo '</p>';
	
	echo '<p>';
	echo '<label class="screen-reader-text" for="' . $field . '">';
	echo $label;
	echo '</label>';
	
	switch($type) {
		case 'checkbox':
			foreach($options as $key => $option){
				$checked = false;
				echo '<input type="checkbox" id="' 
				. $field . $key 
				. '" name="' . $id . '" value="' 
				. esc_attr($key) . '"'
				. $checked ? 'checked' : ''
				. '/>';
				echo '<span>' . htmlspecialchars($option) . '</span>';
			}
			break;
			
		case 'checkbox_price':
			foreach($options as $key => $option){
				$checked = false;
				$price = 0.00; // We will have the value as a json object to figure out these values
				echo '<input type="checkbox" id="' 
				. $field . $key 
				. '" name="' . $id . '" value="' 
				. esc_attr($option) . '"'
				. $checked ? ' checked' : ''
				. '/>';
				echo '<span>' . htmlspecialchars($option) . '</span>';
				echo '<input type="number" name="' . $id . '_price" value="' . $price . '"/>';
			}
			break;
			
		case 'textarea':
			echo '<textarea id="' . $field . '" name="' . $id . '">';
			echo htmlspecialchars($value);
			echo '</textarea>';
			break;
      
		default:
			echo '<input type="text" id="' . $field . '" name="' . $id . '" value="' . esc_attr($value) . '" size="25" />';
	}
	
	echo '</p>';
}

function get_meta_values(){
	$meta_values = array(
		array(
			"meta" => "_go_options",
			"name" => "Available Types",
			"type" => "checkbox_price",
			"options" => array("GET OPTIONS")
		)
	);
	
	return $meta_values;
}

function meta_box_callback($post){
	wp_nonce_field('OrderGallery\meta_box_save', 'go_box_nonce');
	  
    $meta_values = get_meta_values();
    $meta_count = count($meta_values);
    for ($i=0; $i < $meta_count; ++$i) {
        $meta_value = $meta_values[$i];
        meta_box_add_field($post, $meta_values[$i]["meta"], $meta_values[$i]["name"], $meta_values[$i]["type"]);
    }
}

function meta_box_save($post_id){
	// Check if our nonce is set. This verifies it's from the correct screen
    if (!isset($_POST['go_meta_box_nonce'])) {
        return;
    }
	
	// Check if the nonce is valid
    if (!wp_verify_nonce($_POST['meta_box_nonce'], 'OrderGallery\meta_box_save')) {
        return;
    }
	
	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
	
	// Check the user's permissions.
    if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
        if (!current_user_can('edit_page', $post_id)) {
            return;
        }
    } else {
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    }
	
	// Section to save the information
    $meta_values = get_meta_values();
    $meta_count = count($meta_values);
    for ($i=0; $i < $meta_count; ++$i) {
		// TODO: Clean this up
        $meta_value = $meta_values[$i];
        $value = sanitize_text_field($_POST[$meta_value["meta"]]);
        update_post_meta($post_id, $meta_value["meta"], $value);
    }
}
add_action('save_post', 'OrderGallery\meta_box_save');