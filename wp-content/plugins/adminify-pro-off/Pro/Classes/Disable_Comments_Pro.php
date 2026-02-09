<?php

namespace WPAdminify\Pro;

use WPAdminify\Inc\Admin\AdminSettings;
use WPAdminify\Inc\Modules\DisableComments\DisableComments;
// no direct access allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Post Columns: Featured Image and ID
 *
 * @package WP Adminify
 *
 * @author WP Adminify <support@wpadminify.com>
 */

class Disable_Comments_Pro extends DisableComments {

	// public $disable_comments;
	public function __construct() {
		parent::__construct();

		$this->options = (array) AdminSettings::get_instance()->get();

		// Hide existing comments
		if (!empty($this->disable_comments['apply_for']) && in_array('hide_existing', $this->disable_comments['apply_for'])) {
			add_filter( 'comments_array', '__return_empty_array', 10, 2 );
		}

		if (!empty($this->disable_comments['apply_for']) && in_array('replace_author_link', $this->disable_comments['apply_for'])) {
			add_filter( 'comment_text', [ $this, 'disable_comments_text_comment_pseudo_links' ] );
			// add_filter('get_comment_author_link', [$this, 'remove_comments_author_link']);
			add_filter( 'get_comment_author_link', [ $this, 'comments_author_link_to_js' ], 100, 3 );
		}

		// Remove comments autolinking
		if (!empty($this->disable_comments['apply_for']) && in_array('comments_autolinking', $this->disable_comments['apply_for'])) {

		}

		// Disable Comments for Media Attachments
		add_filter( 'comments_open', [ $this, 'disable_comments_for_attachments' ], 10, 2 );

		if (!empty($this->disable_comments['apply_for']) && in_array('replace_comment_link', $this->disable_comments['apply_for'])) {

			if ( ! is_admin() ) {
				remove_filter('comment_text', 'make_clickable', 9);
				add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );

				// Hook into 'comment_text' to modify comment content
				// add_filter('comment_text', [$this, 'remove_urls_from_comments']);

				// Hook into 'comment_text_rss' to modify comment content in RSS feeds
				// add_filter('comment_text_rss', [$this, 'remove_urls_from_comments']);

				// Hook into 'comment_excerpt' to modify comment excerpt
				// add_filter('comment_excerpt', [$this, 'remove_urls_from_comments']);
			}
		}
	}


	// Function to remove URLs from comment text
	function remove_urls_from_comments($comment_text) {
		// Regular expression to match both http and https URLs
		$pattern = '/\bhttps?:\/\/[^\s]+/i';
		// Replace URLs with an empty string
		$comment_text = preg_replace($pattern, '', $comment_text);
		return $comment_text;
	}


	/**
	 * Disable Comments for Attachments
	 *
	 * @param [type] $open
	 * @param [type] $post_id
	 *
	 * @return void
	 */
	public function disable_comments_for_attachments( $open, $post_id = null ) {
		if (!empty($this->disable_comments['apply_for']) && in_array('media', $this->disable_comments['apply_for'])) {
			$post = get_post( $post_id );
			if ( $post->post_type == 'attachment' ) {
				return false;
			}
		}
		return $open;
	}



	// Scripts for Comments
	public function enqueue_scripts() {
		wp_register_script( 'wp-adminify-disable-comments', WP_ADMINIFY_URL . 'Inc/Modules/DisableComments/disable-comments-links.js', [ 'jquery' ], WP_ADMINIFY_VER, true );
		wp_enqueue_script( 'wp-adminify-disable-comments' );
	}


	public function remove_comments_author_link($author_link)
	{
		return strip_tags($author_link);
	}

	public function disable_comments_text_comment_pseudo_links( $comment_text ) {
		return $this->convert_to_pseudo( $comment_text );
	}

	/**
	 * Convert author link to pseudo link
	 *
	 * @return string
	 */

	public function comments_author_link_to_js( $return, $author, $comment_ID ) {
		$url    = get_comment_author_url( $comment_ID );
		$author = get_comment_author( $comment_ID );

		if ( empty( $url ) || 'http://' == $url ) {
			$return = $author;
		} else {
			$return = '<span class="wp-adminify-author-link-to-data-uri" data-adminify-comment-uri="' . esc_url( $url ) . '">' . esc_html( $author ) . '</span>';
		}
		return $return;
	}


	/**
	 * Convert links into span pseudo links
	 *
	 * @param $text
	 *
	 * @return mixed
	 */

	public function convert_to_pseudo($text)
	{
		return preg_replace_callback('/<a[^>]+href=[\'"](https?:\/\/[^"\']+)[\'"][^>]+>(.*?)<\/a>/i', [$this, 'jltwp_adminify_links_replace'], $text);
	}

	public function jltwp_adminify_links_replace($matches)
	{
		if ($matches[1] == get_home_url()) {
			return $matches[0];
		}

		return '<span class="wp-adminify-author-link-to-data-uri" data-adminify-comment-uri="' . esc_attr($matches[1]) . '" > ' . wp_kses_post($matches[2]) . '</span>';
	}

}
