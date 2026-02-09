<?php
/**
 * Bundle Exporter
 *
 * @package TutorPro\Tools
 * @author  Themeum<support@themeum.com>
 * @link    https://themeum.com
 * @since   3.6.0
 */

namespace TutorPro\Tools;

use TutorPro\CourseBundle\Models\BundleModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handle a bundle exporting
 *
 * @since 3.6.0
 */
class BundleExporter {

	/**
	 * Export a bundle as array
	 *
	 * @since 3.6.0
	 *
	 * @throws \Exception Throws exception if course id is invalid.
	 * @throws \Throwable If failed to export a course.
	 *
	 * @param integer $bundle_id Bundle id.
	 * @param bool    $keep_media_files Whether to keep media files or not. Default false.
	 *
	 * @return array Export the entire bundle as an array
	 */
	public static function export( int $bundle_id, $keep_media_files = false ) {
		$exporter = tutor_pro_tools()->exporter;

		// Bundle Post.
		$bundle_data = get_post( $bundle_id );
		if ( ! is_a( $bundle_data, 'WP_Post' ) ) {
			throw new \Exception( __( 'Invalid bundle id detected', 'tutor-pro' ) );
		}

		if ( tutor()->bundle_post_type !== $bundle_data->post_type ) {
			throw new \Exception( __( 'Invalid bundle', 'tutor-pro' ) );
		}

		// Thumbnail.
		$bundle_data->thumbnail_url = get_the_post_thumbnail_url( $bundle_id, 'full' );

		// Post Meta.
		$bundle_data->meta = get_post_meta( $bundle_id );

		$bundle_courses = array();

		$course_ids = BundleModel::get_bundle_course_ids( $bundle_id );
		foreach ( $course_ids as $course_id ) {
			$content_types = array();
			foreach ( $exporter->get_exportable_sub_contents() as $sub_content ) {
				$content_types[ $sub_content['key'] ] = $sub_content['label'];
			}
			unset( $content_types[ $exporter::TYPE_ATTACHMENTS ] );

			try {
				$bundle_courses[] = CourseExporter::export( $course_id, array_keys( $content_types ), $keep_media_files );
			} catch ( \Throwable $th ) {
				throw $th;
			}
		}

		$bundle_data->courses = $bundle_courses;

		return $bundle_data;
	}
}
