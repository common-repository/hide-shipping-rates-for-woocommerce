<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Cart rule class
 */
final class Cart {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_ui_values', array($this, 'rule_ui_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filter_coupon'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'coupon_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'common_templates'));
		add_action('hide_shipping_rates/cart_common_fields', array($this, 'cart_common_fields'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'coupons' => [],
			'value_two' => '',
			'cart_value_type' => 'in_cart',
		));
	}

	/**
	 * Rule UI values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_ui_values($values) {
		return array_merge($values, array(
			'hold_coupons' => [],
		));
	}

	/**
	 * Cart rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		if (!in_array($rule['type'], array('cart:subtotal', 'cart:total_quantity', 'cart:total_weight', 'cart:coupons'))) {
			return $matched;
		}

		$operator = $rule['operator'];
		$value_one = floatval($rule['value']);
		$value_two = isset($rule['value_two']) ? floatval($rule['value_two']) : 0.00;


		$compare_value = 0.00;
		if ('cart:subtotal' === $rule['type']) {
			$compare_value = (float) WC()->cart->get_subtotal();
		}

		if ('cart:total_quantity' === $rule['type']) {
			$compare_value = WC()->cart->get_cart_contents_count();
		}

		if ('cart:total_weight' === $rule['type']) {
			$compare_value = WC()->cart->cart_contents_weight;
		}

		$compare_value = apply_filters('hide_shipping_rates/cart_compare_value', $compare_value, $rule);
		if ('equal_to' === $operator && $compare_value == $value_one) {
			return true;
		}

		if ('less_than' === $operator && $compare_value < $value_one) {
			return true;
		}

		if ('less_than_or_equal' === $operator && $compare_value <= $value_one) {
			return true;
		}

		if ('greater_than_or_equal' === $operator && $compare_value >= $value_one) {
			return true;
		}

		if ('greater_than' === $operator && $compare_value > $value_one) {
			return true;
		}

		if ('between' === $operator && $compare_value >= $value_one && $compare_value <= $value_two) {
			return true;
		}

		if ('not_between' === $operator && ($compare_value < $value_one || $compare_value > $value_two)) {
			return true;
		}

		return $matched;
	}


	/**
	 * Coupon rule filter
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filter_coupon($matched, $rule) {
		$operator = $rule['operator'];

		if ('cart:coupons' !== $rule['type'] || !isset($rule['coupons']) || !is_array($rule['coupons'])) {
			return $matched;
		}

		$applied_coupons = WC()->cart->applied_coupons;
		if (empty($applied_coupons)) {
			return $matched;
		}

		$coupons = array_map(function ($coupon_id) {
			return get_post_field('post_name', $coupon_id);
		}, $rule['coupons']);

		$matched_coupons = array_intersect($coupons, $applied_coupons);
		if ('any_in_list' === $operator && count($matched_coupons) > 0) {
			return true;
		}

		if ('not_in_list' === $operator && count($matched_coupons) == 0) {
			return true;
		}

		return $matched;
	}

	/**
	 * Common templates
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function common_templates() { ?>
		<template v-if="['cart:subtotal', 'cart:total_quantity', 'cart:total_weight'].includes(type)">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('equal_to', 'less_than', 'less_than_or_equal', 'greater_than_or_equal', 'greater_than', 'between', 'not_between')); ?>
			</select>

			<input type="number" step="0.01" v-model="value" placeholder="<?php echo '0.00'; ?>" style="width: 80px!important;text-align:center">
			<input type="number" step="0.01" v-model="value_two" placeholder="<?php echo '0.00'; ?>" v-if="'between' == operator || 'not_between' == operator" style="width: 80px!important;text-align:center">
			<?php do_action('hide_shipping_rates/cart_common_fields') ?>
		</template>
	<?php
	}

	/**
	 * Coupon template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function coupon_template() {
		$model_values = array(
			'model' => 'coupons',
			'hold_data' => 'hold_coupons',
			'data_type' => 'post_type:shop_coupon',
		); ?>

		<template v-if="type == 'cart:coupons'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<div class="loading-indicator" v-if="loading"></div>
			<select class="select2-flex1" ref="select2_ajax" multiple v-else data-placeholder="<?php esc_html_e('Coupons', 'hide-shipping-rates-for-woocommerce'); ?>" data-select2-map="<?php echo esc_attr(wp_json_encode($model_values)) ?>">
				<option v-for="coupon in get_ui_data_items('hold_coupons')" :value="coupon.id" :selected="coupons.includes(coupon.id.toString())">{{coupon.name}}</option>
			</select>

			<div class="guideline" v-if="'any_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart contains any coupon in the selected list.', 'hide-shipping-rates-for-woocommerce') ?></div>
			<div class="guideline" v-if="'not_in_list' == operator"><?php esc_html_e('This rule will be matched if the cart does not contain any coupon in the selected list.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Cart common fields
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function cart_common_fields() { ?>
		<select v-model="cart_value_type">
			<option value="in_cart"><?php esc_html_e('In Cart', 'hide-shipping-rates-for-woocommerce'); ?></option>

			<?php foreach (Utils::get_product_taxonomies() as $taxonomy_data) : ?>
				<option disabled><?php esc_html_e(sprintf(__('In %s (pro)', 'hide-shipping-rates-for-woocommerce'), $taxonomy_data->label)); ?></option>
			<?php endforeach; ?>
		</select>
<?php
	}
}
