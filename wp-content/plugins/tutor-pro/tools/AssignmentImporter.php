<?php
/**
 * Assignment Importer
 *
 * @package TutorPro\Tools
 * @author  Themeum<support@themeum.com>
 * @link    https://themeum.com
 * @since   3.6.0
 */

namespace TutorPro\Tools;

use TUTOR\Input;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Assignment Importer Class.
 */
class AssignmentImporter {

	/**
	 * Sets the assignment post meta.
	 *
	 * @since 3.6.0
	 *
	 * @param array $assignment_meta the assignment meta data to set.
	 * @param array $post_id the post id.
	 * @param array $attachment_ids the array of attachment ids.
	 *
	 * @return void
	 */
	public function set_assignment_meta( $assignment_meta, $post_id, $attachment_ids ) {
		$data = array();

		$assignment_meta = array_map( fn( $val ) => $val[0], $assignment_meta );

		if ( isset( $assignment_meta['assignment_option'] ) ) {
			update_post_meta( $post_id, 'assignment_option', maybe_serialize( $assignment_meta['assignment_option'] ) );
		}

		if ( isset( $assignment_meta['_tutor_assignment_total_mark'] ) && isset( $assignment_meta['_tutor_assignment_pass_mark'] ) ) {
			$total_mark = Input::sanitize( $assignment_meta['_tutor_assignment_total_mark'], 0, INPUT::TYPE_NUMERIC );
			$pass_mark  = Input::sanitize( $assignment_meta['_tutor_assignment_pass_mark'], 0, INPUT::TYPE_NUMERIC );

			update_post_meta( $post_id, '_tutor_assignment_total_mark', $total_mark );
			update_post_meta( $post_id, '_tutor_assignment_pass_mark', $pass_mark );

		}

		if ( isset( $assignment_meta['_tutor_course_id_for_assignments'] ) ) {
			$topic_id  = wp_get_post_parent_id( $post_id );
			$course_id = wp_get_post_parent_id( $topic_id );

			update_post_meta( $post_id, '_tutor_course_id_for_assignments', $course_id );
		}

		if ( $attachment_ids ) {
			update_post_meta( $post_id, '_tutor_assignment_attachments', $attachment_ids );
		}

		if ( isset( $assignment_meta['_content_drip_settings'] ) ) {
			$_POST['content_drip_settings'] = $assignment_meta['_content_drip_settings'];
		}

		do_action( 'tutor_assignment_created', $post_id );
	}
}
