<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Utilities class 
 */
class Utils {

	/**
	 * Is shipping screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_shipping_screen() {
		return ('woocommerce_page_wc-settings' === get_current_screen()->id && isset($_GET['tab']) && 'shipping' === $_GET['tab']);
	}

	/**
	 * Is plugin supported screen
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_plugin_screen() {
		$screen_matched = false;
		if ('woocommerce_page_wc-settings' === get_current_screen()->id && isset($_GET['tab']) && 'shipping' === $_GET['tab']) {
			$screen_matched = true;
		}

		if ('plugins' === get_current_screen()->id) {
			$screen_matched = true;
		}

		return $screen_matched;
	}

	/**
	 * Check if pro version installed
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function has_pro_installed() {
		return file_exists(WP_PLUGIN_DIR . '/hide-shipping-rates-for-woocommerce-pro/hide-shipping-rates-for-woocommerce-pro.php');
	}

	/**
	 * Check if pro plugin activated
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function is_pro_activated() {
		return class_exists('\Hide_Shipping_Rates_Pro\Main');
	}

	/**
	 * Check if pro plugin activated the license
	 * 
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function license_activated() {
		if (!class_exists('\Hide_Shipping_Rates_Pro\Upgrade')) {
			return false;
		}

		return \Hide_Shipping_Rates_Pro\Upgrade::license_activated();
	}

	/**
	 * Get rule operators
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators($operators = array()) {
		$supported_operators = array(
			'equal_to' => __('Equal To ( = )', 'hide-shipping-rates-for-woocommerce'),
			'less_than' => __('Less than ( < )', 'hide-shipping-rates-for-woocommerce'),
			'less_than_or_equal' => __('Less than or equal ( <= )', 'hide-shipping-rates-for-woocommerce'),
			'greater_than_or_equal' => __('Greater than or equal ( >= )', 'hide-shipping-rates-for-woocommerce'),
			'greater_than' => __('Greater than ( > )', 'hide-shipping-rates-for-woocommerce'),
			'between' => __('Between', 'hide-shipping-rates-for-woocommerce'),
			'not_between' => __('Not Between', 'hide-shipping-rates-for-woocommerce'),

			'any_in_list' => __('Any in list', 'hide-shipping-rates-for-woocommerce'),
			'all_in_list' => __('All in list', 'hide-shipping-rates-for-woocommerce'),
			'not_in_list' => __('Not in list', 'hide-shipping-rates-for-woocommerce'),

			'before' => __('Before', 'hide-shipping-rates-for-woocommerce'),
			'after' => __('After', 'hide-shipping-rates-for-woocommerce'),
		);

		$return_operators = [];
		while ($key = current($operators)) {
			if (isset($supported_operators[$key])) {
				$return_operators[$key] = $supported_operators[$key];
			}

			next($operators);
		}

		return $return_operators;
	}

	/**
	 * Get rule operators dropdown
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_operators_options($args = array()) {
		$operators = self::get_operators($args);

		$options = array_map(function ($label, $key) {
			return sprintf('<option value="%s">%s</option>', $key, $label);
		}, $operators, array_keys($operators));

		echo wp_kses(implode('', $options), array(
			'option' => array(
				'value' => true
			)
		));
	}

	/**
	 * Group of rule types
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_groups() {
		return apply_filters('hide_shipping_rates/rule_type_groups', array(
			'cart' => __('Cart', 'hide-shipping-rates-for-woocommerce'),
			'cart_products' => __('Cart Products', 'hide-shipping-rates-for-woocommerce'),
			'date' => __('Date', 'hide-shipping-rates-for-woocommerce'),
			'billing' => __('Billing', 'hide-shipping-rates-for-woocommerce'),
			'shipping' => __('Shipping', 'hide-shipping-rates-for-woocommerce'),
			'user' => __('Customer', 'hide-shipping-rates-for-woocommerce'),
			'order_history' => __('Order History', 'hide-shipping-rates-for-woocommerce'),
			'others' => __('Others', 'hide-shipping-rates-for-woocommerce'),
		));
	}

	/**
	 * Get types of rule
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_types() {
		return apply_filters('hide_shipping_rates/rule_types', array(

			/** Cart related field types */
			'cart:subtotal' => array(
				'group' => 'cart',
				'priority' => 10,
				'label' => __('Subtotal', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:total_quantity' => array(
				'group' => 'cart',
				'priority' => 15,
				'label' => __('Total quantity', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:total_weight' => array(
				'group' => 'cart',
				'priority' => 20,
				'label' => __('Total weight', 'hide-shipping-rates-for-woocommerce'),
			),
			'cart:coupons' => array(
				'group' => 'cart',
				'priority' => 25,
				'label' => __('Coupons', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Cart products related field types */
			'cart_products:products' => array(
				'group' => 'cart_products',
				'priority' => 5,
				'label' => __('Products', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Date related field types */
			'date:time' => array(
				'group' => 'date',
				'priority' => 5,
				'label' => __('Time', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:date' => array(
				'group' => 'date',
				'priority' => 10,
				'label' => __('Date', 'hide-shipping-rates-for-woocommerce'),
			),
			'date:weekly_days' => array(
				'group' => 'date',
				'priority' => 15,
				'label' => __('Weekly Days', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Billing address related field types */
			'billing:city' => array(
				'group' => 'billing',
				'priority' => 10,
				'label' => __('City', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:zipcode' => array(
				'group' => 'billing',
				'priority' => 20,
				'label' => __('Zip code', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:state' => array(
				'group' => 'billing',
				'priority' => 25,
				'label' => __('State', 'hide-shipping-rates-for-woocommerce'),
			),
			'billing:country' => array(
				'group' => 'billing',
				'priority' => 30,
				'label' => __('Country', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Shipping address related field types */
			'shipping:city' => array(
				'group' => 'shipping',
				'priority' => 10,
				'label' => __('City', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:zipcode' => array(
				'group' => 'shipping',
				'priority' => 15,
				'label' => __('Zip code', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:state' => array(
				'group' => 'shipping',
				'priority' => 20,
				'label' => __('State', 'hide-shipping-rates-for-woocommerce'),
			),
			'shipping:country' => array(
				'group' => 'shipping',
				'priority' => 25,
				'label' => __('Country', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Customer related field types */
			'user:users' => array(
				'group' => 'user',
				'priority' => 10,
				'label' => __('Users', 'hide-shipping-rates-for-woocommerce'),
			),
			'user:roles' => array(
				'group' => 'user',
				'priority' => 15,
				'label' => __('Roles', 'hide-shipping-rates-for-woocommerce'),
			),
			'user:logged_in' => array(
				'group' => 'user',
				'priority' => 20,
				'label' => __('Logged In', 'hide-shipping-rates-for-woocommerce'),
			),

			/** Order History related field types */
			'order_history:first_purchase' => array(
				'group' => 'order_history',
				'priority' => 10,
				'label' => __('First Purchase', 'hide-shipping-rates-for-woocommerce'),
			),
		));
	}

	/**
	 * Get rule types of group
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_types_by_group($group) {
		$group_types = [];
		foreach (self::get_rule_types() as $key => $type) {
			if ($group !== $type['group']) {
				continue;
			}

			$group_types[$key] = $type;
		}

		uasort($group_types, function ($a, $b) {
			return $a['priority'] > $b['priority'] ? 1 : -1;
		});

		return $group_types;
	}

	/**
	 * Get rule values
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_values() {
		$rule_values = apply_filters('hide_shipping_rates/rule_values', array());

		return array_merge($rule_values, array(
			'value' => '',
			'type' => 'cart:subtotal',
			'operator' => 'greater_than',
		));
	}

	/**
	 * Get rule extra values for UI management
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_ui_values() {
		return apply_filters('hide_shipping_rates/rule_ui_values', array('loading' => false));
	}

	/**
	 * Converted and get shipping rule data
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public static function get_rule_settings($json_data) {
		$rule_settings = json_decode($json_data, true);
		if (!is_array($rule_settings)) {
			return array();
		}

		if (!isset($rule_settings['rules']) || !is_array($rule_settings['rules'])) {
			$rule_settings['rules'] = array();
		}

		$rule_settings['rules'] = array_map(function ($rule) {
			$rule =  wp_parse_args($rule, self::get_rule_values());

			if ('cart_products:categories' === $rule['type']) {
				$rule['type'] = 'cart_products:product_cat';
				if (isset($rule['categories']) && is_array($rule['categories'])) {
					$rule['cart_products_product_cat'] = $rule['categories'];
				}
			}

			if ('cart_products:tags' === $rule['type']) {
				$rule['type'] = 'cart_products:product_tag';
				if (isset($rule['tags']) && is_array($rule['tags'])) {
					$rule['cart_products_product_tag'] = $rule['tags'];
				}
			}

			if ('cart_products:shipping_classes' === $rule['type']) {
				$rule['type'] = 'cart_products:product_shipping_class';
				if (isset($rule['shipping_classes']) && is_array($rule['shipping_classes'])) {
					$rule['cart_products_product_shipping_class'] = $rule['shipping_classes'];
				}
			}

			return apply_filters('hide_shipping_rates/rule_migrate', $rule);
		}, $rule_settings['rules']);

		return $rule_settings;
	}

	/**
	 * Pro field lock message
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public static function field_lock_message() {
		if (self::has_pro_installed()) {
			if (self::is_pro_activated()) {
				if (!self::license_activated()) {
					echo '<div class="locked-message locked-message-activate-license">';
					$message = sprintf(
						/* translators: %1$s: Link open, %2%s: Link close */
						esc_html__('Please activate your license for unlock this feature. %1$sClick here%2$s for activate license.', 'hide-shipping-rates-for-woocommerce'),
						'<a href="#" class="btn-open-hide-shipping-rates-license-form">',
						'</a>'
					);
					echo wp_kses($message, array('a' => array('href' => true,  'target' => true, 'class' => true)));
					echo '</div>';
				}
			} else {
				echo '<div class="locked-message locked-message-deactivate">';
				esc_html_e('Please activate the "Hide Shipping Rates for WooCommerce Pro" plugin.', 'hide-shipping-rates-for-woocommerce');
				echo '</div>';
			}
		} else {
			echo '<div class="locked-message locked-message-get-pro">Get the <a target="_blank" :href="get_pro_link">pro version</a> for unlock this feature.</div>';
		}
	}

	/**
	 * Get registered taxonomies of product
	 * 
	 * @since 1.0.2
	 * @return array
	 */
	public static function get_product_taxonomies() {
		$taxonomies = get_object_taxonomies('product', 'objects');
		foreach ($taxonomies as $tax_slug => $taxonomy) {
			if (false === $taxonomy->public) {
				unset($taxonomies[$tax_slug]);
			}
		}

		$taxonomies = array_map(function ($taxonomy) {
			return (object) array(
				'slug' => $taxonomy->name,
				'label' => $taxonomy->label,
			);
		}, $taxonomies);

		return $taxonomies;
	}

	/**
	 * Get total value of terms from cart
	 * 
	 * @since 1.0.2
	 * @return array
	 */
	public static function get_terms_total($taxonomy, $allow_terms = false) {
		$term_totals = array();

		foreach (WC()->cart->get_cart() as $item) {
			$product = wc_get_product($item['product_id']);
			$cart_item_terms = wc_get_product_term_ids($item['product_id'], $taxonomy);

			foreach ($cart_item_terms as $term_id) {
				if (is_array($allow_terms) && !in_array($term_id, $allow_terms)) {
					continue;
				}

				if (!isset($term_totals[$term_id])) {
					$term_totals[$term_id] = array(
						'line_subtotal' => 0,
						'quantity' => 0,
						'weight'   => 0,
					);
				}

				$term_totals[$term_id]['line_subtotal'] += $item['line_subtotal'];
				$term_totals[$term_id]['quantity'] += $item['quantity'];

				if ($product->has_weight()) {
					$term_totals[$term_id]['weight'] += $product->get_weight() * $item['quantity'];
				}
			}
		}

		return $term_totals;
	}
}
