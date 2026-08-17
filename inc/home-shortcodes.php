<?php
/**
 * Homepage dynamic sections as shortcodes.
 *
 * The marketing homepage lives in Gutenberg (page_on_front). Sections whose
 * design cannot be expressed with core blocks (curated ordering, product image
 * with model band, project KPI footer, 1-big-3-small knowledge layout, support
 * form) are rendered here so the block editor stays the source of truth while
 * the markup/classes match assets/css/ttc.css exactly.
 */

if (!defined('ABSPATH')) {
	exit;
}

/** Active theme image URL helper. */
function ttc_home_asset($file) {
	return get_stylesheet_directory_uri() . '/assets/img/' . ltrim($file, '/');
}

/**
 * [ttc_phone] — canonical site phone number (single source, see ttc_phone()).
 * Use inside page/FAQ content so every mention stays in sync.
 *   [ttc_phone]           → 02462931272
 *   [ttc_phone link="1"]  → <a href="tel:...">02462931272</a>
 */
add_shortcode('ttc_phone', function ($atts) {
	$atts = shortcode_atts(['link' => '0'], $atts, 'ttc_phone');
	if (in_array((string) $atts['link'], ['1', 'true', 'yes'], true)) {
		return sprintf('<a href="%s">%s</a>', esc_attr(ttc_phone_href()), esc_html(ttc_phone()));
	}
	return esc_html(ttc_phone());
});

/* ------------------------------------------------------------------ *
 * [ttc_home_categories] — curated order + icons, links to category.
 * ------------------------------------------------------------------ */
add_shortcode('ttc_home_categories', function () {
	// Native WooCommerce categories: name = label, term thumbnail = icon.
	// Managed in Sản phẩm → Danh mục (image) + ACF fields "Hiện trên trang chủ"
	// / "Thứ tự trang chủ" per term. No hardcoded labels or icon files.
	$terms = get_terms([
		'taxonomy' => 'product_cat',
		'hide_empty' => false,
		'parent' => 0,
	]);
	if (is_wp_error($terms) || !$terms) {
		return '';
	}

	// Keep categories flagged to show. Unset (never edited) defaults to visible —
	// only an explicit un-tick (stored false) hides a category.
	$terms = array_filter($terms, static function ($t) {
		if (!function_exists('get_field')) {
			return true;
		}
		$show = get_field('ttc_cat_home_show', 'product_cat_' . $t->term_id);
		return ($show === null) ? true : (bool) $show;
	});
	if (!$terms) {
		return '';
	}

	// Sort by the per-term "Thứ tự trang chủ", then by name.
	usort($terms, static function ($a, $b) {
		$oa = function_exists('get_field') ? (int) get_field('ttc_cat_home_order', 'product_cat_' . $a->term_id) : 0;
		$ob = function_exists('get_field') ? (int) get_field('ttc_cat_home_order', 'product_cat_' . $b->term_id) : 0;
		return ($oa === $ob) ? strcasecmp($a->name, $b->name) : ($oa <=> $ob);
	});

	ob_start();
	echo '<ul class="ttc-home-cats__grid">';
	foreach ($terms as $term) {
		$url = get_term_link($term);
		if (is_wp_error($url)) {
			continue;
		}
		$thumb_id = (int) get_term_meta($term->term_id, 'thumbnail_id', true);
		$icon = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'medium') : '';
		if (!$icon && function_exists('wc_placeholder_img_src')) {
			$icon = wc_placeholder_img_src('medium');
		}
		printf(
			'<li><a href="%s"><span class="ttc-home-cats__icon"><img src="%s" alt="%s" width="96" height="96" loading="lazy" decoding="async" /></span><span class="ttc-home-cats__label">%s</span></a></li>',
			esc_url($url),
			esc_url($icon),
			esc_attr($term->name),
			esc_html($term->name)
		);
	}
	echo '</ul>';
	return ob_get_clean();
});

/* ------------------------------------------------------------------ *
 * [ttc_home_products] — featured products, full (uncropped) image so
 * the manufacturer model band stays visible, brand logo + "Chi tiết".
 * ------------------------------------------------------------------ */
function ttc_home_featured_products($limit = 6) {
	if (!function_exists('wc_get_products')) {
		return [];
	}
	$featured = wc_get_products([
		'limit' => $limit,
		'status' => 'publish',
		'featured' => true,
		'orderby' => 'date',
		'order' => 'DESC',
	]);
	if (count($featured) >= $limit) {
		return $featured;
	}
	$ids = array_map(static fn($p) => $p->get_id(), $featured);
	$more = wc_get_products([
		'limit' => $limit - count($featured),
		'status' => 'publish',
		'orderby' => 'date',
		'order' => 'DESC',
		'exclude' => $ids,
	]);
	return array_merge($featured, $more);
}

add_shortcode('ttc_home_products', function () {
	$products = ttc_home_featured_products(6);
	if (!$products) {
		return '';
	}
	ob_start();
	echo '<ul class="ttc-home-products__grid">';
	foreach ($products as $product) {
		$brand = function_exists('ttc_brand_image_for_product') ? ttc_brand_image_for_product($product->get_id()) : null;
		$img_id = $product->get_image_id();
		$img = $img_id ? wp_get_attachment_image_url($img_id, 'large') : '';
		if (!$img && function_exists('wc_placeholder_img_src')) {
			$img = wc_placeholder_img_src('large');
		}
		$link = get_permalink($product->get_id());
		echo '<li class="ttc-home-product">';
		printf(
			'<a class="ttc-home-product__media" href="%s"><img src="%s" alt="%s" loading="lazy" decoding="async" /></a>',
			esc_url($link),
			esc_url($img),
			esc_attr($product->get_name())
		);
		if ($brand && !empty($brand['img'])) {
			printf(
				'<span class="ttc-home-product__brand"><img src="%s" alt="%s" /></span>',
				esc_url($brand['img']),
				esc_attr($brand['name'] ?? '')
			);
		} else {
			echo '<span class="ttc-home-product__brand ttc-home-product__brand--text">TTCTECH</span>';
		}
		printf('<h3><a href="%s">%s</a></h3>', esc_url($link), esc_html($product->get_name()));
		printf('<a class="ttc-home-product__more" href="%s">Chi tiết</a>', esc_url($link));
		echo '</li>';
	}
	echo '</ul>';
	return ob_get_clean();
});

/* ------------------------------------------------------------------ *
 * [ttc_home_projects] — Dự án CPT with KPI footer (stat + duration).
 * ------------------------------------------------------------------ */
add_shortcode('ttc_home_projects', function () {
	$projects = get_posts([
		'post_type' => 'ttc_project',
		'post_status' => 'publish',
		'numberposts' => 4,
		'orderby' => 'date',
		'order' => 'ASC',
	]);
	if (!$projects) {
		return '';
	}
	ob_start();
	echo '<ul class="ttc-home-projects__grid">';
	foreach ($projects as $project) {
		$link = get_permalink($project);
		$img = get_the_post_thumbnail_url($project, 'large') ?: ttc_home_asset('home/projects/p1.jpg');
		$desc = $project->post_content ?: $project->post_excerpt;

		$stat = get_post_meta($project->ID, '_ttc_stat', true);
		$stat_label = get_post_meta($project->ID, '_ttc_stat_label', true);
		$days = get_post_meta($project->ID, '_ttc_days', true);
		$days_label = get_post_meta($project->ID, '_ttc_days_label', true);

		// Fallback: parse "stat · days" out of the excerpt if meta is unset.
		if (($stat === '' || $days === '') && strpos((string) $project->post_excerpt, ' · ') !== false) {
			$tail = trim(str_replace($desc, '', $project->post_excerpt));
			$parts = array_map('trim', explode(' · ', $tail));
			if ($stat === '' && isset($parts[0])) {
				$stat = $parts[0];
				$stat_label = $stat_label ?: 'Kết quả';
			}
			if ($days === '' && isset($parts[1])) {
				$days = $parts[1];
				$days_label = $days_label ?: 'Thời gian triển khai';
			}
		}

		echo '<li class="ttc-home-project">';
		printf(
			'<a class="ttc-home-project__media" href="%s"><img src="%s" alt="%s" loading="lazy" decoding="async" /></a>',
			esc_url($link),
			esc_url($img),
			esc_attr(get_the_title($project))
		);
		echo '<div class="ttc-home-project__body">';
		printf('<h3><a href="%s">%s</a></h3>', esc_url($link), esc_html(get_the_title($project)));
		printf('<p>%s</p>', esc_html($desc));
		if ($stat || $days) {
			echo '<div class="ttc-home-project__metrics">';
			if ($stat) {
				printf(
					'<div class="ttc-home-project__metric"><strong>%s</strong><span>%s</span></div>',
					esc_html($stat),
					esc_html($stat_label)
				);
			}
			if ($days) {
				printf(
					'<div class="ttc-home-project__metric ttc-home-project__metric--muted"><strong>%s</strong><span>%s</span></div>',
					esc_html($days),
					esc_html($days_label)
				);
			}
			echo '</div>';
		}
		echo '</div></li>';
	}
	echo '</ul>';
	return ob_get_clean();
});

/* ------------------------------------------------------------------ *
 * [ttc_home_knowledge] — 1 feature + up to 3 list items.
 * ------------------------------------------------------------------ */
add_shortcode('ttc_home_knowledge', function () {
	$posts = get_posts([
		'numberposts' => 4,
		'post_status' => 'publish',
		'post_type' => 'post',
	]);
	if (!$posts) {
		return '';
	}
	$feature = array_shift($posts);
	ob_start();
	echo '<div class="ttc-home-knowledge__layout">';
	$fthumb = get_the_post_thumbnail_url($feature, 'large') ?: ttc_home_asset('home/projects/p2.jpg');
	printf(
		'<a class="ttc-home-knowledge__feature" href="%s"><img src="%s" alt="" loading="lazy" decoding="async" /><span class="ttc-home-knowledge__feature-copy"><strong>%s</strong><em>Xem chi tiết</em></span></a>',
		esc_url(get_permalink($feature)),
		esc_url($fthumb),
		esc_html(get_the_title($feature))
	);
	if ($posts) {
		echo '<ul class="ttc-home-knowledge__list">';
		foreach ($posts as $post) {
			$thumb = get_the_post_thumbnail_url($post, 'medium') ?: ttc_home_asset('home/projects/p1.jpg');
			printf(
				'<li><a href="%s"><img src="%s" alt="" width="140" height="96" loading="lazy" decoding="async" /><span>%s</span></a></li>',
				esc_url(get_permalink($post)),
				esc_url($thumb),
				esc_html(get_the_title($post))
			);
		}
		echo '</ul>';
	}
	echo '</div>';
	return ob_get_clean();
});

/* ------------------------------------------------------------------ *
 * [ttc_home_about] — "Về chúng tôi" grid: image + mission + core-values
 * panel (single box, SVG check icons) + stats + CTA.
 * ------------------------------------------------------------------ */
add_shortcode('ttc_home_about', function () {
	$img = ttc_home_field('about_image', ttc_home_asset('home/about-front.jpg'));
	$about_url = ttc_home_field('about_cta_url', home_url('/gioi-thieu/'));
	$cta_text = ttc_home_field('about_cta_text', 'Tìm hiểu thêm');
	$mission_title = ttc_home_field('about_mission_title', 'Sứ mệnh của chúng tôi');
	$mission = ttc_home_field('about_mission', 'Mang đến sản phẩm chính hãng cùng giải pháp kỹ thuật tối ưu, giúp khách hàng nâng cao hiệu quả sản xuất và năng lực cạnh tranh.');
	$values_title = ttc_home_field('about_values_title', 'Giá trị cốt lõi');
	$values = [
		[ttc_home_field('about_v1_title', 'Chính hãng & chất lượng'), ttc_home_field('about_v1_desc', 'Phân phối dụng cụ cắt và đo lường chính hãng từ các thương hiệu hàng đầu.')],
		[ttc_home_field('about_v2_title', 'Đội ngũ kỹ thuật'), ttc_home_field('about_v2_desc', 'Kỹ sư giàu kinh nghiệm hỗ trợ ứng dụng và tối ưu quy trình tại xưởng.')],
		[ttc_home_field('about_v3_title', 'Đồng hành cùng khách hàng'), ttc_home_field('about_v3_desc', 'Tư vấn và giao hàng trên toàn quốc, gắn bó lâu dài cùng doanh nghiệp.')],
	];
	$stats = [
		[ttc_home_field('about_s1_num', '20+'), ttc_home_field('about_s1_label', 'Năm kinh nghiệm')],
		[ttc_home_field('about_s2_num', '45+'), ttc_home_field('about_s2_label', 'Thương hiệu')],
		[ttc_home_field('about_s3_num', '58+'), ttc_home_field('about_s3_label', 'Chuyên gia kỹ thuật')],
	];
	$check = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>';
	ob_start();
	?>
<div class="ttc-home-about__grid">
	<div class="ttc-home-about__media">
		<img class="ttc-home-about__img" src="<?php echo esc_url($img); ?>" alt="Đội ngũ kỹ thuật TTCTECH" loading="lazy" decoding="async" width="560" height="440" />
	</div>
	<div class="ttc-home-about__copy">
		<h3 class="ttc-home-about__subtitle"><?php echo esc_html($mission_title); ?></h3>
		<p class="ttc-home-about__body"><?php echo esc_html($mission); ?></p>
		<div class="ttc-home-about__values">
			<p class="ttc-home-about__values-title"><?php echo esc_html($values_title); ?></p>
			<ul>
				<?php foreach ($values as [$title, $desc]) : ?>
				<li>
					<span class="ttc-home-about__values-icon" aria-hidden="true"><?php echo $check; ?></span>
					<span class="ttc-home-about__values-text"><strong><?php echo esc_html($title); ?></strong><em><?php echo esc_html($desc); ?></em></span>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<div class="ttc-home-about__stats">
			<?php foreach ($stats as [$num, $label]) : ?>
			<div><strong><?php echo esc_html($num); ?></strong><span><?php echo esc_html($label); ?></span></div>
			<?php endforeach; ?>
		</div>
		<a class="ttc-btn ttc-btn--primary" href="<?php echo esc_url($about_url); ?>"><?php echo esc_html($cta_text); ?></a>
	</div>
</div>
	<?php
	return ob_get_clean();
});

/* The shared CF7 form already contains intentional block markup. */
add_filter('wpcf7_autop_or_not', '__return_false');

/* [ttc_support_form] — one shared Contact Form 7 form for every placement. */
add_shortcode('ttc_support_form', function () {
	if (!shortcode_exists('contact-form-7')) {
		return current_user_can('activate_plugins')
			? '<p class="ttc-support__form-error">Vui lòng cài và kích hoạt Contact Form 7.</p>'
			: '';
	}

	$form = get_page_by_path('ttctech-yeu-cau-tu-van', OBJECT, 'wpcf7_contact_form');
	if (!$form) {
		return current_user_can('manage_options')
			? '<p class="ttc-support__form-error">Chưa có form Contact Form 7 “TTCTECH – Yêu cầu tư vấn”.</p>'
			: '';
	}

	return do_shortcode(sprintf(
		'[contact-form-7 id="%d" title="TTCTECH – Yêu cầu tư vấn" html_class="ttc-support__form"]',
		$form->ID
	));
});

/* ------------------------------------------------------------------ *
 * [ttc_support] — support band, now a single synced pattern (block)
 * shared by the homepage and every shop page so the design + copy stay
 * identical everywhere. Client edits it as blocks; a plain fallback
 * renders if the pattern is missing.
 * ------------------------------------------------------------------ */
add_shortcode('ttc_support', function () {
	$ref = (int) get_option('ttc_support_pattern_id');
	if ($ref && get_post_status($ref) === 'publish') {
		return do_shortcode(do_blocks('<!-- wp:block {"ref":' . $ref . '} /-->'));
	}
	ob_start();
	?>
<section class="ttc-support">
	<div class="ttc-container ttc-support__inner">
		<div class="ttc-support__copy">
			<h2>Hỗ trợ giải pháp gia công toàn diện</h2>
			<p>Đội ngũ kỹ sư giàu kinh nghiệm của chúng tôi luôn sẵn sàng hỗ trợ khách hàng tối ưu hóa quy trình sản xuất, nâng cao tuổi thọ dao cụ và giảm thiểu tối đa chi phí vận hành.</p>
			<p class="ttc-support__phone">Business: <?php echo esc_html(ttc_phone()); ?></p>
		</div>
		<?php echo do_shortcode('[ttc_support_form]'); ?>
	</div>
</section>
	<?php
	return ob_get_clean();
});
