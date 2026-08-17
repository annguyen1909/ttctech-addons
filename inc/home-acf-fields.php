<?php
/**
 * Homepage editable content — ACF (Free) local field groups.
 *
 * The homepage sections "Về chúng tôi", "Danh mục sản phẩm" and the support
 * band ([ttc_support]) were previously hardcoded in home-shortcodes.php. These
 * field groups attach to the front page (page_on_front) so the client can edit
 * the copy, numbers, categories and images from the "Trang chủ" edit screen.
 *
 * ACF Free has no repeater/options-page, so fields are flat (value 1/2/3) and
 * every shortcode keeps the original hardcoded values as a fallback — clearing
 * a field simply restores the default, never a blank section.
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Read a homepage field from the front page, whatever page renders the section.
 * Returns $default when ACF is inactive or the value is empty.
 */
function ttc_home_field($name, $default = '') {
	static $pid = null;
	if ($pid === null) {
		$pid = (int) get_option('page_on_front');
	}
	if (!$pid || !function_exists('get_field')) {
		return $default;
	}
	$val = get_field($name, $pid);
	if ($val === null || $val === false || $val === '' || (is_array($val) && !$val)) {
		return $default;
	}
	return $val;
}

/**
 * Single source of truth for the site phone number (header, footer, support band).
 * Edited in one ACF field on the front page so it can never diverge across the site.
 */
function ttc_phone($default = '02462931272') {
	return (string) ttc_home_field('site_phone', $default);
}

/** tel: href for ttc_phone(), digits/plus only. */
function ttc_phone_href() {
	return 'tel:' . preg_replace('/[^0-9+]/', '', ttc_phone());
}

add_action('acf/init', function () {
	if (!function_exists('acf_add_local_field_group')) {
		return;
	}

	$front_location = [[[
		'param' => 'page_type',
		'operator' => '==',
		'value' => 'front_page',
	]]];

	/* ------------------------------------------------------------ *
	 * 0) Thông tin liên hệ (toàn site) — single source of truth.
	 * ------------------------------------------------------------ */
	acf_add_local_field_group([
		'key' => 'group_ttc_site_contact',
		'title' => 'Liên hệ (dùng chung toàn site)',
		'location' => $front_location,
		'menu_order' => 5,
		'position' => 'normal',
		'style' => 'default',
		'fields' => [
			['key' => 'field_ttc_site_phone_msg', 'label' => 'Số điện thoại toàn site', 'type' => 'message',
				'message' => 'Số này hiển thị ở <strong>header, footer và khối hỗ trợ</strong>. Sửa 1 chỗ ở đây là đổi trên toàn bộ trang.'],
			['key' => 'field_ttc_site_phone', 'label' => 'Số điện thoại', 'name' => 'site_phone', 'type' => 'text',
				'default_value' => '02462931272',
				'instructions' => 'Số hiển thị chung cho cả site (header, footer, khối hỗ trợ).'],
		],
	]);

	/* "Về chúng tôi" is now authored with core Gutenberg blocks directly on the
	 * homepage (like the Tuyển dụng page) — no shortcode, no meta box. See the
 * .ttc-home-about styles in the active theme. */

	/* ------------------------------------------------------------ *
	 * Danh mục sản phẩm  →  [ttc_home_categories]
	 * Native WooCommerce: tên + ảnh thumbnail của danh mục. Hai field
	 * dưới đây gắn vào từng danh mục (Sản phẩm → Danh mục) để chọn
	 * hiện/ẩn và thứ tự trên trang chủ.
	 * ------------------------------------------------------------ */
	acf_add_local_field_group([
		'key' => 'group_ttc_cat_home',
		'title' => 'Hiển thị trên trang chủ',
		'location' => [[[ 'param' => 'taxonomy', 'operator' => '==', 'value' => 'product_cat' ]]],
		'menu_order' => 5,
		'position' => 'normal',
		'style' => 'default',
		'fields' => [
			['key' => 'field_ttc_cat_home_show', 'label' => 'Hiện trên trang chủ', 'name' => 'ttc_cat_home_show',
				'type' => 'true_false', 'ui' => 1, 'default_value' => 1,
				'message' => 'Hiển thị danh mục này trong lưới “Danh mục sản phẩm” ở trang chủ.',
				'instructions' => 'Icon = ảnh (Thumbnail) của danh mục; nhãn = tên danh mục.'],
			['key' => 'field_ttc_cat_home_order', 'label' => 'Thứ tự trang chủ', 'name' => 'ttc_cat_home_order',
				'type' => 'number', 'default_value' => 100, 'min' => 0, 'step' => 1,
				'instructions' => 'Số nhỏ hiển thị trước (1, 2, 3…). Bằng nhau thì xếp theo tên.'],
		],
	]);

	/* "Khối hỗ trợ" is now a synced pattern (block) shared by the homepage and
	 * shop pages — edited as blocks, not a meta box. See [ttc_support] /
	 * [ttc_support_form] in this plugin's home-shortcodes module. */
});
