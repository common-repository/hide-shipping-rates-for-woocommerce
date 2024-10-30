<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Billing & Shipping rule class
 */
final class Billing_Shipping {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_city_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_city_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_country_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_country_template'));

		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_zipcode_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'billing_state_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_zipcode_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'shipping_state_template'));
	}

	/**
	 * Rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'billing_cities' => '',
			'shipping_cities' => '',
			'billing_countries' => [],
			'shipping_countries' => [],
		));
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		$operator = $rule['operator'];

		if ('billing:city' === $rule['type']) {
			$cities = $rule['billing_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_billing_city());
			if ('any_in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('shipping:city' === $rule['type']) {
			$cities = $rule['shipping_cities'] ?? '';
			$cities = array_filter(array_map('trim', explode(',', strtolower($cities))));

			$customer_city = strtolower(WC()->customer->get_shipping_city());

			if ('any_in_list' === $operator && in_array($customer_city, $cities)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_city, $cities)) {
				return true;
			}
		}

		if ('billing:country' === $rule['type'] || 'shipping:country' === $rule['type']) {
			$countries = isset($rule['shipping_countries']) && is_array($rule['shipping_countries']) ? $rule['shipping_countries'] : array();

			$customer_country = WC()->customer->get_shipping_country();
			if ('billing:country' === $rule['type']) {
				$countries = isset($rule['billing_countries']) && is_array($rule['billing_countries']) ? $rule['billing_countries'] : array();
				$customer_country = WC()->customer->get_billing_country();
			}

			if ('any_in_list' === $operator && in_array($customer_country, $countries)) {
				return true;
			}

			if ('not_in_list' === $operator && !in_array($customer_country, $countries)) {
				return true;
			}
		}

		return $matched;
	}

	/**
	 * Add billing city template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_city_template() { ?>
		<template v-if="type == 'billing:city'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<?php $placeholder = __('Example: Chicago, New York', 'hide-shipping-rates-for-woocommerce'); ?>
			<input class="input-flex1" type="text" v-model="billing_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
			<div class="guideline"><?php esc_html_e('For multiple cities use commas for separate. Example: Chicago, New York.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Add shipping city template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_city_template() { ?>
		<template v-if="type == 'shipping:city'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<?php $placeholder = __('Example: Chicago, New York', 'hide-shipping-rates-for-woocommerce'); ?>
			<input class="input-flex1" type="text" v-model="shipping_cities" placeholder="<?php echo esc_attr($placeholder); ?>" title="<?php echo esc_attr($placeholder); ?>">
			<div class="guideline"><?php esc_html_e('For multiple cities use commas for separate. Example: Chicago, New York.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</template>
	<?php
	}

	/**
	 * Add billing country template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_country_template() { ?>
		<template v-if="type == 'billing:country'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select class="select2-flex1" v-model="billing_countries" ref="select2_dropdown" multiple data-model="billing_countries" data-placeholder="<?php esc_attr_e('Select country', 'hide-shipping-rates-for-woocommerce'); ?>">
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add shipping country template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_country_template() { ?>
		<template v-if="type == 'shipping:country'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select class="select2-flex1" v-model="shipping_countries" ref="select2_dropdown" multiple data-model="shipping_countries" data-placeholder="<?php esc_attr_e('Select country', 'hide-shipping-rates-for-woocommerce'); ?>">
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
		</template>
	<?php
	}

	/**
	 * Add zipcode template of billing
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_zipcode_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'billing:zipcode'">
			<select>
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<input class="input-flex1" type="text">
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add billing state template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function billing_state_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'billing:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select>
				<option value=""><?php esc_html_e('Select a country', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add shipping zipcode template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_zipcode_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'shipping:zipcode'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<input class="input-flex1" type="text">
			<?php Utils::field_lock_message(); ?>
		</div>
	<?php
	}

	/**
	 * Add shipping state template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function shipping_state_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'shipping:state'">
			<select v-model="operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select>
				<option value=""><?php esc_html_e('Select a country', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option v-for="(country, country_code) in get_countries()" :value="country_code">{{country}}</option>
			</select>
			<?php Utils::field_lock_message(); ?>
		</div>
<?php
	}
}
