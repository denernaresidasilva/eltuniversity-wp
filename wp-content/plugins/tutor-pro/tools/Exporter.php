<?php
/**
 * TutorPro Exporter
 *
 * @package TutorPro\Tools
 * @author  Themeum<support@themeum.com>
 * @link    https://themeum.com
 * @since   3.6.0
 */

namespace TutorPro\Tools;

use TutorPro\ContentBank\Models\CollectionModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handling export functionality.
 *
 * @since 3.6.0
 */
class Exporter {

	/**
	 * Course ids to export
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $course_ids = array();

	/**
	 * Bundle ids to export
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $bundle_ids = array();

	/**
	 * Collection ids to export
	 *
	 * @since 3.7.0
	 *
	 * @var array
	 */
	private $collection_ids = array();

	/**
	 * Course content types to export
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $content_types = array();

	/**
	 * Export settings
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $export_settings = false;

	/**
	 * Keep media files
	 *
	 * @since 3.6.0
	 *
	 * @var array
	 */
	private $keep_media_files = false;

	/**
	 * Version for the JSON file
	 *
	 * @since 3.6.0
	 */
	const SCHEMA_VERSION = '1.0.0';

	/**
	 * Exportable content types other than posts.
	 *
	 * @since 3.6.0
	 */
	const TYPE_SETTINGS    = 'settings';
	const TYPE_ATTACHMENTS = 'attachments';

	/**
	 * Content bank
	 *
	 * @since 3.7.0
	 */
	const TYPE_CONTENT_BANK = 'content_bank';

	/**
	 * Questions
	 *
	 * @since 3.7.1
	 */
	const TYPE_QUESTIONS = 'questions';

	/**
	 * Export option name
	 *
	 * Each job id will be concat with this option name
	 *
	 * @since 3.6.0
	 */
	const OPT_NAME = 'tutor_pro_export_';

	/**
	 * Get exportable main types.
	 *
	 * @since 3.6.0
	 *
	 * @since 3.7.1 $context param added.
	 *
	 * @param string $context Context content-bank or empty.
	 *
	 * @return array
	 */
	public function get_exportable_content( string $context = '' ): array {
		$contents = array(
			array(
				'key'      => tutor()->course_post_type,
				'label'    => __( 'Courses', 'tutor-pro' ),
				'contents' => $this->get_exportable_sub_contents(),
			),
			array(
				'key'      => tutor()->bundle_post_type,
				'label'    => __( 'Bundles', 'tutor-pro' ),
				'contents' => array(),
			),
			array(
				'key'      => self::TYPE_CONTENT_BANK,
				'label'    => __( 'Content Bank', 'tutor-pro' ),
				'contents' => array(),
			),
			array(
				'key'      => self::TYPE_SETTINGS,
				'label'    => __( 'Settings', 'tutor-pro' ),
				'contents' => array(),
			),
			array(
				'key'      => 'keep_media_files',
				'label'    => __( 'Keep media files', 'tutor-pro' ),
				'contents' => array(),
			),
		);
		if ( self::TYPE_CONTENT_BANK === $context ) {
			$contents = array_filter(
				$contents,
				fn( $content ) => tutor()->course_post_type === $content['key']
			);
			$contents = array_values( $contents ); // Reset keys.
		}

		return apply_filters( 'tutor_pro_exportable_contents', $contents );
	}

	/**
	 * Get exportable sub types.
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_exportable_sub_contents(): array {
		$types = array(
			tutor()->quiz_post_type       => __( 'Quizzes', 'tutor-pro' ),
			tutor()->assignment_post_type => __( 'Assignments', 'tutor-pro' ),
		);

		$types = array(
			array(
				'label' => __( 'Questions', 'tutor-pro' ),
				'key'   => self::TYPE_QUESTIONS,
			),
			array(
				'label' => __( 'Lessons', 'tutor-pro' ),
				'key'   => tutor()->lesson_post_type,
			),
			array(
				'label' => __( 'Quizzes', 'tutor-pro' ),
				'key'   => tutor()->quiz_post_type,
			),
			array(
				'label' => __( 'Assignments', 'tutor-pro' ),
				'key'   => tutor()->assignment_post_type,
			),
		);

		if ( ! tutor_utils()->is_addon_enabled( 'tutor-assignments' ) ) {
			unset( $types[2] );
		}

		return apply_filters( 'tutor_pro_exportable_sub_types', $types );
	}

	/**
	 * Add courses to export
	 *
	 * @since 3.6.0
	 *
	 * @param string|array $course_ids Course IDs, comma separate or array.
	 * @param string|array $content_types A single type or array of types to export.
	 *
	 * @return $this object
	 */
	public function add_courses( $course_ids, $content_types ) {
		if ( is_string( $content_types ) ) {
			$content_types = array( $content_types );
		}

		$this->course_ids    = $this->prepare_content_ids( $course_ids );
		$this->content_types = $content_types;
		return $this;
	}

	/**
	 * Add bundles to export
	 *
	 * @since 3.6.0
	 *
	 * @param string|array $bundle_ids Bundle IDs comma separate or array.
	 *
	 * @return $this object
	 */
	public function add_bundles( $bundle_ids ) {
		$this->bundle_ids = $this->prepare_content_ids( $bundle_ids );
		return $this;
	}

	/**
	 * Add collection to export
	 *
	 * @since 3.7.0
	 *
	 * @param string|array $collection_ids Collection IDs comma separate or array.
	 *
	 * @return $this object
	 */
	public function add_collections( $collection_ids ) {
		$this->collection_ids = $this->prepare_content_ids( $collection_ids );
		return $this;
	}

	/**
	 * Add bundles to export
	 *
	 * @since 3.6.0
	 *
	 * @return $this object
	 */
	public function add_settings() {
		$this->export_settings = true;
		return $this;
	}

	/**
	 * Add media files to export
	 *
	 * @since 3.6.0
	 *
	 * @return $this object
	 */
	public function add_media_files() {
		$this->keep_media_files = true;
		return $this;
	}

	/**
	 * Export new export
	 *
	 * @throws \Throwable If invalid course id bundle id detect.
	 *
	 * @since 3.6.0
	 */
	public function export() {
		$export = array(
			'schema_version'   => self::SCHEMA_VERSION,
			'exported_at'      => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'keep_media_files' => $this->keep_media_files,
			'data'             => array(),
		);

		if ( count( $this->course_ids ) ) {
			$course_data = array(
				'content_type' => tutor()->course_post_type,
				'data'         => array(),
			);

			foreach ( $this->course_ids as $id ) {
				try {
					$export_data           = CourseExporter::export( $id, $this->content_types, $this->keep_media_files );
					$course_data['data'][] = $export_data;
				} catch ( \Throwable $th ) {
					throw $th;
				}
			}

			$export['data'][] = $course_data;
		}

		if ( count( $this->bundle_ids ) ) {
			$bundle_data = array(
				'content_type' => tutor()->bundle_post_type,
				'data'         => array(),
			);

			if ( tutor_utils()->is_addon_enabled( 'course-bundle' ) ) {
				foreach ( $this->bundle_ids as $id ) {
					try {
						$export_data           = BundleExporter::export( $id, $this->keep_media_files );
						$bundle_data['data'][] = $export_data;
					} catch ( \Throwable $th ) {
						throw $th;
					}
				}
			}

			$export['data'][] = $bundle_data;
		}

		if ( count( $this->collection_ids ) ) {
			$collection_data = array(
				'content_type' => CollectionModel::POST_TYPE,
				'data'         => array(),
			);

			foreach ( $this->collection_ids as $id ) {
				try {
					$export_data               = CollectionExporter::export( $id, $this->keep_media_files );
					$collection_data['data'][] = $export_data;
				} catch ( \Throwable $th ) {
					throw $th;
				}
			}

			$export['data'][] = $collection_data;
		}

		if ( $this->export_settings ) {
			$settings = array(
				'content_type' => self::TYPE_SETTINGS,
				'data'         => get_option( 'tutor_option' ),
			);

			$export['data'][] = $settings;
		}

		return $export;
	}

	/**
	 * Convert comma separated id to array id
	 *
	 * @since 3.6.0
	 *
	 * @param mixed $ids Content ids String|array.
	 *
	 * @return array
	 */
	private function prepare_content_ids( $ids ): array {
		return array_map( 'intval', is_array( $ids ) ? $ids : explode( ',', $ids ) );
	}

	/**
	 * Get default exporter schema
	 *
	 * @since 3.6.0
	 *
	 * @return array
	 */
	public function get_schema() {
		$export = array(
			'schema_version'   => self::SCHEMA_VERSION,
			'exported_at'      => current_time( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ),
			'keep_media_files' => $this->keep_media_files,
			'data'             => array(),
		);

		return $export;
	}
}
