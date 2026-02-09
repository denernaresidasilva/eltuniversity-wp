<?php

namespace WPAdminify\Inc\Modules\Folders;

use WPAdminify\Inc\Utils;
use WPAdminify\Inc\Admin\AdminSettings;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
class Folders {
    private static $postIds;

    public $options;

    public function __construct() {
        $this->options = (array) AdminSettings::get_instance()->get( 'folders' );
        $needed_keys = ['user_roles', 'enable_for', 'media'];
        add_action( 'init', [$this, 'register_folders_terms'], 999 );
        add_action( 'admin_footer', [$this, 'add_footer_markup'] );
        add_action( 'admin_enqueue_scripts', [$this, 'enqueue_scripts'], PHP_INT_MAX );
        add_action( 'wp_ajax_adminify_folder', [$this, 'handle_adminify_folder'] );
        add_action( 'ajax_query_attachments_args', [$this, 'filter_grid'] );
        add_filter( 'pre_get_posts', [$this, 'filter_list'] );
        add_action( 'admin_init', [$this, 'modify_columns_actions'] );
        add_action( 'print_media_templates', [$this, 'modify_media_templates'] );
        add_filter( 'wp_prepare_attachment_for_js', [$this, 'modify_attachment_for_js'] );
        add_action( 'pre-upload-ui', [$this, 'wp_adminify_select_folder_when_upload'] );
        add_filter(
            'attachment_fields_to_edit',
            [$this, 'wp_adminify_edit_attachment_fields'],
            25,
            2
        );
        add_action( 'wp_ajax_wp_adminify_assign_media_folder', array($this, 'wp_adminify_handle_folder_assignment') );
        add_action( 'restrict_manage_posts', [$this, 'wp_adminify_show_folders_in_list_filter'] );
        add_action( 'add_attachment', [$this, 'wp_adminify_assign_media_folder_to_new_attachment'] );
        add_action( 'enqueue_block_editor_assets', [$this, 'enqueue_scripts_for_media_uploads'], PHP_INT_MAX );
    }

    public function modify_attachment_for_js( $response ) {
        if ( !(bool) $this->options['media'] ) {
            return $response;
        }
        $folders = wp_get_post_terms( $response['id'], 'media_folder' );
        $folder_ids = wp_list_pluck( $folders, 'term_id' );
        $response['media_folder'] = implode( ',', $folder_ids );
        return $response;
    }

    public function get_post_types() {
        $post_types = get_post_types( [
            'public' => true,
        ] );
        // @todo add more configurations here
        return $post_types;
    }

    public function filter_list( $query ) {
        global $typenow;
        global $pagenow;
        $post_type = $typenow;
        if ( empty( $post_type ) ) {
            if ( $pagenow == 'edit.php' ) {
                $post_type = 'post';
            } elseif ( $pagenow == 'upload.php' ) {
                $post_type = 'attachment';
            }
        }
        if ( !isset( $query->query['post_type'] ) ) {
            return $query;
        }
        $taxonomy = self::get_post_type_taxonomy( $post_type );
        if ( !isset( $_REQUEST[$taxonomy] ) ) {
            return $query;
        }
        $term = sanitize_text_field( wp_unslash( $_REQUEST[$taxonomy] ) );
        if ( $term != '-1' ) {
            return $query;
        }
        unset($query->query_vars[$taxonomy]);
        $tax_query = [
            'taxonomy' => $taxonomy,
            'operator' => __( 'NOT EXISTS', 'adminify' ),
        ];
        $query->set( 'tax_query', [$tax_query] );
        $query->tax_query = new \WP_Tax_Query([$tax_query]);
        return $query;
    }

    public function filter_grid( $args ) {
        $taxonomy = self::get_post_type_taxonomy( 'attachment' );
        if ( !isset( $args[$taxonomy] ) ) {
            return $args;
        }
        $term = sanitize_text_field( $args[$taxonomy] );
        if ( $term != '-1' ) {
            return $args;
        }
        unset($args[$taxonomy]);
        $args['tax_query'] = [[
            'taxonomy' => $taxonomy,
            'operator' => __( 'NOT EXISTS', 'adminify' ),
        ]];
        return $args;
    }

    public function is_module_active( $post_type = null ) {
        $current_screen = get_current_screen();
        if ( empty( $current_screen->base ) || !in_array( $current_screen->base, ['upload', 'edit', 'media'] ) ) {
            return false;
        }
        if ( $current_screen->base === 'media' && $current_screen->action === 'add' ) {
            $post_type = 'attachment';
        }
        if ( empty( $post_type ) ) {
            if ( empty( $post_type = $current_screen->post_type ) ) {
                return false;
            }
        }
        if ( $post_type == 'attachment' ) {
            return (bool) $this->options['media'];
        }
        $default_post_types = ['post', 'page'];
        $folders_enable_for = (array) $this->options['enable_for'];
        $folders_enable_for = array_intersect( $folders_enable_for, $default_post_types );
        if ( in_array( $post_type, $folders_enable_for ) ) {
            return true;
        }
        return false;
    }

    public function add_footer_markup() {
        if ( $this->is_module_active() ) {
            echo '</div><div id="wp-adminify--folder-app"></div>';
        }
    }

    public function get_uncategorized_posts( $post_type ) {
        global $wpdb;
        $post_table = $wpdb->prefix . 'posts';
        $term_table = $wpdb->prefix . 'term_relationships';
        $term_taxonomy_table = $wpdb->prefix . 'term_taxonomy';
        $post_type_tax = self::get_post_type_taxonomy( $post_type );
        if ( $post_type != 'attachment' ) {
            $query = "SELECT COUNT(DISTINCT({$post_table}.ID)) AS total_records FROM {$post_table} WHERE 1=1  AND (\n                NOT EXISTS (\n                    SELECT 1\n                    FROM {$term_table}\n                    INNER JOIN {$term_taxonomy_table}\n                    ON {$term_taxonomy_table}.term_taxonomy_id = {$term_table}.term_taxonomy_id\n                    WHERE {$term_taxonomy_table}.taxonomy = '%s'\n                    AND {$term_table}.object_id = {$post_table}.ID\n                )\n            ) AND {$post_table}.post_type = '%s' AND (({$post_table}.post_status = 'publish' OR {$post_table}.post_status = 'future' OR {$post_table}.post_status = 'draft' OR {$post_table}.post_status = 'private'))";
        } else {
            $query = "SELECT COUNT(DISTINCT({$post_table}.ID)) AS total_records FROM {$post_table} WHERE 1=1  AND (\n                NOT EXISTS (\n                    SELECT 1\n                    FROM {$term_table}\n                    INNER JOIN {$term_taxonomy_table}\n                    ON {$term_taxonomy_table}.term_taxonomy_id = {$term_table}.term_taxonomy_id\n                    WHERE {$term_taxonomy_table}.taxonomy = '%s'\n                    AND {$term_table}.object_id = {$post_table}.ID\n                )\n            ) AND {$post_table}.post_type = '%s' AND {$post_table}.post_status = 'inherit'";
        }
        $query = $wpdb->prepare( $query, $post_type_tax, $post_type );
        $total = $wpdb->get_var( $query );
        return ( empty( $total ) ? 0 : $total );
    }

    public function get_folders( $post_type, $post_type_tax ) {
        $folders = get_terms( $post_type_tax, [
            'hide_empty' => false,
            'pad_counts' => true,
        ] );
        if ( is_wp_error( $folders ) ) {
            return [];
        }
        foreach ( $folders as $folder ) {
            $posts = $this->get_posts_by_folder( $folder->term_id, $post_type, $post_type_tax );
            $color = get_term_meta( $folder->term_id, '_wp_adminify_fodler_color', true );
            if ( empty( $color ) ) {
                $color = '';
            }
            $folder->color = $color;
            $folder->count = count( $posts );
            $folder->posts = $posts;
        }
        return $folders;
    }

    public function enqueue_scripts() {
        if ( !$this->is_module_active() ) {
            return;
        }
        $current_screen = get_current_screen();
        wp_enqueue_style(
            'wp-adminify--folder',
            WP_ADMINIFY_URL . 'assets/admin/css/wp-adminify--folder' . Utils::assets_ext( '.css' ),
            [],
            WP_ADMINIFY_VER
        );
        wp_enqueue_style(
            'wp-adminify-simple-line-icons',
            WP_ADMINIFY_ASSETS . '/vendors/font-icons/simple-line-icons/css/simple-line-icons' . Utils::assets_ext( '.css' ),
            false,
            WP_ADMINIFY_VER
        );
        $post_type = $current_screen->post_type;
        $post_type_tax = self::get_post_type_taxonomy( $post_type );
        if ( $current_screen->base === 'media' && $current_screen->action === 'add' ) {
            $post_type = 'attachment';
            $post_type_tax = 'media_folder';
        }
        $media_folders = get_terms( [
            'taxonomy'   => 'media_folder',
            'hide_empty' => false,
        ] );
        $media_folders_data = [];
        foreach ( $media_folders as $media_folder ) {
            $media_folders_data[$media_folder->term_id] = [
                'name'    => $media_folder->name,
                'slug'    => $media_folder->slug,
                'term_id' => $media_folder->term_id,
            ];
        }
        $data = [
            'adminurl'           => admin_url(),
            'ajaxurl'            => admin_url( 'admin-ajax.php' ),
            'post_type'          => $post_type,
            'nonce'              => wp_create_nonce( '__adminify-folder-secirity__' ),
            'post_type_tax'      => $post_type_tax,
            'media_folders_data' => $media_folders_data,
            'is_pro'             => wp_validate_boolean( jltwp_adminify()->can_use_premium_code__premium_only() ),
            'pro_notice'         => Utils::adminify_upgrade_pro(),
        ];
        $data = array_merge( $data, $this->refreshed_folder_data( $post_type, $post_type_tax ) );
        // Adminify Folder
        wp_enqueue_script(
            'wp-adminify-vue-vendors',
            WP_ADMINIFY_ASSETS . 'admin/js/vendor' . Utils::assets_ext( '.js' ),
            [],
            WP_ADMINIFY_VER,
            true
        );
        wp_enqueue_script(
            'wp-adminify--folder',
            WP_ADMINIFY_ASSETS . 'admin/js/wp-adminify--folder' . Utils::assets_ext( '.js' ),
            array(
                'jquery',
                'jquery-ui-droppable',
                'jquery-ui-draggable',
                'wp-adminify-vue-vendors'
            ),
            WP_ADMINIFY_VER,
            true
        );
        wp_localize_script( 'wp-adminify--folder', 'wp_adminify__folder_data', $data );
        $inline_script_content = '
			(function($) {
				$(document).ready(function() {

					let selectedFolder = null;

					if (typeof wp.media !== "undefined") {
						const urlParams = new URLSearchParams(window.location.search);
						const currentFolder = urlParams.get("media_folder");
						selectedFolder = currentFolder;

						const MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
							id: "wp-adminify-media-folder-filter",
							createFilters: function() {
								const filters = {};
								filters.all = {
									text: "All folders",
									props: { folders: "" },
									priority: 10
								};
								_.each(window.wp_adminify__folder_data?.media_folders_data || {}, function(value) {
									filters[value.slug] = {
										text: value.name,
										props: { folders: value.slug },
										priority: 20
									};
								});
								this.filters = filters;
							},
							initialize: function() {
								wp.media.view.AttachmentFilters.prototype.initialize.apply(this, arguments);
								if (currentFolder) {
									this.model.set("folders", currentFolder);
									setTimeout(() => {
										this.$el.find("select").val(currentFolder);
									}, 100);
								}
							}
						});

						const AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
						wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend({
							createToolbar: function() {
								AttachmentsBrowser.prototype.createToolbar.call(this);
								this.toolbar.set("MediaLibraryTaxonomyFilter", new MediaLibraryTaxonomyFilter({
									controller: this.controller,
									model: this.collection.props,
									priority: -75
								}).render());
							}
						});
					}

					if (typeof wp.Uploader === "function") {
						$.extend(wp.Uploader.prototype, {
							init: function() {
								selectedFolder = $("#folders").val() || currentFolder;

								$("body").on("change", "#folders", function() {
									selectedFolder = $(this).val();
								});

								if (this.uploader) {
									this.uploader.bind("BeforeUpload", function(up, file) {
										up.settings.multipart_params = up.settings.multipart_params || {};
										up.settings.multipart_params.folder_id = selectedFolder;
									});
									this.uploader.bind("UploadComplete", function() {
										if (typeof wp !== "undefined" && wp.media && wp.media.frame) {
											const frame = wp.media.frame;
											const state = frame.state();
											const library = state.get("library");

											if (library) {
												library.props.set("ignore", Date.now());
												state.trigger("reset");
											}
										}
									});
								}
							}
						});
					}

				});
			})(jQuery);
		';
        wp_add_inline_script( 'media-views', $inline_script_content, 'after' );
    }

    function enqueue_scripts_for_media_uploads() {
        wp_enqueue_media();
        wp_enqueue_script( 'media-views' );
        $post_type = 'attachment';
        $post_type_tax = 'media_folder';
        $media_folders = get_terms( [
            'taxonomy'   => 'media_folder',
            'hide_empty' => false,
        ] );
        $media_folders_data = [];
        foreach ( $media_folders as $folder ) {
            $media_folders_data[$folder->term_id] = [
                'name'    => $folder->name,
                'slug'    => $folder->slug,
                'term_id' => $folder->term_id,
            ];
        }
        // Localize the data
        wp_localize_script( 'media-views', 'wp_adminify__folder_data', [
            'media_folders_data' => $media_folders_data,
        ] );
        $inline_script_content = '
			(function($) {
				$(document).ready(function() {

					let selectedFolder = null;

					if (typeof wp.media !== "undefined") {
						const urlParams = new URLSearchParams(window.location.search);
						const currentFolder = urlParams.get("media_folder");
						selectedFolder = currentFolder;

						const MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
							id: "wp-adminify-media-folder-filter",
							createFilters: function() {
								const filters = {};
								filters.all = {
									text: "All folders",
									props: { folders: "" },
									priority: 10
								};
								_.each(window.wp_adminify__folder_data?.media_folders_data || {}, function(value) {
									filters[value.slug] = {
										text: value.name,
										props: { folders: value.slug },
										priority: 20
									};
								});
								this.filters = filters;
							},
							initialize: function() {
								wp.media.view.AttachmentFilters.prototype.initialize.apply(this, arguments);
								if (currentFolder) {
									this.model.set("folders", currentFolder);
									setTimeout(() => {
										this.$el.find("select").val(currentFolder);
									}, 100);
								}
							}
						});

						const AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
						wp.media.view.AttachmentsBrowser = AttachmentsBrowser.extend({
							createToolbar: function() {
								AttachmentsBrowser.prototype.createToolbar.call(this);
								this.toolbar.set("MediaLibraryTaxonomyFilter", new MediaLibraryTaxonomyFilter({
									controller: this.controller,
									model: this.collection.props,
									priority: -75
								}).render());
							}
						});
					}

					if (typeof wp.Uploader === "function") {
						$.extend(wp.Uploader.prototype, {
							init: function() {
								selectedFolder = $("#folders").val() || currentFolder;

								$("body").on("change", "#folders", function() {
									selectedFolder = $(this).val();
								});

								if (this.uploader) {
									this.uploader.bind("BeforeUpload", function(up, file) {
										up.settings.multipart_params = up.settings.multipart_params || {};
										up.settings.multipart_params.folder_id = selectedFolder;
									});
									this.uploader.bind("UploadComplete", function() {
										if (typeof wp !== "undefined" && wp.media && wp.media.frame) {
											const frame = wp.media.frame;
											const state = frame.state();
											const library = state.get("library");

											if (library) {
												library.props.set("ignore", Date.now());
												state.trigger("reset");
											}
										}
									});
								}
							}
						});
					}

				});
			})(jQuery);
		';
        wp_add_inline_script( 'media-views', $inline_script_content, 'after' );
    }

    public function modify_media_templates() {
        ?>

		<script>
			var attachment_template = jQuery('#tmpl-attachment');
            if ( attachment_template.length ) {
                var template = attachment_template.html().replace( 'data.orientation }}">', 'data.orientation }}" data-folders="{{ data.media_folder }}">' );
                attachment_template.html( template );
            }
		</script>

		<?php 
    }

    public function handle_adminify_folder() {
        check_ajax_referer( '__adminify-folder-secirity__' );
        if ( !empty( $_POST['route'] ) ) {
            $route_handler = 'handle_' . sanitize_text_field( wp_unslash( $_POST['route'] ) );
            if ( is_callable( get_class( $this ), $route_handler ) ) {
                $this->{$route_handler}( $_POST );
            }
        }
        wp_send_json_error( [
            'message' => __( 'Something is wrong, no route found' ),
        ], 400 );
    }

    public function handle_delete_folders( $data ) {
        if ( empty( $data['term_ids'] ) || empty( $data['post_type'] ) || empty( $data['post_type_tax'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        $term_ids = $data['term_ids'];
        $post_type = $data['post_type'];
        $post_type_tax = $data['post_type_tax'];
        foreach ( $term_ids as $term_id ) {
            wp_delete_term( $term_id, $post_type_tax );
        }
        $data = $this->refreshed_folder_data( $post_type, $post_type_tax );
        wp_send_json_success( $data );
    }

    public function handle_rename_folder( $data ) {
        if ( empty( $data['term_id'] ) || empty( $data['post_type'] ) || empty( $data['post_type_tax'] ) || empty( $data['folder_name'] ) || empty( $data['folder_color_tag'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        $term_id = $data['term_id'];
        $folder_name = $data['folder_name'];
        $post_type = $data['post_type'];
        $post_type_tax = $data['post_type_tax'];
        $folder_color_tag = $data['folder_color_tag'];
        $update = wp_update_term( $term_id, $post_type_tax, [
            'name' => $folder_name,
        ] );
        if ( is_wp_error( $update ) ) {
            wp_send_json_success( [
                'message' => $update->get_error_message(),
            ], 202 );
        }
        update_term_meta( $term_id, '_wp_adminify_fodler_color', $folder_color_tag );
        $data = $this->refreshed_folder_data( $post_type, $post_type_tax );
        wp_send_json_success( $data );
    }

    public function handle_move_to_folder( $data ) {
        if ( empty( $data['post_ids'] ) || empty( $data['folder_id'] ) || empty( $data['post_type'] ) || empty( $data['post_type_tax'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        $post_ids = (array) $data['post_ids'];
        $folder_id = sanitize_text_field( $data['folder_id'] );
        $post_type = sanitize_text_field( $data['post_type'] );
        $post_type_tax = sanitize_text_field( $data['post_type_tax'] );
        global $mode;
        $mode = ( empty( $data['mode'] ) ? 'list' : sanitize_text_field( $data['mode'] ) );
        $move_to_folder = wp_validate_boolean( $data['move_to_folder'] );
        foreach ( $post_ids as $post_id ) {
            if ( $folder_id == 'uncategorized' ) {
                wp_set_object_terms(
                    $post_id,
                    '',
                    $post_type_tax,
                    false
                );
                continue;
            }
            $term = get_term( $folder_id );
            if ( !empty( $term ) && isset( $term->slug ) ) {
                wp_set_object_terms(
                    $post_id,
                    $term->slug,
                    $post_type_tax,
                    !$move_to_folder
                );
            }
        }
        $updated_rows = '';
        if ( $post_type == 'attachment' ) {
            global $wp_query;
            $args = [
                'posts_per_page' => count( $post_ids ),
                'orderby'        => 'title',
                'order'          => 'ASC',
                'post_type'      => $post_type,
                'post_status'    => 'any',
                'post__in'       => $post_ids,
            ];
            $wp_query = new \WP_Query($args);
            $wp_list_table = \_get_list_table( 'WP_Media_List_Table', [
                'screen' => sanitize_text_field( wp_unslash( $_POST['screen'] ) ),
            ] );
            foreach ( $post_ids as $post_id ) {
                ob_start();
                $wp_list_table->display_rows();
                $updated_rows = ob_get_clean();
            }
        } else {
            $wp_list_table = \_get_list_table( 'WP_Posts_List_Table', [
                'screen' => sanitize_text_field( wp_unslash( $_POST['screen'] ) ),
            ] );
            foreach ( $post_ids as $post_id ) {
                $level = 0;
                if ( is_post_type_hierarchical( $wp_list_table->screen->post_type ) ) {
                    $request_post = [get_post( $post_id )];
                    $parent = $request_post[0]->post_parent;
                    while ( $parent > 0 ) {
                        $parent_post = get_post( $parent );
                        $parent = $parent_post->post_parent;
                        $level++;
                    }
                }
                ob_start();
                $wp_list_table->display_rows( [get_post( $post_id )], $level );
                $updated_rows .= ob_get_clean();
            }
        }
        $data = [
            'updated_rows' => $updated_rows,
        ];
        $data = array_merge( $data, $this->refreshed_folder_data( $post_type, $post_type_tax ) );
        wp_send_json_success( $data );
    }

    public function handle_refresh_folders( $data ) {
        if ( empty( $data['post_type'] ) || empty( $data['post_type_tax'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        $post_type = $data['post_type'];
        $post_type_tax = $data['post_type_tax'];
        wp_send_json_success( $this->refreshed_folder_data( $post_type, $post_type_tax ) );
    }

    public function handle_create_new_folder( $data ) {
        if ( empty( $data['post_type'] ) || empty( $data['post_type_tax'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        if ( empty( $data['new_folder_name'] ) || empty( $data['folder_color_tag'] ) ) {
            wp_send_json_error( [
                'message' => __( 'Something is wrong, few args are missing' ),
            ], 400 );
        }
        $post_type = $data['post_type'];
        $post_type_tax = $data['post_type_tax'];
        $new_folder_name = $data['new_folder_name'];
        $folder_color_tag = $data['folder_color_tag'];
        $parent_term_id = 0;
        if ( jltwp_adminify()->can_use_premium_code__premium_only() && !empty( $data['parent_folder'] ) ) {
            $parent_term_id = $data['parent_folder'];
        }
        $insert_data = wp_insert_term( $new_folder_name, $post_type_tax, [
            'parent' => $parent_term_id,
        ] );
        if ( !is_wp_error( $insert_data ) ) {
            update_term_meta( $insert_data['term_id'], '_wp_adminify_fodler_color', $folder_color_tag );
            wp_send_json_success( $this->refreshed_folder_data( $post_type, $post_type_tax ) );
        } else {
            wp_send_json_success( [
                'message' => $insert_data->get_error_message(),
            ], 202 );
        }
    }

    function refreshed_folder_data( $post_type, $post_type_tax ) {
        remove_filter( 'pre_get_posts', [$this, 'filter_list'] );
        return [
            'folders'           => $this->get_folders( $post_type, $post_type_tax ),
            'folder_hierarchy'  => _get_term_hierarchy( $post_type_tax ),
            'total_posts'       => wp_count_posts( $post_type ),
            'total_uncat_posts' => $this->get_uncategorized_posts( $post_type ),
        ];
    }

    function wp_set_post_categories( $post_ID = 0, $post_categories = [], $append = false ) {
        $post_ID = (int) $post_ID;
        $post_type = get_post_type( $post_ID );
        $post_status = get_post_status( $post_ID );
        // If $post_categories isn't already an array, make it one.
        $post_categories = (array) $post_categories;
        if ( empty( $post_categories ) ) {
            /**
             * Filters post types (in addition to 'post') that require a default category.
             *
             * @since 5.5.0
             *
             * @param string[] $post_types An array of post type names. Default empty array.
             */
            $default_category_post_types = apply_filters( 'default_category_post_types', [] );
            // Regular posts always require a default category.
            $default_category_post_types = array_merge( $default_category_post_types, ['post'] );
            if ( in_array( $post_type, $default_category_post_types, true ) && is_object_in_taxonomy( $post_type, 'category' ) && 'auto-draft' !== $post_status ) {
                $post_categories = [get_option( 'default_category' )];
                $append = false;
            } else {
                $post_categories = [];
            }
        } elseif ( 1 === count( $post_categories ) && '' === reset( $post_categories ) ) {
            return true;
        }
        return wp_set_post_terms(
            $post_ID,
            $post_categories,
            'category',
            $append
        );
    }

    public function get_posts_by_folder( $term_id, $post_type, $taxonomy ) {
        $posts = get_posts( [
            'numberposts' => -1,
            'post_status' => 'any',
            'post_type'   => $post_type,
            'fields'      => 'ids',
            'tax_query'   => [[
                'operator' => 'IN',
                'taxonomy' => $taxonomy,
                'field'    => 'term_id',
                'terms'    => $term_id,
            ]],
        ] );
        return $posts;
    }

    public static function get_post_type_taxonomy( $post_type ) {
        if ( $post_type == 'page' ) {
            return 'folder';
        }
        if ( $post_type == 'attachment' ) {
            $post_type = 'media';
        }
        return $post_type . '_folder';
    }

    public function register_folders_terms() {
        $post_types = $this->get_post_types();
        foreach ( $post_types as $post_type ) {
            $labels = [
                'name'          => esc_html__( 'Folders', 'adminify' ),
                'singular_name' => esc_html__( 'Folder', 'adminify' ),
                'all_items'     => esc_html__( 'All Folders', 'adminify' ),
                'edit_item'     => esc_html__( 'Edit Folder', 'adminify' ),
                'update_item'   => esc_html__( 'Update Folder', 'adminify' ),
                'add_new_item'  => esc_html__( 'Add New Folder', 'adminify' ),
                'new_item_name' => esc_html__( 'Add folder name', 'adminify' ),
                'menu_name'     => esc_html__( 'Folders', 'adminify' ),
                'search_items'  => esc_html__( 'Search Folders', 'adminify' ),
                'parent_item'   => esc_html__( 'Parent Folder', 'adminify' ),
            ];
            $args = [
                'label'             => esc_html__( 'Folder', 'adminify' ),
                'labels'            => $labels,
                'show_tagcloud'     => false,
                'hierarchical'      => true,
                'public'            => false,
                'show_ui'           => false,
                'show_in_menu'      => false,
                'show_in_rest'      => true,
                'show_admin_column' => true,
                'query_var'         => true,
                'rewrite'           => false,
                'capabilities'      => [
                    'manage_terms' => 'manage_categories',
                    'edit_terms'   => 'manage_categories',
                    'delete_terms' => 'manage_categories',
                    'assign_terms' => 'manage_categories',
                ],
            ];
            $taxonomy = self::get_post_type_taxonomy( $post_type );
            register_taxonomy( $taxonomy, $post_type, $args );
        }
    }

    public function modify_columns_actions() {
        $post_types = $this->get_post_types();
        foreach ( $post_types as $post_type ) {
            if ( $post_type == 'post' ) {
                add_filter( 'manage_edit-post_columns', [$this, 'manage_columns_head'] );
                add_filter( 'manage_posts_columns', [$this, 'manage_columns_head'] );
                add_action(
                    'manage_posts_custom_column',
                    [$this, 'manage_columns_content'],
                    10,
                    2
                );
                add_filter( 'bulk_actions-edit-post', [$this, 'custom_bulk_action'] );
            } elseif ( $post_type == 'page' ) {
                add_filter( 'manage_edit-page_columns', [$this, 'manage_columns_head'] );
                add_filter( 'manage_page_posts_columns', [$this, 'manage_columns_head'] );
                add_action(
                    'manage_page_posts_custom_column',
                    [$this, 'manage_columns_content'],
                    10,
                    2
                );
                add_filter( 'bulk_actions-edit-page', [$this, 'custom_bulk_action'] );
            } elseif ( $post_type == 'attachment' ) {
                add_filter( 'manage_media_columns', [$this, 'manage_columns_head'] );
                add_action(
                    'manage_media_custom_column',
                    [$this, 'manage_columns_content'],
                    10,
                    2
                );
            } else {
                add_filter( 'manage_edit-' . $post_type . '_columns', [$this, 'manage_columns_head'], 99999 );
                add_action(
                    'manage_' . $post_type . '_posts_custom_column',
                    [$this, 'manage_columns_content'],
                    2,
                    2
                );
                add_filter( 'bulk_actions-edit-' . $post_type, [$this, 'custom_bulk_action'] );
            }
        }
    }

    function manage_columns_head( $posts_columns ) {
        $post_type = null;
        if ( isset( $_REQUEST['post_type'] ) ) {
            $post_type = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
        }
        if ( $this->is_module_active( $post_type ) ) {
            $title = sprintf( __( 'Moving selected items', 'adminify' ), '<span class="adminify-folder-items--count"></span>' );
            return [
                'adminify_move' => '<div class="adminify-move-multiple adminify-col" title="' . esc_attr__( 'Move selected items', 'adminify' ) . '"><span class="dashicons dashicons-move"></span><span class="adminify-move-file--title">' . wp_kses_post( $title ) . '</span><div class="adminify-items"></div></div>',
            ] + $posts_columns;
        }
        return $posts_columns;
    }

    function manage_columns_content( $column_name, $post_ID ) {
        $post_type = null;
        if ( isset( $_REQUEST['post_type'] ) ) {
            $post_type = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) );
        }
        $postIDs = self::$postIds;
        $folder_ids = $this->get_folders_by_post( $post_ID );
        $folder_ids = implode( ',', $folder_ids );
        if ( !is_array( $postIDs ) ) {
            $postIDs = [];
        }
        if ( !in_array( $post_ID, $postIDs ) ) {
            $postIDs[] = $post_ID;
            self::$postIds = $postIDs;
            if ( $this->is_module_active( $post_type ) ) {
                if ( $column_name == 'adminify_move' ) {
                    $title = get_the_title();
                    if ( strlen( $title ) > 20 ) {
                        $title = substr( $title, 0, 20 ) . '...';
                    }
                    echo Utils::wp_kses_custom( '<div class="adminify-move-file" data-id="' . esc_attr( $post_ID ) . '" data-folders="' . esc_attr( $folder_ids ) . '"><span class="adminify-move dashicons dashicons-move"></span><span class="adminify-move-file--title">' . esc_html( $title ) . '</span></div>' );
                }
            }
        }
    }

    public function get_folders_by_post( $post_ID, $return_type = 'ids' ) {
        $folder_tax = self::get_post_type_taxonomy( get_post_type( $post_ID ) );
        $folders = wp_get_post_terms( $post_ID, $folder_tax );
        if ( $return_type == 'ids' ) {
            return wp_list_pluck( $folders, 'term_id' );
        }
        return $folders;
    }

    public function custom_bulk_action( $bulk_actions ) {
        $bulk_actions['move_to_folder'] = __( 'Move to Folder', 'adminify' );
        return $bulk_actions;
    }

    public function wp_adminify_select_folder_when_upload() {
        if ( !$this->options['media'] ) {
            return;
        }
        $selected = ( isset( $_GET['media_folder'] ) ? $_GET['media_folder'] : '' );
        wp_dropdown_categories( array(
            'show_option_none' => 'Choose folder',
            'taxonomy'         => 'media_folder',
            'name'             => 'folder_id',
            'id'               => 'folders',
            'orderby'          => 'name',
            'selected'         => $selected,
            'hierarchical'     => true,
            'value_field'      => 'slug',
            'hide_empty'       => 0,
        ) );
    }

    public function wp_adminify_assign_media_folder_to_new_attachment( $post_ID ) {
        if ( isset( $_POST['folder_id'] ) && !empty( $_POST['folder_id'] ) && $_POST['folder_id'] !== '-1' ) {
            $term = get_term_by( 'slug', $_POST['folder_id'], 'media_folder' );
            if ( !$term && is_numeric( $_POST['folder_id'] ) ) {
                $term = get_term_by( 'id', (int) $_POST['folder_id'], 'media_folder' );
            }
            if ( $term && !is_wp_error( $term ) ) {
                wp_set_object_terms( $post_ID, (int) $term->term_id, 'media_folder' );
                $post_type = 'attachment';
                $post_type_tax = $_POST['folder_id'];
            } else {
                error_log( 'Media Folder: Could not find term for value "' . $_POST['folder_id'] . '" for attachment ID ' . $post_ID );
            }
            $this->refreshed_folder_data( $post_type, $post_type_tax );
        }
    }

    public function wp_adminify_edit_attachment_fields( $form_fields, $post ) {
        if ( !$this->options['media'] ) {
            return;
        }
        $folder_fields = array(
            'label'        => 'Folders',
            'show_in_edit' => false,
            'input'        => 'html',
            'value'        => '',
        );
        $taxonomy_name = 'media_folder';
        // get the assigned media library folders from the cache
        $terms = get_the_terms( $post->ID, $taxonomy_name );
        if ( $terms ) {
            $folder_fields['value'] = join( ', ', wp_list_pluck( $terms, 'slug' ) );
        }
        ob_start();
        $this->wp_adminify_render_terms_dropdown( $post->ID, $taxonomy_name );
        $html = ob_get_contents();
        ob_end_clean();
        $folder_fields['html'] = $html;
        $form_fields[$taxonomy_name] = $folder_fields;
        return $form_fields;
    }

    public function wp_adminify_render_terms_dropdown( $post_id, $taxonomy ) {
        $selected_terms = wp_get_object_terms( $post_id, $taxonomy, array(
            'fields' => 'ids',
        ) );
        $selected_term = ( !empty( $selected_terms ) ? $selected_terms[0] : 0 );
        $terms = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => 0,
        ) );
        echo '<select name="media_folder_select" class="media-folder-select" data-attachment-id="' . $post_id . '">';
        echo '<option value="">— Select Folder —</option>';
        if ( !empty( $terms ) && !is_wp_error( $terms ) ) {
            foreach ( $terms as $term ) {
                $this->wp_adminify_render_term_option( $term, $selected_term, $taxonomy );
            }
        }
        echo '</select>';
        $this->wp_adminify_add_folder_script();
    }

    public function wp_adminify_render_term_option(
        $term,
        $selected_term,
        $taxonomy,
        $depth = 0
    ) {
        $indent = str_repeat( '&nbsp;&nbsp;&nbsp;', $depth );
        $selected = selected( $term->term_id, $selected_term, false );
        echo sprintf(
            '<option value="%s" %s>%s%s</option>',
            $term->term_id,
            $selected,
            $indent,
            esc_html( $term->name )
        );
        // Get children
        $children = get_terms( array(
            'taxonomy'   => $taxonomy,
            'hide_empty' => false,
            'parent'     => $term->term_id,
        ) );
        if ( !empty( $children ) && !is_wp_error( $children ) ) {
            foreach ( $children as $child ) {
                $this->wp_adminify_render_term_option(
                    $child,
                    $selected_term,
                    $taxonomy,
                    $depth + 1
                );
            }
        }
    }

    public function wp_adminify_add_folder_script() {
        ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        $('.media-folder-select').on('change', function() {
            var $select = $(this);
            var attachment_id = $select.data('attachment-id');
            var term_id = $select.val();
            
            if (!term_id) return;
            
            // Store original value in case we need to revert
            $select.data('prev-value', $select.val());
            
            // Show loading indicator
            var $spinner = $('<span class="spinner is-active"></span>');
            $select
                .prop('disabled', true)
                .after($spinner);
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'wp_adminify_assign_media_folder',
                    attachment_id: attachment_id,
                    term_id: term_id,
                    security: '<?php 
        echo wp_create_nonce( "wp_adminify_media_folder" );
        ?>'
                },
                success: function(response) {
                    if (response.success) {
                    } else {
                        $select.val($select.data('prev-value'));
                        console.log('Error: ' + (response.data || 'Failed to update folder'));
                    }
                },
                error: function(xhr, status, error) {
                    $select.val($select.data('prev-value'));
                    console.log('Error: ' + error);
                },
                complete: function() {
                    // Always clean up
                    $spinner.remove();
                    $select.prop('disabled', false);
                }
            });
        });
    });
    </script>
    <?php 
    }

    public function wp_adminify_handle_folder_assignment() {
        check_ajax_referer( 'wp_adminify_media_folder', 'security' );
        $attachment_id = ( isset( $_POST['attachment_id'] ) ? intval( $_POST['attachment_id'] ) : 0 );
        $term_id = ( isset( $_POST['term_id'] ) ? intval( $_POST['term_id'] ) : 0 );
        $taxonomy = 'media_folder';
        if ( !$attachment_id || !$term_id ) {
            wp_send_json_error( 'Invalid data' );
        }
        // First remove all terms from this taxonomy
        wp_delete_object_term_relationships( $attachment_id, $taxonomy );
        // Add the new term
        $result = wp_set_object_terms( $attachment_id, $term_id, $taxonomy );
        if ( is_wp_error( $result ) ) {
            wp_send_json_error( $result->get_error_message() );
        }
        wp_send_json_success();
    }

    public function wp_adminify_show_folders_in_list_filter() {
        global $typenow;
        if ( !$this->is_module_active() ) {
            return;
        }
        if ( 'attachment' !== $typenow ) {
            return;
        }
        $selected = ( isset( $_GET['media_folder'] ) ? $_GET['media_folder'] : '' );
        wp_dropdown_categories( array(
            'show_option_all' => 'All folders',
            'taxonomy'        => 'media_folder',
            'name'            => 'folder_id',
            'id'              => 'wp-adminify-media-folder-filter',
            'orderby'         => 'name',
            'selected'        => $selected,
            'hierarchical'    => true,
            'value_field'     => 'slug',
            'depth'           => 3,
            'hide_empty'      => false,
        ) );
    }

}
