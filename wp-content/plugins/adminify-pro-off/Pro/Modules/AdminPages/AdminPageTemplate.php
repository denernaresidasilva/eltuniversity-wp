<?php

// no direct access allowed
if (!defined('ABSPATH')) {
	exit;
}
/**
 * WPAdminify
 *
 * @package Admin Pages
 *
 * @author WP Adminify <support@wpadminify.com>
 */

$remove_page_title = get_post_meta($post->ID, '_wp_adminify_page_title', true);
?>

<!doctype html>

<html <?php language_attributes(); ?>>

<head>
	<meta charset="<?php bloginfo('charset'); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

	<?php
	wp_body_open();

	if (have_posts()) {
		while (have_posts()) {
			the_post();

		if (!$remove_page_title) { ?>
			<h1 class="adminify-admin-page--title"><?php the_title(); ?></h1>
		<?php } ?>

		<main id="content" class="site-content">
			<?php
			$theme = wp_get_theme();
			if ('Bricks' == $theme->name || 'Bricks' == $theme->parent_theme) {
				$post_id     = get_the_ID();
				$post_type   = get_post_type();
				$bricks_data = \Bricks\Helpers::get_bricks_data($post_id, 'content');
				$preview_id  = \Bricks\Helpers::get_template_setting('templatePreviewPostId', $post_id);
				// Render Bricks data
				if ($bricks_data) {
					\Bricks\Frontend::render_content($bricks_data);
				}
			} else {
				the_content();
			}
			?>
		</main>

	<?php
		}
	}

	wp_footer();
	?>

</body>

</html>
