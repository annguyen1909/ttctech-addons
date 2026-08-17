<?php
/**
 * Knowledge Widgets
 *
 * Reusable widgets for knowledge sidebar sections
 */

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Knowledge Catalog Widget
 * Displays product categories in a list
 */
class TTCTech_Knowledge_Catalog_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ttctech_knowledge_catalog',
			'[TTCTech Tiện ích] Knowledge Catalog',
			['description' => 'Display product categories list']
		);
	}

	public function widget($args, $instance) {
		$title = !empty($instance['title']) ? $instance['title'] : 'Danh mục sản phẩm';

		// Get catalog items using theme function if exists
		$catalog_items = function_exists('ttc_catalog_sidebar_items')
			? ttc_catalog_sidebar_items()
			: $this->get_catalog_items();

		if (empty($catalog_items)) {
			return;
		}

		echo $args['before_widget'];
		?>
		<section class="ttc-knowledge-catalog">
			<h2><?php echo esc_html($title); ?></h2>
			<ul>
				<?php foreach ($catalog_items as $item) : ?>
					<li>
						<a href="<?php echo esc_url($item['url']); ?>">
							<span><?php echo esc_html($item['label']); ?></span>
							<i aria-hidden="true"></i>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</section>
		<?php
		echo $args['after_widget'];
	}

	public function form($instance) {
		$title = !empty($instance['title']) ? $instance['title'] : 'Danh mục sản phẩm';
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
				Title:
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr($this->get_field_id('title')); ?>"
				name="<?php echo esc_attr($this->get_field_name('title')); ?>"
				type="text"
				value="<?php echo esc_attr($title); ?>"
			/>
		</p>
		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = [];
		$instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		return $instance;
	}

	private function get_catalog_items() {
		$items = [];
		$terms = get_terms([
			'taxonomy' => 'product_cat',
			'hide_empty' => true,
			'parent' => 0,
		]);

		if (!is_wp_error($terms)) {
			foreach ($terms as $term) {
				if ($term->slug === 'uncategorized') {
					continue;
				}
				$items[] = [
					'label' => $term->name,
					'url' => get_term_link($term),
				];
			}
		}

		return $items;
	}
}

/**
 * Knowledge Popular Widget
 * Displays popular articles with thumbnails
 */
class TTCTech_Knowledge_Popular_Widget extends WP_Widget {

	public function __construct() {
		parent::__construct(
			'ttctech_knowledge_popular',
			'[TTCTech Tiện ích] Knowledge Popular',
			['description' => 'Display popular articles with thumbnails']
		);
	}

	public function widget($args, $instance) {
		$title = !empty($instance['title']) ? $instance['title'] : 'Xem nhiều';
		$number = !empty($instance['number']) ? absint($instance['number']) : 3;

		$current_id = get_queried_object_id();
		$exclude = $current_id ? [$current_id] : [];

		// Get popular posts using theme function if exists
		$popular = function_exists('ttc_popular_articles')
			? ttc_popular_articles($number, $exclude)
			: $this->get_popular_articles($number, $exclude);

		if (empty($popular)) {
			return;
		}

		echo $args['before_widget'];
		?>
		<section class="ttc-knowledge-popular">
			<h2><?php echo esc_html($title); ?></h2>
			<div class="ttc-knowledge-popular__list">
				<?php foreach ($popular as $popular_post) : ?>
					<a href="<?php echo esc_url(get_permalink($popular_post)); ?>">
						<?php if (has_post_thumbnail($popular_post)) : ?>
							<?php echo get_the_post_thumbnail($popular_post, 'medium', [
								'width' => 120,
								'height' => 82,
								'loading' => 'lazy'
							]); ?>
						<?php else : ?>
							<img src="<?php echo esc_url($this->get_article_image($popular_post->ID)); ?>"
								alt="" width="120" height="82" loading="lazy" />
						<?php endif; ?>
						<strong><?php echo esc_html(get_the_title($popular_post)); ?></strong>
					</a>
				<?php endforeach; ?>
			</div>
		</section>
		<?php
		echo $args['after_widget'];
	}

	public function form($instance) {
		$title = !empty($instance['title']) ? $instance['title'] : 'Xem nhiều';
		$number = !empty($instance['number']) ? absint($instance['number']) : 3;
		?>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
				Title:
			</label>
			<input
				class="widefat"
				id="<?php echo esc_attr($this->get_field_id('title')); ?>"
				name="<?php echo esc_attr($this->get_field_name('title')); ?>"
				type="text"
				value="<?php echo esc_attr($title); ?>"
			/>
		</p>
		<p>
			<label for="<?php echo esc_attr($this->get_field_id('number')); ?>">
				Number of posts:
			</label>
			<input
				class="tiny-text"
				id="<?php echo esc_attr($this->get_field_id('number')); ?>"
				name="<?php echo esc_attr($this->get_field_name('number')); ?>"
				type="number"
				step="1"
				min="1"
				max="10"
				value="<?php echo esc_attr($number); ?>"
			/>
		</p>
		<?php
	}

	public function update($new_instance, $old_instance) {
		$instance = [];
		$instance['title'] = !empty($new_instance['title']) ? sanitize_text_field($new_instance['title']) : '';
		$instance['number'] = !empty($new_instance['number']) ? absint($new_instance['number']) : 3;
		return $instance;
	}

	private function get_popular_articles($limit = 3, $exclude = []) {
		return get_posts([
			'numberposts' => $limit,
			'post_status' => 'publish',
			'post__not_in' => array_map('intval', $exclude),
			'orderby' => [
				'comment_count' => 'DESC',
				'date' => 'DESC',
			],
		]);
	}

	private function get_article_image($post_id) {
		// Fallback to theme function if exists
		if (function_exists('ttc_article_image')) {
			return ttc_article_image($post_id, 'medium');
		}

		// Default placeholder or first image
		$thumbnail = get_the_post_thumbnail_url($post_id, 'medium');
		return $thumbnail ?: '';
	}
}

/**
 * Register widgets
 */
function ttctech_register_widgets() {
	register_widget('TTCTech_Knowledge_Catalog_Widget');
	register_widget('TTCTech_Knowledge_Popular_Widget');
}
add_action('widgets_init', 'ttctech_register_widgets');
