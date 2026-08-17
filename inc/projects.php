<?php
/**
 * Dự án tiêu biểu — add/remove in WP admin (Dự án).
 */

if (!defined('ABSPATH')) {
	exit;
}

add_action('init', function () {
	register_post_type('ttc_project', [
		'labels' => [
			'name' => 'Dự án',
			'singular_name' => 'Dự án',
			'add_new' => 'Thêm dự án',
			'add_new_item' => 'Thêm dự án',
			'edit_item' => 'Sửa dự án',
		],
		'public' => true,
		'has_archive' => false,
		'show_in_rest' => true,
		'menu_icon' => 'dashicons-portfolio',
		'supports' => ['title', 'editor', 'thumbnail', 'excerpt'],
		'rewrite' => ['slug' => 'du-an'],
	]);
});

/**
 * KPI fields shown in the "Dự án tiêu biểu" cards on the homepage.
 * key => label in the editor.
 */
function ttc_project_meta_fields() {
	return [
		'_ttc_stat' => 'Chỉ số nổi bật (vd: Sai số ± 0.005 mm)',
		'_ttc_stat_label' => 'Nhãn chỉ số (vd: Độ chính xác)',
		'_ttc_days' => 'Thời gian (vd: 45 ngày)',
		'_ttc_days_label' => 'Nhãn thời gian (vd: Thời gian triển khai)',
	];
}

add_action('init', function () {
	foreach (array_keys(ttc_project_meta_fields()) as $key) {
		register_post_meta('ttc_project', $key, [
			'type' => 'string',
			'single' => true,
			'show_in_rest' => true,
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback' => static fn() => current_user_can('edit_posts'),
		]);
	}
});

add_action('add_meta_boxes', function () {
	add_meta_box(
		'ttc_project_kpi',
		'Chỉ số dự án (KPI)',
		function ($post) {
			wp_nonce_field('ttc_project_kpi', 'ttc_project_kpi_nonce');
			echo '<div style="display:grid;gap:12px">';
			foreach (ttc_project_meta_fields() as $key => $label) {
				$val = get_post_meta($post->ID, $key, true);
				printf(
					'<label style="display:block"><span style="display:block;font-weight:600;margin-bottom:4px">%s</span><input type="text" name="%s" value="%s" class="widefat" /></label>',
					esc_html($label),
					esc_attr($key),
					esc_attr($val)
				);
			}
			echo '</div>';
		},
		'ttc_project',
		'normal',
		'high'
	);
});

add_action('save_post_ttc_project', function ($post_id) {
	if (
		!isset($_POST['ttc_project_kpi_nonce']) ||
		!wp_verify_nonce($_POST['ttc_project_kpi_nonce'], 'ttc_project_kpi') ||
		(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) ||
		!current_user_can('edit_post', $post_id)
	) {
		return;
	}
	foreach (array_keys(ttc_project_meta_fields()) as $key) {
		if (isset($_POST[$key])) {
			update_post_meta($post_id, $key, sanitize_text_field(wp_unslash($_POST[$key])));
		}
	}
});
