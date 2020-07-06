<?php
/**
 * Authors List WP.
 *
 * @package     Authors_List_WP
 * @author      Vinsha KP
 * @copyright   2020 Vinsha KP
 * @license     GPL-2.0+
 *
Plugin Name:  Authors List WP
Plugin URI:
Description: Authors List WP plugin listing allow to select contributors for a post. Admin, editor or  author can select authors for the posts. These
Selected users will be listed on post contents.
Version:     1.0
Author:      vinshakp
Author URI:
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
 */

/** Defining resources constants. **/
define( 'AL_DIR', plugin_dir_path( __FILE__ ), false );
define( 'AL_DIR_URL', plugin_dir_url( __FILE__ ), false );

/** Css url constant . */
define( 'AL_CSS_DIR', AL_DIR . 'assets\css\\' );
define( 'AL_CSS_URL', AL_DIR_URL . 'assets/css/' );

/** Plugin Activation code. */
register_activation_hook( __FILE__, 'al_activation' );
/** Code For Plugin Activation. */
function al_activation() {  }

/** Register Deactivation Hook here. */
register_deactivation_hook( __FILE__, 'al_deactivation' );
/** Code For Plugin Deactivation. */
function al_deactivation() { }

/** Register Uninstall Hook here. */
register_uninstall_hook( __FILE__, 'al_uninstall' );

/** Code For Plugin Uninstall. */
function al_uninstall() { }
/** Adding style sheet to front end. */
add_action(
	'wp_enqueue_scripts',
	function() {
		wp_enqueue_style( 'author_list_style', AL_CSS_URL . '/author_list_style.css', array(), '1.0.0' );
	}
);

/** Adding meta box for contributors in posts. */
add_action( 'add_meta_boxes', 'al_authors_box' );
/** Callback function of add_meta_box. */
function al_authors_box() {
	wp_nonce_field( 'theme_meta_box_nonce', 'meta_box_nonce' );

	add_meta_box( 'al_authors', __( 'Contributors', 'text-domain' ), 'al_author_lists', 'post', 'normal', 'high' );
}
/**
 * Define al_author_lists callback function for meta_box.
 *
 * @param Posts $posts for the details of post.
 */
function al_author_lists( $posts ) {
	wp_nonce_field( 'al_users_custom_box', 'al_users_nonce' );

	$meta_key = 'al_contributors';

	/** Get the meta value of post meta al_contributors. */
	$meta_value = get_post_meta( $posts->ID, $meta_key, true );

	if ( ! empty( $meta_value ) ) {
		$selected_author_lists = array_map( 'intval', explode( ',', $meta_value ) );
	}
	?>
	<div> 
		<?php
			$users = get_users();
		foreach ( $users as $user ) {
			$selected = '';
			$userid   = (int) $user->ID;
			if ( ! empty( $selected_author_lists ) && in_array( $userid, $selected_author_lists, true ) ) {
				$selected = 'checked';
			}

			echo '<p><input type="checkbox" id="user' . esc_attr( $userid ) . '" value="' . esc_attr( $userid ) . '" name="al_users_list[]" ' . esc_html( $selected ) . '/>';
			echo '<label for="user' . esc_html( $userid ) . '" >' . esc_html( $user->display_name ) . '</label></p>';
		}
		?>
	</div>
	<?php
}

/** Saving metabox value in post. */
add_action( 'save_post', 'al_author_save', 10, 1 );
/**
 * Save_post callback function.
 *
 * @param Number $post_id of current post.
 */
function al_author_save( $post_id ) {
	/** Check if user is either admin, editor, or author. */
	if ( ! current_user_can( 'publish_posts' ) ) {
		return;
	}

	/** Check if our nonce is set. */
	if ( ! isset( $_POST['al_users_nonce'] ) ) {
		return $post_id;
	}
	$nonce = filter_input( INPUT_POST, 'al_users_nonce', FILTER_SANITIZE_STRING );

	/** Verify that the nonce is valid. */
	if ( ! wp_verify_nonce( $nonce, 'al_users_custom_box' ) ) {
		return $post_id;
	}
	$meta_key = 'al_contributors';
	if ( isset( $_POST['al_users_list'] ) || empty( $_POST['al_users_list'] ) ) {
		if ( is_array( $_POST['al_users_list'] ) ) {
			$keyvalue = filter_input( INPUT_POST, 'al_users_list', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$keyvalue = implode( ',', $keyvalue );
		} else {
			$keyvalue = filter_input( INPUT_POST, 'al_users_list', FILTER_SANITIZE_STRING );
		}

		if ( ! ( metadata_exists( 'post', $post_id, $meta_key ) ) ) {
			add_post_meta( $post_id, $meta_key, $keyvalue );
		} else {
			update_post_meta( $post_id, $meta_key, $keyvalue );
		}
	}
	return $post_id;
}

/** Show selected users at end of post_content filter. */
add_filter( 'the_content', 'al_users_display_after_contents' );
/**
 * Callback function of the_content filter.
 *
 * @param Contents $content of Every post.
 */
function al_users_display_after_contents( $content ) {
	global $post;
	$meta_key = 'al_contributors';
	/** Get the meta value of al_contributors. */
	$al_userslists = get_post_meta( $post->ID, $meta_key, true );
	if ( $al_userslists ) {
		$al_users     = explode( ',', $al_userslists );
		$usersdisplay = '<div class="al_users_show"><h3>Contributors</h3>';
		foreach ( $al_users as $al_user_id ) {
			$get_userdata  = get_userdata( $al_user_id );
			$user_data     = "<div class='al_user_data'>";
			$user_data    .= "<img src='" . esc_url( get_avatar_url( $al_user_id ) ) . "' />";
			$user_data    .= '<p><a href="' . get_author_posts_url( $al_user_id ) . '">' . $get_userdata->display_name . '</a></p>';
			$user_data    .= '</div>';
			$usersdisplay .= $user_data;
		}
		$usersdisplay .= '</div>';
		/** Attach users data to content. */
		$content .= '<div class="al_post_author_details">' . $usersdisplay . '</div>';
	}
	return $content;
}
