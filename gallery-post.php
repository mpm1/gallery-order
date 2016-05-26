<?php
namespace OrderGallery;
defined('ABSPATH') or die('No script kiddies please!');

function create_posttype(){
	// Create the Order Gallery Post Type
	$args = array(
		'labels' => array(
			'name' => 'Order Galleries',
			'singular_name' => 'Order Gallery'
		),
        'supports' => array(
            'title',
            'thumbnail',
            'comments',
            'editor'),
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => true,
		'has_archive' => true,
		'taxonomies' => array('category'),
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


function meta_box_add_field($post, $id, $label, $type){
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
		case 'textarea':
			echo '<textarea id="' . $field . '" name="' . $id . '">';
			echo htmlspecialchars($value);
			echo '</textarea>';
			break;

        case 'number':
            echo '<input type="number" id="' . $field . '" name="' . $id . '" value="' . esc_attr($value) . '" size="25" step="any" />';
            break;
      
		default:
			echo '<input type="text" id="' . $field . '" name="' . $id . '" value="' . esc_attr($value) . '" size="25"/>';
	}
	
	echo '</p>';
}

function get_meta_values(){
	$meta_values = array(
		array(
			"meta" => "_go_fields",
			"name" => "Fields(Separated by new lines)",
			"type" => "textarea"
		),
        array(
            "meta" => "_go_price",
            "name" => "Price",
            "type" => "number"
        )
	);
	
	return $meta_values;
}

function meta_box_callback($post){
	wp_nonce_field('OrderGallery\meta_box_save', 'go_meta_box_nonce');
	  
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
    if (!wp_verify_nonce($_POST['go_meta_box_nonce'], 'OrderGallery\meta_box_save')) {
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
        $value = implode("\n", array_map('sanitize_text_field', explode("\n", $_POST[$meta_value["meta"]])));
        update_post_meta($post_id, $meta_value["meta"], $value);
    }
}
add_action('save_post', 'OrderGallery\meta_box_save');