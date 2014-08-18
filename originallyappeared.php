<?php
/**
 * @package OriginallyAppeared
 * @version 1.0
 */
/*
Plugin Name: Originally Appeared
Plugin URI: http://github.com/krues8dr/
Description: A plugin to help with reposting content without hurting SEO.  Adds canonical and no-index meta tags to posts, and a note linking to the original post.
Author: Krues8dr
Version: 1.0
Author URI: http://krues8dr.com/
*/


defined('ABSPATH') or die("No script kiddies please!");

/**
 * Adds a box to the main column on the Post and Page edit screens.
 */
function originallyappeared_add_meta_box() {

	$screens = array( 'post', 'page' );

	foreach ( $screens as $screen ) {

		add_meta_box(
			'originallyappeared_sectionid',
			__( 'Canonical Link', 'originallyappeared_textdomain' ),
			'originallyappeared_meta_box_callback',
			$screen
		);
	}
}
add_action( 'add_meta_boxes', 'originallyappeared_add_meta_box' );

/**
 * Prints the box content.
 *
 * @param WP_Post $post The object for the current post/page.
 */
function originallyappeared_meta_box_callback( $post ) {

	// Add an nonce field so we can check for it later.
	wp_nonce_field( 'originallyappeared_meta_box', 'originallyappeared_meta_box_nonce' );

	echo '<div><label for="originallyappeared_site_name">';
	_e( 'External Site Name', 'originallyappeared_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="originallyappeared_site_name" name="originallyappeared_site_name" value="' .
		esc_attr( get_post_meta( $post->ID, 'originallyappeared_site_name', true ) ) . '" size="25" /></div>';

	echo '<div><label for="originallyappeared_site_url">';
	_e( 'External Site Url', 'originallyappeared_textdomain' );
	echo '</label> ';
	echo '<input type="text" id="originallyappeared_site_url" name="originallyappeared_site_url" value="' .
		esc_attr( get_post_meta( $post->ID, 'originallyappeared_site_url', true ) ) . '" size="25" /></div>';

	echo '<div><label for="originallyappeared_site_url">';
	_e( 'Don\'t Index', 'originallyappeared_textdomain' );
	echo '</label> ';

	echo '<input type="checkbox" id="originallyappeared_no_index" name="originallyappeared_no_index" value="1"';
	if( get_post_meta( $post->ID, 'originallyappeared_no_index', true ) ) {
		echo ' checked="checked"';
	}
	echo ' /></div>';

	echo '<div><label for="originallyappeared_custom_message">';
	_e( 'Custom Message', 'originallyappeared_textdomain' );
	echo '</label> ';
	echo '<textarea id="originallyappeared_custom_message" name="originallyappeared_custom_message">' .
		esc_attr( get_post_meta( $post->ID, 'originallyappeared_custom_message', true ) ) . '</textarea></div>';
}

/**
 * When the post is saved, saves our custom data.
 *
 * @param int $post_id The ID of the post being saved.
 */
function originallyappeared_save_meta_box_data( $post_id ) {

	/*
	 * We need to verify this came from our screen and with proper authorization,
	 * because the save_post action can be triggered at other times.
	 */

	// Check if our nonce is set.
	if ( ! isset( $_POST['originallyappeared_meta_box_nonce'] ) ) {
		return;
	}

	// Verify that the nonce is valid.
	if ( ! wp_verify_nonce( $_POST['originallyappeared_meta_box_nonce'], 'originallyappeared_meta_box' ) ) {
		return;
	}

	// If this is an autosave, our form has not been submitted, so we don't want to do anything.
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	// Check the user's permissions.
	if ( isset( $_POST['post_type'] ) && 'page' == $_POST['post_type'] ) {

		if ( ! current_user_can( 'edit_page', $post_id ) ) {
			return;
		}

	} else {

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
	}

	/* OK, it's safe for us to save the data now. */

	// Update the meta field in the database.
	update_post_meta( $post_id, 'originallyappeared_site_name',
		sanitize_text_field( $_POST['originallyappeared_site_name'] ) );

	update_post_meta( $post_id, 'originallyappeared_site_url',
		sanitize_text_field( $_POST['originallyappeared_site_url'] ) );

	update_post_meta( $post_id, 'originallyappeared_no_index',
		sanitize_text_field( $_POST['originallyappeared_no_index'] ) );

	update_post_meta( $post_id, 'originallyappeared_custom_message',
		sanitize_text_field( $_POST['originallyappeared_custom_message'] ) );

}
add_action( 'save_post', 'originallyappeared_save_meta_box_data' );

/**
 * Display functions
 */

function get_originallyappeared_meta($post = null) {
	if(!isset($post)) {
		global $post;
	}

	$meta = array(
		'name' => get_post_meta($post->ID, 'originallyappeared_site_name', true),
		'site_url' => get_post_meta($post->ID, 'originallyappeared_site_url', true),
		'no_index' => get_post_meta($post->ID, 'originallyappeared_no_index', true),
		'custom_message' => get_post_meta($post->ID, 'originallyappeared_custom_message', true)
	);

	return $meta;
}

function get_originallyappeared($post = null){
	$meta = get_originallyappeared_meta($post);

	if($meta['custom_message']) {
		$message = $meta['custom_message'];
	}
	else {
		$message = __( 'This post originally appeared on <a href="[SITE_URL]">[NAME]</a>.',
			'originallyappeared_textdomain' );
	}

	// Replace our data.
	foreach($meta as $key => $value) {
		$message = str_replace('[' . strtoupper($key) . ']', $value, $message);
	}

	return $message;
}

function show_originallyappeared($post = null) {
	echo '<div class="originallyappeared">';
	echo get_originallyappeared($post);
	echo '</div>';
}

// Alias
function originallyappeared($post = null) {
	show_originallyappeared($post);
}

/**
 * Custom shortcode - [originallyappeared]
 */
function originallyappeared_shortcode( $atts ){
	show_originallyappeared();
}
add_shortcode( 'originallyappeared', 'originallyappeared_shortcode' );

/**
 * Extra header for canonical links
 */
remove_action('wp_head', 'rel_canonical');
function originallyappeared_meta_tags() {
	if(is_single()) {
		$meta = get_originallyappeared_meta();
		if(isset($meta['site_url']) && strlen($meta['site_url'])) {
			echo '<link rel="canonical" href="' . $meta['site_url'] . '" />' . "\n";
		}
		else {
			rel_canonical();
		}
		if(isset($meta['no_index']) && $meta['no_index']) {
			echo '<meta name="robots" content="noindex" />' . "\n";
		}
	}
	else {
		rel_canonical();
	}
}
add_action('wp_head', 'originallyappeared_meta_tags');


