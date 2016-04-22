<?php
/*
  Plugin Name: Gallery Wordpress Paypal
  Description: A library used to order image prints through paypal.
  Author: Mark McKellar
  Text Domain: gallery-order
 */
defined('ABSPATH') or die('No script kiddies please!');

function go_create_posttype(){
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
add_action('init', 'go_create_posttype');
