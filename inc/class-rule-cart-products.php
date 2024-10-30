<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart products rule class
 */
final class Cart_Products {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_types', array($this, 'add_rule_types'));
		add_filter('hide_shipping_rates/rule_ui_values', array($this, 'rule_ui_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'products_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'add_taxonomy_templates'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		$values['products'] = [];
		foreach (array_keys(Utils::get_product_taxonomies()) as $tax_key) {
			$values['cart_products_' . $tax_key] = [];
		}

		return $values;
	}

	/**
	 * Rule UI values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_ui_values($values) {
		$values['hold_products'] = [];
		foreach (array_keys(Utils::get_product_taxonomies()) as $tax_key) {
			$values['hold_cart_products_' . $tax_key] = [];
		}

		return $values;
	}

	/**
	 * Add rule types of cart products
	 * 
	 * @since 1.0.4
	 * @return array
	 */
	public function add_rule_types($types) {
		$priority = 20;

		foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) {
			$priority++;
			$types['cart_products:' . $tax_key] = array(
				'group' => 'cart_products',
				'priority' => $priority,
				'label' => $taxonomy->label,
			);
		}

		return $types;
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		if ('cart_products:products' === $rule['type']) {
			$rule_products = isset($rule['products']) && is_array($rule['products']) ? $rule['products'] : array();

			$cart_products = [];
			foreach (WC()->cart->get_cart() as $item) {
				$cart_products[] = $item['product_id'];
			}

			$cart_products = array_unique(array_filter($cart_products));

			$matched_items = array_intersect($rule_products, $cart_products);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($matched_items) === count($rule_products)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		foreach (array_keys(Utils::get_product_taxonomies()) as $tax_key) {
			if ('product_shipping_class' === $tax_key) {
				continue;
			}

			if ('cart_products:' . $tax_key === $rule['type']) {
				$model_name = 'cart_products_' . $tax_key;
				$rule_terms = isset($rule[$model_name]) && is_array($rule[$model_name]) ? $rule[$model_name] : array();

				$cart_terms = [];
				foreach (WC()->cart->get_cart() as $item) {
					$cart_terms = array_merge($cart_terms, wc_get_product_term_ids($item['product_id'], $tax_key));
				}

				$matched_items = array_intersect($cart_terms, $rule_terms);
				if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
					return true;
				}

				if ('all_in_list' == $rule['operator'] && count($rule_terms) === count($matched_items)) {
					return true;
				}

				if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
					return true;
				}
			}
		}

		if ('cart_products:product_shipping_class' === $rule['type']) {
			$shipping_classes = isset($rule['cart_products_product_shipping_class']) && is_array($rule['cart_products_product_shipping_class']) ? $rule['cart_products_product_shipping_class'] : array();

			$product_shipping_classes = [];
			foreach (WC()->cart->get_cart() as $item) {
				$product_shipping_classes[] = $item['data']->get_shipping_class_id();
			}

			$product_shipping_classes = array_unique(array_filter($product_shipping_classes));

			$matched_items = array_intersect($shipping_classes, $product_shipping_classes);
			if ('any_in_list' == $rule['operator'] && count($matched_items) > 0) {
				return true;
			}

			if ('all_in_list' == $rule['operator'] && count($shipping_classes) === count($matched_items)) {
				return true;
			}

			if ('not_in_list' == $rule['operator'] && 0 === count($matched_items)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Products template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function products_template() {
		$model_values = array(
			'model' => 'products',
			'hold_data' => 'hold_products',
			'data_type' => 'post_type:product',
		); ?>
		<template v-if="type == 'cart_products:products'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" v-model="products" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Products', 'hide-shipping-rates-for-woocommerce'); ?>" data-select2-map="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="product in get_ui_data_items('hold_products')" :value="product.id">{{product.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if any of the above products are available in the cart.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the above product(s) is available in the cart.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart does not contain any product in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Add templates for taxonomy
	 * 
	 * @since 1.0.4
	 * @return void
	 */
	public function add_taxonomy_templates() { ?>
		<?php foreach (Utils::get_product_taxonomies() as $tax_key => $taxonomy) :
			$data_map_values = array(
				'model' => 'cart_products_' . $tax_key,
				'hold_data' => 'hold_cart_products_' . $tax_key,
				'data_type' => 'taxonomy:' . $tax_key,
			); ?>

			<template v-if="type == 'cart_products:<?php echo esc_attr($tax_key) ?>'">
				<select v-model="operator">
					<?php Utils::get_operators_options(array('any_in_list', 'all_in_list', 'not_in_list')); ?>
				</select>

				<div class="loading-indicator" v-if="loading"></div>
				<select class="select2-flex1" v-model="cart_products_<?php echo esc_attr($tax_key); ?>" ref="select2_ajax" data-select2-map="<?php echo esc_attr(wp_json_encode($data_map_values)) ?>" multiple v-else data-placeholder="<?php echo esc_attr($taxonomy->label); ?>">
					<option v-for="term in get_ui_data_items('hold_cart_products_<?php echo esc_attr($tax_key) ?>')" :value="term.id">{{term.name}}</option>
				</select>

				<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the any of above terms are available in the cart products.', 'hide-shipping-rates-for-woocommerce') ?></div>
				<div class="guideline" v-if="'all_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products contain all of the above terms.', 'hide-shipping-rates-for-woocommerce') ?></div>
				<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart products do not contain any terms in the above list.', 'hide-shipping-rates-for-woocommerce') ?></div>
			</template>

		<?php endforeach; ?>
<?php
	}
}
