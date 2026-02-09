<?php
/**
 * TutorPro Importer
 *
 * @package TutorPro\Tools
 * @author  Themeum<support@themeum.com>
 * @link    https://themeum.com
 * @since   3.6.0
 */

namespace TutorPro\Tools;

use Tutor\Helpers\ValidationHelper;
use TUTOR\Lesson;
use Tutor\Models\CourseModel;
use Tutor\Options_V2;
use TutorPro\ContentBank\Models\ContentModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Importer class
 */
class Importer {
	/**
	 * Course Importer Class Instance.
	 *
	 * @since 3.6.0
	 *
	 * @var CourseImporter
	 */
	private $course_importer;


	/**
	 * Quiz Importer Class Instance.
	 *
	 * @since 3.6.0
	 *
	 * @var QuizImporter
	 */
	private $quiz_importer;

	/**
	 * Assignment Importer Class Instance.
	 *
	 * @since 3.6.0
	 *
	 * @var AssignmentImporter
	 */
	private $assignment_importer;

	/**
	 * Bundle Importer Class Instance.
	 *
	 * @since 3.6.0
	 *
	 * @var BundleImporter
	 */
	private $bundle_importer;

	/**
	 * Tutor Lesson Class Instance.
	 *
	 * @since 3.7.0
	 *
	 * @var Lesson
	 */
	private $lesson;


	/**
	 * Import option name
	 *
	 * Each job id will be concat with this option name
	 *
	 * @since 3.6.0
	 */
	const OPT_NAME = 'tutor_pro_import_';


	/**
	 * Content Bank Type
	 *
	 * @since 3.7.0
	 */
	const TYPE_CONTENT_BANK = 'cb-collection';

	/**
	 * Constants for content type.
	 *
	 * @since 3.7.0
	 *
	 * @var string
	 */
	const TYPE_LESSON     = 'lesson';
	const TYPE_ASSIGNMENT = 'assignment';
	const TYPE_QUESTION   = 'question';


	/**
	 * Importer class constructor.
	 */
	public function __construct() {
		$this->lesson              = tutor_lms()->lesson;
		$this->course_importer     = new CourseImporter();
		$this->quiz_importer       = new QuizImporter();
		$this->assignment_importer = new AssignmentImporter();
		$this->bundle_importer     = new BundleImporter();
	}

	/**
	 * Import tutor settings.
	 *
	 * @since 3.6.0
	 *
	 * @param array $data array of settings data.
	 *
	 * @return bool|\WP_Error
	 */
	public function import_settings( $data ) {
		if ( is_array( $data ) && count( $data ) ) {
			$update_option = tutor_utils()->sanitize_recursively( $data );

			$tutor_option = get_option( 'tutor_option' );

			if ( $update_option === $tutor_option || maybe_serialize( $tutor_option ) === maybe_serialize( $update_option ) ) {
				return true;
			}

			$response = update_option( 'tutor_option', $update_option );

			( new Options_V2( false ) )->update_settings_log( $update_option, 'Imported' );
			return $response;
		}
	}

	/**
	 * Prepare tutor settings.
	 *
	 * @since 3.6.0
	 *
	 * @param array $settings array of settings data.
	 *
	 * @return array
	 */
	public function prepare_tutor_settings( $settings ) {
		$data = $settings;

		$skip_options = array(
			'tutor_dashboard_page_id',
			'tutor_toc_page_id',
			'tutor_cart_page_id',
			'tutor_checkout_page_id',
			'course_permalink_base',
			'lesson_permalink_base',
			'membership_pricing_page_id',
			'quiz_permalink_base',
			'assignment_permalink_base',
			'student_register_page',
			'instructor_register_page',
			'course_archive_page',
			'tutor_certificate_page',
		);

		foreach ( $skip_options as $option_key ) {
			if ( isset( $data[ $option_key ] ) ) {
				unset( $data[ $option_key ] );
			}
		}

		return $data;
	}

	/**
	 * Import bundle using importer.
	 *
	 * @since 3.6.0
	 *
	 * @param array $post the bundle data.
	 * @param bool  $keep_media_files whether to download media files or not.
	 * @param array $course_ids_map array of new course ids map to previous.
	 *
	 * @return bool|\WP_Error
	 */
	public function import_bundle( array $post, bool $keep_media_files = false, $course_ids_map = array() ) {
		if ( is_array( $post ) && count( $post ) ) {
			$courses           = $post['courses'] ?? null;
			$meta              = $post['meta'] ?? null;
			$course_ids        = array();
			$failed_course_ids = array();
			$thumbnail_url     = $post['thumbnail_url'] ?? null;
			$attachment_links  = $post['attachment_links'] ?? null;
			$attachment_ids    = array();

			if ( $meta ) {
				$this->bundle_importer->prepare_bundle_meta( $meta );
			}

			$post = $this->prepare_post_data( $post );

			if ( is_wp_error( $post ) ) {
				return $post;
			}

			$post['post_status'] = 'draft';

			$id = wp_insert_post( $post, true );

			if ( is_wp_error( $id ) ) {
				return $id;
			}

			if ( $thumbnail_url && $keep_media_files ) {
				$this->save_post_thumbnail( $thumbnail_url, $id );
			}

			if ( $attachment_links && $keep_media_files ) {
				$attachment_ids = $this->get_post_attachments_id( $attachment_links );
			}

			if ( $attachment_ids ) {
				update_post_meta( $id, '_tutor_attachments', maybe_serialize( $attachment_ids ) );
			}

			if ( $courses ) {
				foreach ( $courses as $post ) {
					$course_id = 0;
					if ( $course_ids_map && in_array( $post['ID'], array_keys( $course_ids_map ) ) ) {
						$course_id = $course_ids_map[ $post['ID'] ];
					}

					if ( $course_id ) {
						$course_ids[] = $course_id;
					}
				}
			}

			if ( $course_ids ) {
				$this->bundle_importer->update_course_bundle_ids( $id, $course_ids );
			}

			if ( $id ) {
				if ( $meta ) {
					$this->bundle_importer->prepare_bundle_meta( $meta );
				}
				$id = wp_update_post(
					array(
						'ID'          => $id,
						'post_status' => 'publish',
					),
					true
				);
				if ( is_wp_error( $id ) ) {
					return $id;
				}
			}

			return array(
				'bundle_id'         => $id,
				'failed_course_ids' => $failed_course_ids,
			);
		}
	}

	/**
	 * Import tutor posts recursively.
	 *
	 * @since 3.6.0
	 * @since 3.7.1 param $collection_id and $import_id added.
	 *
	 * @param array $posts the array of data to import.
	 * @param bool  $keep_media_files whether to download media files or not.
	 * @param int   $collection_id the collection id.
	 * @param int   $import_id the imported content id.
	 *
	 * @return array|bool|\WP_Error
	 */
	public function import_content( array $posts, bool $keep_media_files = false, $collection_id = 0, $import_id = 0 ) {
		if ( is_array( $posts ) && count( $posts ) ) {
			$parent_id     = 0;
			$cb_assignment = '';
			$cb_question   = '';
			$cb_lesson     = '';

			if ( tutor_utils()->is_addon_enabled( 'content-bank' ) ) {
				$cb_assignment = ContentModel::get_post_type_by_content_type( self::TYPE_ASSIGNMENT );
				$cb_question   = ContentModel::get_post_type_by_content_type( self::TYPE_QUESTION );
				$cb_lesson     = ContentModel::get_post_type_by_content_type( self::TYPE_LESSON );
			}

			foreach ( $posts as $key => $post ) {
				$contents         = $post['contents'] ?? null;
				$children         = $post['children'] ?? null;
				$taxonomies       = $post['taxonomies'] ?? null;
				$meta             = $post['meta'] ?? null;
				$thumbnail_url    = $post['thumbnail_url'] ?? null;
				$attachment_links = $post['attachment_links'] ?? null;
				$question_answer  = $post['question_answer'] ?? null;
				$question         = $post['question'] ?? null;
				$answers          = $post['answers'] ?? null;
				$attachment_ids   = array();

				if ( $collection_id ) {
					if ( get_tutor_post_types( 'course' ) === $post['post_type'] && ! $contents ) {
						return $post['ID'];
					}

					if ( get_tutor_post_types( 'topics' ) === $post['post_type'] && ! $children ) {
						continue;
					}

					if ( $contents ) {
						$response = $this->import_content( $contents, $keep_media_files, $collection_id, $post['ID'] );
						return $response;
					}

					if ( $children ) {
						$children = $this->prepare_content_bank_question( $children, $collection_id );
						$response = $this->import_content( $children, $keep_media_files, $collection_id, $import_id );
						if ( is_wp_error( $response ) ) {
							return $response;
						}
						continue;
					}
				}

				if ( $meta ) {
					$this->course_importer->prepare_course_meta( $meta, $keep_media_files );
				}

				if ( $taxonomies ) {
					$this->set_tutor_course_taxonomies( $taxonomies );
				}
				// Prepare post data before insert.
				$post = $this->prepare_post_data( $post, $collection_id );

				if ( is_wp_error( $post ) ) {
					return $post;
				}

				$parent_id = wp_insert_post( $post, true );

				if ( is_wp_error( $parent_id ) ) {
					return $parent_id;
				}

				if ( get_post_type( $parent_id ) === $cb_lesson ) {
					$this->lesson->save_lesson_meta( $parent_id );
				}

				if ( $thumbnail_url && $keep_media_files ) {
					$this->save_post_thumbnail( $thumbnail_url, $parent_id );
				}

				if ( $attachment_links && $keep_media_files ) {
					$attachment_ids = $this->get_post_attachments_id( $attachment_links );
				}

				if ( $attachment_ids && ( get_tutor_post_types( 'assignment' ) !== get_post_type( $parent_id ) || get_post_type( $parent_id ) !== $cb_assignment ) ) {
					update_post_meta( $parent_id, '_tutor_attachments', $attachment_ids );
				}

				if ( $meta && ( get_tutor_post_types( 'assignment' ) === get_post_type( $parent_id ) || get_post_type( $parent_id ) === $cb_assignment ) ) {
					$this->assignment_importer->set_assignment_meta( $meta, $parent_id, $attachment_ids );
				}

				if ( $taxonomies ) {
					$result = $this->course_importer->course_importer_set_categories_tags( array( $parent_id => $taxonomies ) );
					if ( is_wp_error( $result ) ) {
						return $result;
					}
				}

				if ( get_post_type( $parent_id ) === $cb_question ) {
					$quiz_question_answer = $this->quiz_importer->flatten_quiz_question_answer( null, $parent_id, $question, $answers );
					$this->quiz_importer->save_quiz_questions_answers( $quiz_question_answer );
				}

				if ( $meta && get_tutor_post_types( 'quiz' ) === get_post_type( $parent_id ) ) {
					$this->quiz_importer->set_quiz_meta( $meta, $parent_id );
				}

				if ( get_tutor_post_types( 'quiz' ) === get_post_type( $parent_id ) && $question_answer ) {
					$quiz_question_answer = $this->quiz_importer->flatten_quiz_question_answer( array( $parent_id => $question_answer ) );
					$this->quiz_importer->save_quiz_questions_answers( $quiz_question_answer );
				}

				if ( $contents ) {
					$contents = $this->add_post_parent( $contents, $parent_id );
					$this->import_content( $contents, $keep_media_files, $collection_id );
				}

				if ( $children ) {
					$children = $this->add_post_parent( $children, $parent_id );
					$this->import_content( $children, $keep_media_files, $collection_id );
				}
			}

			if ( $collection_id ) {
				return $import_id;
			}

			return $parent_id;
		}
	}

	/**
	 * Prepare child contents when importing contents to content bank.
	 *
	 * @since 3.7.1
	 *
	 * @param array $children the child contents array.
	 * @param int   $collection_id the collection id.
	 *
	 * @return array
	 */
	public function prepare_content_bank_question( $children, $collection_id ) {
		$updated_posts = array();

		foreach ( $children as $key => $data ) {
			if ( get_tutor_post_types( 'quiz' ) === $data['post_type'] ) {
				$question_answers = $data['question_answer'] ?? null;
				if ( $question_answers ) {
					foreach ( $question_answers as $question_answer ) {
						$question                 = $question_answer['question'];
						$answers                  = $question_answer['answers'];
						$question['quiz_id']      = 0;
						$post_data['post_title']  = $question['question_title'];
						$post_data['post_type']   = ContentModel::get_post_type_by_content_type( self::TYPE_QUESTION );
						$post_data['post_status'] = 'publish';
						$post_data['post_parent'] = $collection_id;
						$post_data['question']    = $question;
						$post_data['answers']     = $answers;

						$updated_posts[] = $post_data;
					}
				}
				continue;
			}

			$updated_posts[] = $data;
		}

		return $updated_posts;
	}

	/**
	 * Get attachment ids from attachment url.
	 *
	 * @since 3.6.0
	 *
	 * @param array $attachment_urls the attachment url list.
	 *
	 * @return array
	 */
	public function get_post_attachments_id( array $attachment_urls ) {
		$attachment_ids = array();
		foreach ( $attachment_urls as $url ) {
			if ( $url ) {
				$upload_data = $this->url_upload_file( $url );

				if ( is_wp_error( $upload_data ) ) {
					continue;
				}
				$attachment_ids[] = $upload_data['id'];
			}
		}

		return $attachment_ids;
	}

	/**
	 * Upload and save post thumbnail meta.
	 *
	 * @since 3.6.0
	 *
	 * @param string  $thumbnail_url the thumbnail urls array.
	 * @param integer $post_id the post id to save meta.
	 *
	 * @return bool|\WP_Error
	 */
	public function save_post_thumbnail( string $thumbnail_url, int $post_id ) {

		$upload_data = $this->url_upload_file( $thumbnail_url );
		$response    = true;

		if ( ! is_wp_error( $upload_data ) ) {
			$response = set_post_thumbnail( $post_id, $upload_data['id'] );
		}

		return $response;
	}

	/**
	 * Replace old parent ids with new parent ids after insertion.
	 *
	 * @since 3.6.0
	 *
	 * @param array $contents the array of contents to replace parent id.
	 * @param array $parent_ids the array of parent ids to replace.
	 *
	 * @return array
	 */
	public function replace_parent_ids( $contents, $parent_ids ) {
		$data = array();

		foreach ( array_keys( $contents ) as $key => $id ) {
			$data[ $parent_ids[ $key ] ] = $contents[ $id ];
		}

		return $data;
	}

	/**
	 * Flatten an array with child content as value and key as parent id,
	 * replace old parent id with parent id from key.
	 *
	 * @since 3.6.0
	 *
	 * @param array $contents the array of contents to flatten.
	 * @param array $parent_id the array of parent ids to replace.
	 *
	 * @return array
	 */
	public function add_post_parent( $contents, $parent_id ) {
		$posts = array();
		foreach ( $contents as $content ) {
			$content['post_parent'] = $parent_id;
			$posts[]                = $content;
		}
		return $posts;
	}

	/**
	 * Inserts categories and tags if not exist in new site.
	 *
	 * @since 3.6.0
	 *
	 * @param array $taxonomies the array of taxonomies.
	 *
	 * @return bool|\WP_Error
	 */
	public function set_tutor_course_taxonomies( $taxonomies ) {
		$categories = array_merge( ...array_column( $taxonomies, 'categories' ) );
		$tags       = array_merge( ...array_column( $taxonomies, 'tags' ) );

		if ( $categories ) {
			foreach ( $categories as $category ) {
				if ( ! term_exists( $category['name'] ) ) {

					if ( $category['parent'] ) {
						$category_list      = array_column( $categories, 'name', 'term_id' );
						$parent_term_name   = $category_list[ $category['parent'] ];
						$term               = get_term_by( 'name', $parent_term_name, CourseModel::COURSE_CATEGORY );
						$category['parent'] = $term ? $term->term_id : 0;
					}

					$response = wp_insert_term(
						$category['name'],
						CourseModel::COURSE_CATEGORY,
						array(
							'parent'      => $category['parent'],
							'description' => $category['description'],
							'slug'        => $category['slug'],
						)
					);

					if ( is_wp_error( $response ) ) {
						return $response;
					}
				}
			}
		}

		if ( $tags ) {
			foreach ( $tags as $tag ) {
				if ( ! term_exists( $tag['name'] ) ) {
					$response = wp_insert_term(
						$tag['name'],
						CourseModel::COURSE_TAG,
						array(
							'parent'      => $tag['parent'],
							'description' => $tag['description'],
							'slug'        => $tag['slug'],
						)
					);

					if ( is_wp_error( $response ) ) {
						return $response;
					}
				}
			}
		}

		return true;
	}

	/**
	 * Prepare post data before insertion.
	 *
	 * @since 3.6.0
	 * @since 3.7.1 param $collection_id added.
	 *
	 * @param array $post the post data to prepare.
	 * @param int   $collection_id the collection id.
	 *
	 * @return array|\WP_Error
	 */
	public function prepare_post_data( $post, $collection_id = 0 ) {

		if ( isset( $post['ID'] ) ) {
			unset( $post['ID'] );
		}

		$post = sanitize_post( $post, 'db' );

		$content_bank_post_type = array();

		if ( tutor_utils()->is_addon_enabled( 'content-bank' ) ) {
			$content_bank_post_type = array( self::TYPE_CONTENT_BANK, ...ContentModel::get_content_post_types() );

			if ( ! isset( $post['post_status'] ) ) {
				$post['post_status'] = 'publish';
			}

			if ( $collection_id ) {
				if ( get_tutor_post_types( 'lesson' ) === $post['post_type'] ) {
					$post['post_type'] = ContentModel::get_post_type_by_content_type( self::TYPE_LESSON );
				}

				if ( get_tutor_post_types( 'assignment' ) === $post['post_type'] ) {
					$post['post_type'] = ContentModel::get_post_type_by_content_type( self::TYPE_ASSIGNMENT );
				}

				$post['post_parent'] = $collection_id;
			}
		}

		$rules = array(
			'post_title' => 'required',
			'post_type'  => 'required|match_string:' . implode( ',', array( ...array_values( get_tutor_post_types() ), ...$content_bank_post_type ) ),
		);

		$validate_content = ValidationHelper::validate( $rules, $post );

		if ( ! $validate_content->success ) {
			return new \WP_Error( 'invalid_post_data', __( 'Post data is invalid', 'tutor-pro' ), $validate_content->errors );
		}

		$post['post_author'] = get_current_user_id();
		return $post;
	}


	/**
	 * Handle file upload from given url.
	 *
	 * @since 3.6.0
	 *
	 * @param string $file_url the file url.
	 *
	 * @return int|\WP_Error
	 */
	public static function url_upload_file( $file_url ) {

		if ( empty( $file_url ) ) {
			return new \WP_Error( 'invalid_file_url', 'Invalid file URL provided.' );
		}

		$upload_dir = wp_upload_dir()['basedir'];

		$parse_url = parse_url( $file_url );

		$base_url = $parse_url['scheme'] . '://' . $parse_url['host'];

		if ( isset( $parse_url['port'] ) ) {
			$base_url .= ':' . $parse_url['port'];
		}

		// Add sub path.
		$base_url .= strstr( $parse_url['path'], 'wp-content', true );

		$file_name       = basename( $file_url );
		$source_dir_url  = str_replace( $file_name, '', $file_url );
		$source_dir_part = str_replace( $base_url . 'wp-content/uploads/', '', $source_dir_url );

		$file_path = trailingslashit( $upload_dir ) . trailingslashit( $source_dir_part ) . $file_name;

		$upload_dir = trailingslashit( $upload_dir ) . trailingslashit( $source_dir_part );

		try {
			if ( ! file_exists( $file_path ) ) {

				if ( ! file_exists( $upload_dir ) ) {
					mkdir( $upload_dir, 0777, true );
				}

				$file_data = file_get_contents( $file_url );
				if ( false !== $file_data ) {
					// Save the image to the uploads directory.
					file_put_contents( $file_path, $file_data );
				} else {
					return new \WP_Error( 'download_failed', 'Failed to download content ' . $file_url );
				}
			}
		} catch ( \Throwable $th ) {
			return new \WP_Error( 'download_failed', 'Failed to download content ' . $file_url, $th->getMessage() );
		}

		$file_type = wp_check_filetype( $file_name );

		$file_url = str_replace( $source_dir_url, site_url( '/wp-content/uploads/' . $source_dir_part ), $file_url );

		$attachment_args = array(
			'guid'           => $file_url,
			'post_mime_type' => $file_type['type'],
			'post_title'     => preg_replace( '/\.[^.]+$/', '', $file_name ),
			'post_status'    => 'inherit',
		);

		$attach_id = wp_insert_attachment( $attachment_args, $file_path, 0, true );

		if ( is_wp_error( $attach_id ) ) {
			return $attach_id;
		}

		if ( wp_attachment_is_image( $attach_id ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
			$attach_data = wp_generate_attachment_metadata( $attach_id, $file_path );
			wp_update_attachment_metadata( $attach_id, $attach_data );
		}

		return array(
			'url' => $file_url,
			'id'  => $attach_id,
		);
	}

	/**
	 * Reset global $_POST and $_REQUEST data.
	 *
	 * @since 3.6.0
	 *
	 * @param string $key the key to look for the data.
	 *
	 * @return void
	 */
	public static function reset_post_data( string $key ) {
		if ( isset( $_POST[ $key ] ) ) { //phpcs:ignore
			unset( $_POST[ $key ] );
		}

		if ( isset( $_REQUEST[ $key ] ) ) {
			unset( $_REQUEST[ $key ] );
		}
	}
}
