<?php

namespace Hide_Shipping_Rates;

if (!defined('ABSPATH')) {
	exit;
}

/**
 * Admin class
 */
final class Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action('woocommerce_init', array($this, 'add_rule_settings_field'));
		add_filter('woocommerce_generate_hide_shipping_rates_rules_settings_html', array($this, 'shipping_rules_settings_field_output'), 10, 4);

		add_action('admin_footer', array($this, 'output_modal'));
		add_action('admin_footer', array($this, 'add_vue_component'));
		add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 1);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'), 100);
		add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts_global'), 1);
		add_action('wp_ajax_hide_shipping_rates/get_select2_data', array($this, 'get_select2_data'));
		add_action('hide_shipping_rates/after_shipping_rules', array($this, 'add_rule_settings'));
		add_action('hide_shipping_rates/after_shipping_rules', array($this, 'alternate_matched_result_setting'), 1);
	}

	/**
	 * Add settings field at shipping methods
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rule_settings_field() {
		$methods = WC()->shipping()->load_shipping_methods();
		foreach ($methods as $method) {
			add_filter('woocommerce_shipping_instance_form_fields_' . $method->id, array($this, 'add_hide_shipping_rates_fields'), 10000);
		}
	}

	/**
	 * Add new field below shipping method settings
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function add_hide_shipping_rates_fields($settings) {
		$settings['hide_shipping_rates_rules_settings'] = array(
			'default' => '',
			'title' => esc_html__('Hide this shipping rate if match rule(s).', 'hide-shipping-rates-for-woocommerce'),
			'id' => 'hide_shipping_rates_rules_settings',
			'type' => 'hide_shipping_rates_rules_settings',
		);

		return $settings;
	}

	/**
	 * Add some settings of hide shipping rates
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_rule_settings() { ?>
		<div class="hide-shipping-rates-field-row">
			<label>
				<input type="checkbox" v-model="hide_shipping_rate">
				<?php esc_html_e('Hide this shipping rate on frontend.', 'hide-shipping-rates-for-woocommerce'); ?>
			</label>

			<div style="padding-left: 24px;" class="guideline"><?php esc_html_e('Only the store manager will be able to see this. Use for test purposes only.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</div>

		<div class="hide-shipping-rates-field-row" v-if="hide_shipping_rate !== true">
			<label>
				<input type="checkbox" v-model="disable_shipping_rules">
				<?php esc_html_e('Disable all rules for customers.', 'hide-shipping-rates-for-woocommerce'); ?>
			</label>

			<div style="padding-left: 24px;" class="guideline"><?php esc_html_e('Rule functionality will be applied only for store managers. Use this for test purposes only.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</div>
	<?php
	}

	/**
	 * Add setting for alternate rule matched result
	 * 
	 * @since 1.0.1
	 * @return void
	 */
	public function alternate_matched_result_setting() { ?>
		<div class="hide-shipping-rates-field-row">
			<label>
				<input type="checkbox" disabled>
				<?php esc_html_e('Alternate result of matched rules (pro).', 'hide-shipping-rates-for-woocommerce'); ?>

				<?php if (!Utils::has_pro_installed()): ?>
					<a target="_blank" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/?utm_campaign=hide+shipping+rates&utm_source=alternate+rule+result&utm_medium=shipping+methods"><?php esc_html_e('Get pro', 'hide-shipping-rates-for-woocommerce'); ?></a>
				<?php endif; ?>

				<?php if (Utils::has_pro_installed() && !Utils::is_pro_activated()): ?>
					<?php esc_html_e('Activate the pro version.', 'hide-shipping-rates-for-woocommerce'); ?>
				<?php endif; ?>

				<?php if (Utils::is_pro_activated() && !Utils::license_activated()): ?>
					<a class="btn-open-hide-shipping-rates-license-form" href="#"><?php esc_html_e('Activate license of the pro version.', 'hide-shipping-rates-for-woocommerce'); ?></a>
				<?php endif; ?>
			</label>

			<div style="padding-left: 24px;" class="guideline"><?php esc_html_e('Alternate result of matched rules. The result will be false if the rule matches and true if the rule does not match.', 'hide-shipping-rates-for-woocommerce') ?></div>
		</div>
	<?php
	}

	/**
	 * Output new field below the shipping method settings
	 * 
	 * @since 1.0.0
	 * @return string
	 */
	public function shipping_rules_settings_field_output($html, $field_id, $args, $object) {
		$settings_data = Utils::get_rule_settings(stripslashes($object->get_option($field_id)));

		ob_start(); ?>
		<tr>
			<th>
				<label class="hide-shipping-rates-rules-settings-label"><?php echo esc_html($args['title']); ?></label>
			</th>
			<td>
				<div id="hide-shipping-rates-rules-field" data-settings="<?php echo esc_attr(wp_json_encode($settings_data)) ?>">
					<input type="hidden" name="<?php echo esc_attr($object->get_field_key($field_id)) ?>" :value="get_rule_data">
					<a v-if="rules.length == 0" @click.prevent="add_new_rule()" class="btn-hide-shipping-rates-add-rule-large" href="#"><?php esc_html_e('Add a rule', 'hide-shipping-rates-for-woocommerce') ?></a>

					<div class="hide-shipping-rates-rules-container" v-sortable="{options: {handle: '.rule-move-handle'}}" @end="onOrderChange">
						<rule v-for="(rule, index) in rules" :key="rule.id" :rule="rule" :number="index"></rule>
					</div>

					<div class="hide-shipping-rates-field-row hide-shipping-rates-match-type" v-if="rules.length > 1">
						<label>
							<input type="radio" v-model="match_type" value="all">
							<?php esc_html_e('Match all', 'hide-shipping-rates-for-woocommerce'); ?>
						</label>

						<label>
							<input type="radio" v-model="match_type" value="any">
							<?php esc_html_e('Match any', 'hide-shipping-rates-for-woocommerce'); ?>
						</label>
					</div>

					<a v-if="rules.length > 0" @click.prevent="add_new_rule()" class="button btn-hide-shipping-rates-add-rule" href="#">
						<span class="dashicons dashicons-lock" v-if="rules.length >= max_free_rule_item && !has_pro()"></span>
						<?php esc_html_e('Add another rule', 'hide-shipping-rates-for-woocommerce') ?>
					</a>

					<div style="margin-top: 20px"></div>

					<?php do_action('hide_shipping_rates/after_shipping_rules') ?>
				</div>
			</td>
		</tr>

	<?php
		return ob_get_clean();
	}

	/**
	 * Register styles and scripts
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function register_scripts() {
		if (defined('CODIEPRESS_DEVELOPMENT')) {
			wp_register_script('hide-shipping-rates-vue', HIDE_SHIPPING_RATES_URI . 'assets/vue.js', [], '3.5.12', true);
		} else {
			wp_register_script('hide-shipping-rates-vue', HIDE_SHIPPING_RATES_URI . 'assets/vue.min.js', [], '3.5.12', true);
		}
	}

	/**
	 * Enqueue script on dashboard
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts_global() {
		if (!Utils::is_plugin_screen()) {
			return;
		}

		$wc_countries = new \WC_Countries();

		wp_enqueue_style('hide-shipping-rates-global', HIDE_SHIPPING_RATES_URI . 'assets/global.min.css', [], HIDE_SHIPPING_RATES_VERSION);
		wp_enqueue_script('hide-shipping-rates-global', HIDE_SHIPPING_RATES_URI . 'assets/global.min.js', ['jquery'], HIDE_SHIPPING_RATES_VERSION, true);
		wp_localize_script('hide-shipping-rates-global', 'hide_shipping_rates_admin', array(
			'ajax_url' => admin_url('admin-ajax.php'),
			'countries' => $wc_countries->get_countries(),
			'rule_values' => Utils::get_rule_values(),
			'rule_ui_values' => Utils::get_rule_ui_values(),
			'nonce_select2_data' => wp_create_nonce('_nonce_hide_shipping_rates/get_select2_data'),
			'i10n' => array(
				'delete_rule_warning' => __('Do you want to delete this rule?', 'hide-shipping-rates-for-woocommerce'),
			)
		));

		do_action('hide_shipping_rates/global_enqueue_scripts');
	}

	/**
	 * Enqueue script on shipping page
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function enqueue_scripts() {
		if (!Utils::is_shipping_screen()) {
			return;
		}

		wp_register_script('sortable', HIDE_SHIPPING_RATES_URI . 'assets/sortable.min.js', [], '1.15.2', true);
		wp_register_script('vue-sortable', HIDE_SHIPPING_RATES_URI . 'assets/vue-sortable.js', ['hide-shipping-rates-vue', 'sortable'], '1.0.7', true);

		wp_register_style('select2', HIDE_SHIPPING_RATES_URI . 'assets/select2.min.css', [], '4.1.0');
		wp_enqueue_style('hide-shipping-rates', HIDE_SHIPPING_RATES_URI . 'assets/admin.min.css', ['select2'], HIDE_SHIPPING_RATES_VERSION);

		do_action('hide_shipping_rates/admin_enqueue_scripts');

		wp_enqueue_script('hide-shipping-rates', HIDE_SHIPPING_RATES_URI . 'assets/admin.min.js', ['jquery', 'hide-shipping-rates-vue', 'select2', 'vue-sortable'], HIDE_SHIPPING_RATES_VERSION, true);
	}

	/**
	 * Output plugin modal
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function output_modal() {
		if (!Utils::is_shipping_screen()) {
			return;
		} ?>

		<?php if (!Utils::has_pro_installed()) : ?>
			<div id="hide-shipping-rates-modal">
				<div class="modal-body">
					<a href="#" class="btn-modal-close dashicons dashicons-no-alt" data-modal-close></a>
					<span class="modal-icon dashicons dashicons-lock"></span>

					<div class="modal-pro-missing">
						<?php
						$text = sprintf(
							/* translators: %s for link */
							esc_html__('To add more rules, please get a pro version from %s.', 'hide-shipping-rates-for-woocommerce'),
							'<a target="_blank" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/?utm_campaign=hide+shipping+rates&utm_source=modal&utm_medium=shipping+methods">' . esc_html__('here', 'hide-shipping-rates-for-woocommerce') . '</a>'
						);

						echo wp_kses($text, array('a' => array('href' => true, 'target' => true)));
						?>
					</div>

					<div class="modal-footer">
						<a class="button" data-modal-close href="#"><?php esc_html_e('Close', 'hide-shipping-rates-for-woocommerce'); ?></a>
						<a class="button button-get-pro" href="https://codiepress.com/plugins/hide-shipping-rates-for-woocommerce-pro/?utm_campaign=hide+shipping+rates&utm_source=modal&utm_medium=shipping+methods" target="_blank"><?php esc_html_e('Get Pro', 'hide-shipping-rates-for-woocommerce'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<?php if (!Utils::is_pro_activated()) : ?>
			<div id="hide-shipping-rates-modal">
				<div class="modal-body">
					<a href="#" class="btn-modal-close dashicons dashicons-no-alt" data-modal-close></a>
					<div class="modal-pro-deactivated">
						<?php esc_html_e('Please activate the "Hide Shipping Rates for WooCommerce Pro" plugin on the plugins page.', 'hide-shipping-rates-for-woocommerce'); ?>
					</div>

					<div class="modal-footer">
						<a class="button" data-modal-close href="#"><?php esc_html_e('Close', 'hide-shipping-rates-for-woocommerce'); ?></a>
					</div>
				</div>
			</div>
		<?php endif; ?>
	<?php
	}

	/**
	 * Add vuejs component
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_vue_component() {
		if (!Utils::is_shipping_screen()) {
			return;
		}

		$rule_groups = Utils::get_rule_groups(); ?>

		<template id="component-hide-shipping-rates-rule">
			<fieldset class="hide-shipping-rates-rule-item">
				<select class="rule-type" v-model="type">
					<?php
					foreach ($rule_groups as $group_key => $group_label) {
						$rule_types = Utils::get_types_by_group($group_key);
						if (count($rule_types) == 0) {
							continue;
						}

						echo '<optgroup label="' . esc_attr($group_label) . '">';
						foreach ($rule_types as $key => $rule_type) {
							echo '<option value="' . esc_attr($key) . '">' . esc_html($rule_type['label']) . ' </option>';
						}
						echo '</optgroup>';
					}
					?>
				</select>

				<?php do_action('hide_shipping_rates/rule_templates'); ?>

				<div class="rule-action-tools">
					<span class="rule-move-handle dashicons dashicons-menu-alt"></span>
					<a href="#" class="btn-condition-delete dashicons dashicons-no-alt" @click.prevent="delete_item()"></a>
				</div>
			</fieldset>
		</template>
<?php
	}

	/**
	 * Get select2 dropdown data
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function get_select2_data() {
		check_ajax_referer('_nonce_hide_shipping_rates/get_select2_data', 'security');

		$results = $search_args = array();

		$search_term = !empty($_POST['term']) ? sanitize_text_field($_POST['term'])  : '';
		$query_type = !empty($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
		$query_type = explode(':', $query_type);

		$object_type = !empty($query_type[0]) ? $query_type[0] : '';
		$object_slug = !empty($query_type[1]) ? $query_type[1] : '';

		if ('taxonomy' == $object_type && !empty($object_slug)) {
			$search_args = array('hide_empty' => false, 'taxonomy' => $object_slug);

			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['include'] = array_map('absint', $_POST['ids']);
			}

			$terms = get_terms($search_args);

			$results = array_map(function ($term) {
				return array('id' => $term->term_id, 'name' => $term->name);
			}, $terms);
		}

		if ('users' == $object_type) {
			if (!empty($search_term)) {
				$search_args['search'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['include'] = array_map('absint', $_POST['ids']);
			}

			$get_users = get_users($search_args);
			$results = array_map(function ($user) {
				return array('id' => $user->id, 'name' => $user->display_name);
			}, $get_users);
		}

		if ('states' == $object_type) {
			if (empty($_POST['country'])) {
				wp_send_json_error(array(
					'error' => esc_html__('Country Missing', 'hide-shipping-rates-for-woocommerce')
				));
			}

			$wc_countries = new \WC_Countries();
			$states = $wc_countries->get_states(sanitize_text_field($_POST['country']));

			if (!empty($search_term)) {
				$states = array_filter($states, function ($state) use ($search_term) {
					return stripos($state, $search_term) !== false;
				});
			}

			if (!is_array($states)) {
				$states = [];
			}

			$results = array_map(function ($state, $code) {
				return array('id' => $code, 'name' => html_entity_decode($state));
			}, $states, array_keys($states));
		}

		if ('post_type' == $object_type && !empty($object_slug)) {
			$search_args['post_type'] = $object_slug;
			if (!empty($search_term)) {
				$search_args['s'] = $search_term;
			}

			if (isset($_POST['ids']) && is_array($_POST['ids'])) {
				$search_args['post__in'] = array_map('absint', $_POST['ids']);
			}

			$posts = get_posts($search_args);
			$results = array_map(function ($item) {
				return array('id' => $item->ID, 'name' => $item->post_title);
			}, $posts);
		}

		$results = apply_filters('hide_shipping_rates/get_select2_data', $results, $query_type, $search_term);

		wp_send_json_success($results);
	}
}
