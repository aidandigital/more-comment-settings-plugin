<?php

/*
Plugin Name: More Comment Settings
Author: Aidan Digital
Version: Beta
Description: Adds additional comment settings and moderation tools. Requires and validates the author field of comments made by anyone without publishing permissions, preventing people from impersonating staff members. Also adds support for an optional character limit on comments.
*/
 
/* Comment Name Setting Creation */
// RequireCommentValidName
function rcvn_settings_api_init() {
 	
 	// Add the Field̦
 	add_settings_field(
		'rcvn_setting',
		'Blocked Comment Names',
		'rcvn_callback',
		'discussion',
	);
 	
 	// Register so $_POST is done by WP
 	register_setting( 'discussion', 'rcvn_setting' );
 }
 
add_action( 'admin_init', 'rcvn_settings_api_init' );
 
function rcvn_callback() {
	$rcvn_callback_description = 'Filters the author field of comments made by anyone without publishing permissions, can be used to prevent people from impersonating you and your staff by blocking specific words or phrases. One word or phrase per line (matches inside words, so “press” will match “WordPress”). All non-alphanumeric characters except apostrophes will be permanently removed from the name field when a user submits their comment.';
 	echo $rcvn_callback_description;
 	echo('<textarea name="rcvn_setting" style="display:block; margin-top:11px; width:100%; padding-bottom:25px;" rows="10" cols="50" class="large-text code" placeholder="Example: site admin">' . esc_textarea(get_option( 'rcvn_setting' )) .'</textarea>');
 	echo('<p class="description">You do not have to worry about users inflating their names with spaces, caps, or non-alphanumeric characters to bypass the filter, the filter will handle this all for you.</p>');
}

/* Comment length Setting Creation */
// RequireCommentValidCharacters
function rcvc_settings_api_init() {

 	// Add the Field
 	add_settings_field(
 		'rcvc_setting',
 		'',
 		'rcvc_callback',
 		'discussion', 
 		'default',
 	);

 	register_setting( 'discussion', 'rcvc_setting' );
 }

 add_action( 'admin_init', 'rcvc_settings_api_init' );

 function rcvc_callback() {
 	echo ('Comments may not exceed  <input type="number" name="rcvc_setting" class="small-text" min="0" value="' . get_option( 'rcvc_setting' ) . '">  characters in length.<p class="description">(Enter 0 to remove limit.)</p>');
 }

 /* Validates Comment Length */
add_filter('preprocess_comment', 'require_comment_name');

function comment_length_restrictions($comment) {
	$max_comment_length = get_option('rcvc_setting');
	if (! current_user_can('publish_posts' && $max_comment_length)) { // Only apply to non-staff members and if max length is set
		if (! $max_comment_length == 0) {
			$comment_text = $comment['comment_content'] = trim($comment['comment_content']);
			$comment_length_message = 'Comment is too long. Please keep your comment under ' . $max_comment_length . ' characters.';
			if ( strlen( $comment_text ) > $max_comment_length ) {
				wp_die('<strong>Error</strong>: ' . $comment_length_message . '<br><br><a href="javascript:history.back()">« Back</a>');
			}
		}
	}
    return $comment;
}

add_filter('preprocess_comment', 'comment_length_restrictions');

/* Validates Comment Name */
function require_comment_name($fields) {
	$rcvn_setting = esc_textarea(get_option( 'rcvn_setting' ));
	if (! current_user_can('publish_posts' && $rcvn_setting)) { // Only apply to non-staff members and if blocked names are set
		$no_name_message = 'Please enter a valid name.';
		$invalid_name_message = 'Our systems have detected that your name may be impersonating an administrator, staff member, or developer. Please choose a different name to proceed.';
		$fields['comment_author'] = trim($fields['comment_author']); // Strip extra white space permanently
		$commenter_name = preg_replace('/[^A-Za-z0-9]/', '', $fields['comment_author']); // Temporarily remove non-alphanumeric characters and spaces for comparison (Anything not a-Z or 0-9 is temporarily removed)
		$blocked_names = explode("\n", $rcvn_setting);
		if (empty($commenter_name)) {
			wp_die('<strong>Error</strong>: ' . $no_name_message . '<br><br><a href="javascript:history.back()">« Back</a>');
		} // Check to ensure user has name first
		if (! empty($blocked_names)) {
			foreach ($blocked_names as $blocked) {
				$blocked = str_replace(' ', '', trim($blocked)); // Strip white space for comparison
				if (empty($blocked)) {
					continue;
				}
				if (strripos($commenter_name, $blocked) !== false) {
					wp_die('<strong>Error</strong>: ' . $invalid_name_message . '<br><br><a href="javascript:history.back()">« Back</a>');
					// By using strripos the filter is not case-sensitive
				}
			}
		}
	}
	return $fields;
}

?>