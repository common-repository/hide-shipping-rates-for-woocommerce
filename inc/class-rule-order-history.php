<?php

namespace Hide_Shipping_Rates\Rule;

use Hide_Shipping_Rates\Utils;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Order history rule class
 */
final class Order_History {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('hide_shipping_rates/rule_templates', array($this, 'first_purchase_template'));
	}

	/**
	 * Add first purchase template
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function first_purchase_template() { ?>
		<div class="hide-shipping-rates-pro-field" v-if="type == 'order_history:first_purchase'">
			<select>
				<option value="yes"><?php esc_html_e('Yes', 'hide-shipping-rates-for-woocommerce'); ?></option>
				<option value="no"><?php esc_html_e('No', 'hide-shipping-rates-for-woocommerce'); ?></option>
			</select>

			<?php Utils::field_lock_message(); ?>
		</div>
<?php
	}
}
