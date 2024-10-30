<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Date rule class
 */
final class Date {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_filter('hide_shipping_rates/rule_values', array($this, 'rule_values'));
		add_filter('hide_shipping_rates/rule_matched', array($this, 'rule_filters'), 10, 2);

		add_action('hide_shipping_rates/rule_templates', array($this, 'weekly_days'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'time_template'));
		add_action('hide_shipping_rates/rule_templates', array($this, 'date_template'));
	}

	/**
	 * Date rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function rule_values($values) {
		return array_merge($values, array(
			'date_operator' => '',
			'time_one' => '',
			'time_two' => '',
			'date_one' => '',
			'date_two' => '',
			'weekly_days' => []
		));
	}

	/**
	 * Rule filters
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public function rule_filters($matched, $rule) {
		$operator = $rule['date_operator'];

		if ('date:weekly_days' === $rule['type']) {
			$weekly_days = isset($rule['weekly_days']) && is_array($rule['weekly_days']) ? $rule['weekly_days'] : array();
			$current_day = strtolower(current_time('l'));

			if ('any_in_list' == $operator && in_array($current_day, $weekly_days)) {
				return true;
			}

			if ('not_in_list' == $operator && !in_array($current_day, $weekly_days)) {
				return true;
			}
		}

		if ('date:time' === $rule['type']) {
			$time_one = strtotime($rule['time_one']);
			if (false === $time_one) {
				return $matched;
			}

			if ('before' === $operator) {
				return current_time('timestamp') < $time_one;
			}

			if ('after' === $operator) {
				return current_time('timestamp') > $time_one;
			}

			if ('between' === $operator) {
				$time_two = strtotime($rule['time_two']);
				if (false === $time_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');
				return ($current_time >= $time_one && $current_time <= $time_two);
			}

			if ('not_between' === $operator) {
				$time_two = strtotime($rule['time_two']);
				if (false === $time_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');
				return $current_time < $time_one || $current_time > $time_two;
			}
		}

		if ('date:date' === $rule['type']) {
			$date_one = strtotime($rule['date_one']);
			if (false === $date_one) {
				return $matched;
			}

			if ('before' === $operator) {
				return current_time('timestamp') < $date_one;
			}

			if ('after' === $operator) {
				return current_time('timestamp') > $date_one;
			}

			if ('between' === $operator) {
				$date_two = strtotime($rule['date_two']);
				if (false === $date_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');
				return ($current_time >= $date_one && $current_time <= $date_two);
			}

			if ('not_between' === $operator) {
				$date_two = strtotime($rule['date_two']);
				if (false === $date_two) {
					return $matched;
				}

				$current_time = current_time('timestamp');
				return $current_time < $date_one || $current_time > $date_two;
			}
		}

		return $matched;
	}

	/**
	 * Add time template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function time_template() { ?>
		<template v-if="type == 'date:time'">
			<select v-model="date_operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between', 'not_between')); ?>
			</select>

			<input type="time" v-model="time_one">
			<input type="time" v-model="time_two" v-if="date_operator == 'between' || date_operator == 'not_between'">
		</template>
	<?php
	}

	/**
	 * Add date template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function date_template() { ?>
		<template v-if="type == 'date:date'">
			<select v-model="date_operator">
				<?php Utils::get_operators_options(array('before', 'after', 'between', 'not_between')); ?>
			</select>

			<input type="datetime-local" v-model="date_one">
			<input type="datetime-local" v-model="date_two" v-if="date_operator == 'between' || date_operator == 'not_between'">
		</template>
	<?php
	}

	/**
	 * Weekly days type template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function weekly_days() { ?>
		<template v-if="type == 'date:weekly_days'">
			<select v-model="date_operator">
				<?php Utils::get_operators_options(array('any_in_list', 'not_in_list')); ?>
			</select>

			<select class="select2-flex1" v-model="weekly_days" data-model="weekly_days" ref="select2_dropdown" data-placeholder="<?php esc_attr_e('Select days', 'hide-shipping-rates-for-woocommerce'); ?>" multiple>
				<option value="sunday"><?php esc_html_e('Sunday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="monday"><?php esc_html_e('Monday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="tuesday"><?php esc_html_e('Tuesday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="wednesday"><?php esc_html_e('Wednesday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="thursday"><?php esc_html_e('Thursday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="friday"><?php esc_html_e('Friday', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="saturday"><?php esc_html_e('Saturday', 'hide-shipping-rates-for-woocommerce'); ?></option>
			</select>
		</template>
<?php
	}
}
