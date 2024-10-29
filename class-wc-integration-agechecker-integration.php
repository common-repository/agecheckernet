<?php
if (!class_exists('WC_Integration_AgeChecker_Integration')):
	class WC_Integration_AgeChecker_Integration extends WC_Integration {
		public $key;
		public $secret;
		public $store_name;
		public $element;
		public $workflow_type;
		public $set_status_pending;
		public $set_status_accepted;
		public $set_status_denied;
		public $set_status_disabled;
		public $set_status_blocked;
		public $excluded_categories;
		public $categories_mode;
		public $excluded_groups;
		public $groups_mode;
		public $excluded_shipping;
		public $excluded_payment;
		public $min_total;
		public $form_trigger_name;
		public $form_trigger_value;
		public $form_trigger_mode;
		public $page;
		public $enable_all;
		public $enable_noscript;
		public $script_location;
		public $category_apikeys;
		public $apikeys_list;
		public $client_config;
		public $before_script;
		public $load_script;
		public $tag_order;
		public $set_customer_role;
		public $role_find_account;
		public $customer_info;
		public $trigger_afterpayment_on_failed;
		public $trigger_afterpayment_on_completed;

		public function __construct() {
			$this->id           = 'integration-agechecker';
			$this->method_title = 'AgeChecker.Net';

			$product_categories = get_terms(array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			));

			if (!function_exists('get_editable_roles')) {
				require_once(ABSPATH . '/wp-admin/includes/user.php');
			}

			$roles = array();
			foreach (get_editable_roles() as $role_name => $role_info) {
				$roles[] = $role_name;
			}

			$this->init_form_fields();
			$this->init_settings();

			add_action('wp_head', array(
				$this,
				'add_script_head'
			), 1);
			add_action('wp_footer', array(
				$this,
				'add_script_footer'
			), 1);
			add_action('woocommerce_update_options_integration_integration-agechecker', array(
				$this,
				'process_admin_options'
			));
			add_action('woocommerce_checkout_process', array(
				$this,
				'validate_order_shortcode'
			));
			// Add hook for WooCommerce Block Checkout
			add_action('woocommerce_store_api_checkout_update_order_from_request', array(
				$this,
				'validate_order_blocks'
			), 10, 2);
			add_action( 'woocommerce_new_order', array(
				$this,
				'new_order'
			));
			add_action( 'rest_api_init', array(
				$this,
				'establish_api'
			));

			$this->key                 = $this->get_option('key');
			$this->secret              = $this->get_option('secret');
			$this->store_name          = $this->get_option('store_name');
			$this->element             = $this->get_option('element');
			$this->workflow_type       = $this->get_option('workflow_type');
			$this->set_status_pending  = $this->get_option('set_status_pending');
			$this->set_status_accepted = $this->get_option('set_status_accepted');
			$this->set_status_denied   = $this->get_option('set_status_denied');
			$this->set_status_disabled = $this->get_option('set_status_disabled');
			$this->set_status_blocked  = $this->get_option('set_status_blocked');
			$this->excluded_categories = $this->get_option('excluded_categories');
			$this->categories_mode 	   = $this->get_option('categories_mode');
			$this->excluded_groups     = $this->get_option('excluded_groups');
			$this->groups_mode 	       = $this->get_option('groups_mode');
			$this->excluded_shipping   = $this->get_option('excluded_shipping');
			$this->excluded_payment    = $this->get_option('excluded_payment');
			$this->min_total    	   = $this->get_option('min_total');
			$this->form_trigger_name   = $this->get_option('form_trigger_name');
			$this->form_trigger_value  = $this->get_option('form_trigger_value');
			$this->form_trigger_mode   = $this->get_option('form_trigger_mode');
			$this->page                = $this->get_option('page');
			$this->enable_all	       = $this->get_option('enable_all');
			$this->enable_noscript	   = $this->get_option('enable_noscript');
			$this->script_location	   = $this->get_option('script_location');
			$this->category_apikeys    = $this->get_option('category_apikeys');
			$this->apikeys_list        = $this->get_option('apikeys_list');
			$this->client_config       = wp_specialchars_decode($this->get_option('client_config'), 'double');
			$this->before_script       = wp_specialchars_decode($this->get_option('before_script'), 'double');
			$this->load_script         = wp_specialchars_decode($this->get_option('load_script'), 'double');
			$this->tag_order 	       = $this->get_option('tag_order');
			$this->set_customer_role   = $this->get_option('set_customer_role');
			$this->role_find_account   = $this->get_option('role_find_account');
			$this->customer_info       = $this->get_option('customer_info');
			$this->trigger_afterpayment_on_failed = $this->get_option('trigger_afterpayment_on_failed');
			$this->trigger_afterpayment_on_completed = $this->get_option('trigger_afterpayment_on_completed');

			if($this->get_option('tag_order_setting_updated') != 'true') {
				if ($this->get_option('tag_order') == 'tag_none') {
					$this->update_option('tag_order', 'tag_notes');
				}
				$this->update_option('tag_order_setting_updated', 'true');
			}

			// Set settings to version without entity codes.
			$this->settings['client_config'] = $this->client_config;
			$this->settings['before_script'] = $this->before_script;

			if ($this->workflow_type == "after_payment") {
				add_filter('manage_woocommerce_page_wc-orders_columns', array($this, 'add_order_status_column_header')); // WC 7.1+
				add_action('manage_woocommerce_page_wc-orders_custom_column', array($this, 'add_order_status_column_content'), 10, 2); // WC 7.1+
				add_filter('manage_edit-shop_order_columns', array($this, 'add_order_status_column_header'));
				add_action('manage_shop_order_posts_custom_column', array($this, 'add_order_status_column_content'), 10, 2);
				add_action('admin_print_styles', array($this, 'add_order_status_column_style'));
				add_action('woocommerce_admin_order_data_after_order_details', array($this, 'add_verification_status_order_page'));
			}
		}

		public function init_form_fields() {
			$this->form_fields = array(
				'settings_hider'  => array(
					'type'        => 'settingshider',
					'desc_tip'    => false,
				),
				'workflow_type'   => array(
					'title'       => 'Verification Workflow Type',
					'type'        => 'radio',
					'description' => 'Specify where in the order process you want age verification to occur.',
					'default'     => 'before_payment',
					'desc_tip'    => false,
				),
				'key'             => array(
					'title'       => '* API Key',
					'type'        => 'text',
					'description' => 'The API key for this domain. Find it in the <a href="https://agechecker.net/account/websites" target="blank" rel="noopener">websites tab</a> of your AgeChecker.Net account.',
					'default'     => '',
					'desc_tip'    => false,
				),
				'secret'          => array(
					'title'       => '* Account Secret',
					'type'        => 'text',
					'description' => 'The Account Secret for your AgeChecker account. Find it in the <a href="https://agechecker.net/account/websites" target="blank" rel="noopener">websites tab</a> of your AgeChecker.Net account.',
					'default'     => '',
					'desc_tip'    => false,
				),
				'store_name'      => array(
					'title'       => 'Store Name',
					'type'        => 'text',
					'description' => 'Display name of your store in the popup.',
					'default'     => get_bloginfo('name'),
					'desc_tip'    => false,
				),
				'page'            => array(
					'title'       => 'Page',
					'type'        => 'selectbox',
					'description' => 'Page to show AgeChecker.Net on.',
					'default'     => 'checkout',
					'list'        => 'pages',
					'desc_tip'    => false,
				),
				'enable_all'      => array(
					'title'       => 'Enable on All Pages',
					'type'        => 'radio',
					'description' => '<b>This option only effects the "Before Payment" workflow.</b></br>NOTE: This should only be used if "Page" box above is not working. Try setting the page name in the "Page" input box above first before using this.',
					'default'     => 'dont_enable_all',
					'desc_tip'    => false,
				),
				'element'         => array(
					'title'       => 'Element',
					'type'        => 'text',
					'description' => 'Element selector to attach the popup event to when using the "Before Payment" workflow. You may need to change this if you use a custom theme/template. Please contact us if the popup is not showing when the element is clicked. The default element is the final checkout button.',
					'default'     => '#place_order,.wc-block-components-checkout-place-order-button',
					'desc_tip'    => false,
				),
				'trigger_afterpayment_on_failed' => array(
					'title'       => 'Trigger Popup on Order Failed',
					'type'        => 'radio',
					'description' => 'Determines whether or not to trigger the verification popup if order status is failed.',
					'default'     => 'disable',
					'desc_tip'    => false,
				),
				'trigger_afterpayment_on_completed' => array(
					'title'       => 'Trigger Popup on Order Completed',
					'type'        => 'radio',
					'description' => 'Determines whether or not to trigger the verification popup if order status is completed, and no verification has been performed yet.',
					'default'     => 'enable',
					'desc_tip'    => false,
				),
				'customer_info'   => array(
					'title'       => 'Customer Information',
					'type'        => 'radio',
					'description' => 'Specify which customer data set to use for age verification.',
					'default'     => 'billing',
					'desc_tip'    => false,
				),
				'set_status_pending' => array(
					'title'       => 'Set Order Status when Verification is Pending',
					'type'        => 'selectbox',
					'description' => 'Change the status of an order when a verification is <i>pending</i>. (Such as waiting for photo ID submission or e-signature, or no verification has been performed at all yet) AgeChecker will not set the order status if set to "None".',
					'default'     => 'do_not_set_status',
					'list'		  => 'order_status_pending',
					'desc_tip'    => false,
				),
				'set_status_accepted' => array(
					'title'       => 'Set Order Status when Verification is Accepted',
					'type'        => 'selectbox',
					'description' => 'Change the status of an order when a verification is <i>accepted</i>. AgeChecker will not set the order status if set to "None".',
					'default'     => 'do_not_set_status',
					'list'		  => 'order_status_accepted',
					'desc_tip'    => false,
				),
				'set_status_denied' => array(
					'title'       => 'Set Order Status when Verification is Denied or Failed',
					'type'        => 'selectbox',
					'description' => 'Change the status of an order when a verification is <i>denied</i>, user is banned, or when an error occured while running the verification. AgeChecker will not set the order status if set to "None".',
					'default'     => 'do_not_set_status',
					'list'		  => 'order_status_denied',
					'desc_tip'    => false,
				),
				'set_status_disabled' => array(
					'title'       => 'Set Order Status when Verification is Disabled at Customer\'s Location',
					'type'        => 'selectbox',
					'description' => 'Change the status of an order when a verification is disabled and not needed at customer\'s location. AgeChecker will not set the order status if set to "None".',
					'default'     => 'do_not_set_status',
					'list'		  => 'order_status_disabled',
					'desc_tip'    => false,
				),
				'set_status_blocked' => array(
					'title'       => 'Set Order Status when Verification is Blocked at Customer\'s Location',
					'type'        => 'selectbox',
					'description' => 'Change the status of an order when a verification is blocked due to customer\'s location. AgeChecker will not set the order status if set to "None".',
					'default'     => 'do_not_set_status',
					'list'		  => 'order_status_blocked',
					'desc_tip'    => false,
				),
				'excluded_categories' => array(
					'title'       => 'Exclude/Include Categories',
					'type'        => 'listbox',
					'description' => 'NOTE: To enable age verification on all product categories, the list above should be empty and "Exclude listed roles from age verification" selected.',
					'default'     => '',
					'list'        => 'categories',
					'desc_tip'    => false,
				),
				'categories_mode' => array(
					'type'        => 'radio',
					'default'     => 'exclude',
					'list'     	  => 'categories',
					'desc_tip'    => false,
				),
				'excluded_groups' => array(
					'title'       => 'Exclude/Include Groups',
					'type'        => 'listbox',
					'default'     => '',
					'list'        => 'roles',
					'desc_tip'    => false,
				),
				'groups_mode'     => array(
					'type'        => 'radio',
					'default'     => 'exclude',
					'list'     	  => 'roles',
					'desc_tip'    => false,
				),
				'excluded_shipping'  => array(
					'title'       => 'Exclude Shipping Methods',
					'type'        => 'listbox',
					'default'     => '',
					'list'        => 'shipping_methods',
					'desc_tip'    => false,
				),
				'excluded_payment' => array(
					'title'       => 'Exclude Payment Methods',
					'type'        => 'listbox',
					'default'     => '',
					'list'        => 'payment_methods',
					'desc_tip'    => false,
				),
				'min_total'       => array(
					'title'       => 'Minimum Total',
					'type'        => 'text',
					'description' => 'Specify a minimum payment total that triggers age verification. (A value of 0 would age verify any payment total) E.g. A minimum total of 10 means any order with a payment total under $10 will not be age checked, while any total $10 or above will be age checked.',
					'default'     => '0.00',
					'desc_tip'    => false,
				),
				'form_trigger_name' => array(
					'title'       => 'Custom Form Trigger (Advanced)',
					'type'        => 'titled_text',
					'header'      => 'Element name',
					'description' => '',
					'default'     => '',
					'desc_tip'    => false,
				),
				'form_trigger_value' => array(
					'type'        => 'titled_text',
					'header'      => 'Element value',
					'default'     => '',
					'desc_tip'    => false,
				),
				'form_trigger_mode' => array(
					'type'        => 'radio',
					'description' => '<b>This option only effects the "Before Payment" workflow.</b></br>This setting can be used to run (or stop) age verification based on the value of an element from the checkout form.',
					'default'     => 'run',
					'desc_tip'    => false,
				),
				'enable_noscript' => array(
					'title'       => 'Redirect users if Javascript is disabled',
					'type'        => 'radio',
					'description' => 'Since AgeChecker requires Javascript to load, users will be redirected to a page letting them know to enable Javascript if it\'s disabled. Only disable this if you have another way put in place that alerts users to re-enable Javascript if disabled.',
					'default'     => 'enable_noscript',
					'desc_tip'    => false,
				),
				'script_location' => array(
					'title'       => 'Script Location',
					'type'        => 'radio',
					'description' => '<b>This option only effects the "Before Payment" workflow.</b></br>Location of script in site source',
					'default'     => 'head',
					'desc_tip'    => false,
				),
				'category_apikeys' => array(
					'title'       => 'Category Specific API Keys (Advanced)',
					'type'        => 'apikeys_listbox',
					'default'     => '{}',
					'desc_tip'    => false,
				),
				'apikeys_list'    => array(
					'type'        => 'ordered_listbox',
					'default'     => 'default_api_key',
					'list'        => 'apikeys',
					'description' => 'Move API keys with the arrow buttons to set priority of the key. Keys with a higher position on the list will take priority to be used than ones below it. (E.g. If you have a product in a cart with a category specific API key with age settings set to 21+, you would most likely want this to override another product in the cart with a category specific API key with 18+ age settings)<br><b>Default API Key</b> = API key set in the "API Key" input box at the top of the settings page',
					'desc_tip'    => false,
				),
				'client_config'   => array(
					'title'       => 'Additional Config (Advanced)',
					'type'        => 'textarea',
					'description' => 'Other additional configuration options from our <a href="https://agechecker.net/account/install/custom/client" target="blank" rel="noopener">client API</a>.',
					'desc_tip'    => false,
					'css'         => 'width: 400px'
				),
				'before_script'   => array(
					'title'       => 'Before Load Script (Advanced)',
					'type'        => 'textarea',
					'description' => 'Custom code to run before the script is loaded. Use "return" to prevent loading.',
					'desc_tip'    => false,
					'css'         => 'width: 400px'
				),
				'load_script'   => array(
					'title'       => 'Custom Script Loader (Advanced)',
					'type'        => 'textarea',
					'description' => 'If filled, this will overwrite the default code for the section that starts with "w.AgeCheckerConfig" and ends with "h.insertBefore(a,h.firstChild);".',
					'desc_tip'    => false,
					'css'         => 'width: 400px'
				),
				'tag_order'       => array(
					'title'       => 'Tag Orders',
					'type'        => 'radio',
					'description' => '<b>This option only effects the "Before Payment" workflow. "After Payment" workflow will always add a verification status and UUID to the order fields, ignoring this setting.</b>',
					'default'     => 'tag_none',
					'desc_tip'    => false,
				),
				'set_customer_role' => array(
					'title'       => 'Set Customer Role',
					'type'        => 'selectbox',
					'description' => 'Add a role to a logged-in or newly created customer\'s list of user roles upon accepted verification. (Customers must be logged in to their account for the role to be added)',
					'default'     => 'do_not_set_role',
					'list'		  => 'customer_role',
					'desc_tip'    => false,
				),
				'role_find_account' => array(
					'type'        => 'radio',
					'description' => 'If first option is enabled, a user\'s account will be attempted to be found based on a guest order\'s email and billing details. If matching, the role will be applied to the found user account.',
					'default'     => 'false',
					'desc_tip'    => false,
				),
				'enable_noscript' => array(
					'title'       => 'Redirect users if Javascript is disabled',
					'type'        => 'radio',
					'description' => 'Since AgeChecker requires Javascript to load, users will be redirected to a page letting them know to enable Javascript if it\'s disabled. Only disable this if you have another way put in place that alerts users to re-enable Javascript if disabled.',
					'default'     => 'enable_noscript',
					'desc_tip'    => false,
				),
			);
		}

		public function generate_selectbox_html($key, $data) {
			$field = $this->plugin_id . $this->id . '_' . $key;

			$array = array();
			$default = "";
			$selected = $this->get_option($key);

			if($key == "set_customer_role") {
				$default = "do_not_set_role";
				foreach (get_editable_roles() as $role_name => $role_info) {
					$array[$role_name] = $role_name;
				}
			} else if(substr($key, 0, strlen("set_status_")) === "set_status_")  {
				$default = "do_not_set_status";
				$order_statuses = wc_get_order_statuses();
 
				foreach ($order_statuses as $status_tag => $status_name) {
				  $array[$status_tag] = $status_name;
				}
			} else if ($key == "page") {
				$pages = get_pages();

				$current_found = false;
				$alt_selected = null;
				foreach ($pages as $page) {
    				$slug = $page->post_name;

					if(empty($slug)) {
						continue;
					}

					if($slug === $selected) {
						$current_found = true;
					}

					if(strpos($slug, 'checkout') !== false) {
						$alt_selected = $slug;
					}

					$array[$slug] = $page->post_title;
				}

				// If current saved page was not found, then set the page slug that contains "checkout" to be the selected value
				if (!$current_found && $alt_selected != null) {
					$selected = $alt_selected;
				}
			}

			ob_start();
			?>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
						</legend>
						<p>
							<select id="<?php echo $data['list']; ?>Input" name="<?php echo $data['list']; ?>Input">
								<?php if ($key !== 'page'): ?>
									<option value="<?php echo $default; ?>" <?php echo ($selected === $default ? "selected" : ""); ?>>None</option>
								<?php endif; ?>
								<?php
									foreach ($array as $k => $c) {
										echo '<option value="' . $k . '" '. ($selected === $k ? "selected" : "") .'>' . $c . '</option>';
									}
								?>
							</select>
						</p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>

			<?php
			return ob_get_clean();
		}

		public function generate_settingshider_html($key, $data) {
			?>
			<script>
				const beforePaymentFields = ["[name='woocommerce_integration-agechecker_element']", "#woocommerce_integration-agechecker_enable_all", 
				"#woocommerce_integration-agechecker_script_location", "#woocommerce_integration-agechecker_tag_order", "#pagesInput", "#woocommerce_integration-agechecker_form_trigger_name",
				"#woocommerce_integration-agechecker_form_trigger_value", "#woocommerce_integration-agechecker_form_trigger_mode"];
				const afterPaymentFields = ["[for='woocommerce_integration-agechecker_secret']", "#order_status_pendingInput", "#order_status_acceptedInput", "#order_status_deniedInput",
				"#order_status_disabledInput", "#order_status_blockedInput","[for='woocommerce_integration-agechecker_trigger_afterpayment_on_failed']","[for='woocommerce_integration-agechecker_trigger_afterpayment_on_completed']"];

				function changeWorkflow(sel) {
					let workflow = sel;

					// All elements on the settings has the same type of top level parent,
					// so find this in order to hide the whole element.
					let findParent = function(el) {
						if(!el) return;

						let currentParent = el.parentElement;

						while(!currentParent.getAttribute("valign") || currentParent.getAttribute("valign") != "top") {
							currentParent = currentParent.parentElement;
						}

						return currentParent;
					};

					let hideFields = function(fields) {
						for(let i = 0; i < fields.length; i++) {
							let el = findParent(document.querySelector(fields[i]));

							el.setAttribute("style", "display: none;");
						}
					};

					let showFields = function(fields) {
						for(let i = 0; i < fields.length; i++) {
							let el = findParent(document.querySelector(fields[i]));

							el.removeAttribute("style");
						}
					};

					if(workflow == "before_payment") {
						hideFields(afterPaymentFields);
						showFields(beforePaymentFields);
					} else {
						hideFields(beforePaymentFields);
						showFields(afterPaymentFields);
					}
				}

			</script>
			<?php
		}

		public function generate_radio_html($key, $data) {
			$field              = $this->plugin_id . $this->id . '_' . $key;

			$excluded = true;
			if($this->get_option($key) == 'include')
				$excluded = false;

			ob_start();
			?>

			<tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
                        </legend>
						<p>
							<?php
								if($key == "tag_order") {
								$tag_choice = $this->get_option($key);
						    ?>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="tag_notes" <?php if($tag_choice == "tag_notes") echo 'checked'; ?>> Tag Order <b>Notes</b> with age verification UUID<br>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="tag_fields" <?php if($tag_choice == "tag_fields") echo 'checked'; ?>> Tag Order <b>Custom Fields</b> with age verification UUID<br>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="tag_both" <?php if($tag_choice == "tag_both") echo 'checked'; ?>> Tag Order <b>Notes</b> & <b>Custom Fields</b> with age verification UUID<br>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="tag_none" <?php if($tag_choice == "tag_none") echo 'checked'; ?>> Don't tag order with age verification UUID<br>
							<?php
								} else if($key == "enable_all") {
								$enable_all_choice = $this->get_option($key);
							?>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="dont_enable_all" <?php if($enable_all_choice == "dont_enable_all") echo 'checked'; ?>> Load only on page specified in "Page" input box<br>
								<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="enable_all" <?php if($enable_all_choice == "enable_all") echo 'checked'; ?>> Enable AgeChecker script to all pages<br>
							<?php
								} else if($key == "script_location") {
									$script_location_choice = $this->get_option($key);
								?>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="head" <?php if($script_location_choice == "head") echo 'checked'; ?>> Head<br>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="footer" <?php if($script_location_choice == "footer") echo 'checked'; ?>> Footer<br>
							<?php
								} else if($key == "role_find_account") {
									$find_account_choice = $this->get_option($key);
								?>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="true" <?php if($find_account_choice == "true") echo 'checked'; ?>> Attempt to find user account based on guest order details<br>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="false" <?php if($find_account_choice == "false") echo 'checked'; ?>> Only use user accounts associated with orders<br>
							<?php
								} else if($key == "workflow_type") {
									$workflow_type_choice = $this->get_option($key);
								?>
									<script>
										let findWorkflowValue = setInterval(function() {
											if(document.querySelector("#woocommerce_integration-agechecker_workflow_type") && document.querySelector("#woocommerce_integration-agechecker_workflow_type").value) {
												changeWorkflow("<?php echo $workflow_type_choice; ?>");
												clearInterval(findWorkflowValue);
											}
										}, 100);
									</script>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="before_payment" <?php if($workflow_type_choice == "before_payment") echo 'checked'; ?> onclick="changeWorkflow(this.value);"> Before Payment - Customers will be prompted with AgeChecker before completing their order on the checkout page. Order will be stopped if user fails verification.<br>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="after_payment" <?php if($workflow_type_choice == "after_payment") echo 'checked'; ?> onclick="changeWorkflow(this.value);"> After Payment - Customers will be prompted with AgeChecker after completing their order. All orders will be submitted, with a status of the verification added to the order custom fields.<br>
							<?php
								} else if($key == "enable_noscript") { 
										$enable_noscript = $this->get_option($key);
									?>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="enable_noscript" <?php if($enable_noscript == "enable_noscript") echo 'checked'; ?>> Enable Redirection<br>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="dont_enable_noscript" <?php if($enable_noscript == "dont_enable_noscript") echo 'checked'; ?>> Disable Redirection<br>
								<?php
									} else if($key == "trigger_afterpayment_on_failed") {
										$trigger_afterpayment_on_failed = $this->get_option($key);
									?>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="enable" <?php if($trigger_afterpayment_on_failed == "enable") echo 'checked'; ?>> Enable popup if order status is failed<br>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="disable" <?php if($trigger_afterpayment_on_failed == "disable") echo 'checked'; ?>> Disable popup if order status is failed<br>
								<?php
									} else if($key == "trigger_afterpayment_on_completed") {
										$trigger_afterpayment_on_completed = $this->get_option($key);
									?>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="enable" <?php if($trigger_afterpayment_on_completed == "enable") echo 'checked'; ?>> Enable popup if order status is completed<br>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="disable" <?php if($trigger_afterpayment_on_completed == "disable") echo 'checked'; ?>> Disable popup if order status is completed<br>
								<?php
								} else if($key == "form_trigger_mode") {
										$custom_trigger_mode = $this->get_option($key);
									?>
										<p style="margin-bottom: 2.5px"><b>Trigger Result:</b></p>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="run" <?php if($custom_trigger_mode == "run") echo 'checked'; ?>> Run age verification<br>
										<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="stop" <?php if($custom_trigger_mode == "stop") echo 'checked'; ?>> Stop age verification<br>
								<?php
								} else if($key == "customer_info") {
									$customer_info = $this->get_option($key);
								?>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="billing" <?php if($customer_info == "billing") echo 'checked'; ?>> Use billing information<br>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="shipping" <?php if($customer_info == "shipping") echo 'checked'; ?>> Use shipping information<br>
								<?php
								} else {
								?>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="exclude" <?php if($excluded) echo 'checked'; ?>> Exclude listed <?php echo $data['list'] ?> from age verification<br>
									<input type="radio" id="<?php echo esc_attr($field); ?>" name="<?php echo esc_attr($field); ?>" value="include" <?php if(!$excluded) echo 'checked'; ?>> Include listed <?php echo $data['list'] ?> for age verification<br>
						    <?php
								}
							?>
						</p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>

			<?php
			return ob_get_clean();
		}

		public function product_name_from_value($value, $product_categories) {
			if (substr($value, 0, 6) == "\$_ID_=") {
				$id = substr($value, 6, strlen($value));
				foreach ($product_categories as $c) {
					if($c->term_id == $id) {
						return $c->name;
					}
				}
				return "[DELETED CATEGORY]";
			} else {
				return $value;
			}
		}

		public function generate_listbox_html($key, $data) {
			$field              = $this->plugin_id . $this->id . '_' . $key;
			$product_categories = get_terms(array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			));

			$roles = array();
			foreach (get_editable_roles() as $role_name => $role_info) {
				$roles[] = $role_name;
			}

			$shipping_methods = array();
			foreach(WC_Shipping_Zones::get_zones() as $zone_key => $zone ) {
				foreach($zone['shipping_methods'] as $method_key => $method ) {
					$str = "(".$zone['zone_name'].") ".$method->title;
					$str = str_replace('"', "", $str);
					$str = str_replace("'", "", $str);
					$str = str_replace("`", "", $str);
					$str = str_replace(",", "", $str);

					$shipping_methods[] = $str;
				}
			}

			$payment_methods = WC()->payment_gateways->payment_gateways();

			ob_start();

			?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
                        </legend>
						<p>
							<select multiple name="<?php echo esc_attr($field); ?>" size="5" style="width:400px;height:150px;"
									id="<?php echo esc_attr($field); ?>">
								<?php
								if ($this->get_option($key) != "" && sizeof(explode(",", $this->get_option($key))) != 0) {
									foreach (explode(",", $this->get_option($key)) as $c) {
										if ($data['list'] == "categories") {
											echo '<option value="' . $c . '">' . $this->product_name_from_value($c, $product_categories) . '</option>';
										} 
										else {
											echo '<option value="' . $c . '">' . $c . '</option>';
										}
									}
								}
								?>
							</select>
							<button onclick="document.getElementById('<?php echo esc_attr($field); ?>').remove(document.getElementById('<?php echo esc_attr($field); ?>').selectedIndex); var data = ''; for(var i=0;i<document.getElementById('<?php echo esc_attr($field); ?>').options.length;i++) data += (i != 0 ? ',' : '') + document.getElementById('<?php echo esc_attr($field); ?>').options[i].value; document.getElementById('<?php echo $data['list']; ?>ListData').value = data;"
									class="button-secondary" type="button"
									style="<?php if(array_key_exists("css", $data)) echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>>
								Remove
							</button>
						</p>
                        <p>
                            <select id="<?php echo $data['list']; ?>Input" name="<?php echo $data['list']; ?>Input">
								<?php
								if ($data['list'] == "categories") {
									foreach ($product_categories as $c) {
										echo '<option value="$_ID_=' . $c->term_id . '">' . $c->name . '</option>';
									}
								} else if($data['list'] == "roles") {
									foreach ($roles as $c) {
										echo '<option value="' . $c . '">' . $c . '</option>';
									}
								} else if($data['list'] == "shipping_methods") {
									foreach ($shipping_methods as $c) {
										echo '<option value="' . $c . '">' . $c . '</option>';
									}
								} else if($data['list'] == "payment_methods") {
									foreach ($payment_methods as $c) {
										echo '<option value="' . $c->id . '">' . $c->title . ' (' . $c->id . ') </option>';
									}
								}
								?>
                            </select>
                            <button onclick="var exists = false; for(var i=0;i<document.getElementById('<?php echo esc_attr($field); ?>').options.length;i++) if(!document.getElementById('<?php echo $data['list']; ?>Input').value || document.getElementById('<?php echo esc_attr($field); ?>').options[i].value == document.getElementById('<?php echo $data['list']; ?>Input').value) exists = true; if(!exists) { var x = document.createElement('option'); x.value = document.getElementById('<?php echo $data['list']; ?>Input').value; x.text = document.getElementById('<?php echo $data['list']; ?>Input').value.indexOf('$_ID_') != -1 ? document.getElementById('<?php echo $data['list']; ?>Input').options[document.getElementById('<?php echo $data['list']; ?>Input').selectedIndex].textContent : document.getElementById('<?php echo $data['list']; ?>Input').value; document.getElementById('<?php echo esc_attr($field); ?>').add(x); } var data = ''; for(var i=0;i<document.getElementById('<?php echo esc_attr($field); ?>').options.length;i++) data += (i != 0 ? ',' : '') + document.getElementById('<?php echo esc_attr($field); ?>').options[i].value; document.getElementById('<?php echo $data['list']; ?>ListData').value = data;"
                                    class="button-secondary" type="button"
                                    style="<?php if(array_key_exists("css", $data)) echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>>
                                Add
                            </button>
                            <input hidden="true" type="text" id="<?php echo $data['list']; ?>ListData"
                                   name="<?php echo $data['list']; ?>ListData"
                                   value="<?php echo $this->get_option($key); ?>">
                        </p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>
			<?php
			return ob_get_clean();
		}

		public function generate_apikeys_listbox_html($key, $data) {
			$field              = $this->plugin_id . $this->id . '_' . $key;
			$product_categories = get_terms(array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			));

			ob_start();

			?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
                        </legend>
						<p>
							<script>
								const AgeCheckerCategoryKeys = {
									selectCategory: function() {
										const keySelect = document.querySelector('#categoryApiKey');
										const keySelectText = document.querySelector('#selectedApiKey');
										const categoryKeyList = document.querySelector('#<?php echo esc_attr($field); ?>');
										const categoryKeyListData = document.querySelector('#categoryApiKeysListData');

										keySelect.removeAttribute('hidden'); 
										keySelectText.textContent = 'API Key for ' + categoryKeyList.value;

										let data = JSON.parse(categoryKeyListData.value); 
										keySelect.value = data[categoryKeyList.options[categoryKeyList.selectedIndex].value] || 'default_api_key';
									},
									changeKey: function() {
										const keySelect = document.querySelector('#categoryApiKey');
										const categoryKeyList = document.querySelector('#<?php echo esc_attr($field); ?>');
										const categoryKeyListData = document.querySelector('#categoryApiKeysListData');

										if(categoryKeyListData.value === "[]") {
											categoryKeyListData.value = "{}";
										}

										let data = JSON.parse(categoryKeyListData.value); 
										let index = categoryKeyList.options[categoryKeyList.selectedIndex].value; 
										
										if(keySelect.value == 'default_api_key') 
											delete data[index]; 
										else
											data[index] = keySelect.value; 
										
										categoryKeyListData.value = JSON.stringify(data);
									}
								};
							</script>
							<select multiple name="<?php echo esc_attr($field); ?>" size="5" style="width:400px;height:150px;"
									id="<?php echo esc_attr($field); ?>"
									onchange="AgeCheckerCategoryKeys.selectCategory()">
								<?php
								foreach ($product_categories as $c) {
									echo '<option value="' . $c->name . '">' . $c->name . '</option>';
								}
								?>
							</select>
							<p id="selectedApiKey">Select a product category in the list above.</p>
							<select id="categoryApiKey" name="categoryApiKey" hidden="true"
								onchange="AgeCheckerCategoryKeys.changeKey()">
								<?php
									if ($this->get_option("apikeys_list") != "" && sizeof(explode(",", $this->get_option("apikeys_list"))) != 0) {
										foreach (explode(",", $this->get_option("apikeys_list")) as $c) {
											echo '<option value="' . $c . '">' . ($c ===  "default_api_key" ? "Default API Key" : $c) . '</option>';
										}
									}
								?>
                            </select>
						</p>
                        <p>
                            <input hidden="true" type="text" id="categoryApiKeysListData"
                                   name="categoryApiKeysListData"
                                   value="<?php echo htmlspecialchars(stripslashes($this->get_option($key))); ?>">
                        </p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>
			<?php
			return ob_get_clean();
		}

		public function generate_ordered_listbox_html($key, $data) {
			$field              = $this->plugin_id . $this->id . '_' . $key;

			ob_start();
			?>

            <tr valign="top">
                <th scope="row" class="titledesc">
                    <label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
                </th>
                <td class="forminp">
                    <fieldset>
                        <legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
                        </legend>
						<p>
							<script>
								const AgeCheckerAPIKeys = {
									update: function() {
										const categoryKeyList = document.getElementById('woocommerce_integration-agechecker_category_apikeys');
										const categoryKeysData = document.getElementById('categoryApiKeysListData');
										const keySelect = document.getElementById('categoryApiKey');

										// Update category API key select box with updated list of keys
										keySelect.options.length = 0;
										let options = document.getElementById('<?php echo esc_attr($field); ?>').options;
										for(let i = 0; i < options.length; i++) {
											keySelect.options[i] = new Option(options[i].value == "default_api_key" ? "Default API Key" : options[i].value, options[i].value);
										}
										// Check if keys associated with categories are still valid
										// If not, remove key from object
										let data = JSON.parse(categoryKeysData.value);
										for(let ent in data) {
											let exists = false;
											for(let opt of options) {
												if(data[ent] == opt.value)
													exists = true;
											}
											if(!exists) delete data[ent];
										}
										categoryKeysData.value = JSON.stringify(data);

										if(categoryKeyList.selectedIndex != -1)
											keySelect.value = data[categoryKeyList.options[categoryKeyList.selectedIndex].value] || 'default_api_key';
									},
									remove: function() {
										const keysList = document.getElementById('<?php echo esc_attr($field); ?>');
										const keysData = document.getElementById('<?php echo $data['list']; ?>ListData');

										if(keysList.options[keysList.selectedIndex].value == 'default_api_key') 
											return; 

										keysList.remove(keysList.selectedIndex); 
										
										let data = ''; 
										for(let i=0;i<keysList.options.length;i++) 
											data += (i != 0 ? ',' : '') + keysList.options[i].value; 
										keysData.value = data;
										
										this.update();
									},
									add: function() {
										const keysList = document.getElementById('<?php echo esc_attr($field); ?>');
										const keyInput = document.getElementById('<?php echo $data['list']; ?>Input');
										const keysData = document.getElementById('<?php echo $data['list']; ?>ListData');

										let exists = false; 
										for(var i=0;i<keysList.options.length;i++) 
											if(!keyInput.value || keysList.options[i].value == keyInput.value) 
												exists = true; 

										if(!exists) { 
											let x = document.createElement('option'); 
											x.text = keyInput.value; 
											keysList.add(x); 
											keyInput.value = ''; 
										} 

										let data = ''; 
										for(var i=0;i<keysList.options.length;i++) 
											data += (i != 0 ? ',' : '') + keysList.options[i].value; 
											
										keysData.value = data;

										this.update();
									},
									moveUp: function() {
										const keysList = document.getElementById('<?php echo esc_attr($field); ?>');
										const keyInput = document.getElementById('<?php echo $data['list']; ?>Input');
										const keysData = document.getElementById('<?php echo $data['list']; ?>ListData');

										const selected = keysList.selectedIndex;

										if(selected === 0) return;

										let selectedNode = keysList.options[selected];
										let temp = keysList.options[selected - 1];
										keysList.options[selected - 1] = new Option(selectedNode.text, selectedNode.value);
										keysList.options[selected] = new Option(temp.text, temp.value);

										if(keysList.options[selected].value == "default_api_key")
											keysList.options[selected].style = "color: gray;";
										if(keysList.options[selected - 1].value == "default_api_key")
											keysList.options[selected - 1].style = "color: gray;";	

										keysList.selectedIndex = selected - 1;

										let data = ''; 
										for(var i=0;i<keysList.options.length;i++) 
											data += (i != 0 ? ',' : '') + keysList.options[i].value; 
											
										keysData.value = data;

										this.update();
									},
									moveDown: function() {
										const keysList = document.getElementById('<?php echo esc_attr($field); ?>');
										const keyInput = document.getElementById('<?php echo $data['list']; ?>Input');
										const keysData = document.getElementById('<?php echo $data['list']; ?>ListData');

										const selected = keysList.selectedIndex;

										if(selected === keysList.options.length - 1) return;

										let selectedNode = keysList.options[selected];
										let temp = keysList.options[selected + 1];
										keysList.options[selected + 1] = new Option(selectedNode.text, selectedNode.value);
										keysList.options[selected] = new Option(temp.text, temp.value);

										if(keysList.options[selected].value == "default_api_key")
											keysList.options[selected].style = "color: gray;";
										if(keysList.options[selected + 1].value == "default_api_key")
											keysList.options[selected + 1].style = "color: gray;";	

										keysList.selectedIndex = selected + 1;

										let data = ''; 
										for(var i=0;i<keysList.options.length;i++) 
											data += (i != 0 ? ',' : '') + keysList.options[i].value; 
											
										keysData.value = data;

										this.update();
									}
								};

								function removeAPIKey() {
									if(document.getElementById('<?php echo esc_attr($field); ?>').options[document.getElementById('<?php echo esc_attr($field); ?>').selectedIndex].value == 'default_api_key') return; document.getElementById('<?php echo esc_attr($field); ?>').remove(document.getElementById('<?php echo esc_attr($field); ?>').selectedIndex); var data = ''; for(var i=0;i<document.getElementById('<?php echo esc_attr($field); ?>').options.length;i++) data += (i != 0 ? ',' : '') + document.getElementById('<?php echo esc_attr($field); ?>').options[i].value; document.getElementById('<?php echo $data['list']; ?>ListData').value = data; updateACApiKeys();
								}
							</script>
							<p><b>Add and order API keys:</b></p>
							<select multiple name="<?php echo esc_attr($field); ?>" size="5" style="width:400px;height:150px;"
									id="<?php echo esc_attr($field); ?>">
								<?php
								if ($this->get_option($key) != "" && sizeof(explode(",", $this->get_option($key))) != 0) {
									foreach (explode(",", $this->get_option($key)) as $c) {
										if($c == "default_api_key") {
											echo '<option value="default_api_key" style="color: gray;">Default API Key</option>';
										} else {
											echo '<option value="' . $c . '">' . $c . '</option>';
										}
									}
								}
								?>
							</select>
							<button type="button" onclick="AgeCheckerAPIKeys.moveUp()">&uarr;</button>
							<button type="button" onclick="AgeCheckerAPIKeys.moveDown()">&darr;</button>
							<button onclick="AgeCheckerAPIKeys.remove()"
									class="button-secondary" type="button"
									style="<?php if(array_key_exists("css", $data)) echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>>
								Remove
							</button>
						</p>
                        <p>
							<input type="text" id="<?php echo $data['list']; ?>Input"
                                   name="<?php echo $data['list']; ?>Input"
								   placeholder="Enter an AgeChecker API Key">
                            <button onclick="AgeCheckerAPIKeys.add()"
                                    class="button-secondary" type="button"
                                    style="<?php if(array_key_exists("css", $data)) echo esc_attr($data['css']); ?>" <?php echo $this->get_custom_attribute_html($data); ?>>
                                Add
                            </button>
                            <input hidden="true" type="text" id="<?php echo $data['list']; ?>ListData"
                                   name="<?php echo $data['list']; ?>ListData"
                                   value="<?php echo $this->get_option($key); ?>">
                        </p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>
			<?php
			return ob_get_clean();
		}

		public function generate_titled_text_html($key, $data) {

			$field = $this->plugin_id . $this->id . '_' . $key;

			ob_start();
			?>

			<tr valign="top">
				<th scope="row" class="titledesc">
					<label for="<?php echo esc_attr($field); ?>"><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></label>
					<?php echo $this->get_tooltip_html($data); ?>
				</th>
				<td class="forminp">
					<fieldset>
						<legend class="screen-reader-text"><span><?php if(array_key_exists("title", $data)) echo wp_kses_post($data['title']); ?></span>
						</legend>
						<p><b><?php echo $data['header']; ?></b></p>
						<p>
							<input class="input-text regular-input " type="text" id="<?php echo $field; ?>" name="<?php echo $field; ?>" value="<?php echo $this->get_option($key); ?>"/>
						</p>
						<?php echo $this->get_description_html($data); ?>
                    </fieldset>
                </td>
            </tr>

			<?php
			return ob_get_clean();
		}

		function add_order_status_column_header($columns)
		{
			$new_columns = array();
			foreach ($columns as $column_name => $column_info) {
				$new_columns[$column_name] = $column_info;
				if ('order_status' === $column_name) {
					$new_columns['order_agechecker'] = __('AgeChecker Status', 'Verified');
				}
			}
			
			return $new_columns;
		}

		function get_style($order) {
			$status = $order->get_meta('AgeChecker Status');
			$status = strip_tags(trim(substr($status, 0, strpos($status, "-"))));
			$styles = array(
				"Accepted" => "agechecker_status--accepted",
				"Denied" => "agechecker_status--denied",
				"Photo ID Required" => "agechecker_status--pending",
				"Signature Required" => "agechecker_status--pending",
				"Location Blocked" => "agechecker_status--denied",
				"Buyer Banned" => "agechecker_status--denied",
				"Error" => "agechecker_status--denied",
			);
			return isset($styles[$status]) ? $styles[$status] : "agechecker_status--void";
		}

		function add_order_status_column_content($column, $post_id) {
			if ( 'order_agechecker' === $column ) {
				$order = wc_get_order($post_id);
				if(!$order) {
					return;
				}
				$status = $order->get_meta('AgeChecker Status');
				$status = strip_tags(trim(substr($status, 0, strpos($status, "-"))));
				$style = $this->get_style($order);
				if(!empty($status)) {
				?>
				<div class="agechecker_status <?php echo $style ?>">
					<?php echo $status; ?>
				</div>
				<?php
				}
			}
		}

		function add_order_status_column_style() {
			$css = '.agechecker_status 
			{ padding: 2px 6px; border-radius: 4px; text-align: center; max-width: 18ch; } 
			.agechecker_status--accepted { background-color: #c6e1c6; } 
			.agechecker_status--denied { background-color: #e9b7b7; } 
			.agechecker_status--pending { background-color: #f8dda7; } 
			.agechecker_status--void { background-color: #e5e5e5; } 
			.agechecker_details {
				margin-top: 18px !important;
				border-radius: 4px;
				text-align: center;
				color: #525252 !important;
			}
			.agechecker_logo {
				width: 28px;
				vertical-align: middle;
			';
			wp_add_inline_style('woocommerce_admin_styles', $css);
		}

		function add_verification_status_order_page($order) {
			if(!$order) {
				return;
			}
			$style = $this->get_style($order);
			?>
			<p class="agechecker_details <?php echo $style; ?> form-field form-field-wide wc-customer-user">
				<img src="https://agechecker.net/images/icons/logo.png" class="agechecker_logo" /><b>Age Verification Status</b>
			</p>
			<p class="form-field form-field-wide wc-customer-user"><?php echo $order->get_meta('AgeChecker Status') ? strip_tags($order->get_meta('AgeChecker Status')) : "Customer has not performed verification for this order."; ?></p>
			<p><?php echo $order->get_meta('AgeChecker UUID') ? ("Verification UUID: " . strip_tags($order->get_meta('AgeChecker UUID'))) : ""; ?></p>
			<?php
		}

		public function validate_order_blocks($order, $request) {
			$chosenPayment = "";	
			if(isset($order) && isset($order->payment_method)) {
				$chosenPayment = $order->payment_method;
			}

			if ($this->is_before_excluded($chosenPayment)) {
				return true;
			}

			$uuid = "";
			if(isset($request)) {
				$body = $request->get_body();
				if(isset($body)) {
					$post = json_decode($body);
					if(isset($post->extensions)) {
						$extensions = $post->extensions;
						if(isset($extensions->agecheckernet_checkout) && isset($extensions->agecheckernet_checkout->agechecker_uuid)) {
							$uuid = $extensions->agecheckernet_checkout->agechecker_uuid;
						}
					}
				}
			}
			$this->validate_order($uuid);
			$this->add_verified_notes($order, $uuid);
		}

		public function validate_order_shortcode() {
			if ($this->is_before_excluded($_POST["payment_method"])) {
				return true;
			}

			$this->validate_order($_POST["agechecker_uuid"]);
		}

		public function validate_order($uuid) {
			// Check custom form trigger
			if (!empty($this->get_option("form_trigger_name")) && !empty($this->get_option("form_trigger_value"))) {
				$mode = $this->get_option("form_trigger_mode");
				if ($mode === "run" && strtolower($_POST[$this->get_option("form_trigger_name")]) !== strtolower($this->get_option("form_trigger_value"))) {
					return true;
				}
				if ($mode === "stop" && strtolower($_POST[$this->get_option("form_trigger_name")]) === strtolower($this->get_option("form_trigger_value"))) {
					return true;
				}
			}

			// In order to work around Google Pay or Apple Pay from the WooCommerce Square plugin, we check if
			// 'wc-square-digital-wallet-type' is included in the order data, and if so then set a cookie
			// to trigger the AgeChecker window on the user's end so it shows after closing the Google Pay or
			// Apple Pay window, but catching it before it submits the order and finalizing the payment.
			// If agechecker_uuid is included in the order data, then the user already went through, so continue.
			if(!isset($uuid) && isset($_POST['wc-square-digital-wallet-type'])) {
				$order_data = array(
					"ac_perform_id" => substr(str_shuffle('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'),1,24),
					"action" => $_POST["action"],
					"billing_address_1" => $_POST["billing_address_1"],
					"billing_address_2" => $_POST["billing_address_2"],
					"billing_city" => $_POST["billing_city"],
					"billing_company" => $_POST["billing_company"],
					"billing_country" => $_POST["billing_country"],
					"billing_email" => $_POST["billing_email"],
					"billing_first_name" => $_POST["billing_first_name"],
					"billing_last_name" => $_POST["billing_last_name"],
					"billing_phone" => $_POST["billing_phone"],
					"billing_postcode" => $_POST["billing_postcode"],
					"billing_state" => $_POST["billing_state"],
					"order_comments" => $_POST["order_comments"],
					"payment_method" => $_POST["payment_method"],
					"ship_to_different_address" => intval($_POST["ship_to_different_address"]),
					"shipping_address_1" => $_POST["shipping_address_1"],
					"shipping_address_2" => $_POST["shipping_address_2"],
					"shipping_city" => $_POST["shipping_city"],
					"shipping_company" => $_POST["shipping_company"],
					"shipping_country" => $_POST["shipping_country"],
					"shipping_first_name" => $_POST["shipping_first_name"],
					"shipping_last_name" => $_POST["shipping_last_name"],
					"shipping_method" => $_POST["shipping_method"],
					"shipping_postcode" => $_POST["shipping_postcode"],
					"shipping_state" => $_POST["shipping_state"],
					"terms" => $_POST["terms"],
					"wc-square-credit-card-exp-month" => intval($_POST["wc-square-credit-card-exp-month"]),
					"wc-square-credit-card-exp-year" => intval($_POST["wc-square-credit-card-exp-year"]),
					"wc-square-credit-card-last-four" => $_POST["wc-square-credit-card-last-four"],
					"wc-square-credit-card-payment-nonce" => $_POST["wc-square-credit-card-payment-nonce"],
					"wc-square-credit-card-exp-month" => $_POST["wc-square-credit-card-exp-month"],
					"wc-square-credit-card-payment-postcode" => $_POST["wc-square-credit-card-payment-postcode"],
					"wc-square-digital-wallet-type" => $_POST["wc-square-digital-wallet-type"],
					"_wpnonce" => $_POST["_wpnonce"]
				);

				if(isset($_POST['wc-square-credit-card-buyer-verification-token'])) {
					$order_data['wc-square-credit-card-buyer-verification-token'] = $_POST['wc-square-credit-card-buyer-verification-token'];
				}

				setcookie("perform_agechecker_dw", json_encode($order_data));
				wc_add_notice('Age verification required.', 'error');
			} else {
				if (!isset($uuid) || strlen($uuid) != 32) {
					throw new Exception('You must complete the age verification process! Need help? Contact help@agechecker.net or try using another device or browser. (Error WP201)');
				} else {
					$req  = json_encode(array(
						'key'  => $this->key,
						'uuid' => $uuid
					), JSON_FORCE_OBJECT);
					$post = wp_remote_post("https://api.agechecker.net/v1/validate", array(
						'method'      => 'POST',
						'timeout'     => 20,
						'httpversion' => '1.1',
						'headers'     => array(
							'Content-Type' => 'application/json'
						),
						'body'        => $req
					));
					if (is_wp_error($post)) {
						throw new Exception('The age verification service could not verify you at this time. Need help? Contact help@agechecker.net. (Error WP203)');
					} else {
						$res = json_decode($post['body']);
						if ($res->status == "accepted") {
							return true;
						} else {
							throw new Exception('You have not been approved by the age verification process! Need help? Contact help@agechecker.net. (Error WP205)');
						}
					}
				}
			}
			return false;
		}

		public function is_before_excluded($chosenPayment) {
			if ($this->workflow_type != "before_payment" || $this->is_excluded(false) || $this->is_total_excluded(false) || $this->is_shipping_excluded(null) || $this->is_payment_excluded($chosenPayment)) {
				return true;
			} else {
				return false;
			}
		}

		public function new_order( $order_id ) {
			$order = new WC_Order( $order_id );

			if(!isset($_POST['agechecker_uuid'])) return;

			$uuid = $_POST['agechecker_uuid'];

			$this->add_verified_notes($order, $uuid);
		}

		public function add_verified_notes( $order, $uuid ) {
			if(!isset($uuid)) return;
			
			// Not order tagging logic, but we set this here for before_payment logic
			// so users that are created after an order is created are correctly set
			// with the role.
			if($this->workflow_type == "before_payment") {
				$this->set_verified_role($order);
			}
		   
			if(isset($this->tag_order)) {
				if($this->tag_order == "tag_notes" || $this->tag_order == "tag_both") {
					$note = __("Age Verified order. \n\nAgeChecker UUID: ". $uuid);
					$order->add_order_note( $note );
				}
				if($this->tag_order == "tag_fields" || $this->tag_order == "tag_both") {
					$order->update_meta_data( 'Age Verified', 'AgeChecker UUID: '. $uuid );
				}
			}
		   
			$order->save();
		}

		public function validate_workflow_type_field($key) {
			$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];
			$accountSecretValue = $_POST[ $this->plugin_id . $this->id . '_secret' ];
			if ($value === "after_payment" && empty($accountSecretValue)) {
				?>
                <div class="error notice">
                    <p><strong>Account secret invalid!</strong> Account secret must be filled out when using the after-payment workflow. Use the account secret from the <a
                                href="https://agechecker.net/account/websites" target="blank_">website tab</a> in your
                        AgeChecker.Net account area.</p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_excluded_categories_field($key) {
			$value = $_POST["categoriesListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid categories.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_excluded_groups_field($key) {
			$value = $_POST["rolesListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid roles.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_excluded_shipping_field($key) {
			$value = $_POST["shipping_methodsListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid shipping fields.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_excluded_payment_field($key) {
			$value = $_POST["payment_methodsListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid payment fields.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_min_total_field($key) {
			$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];
			if (!isset($value) || (isset($value) && !is_numeric($value))) {
				?>
                <div class="error notice">
                    <p><strong>Minimum Total invalid!</strong> Total must be a valid numeric value.</p>
                </div>
				<?php
				return $this->get_option($key);
			}
			
			return $value;
		}

		public function validate_titled_text_field($key) {
			echo $_POST[ $this->plugin_id . $this->id . '_' . $key ];
			return $_POST[ $this->plugin_id . $this->id . '_' . $key ];
		}

		public function validate_category_apikeys_field($key) {
			$value = $_POST["categoryApiKeysListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid category api keys.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_apikeys_list_field($key) {
			$value = $_POST["apikeysListData"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid api keys ordered list.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_key_field($key) {
			$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];
			if (isset($value) && strlen($value) != 32) {
				?>
                <div class="error notice">
                    <p><strong>API key invalid!</strong> Use the 32-character domain key from the <a
                                href="https://agechecker.net/account/websites" target="blank_">website tab</a> in your
                        AgeChecker.Net account area.'</p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_customer_role_field($key) {
			$value = $_POST["customer_roleInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid customer role.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_page_field($key) {
			$value = $_POST["pagesInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid page.</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_status_pending_field($key) {
			$value = $_POST["order_status_pendingInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid order status for "Set Order Status When Verification is Pending".</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_status_accepted_field($key) {
			$value = $_POST["order_status_acceptedInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid order status for "Set Order Status When Verification is Accepted".</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_status_denied_field($key) {
			$value = $_POST["order_status_deniedInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid order status for "Set Order Status When Verification is Denied".</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_status_disabled_field($key) {
			$value = $_POST["order_status_disabledInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid order status for "Set Order Status when Verification is Disabled at Customer's Location".</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		public function validate_set_status_blocked_field($key) {
			$value = $_POST["order_status_blockedInput"];
			if (!isset($value)) {
				?>
                <div class="error notice">
                    <p><strong>Invalid order status for "Set Order Status when Verification is Blocked at Customer's Location".</strong></p>
                </div>
				<?php
				return $this->get_option($key);
			}

			return $value;
		}

		function get_api_key($order_items) {
			// If no category specific keys are set, return default api key
			if($this->category_apikeys === "{}" || $this->category_apikeys === "[]") {
				return $this->key;
			}

			$category_keys = json_decode(stripslashes($this->category_apikeys));
			if (is_array($category_keys)) {
				return $this->key;
			}
			$priority_list = explode(",", $this->apikeys_list);

			$default_index = array_search('default_api_key', $priority_list);

			$api_key = null;
			$priority_index = null;

			$cart_items = $order_items;
			if($order_items === false && !is_null(WC()->cart)) {
				$cart_items = WC()->cart->get_cart();
			}

			foreach ($cart_items as $cart_item_key => $cart_item) {
				$prod_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : $cart_item['data']->id;

				$cats = wp_get_post_terms($prod_id, 'product_cat');
				foreach ($cats as $cat_key => $cat) {
					$cat_name = $cat->name;
					$cat_apikey = property_exists($category_keys, $cat_name) ? $category_keys->$cat_name : null;

					// If category's api key is null, then it's using
					// the default api key. Check priority before setting.
					if($cat_apikey === null && ($priority_index === null || $default_index < $priority_index)) {
						$priority_index = $default_index;
						$api_key = $this->key;
						continue;
					}
					
					// If category's api key is not null, then get priority
					// index for that api key. Compare against current priority_index
					// and update current priority_index if less than.
					$index = 0;
					foreach ($priority_list as $key) {
						if($priority_index !== null && $priority_index < $index) break;

						if($key == $cat_apikey) {
							$priority_index = $index;
							$api_key = $key;
							break;
						}

						$index++;
					}
				}
			}

			if(empty($api_key)) {
				$api_key = $this->key;
			}

			return $api_key;
		}

		function print_script_dw() {
			if(!file_exists(stream_resolve_include_path(ABSPATH . 'wp-content/plugins/woocommerce-square/woocommerce-square.php'))) return;		
			include_once(ABSPATH . 'wp-content/plugins/woocommerce-square/woocommerce-square.php');
			if(!in_array('woocommerce-square/woocommerce-square.php', apply_filters('active_plugins', get_option('active_plugins'))) || $this->workflow_type != "before_payment") return;

			?>
			<script data-cfasync="false">
			(function(w,d) {
			<?php echo $this->before_script; ?>

			function getCookie(cname) {
				let name = cname + "=";
				let decodedCookie = decodeURIComponent(document.cookie.replaceAll("+", " "));
				let ca = decodedCookie.split(';');
				for(let i = 0; i <ca.length; i++) {
					let c = ca[i];
					while (c.charAt(0) == ' ') {
					c = c.substring(1);
					}
					if (c.indexOf(name) == 0) {
					return c.substring(name.length, c.length);
					}
				}
				return "";
			}

			let findDW = setInterval(function() {
				let perform = getCookie("perform_agechecker_dw");
				if(perform) {
					const orderData = JSON.parse(perform);
					if(localStorage.getItem('perform_agechecker_id') && orderData.ac_perform_id == localStorage.getItem('perform_agechecker_id')) return;

					localStorage.setItem('perform_agechecker_id', orderData.ac_perform_id);

					clearInterval(findDW);

					let uuid;
					
					var config={key:'<?php echo $this->get_api_key(false); ?>',name:'<?php echo esc_html($this->store_name); ?>',
						data: {
							first_name: orderData.billing_first_name,
							last_name: orderData.billing_last_name,
							address: orderData.billing_address_1,
							zip: orderData.billing_postcode,
							city: orderData.billing_city,
							country: orderData.billing_country,
							state: orderData.billing_state
						},
						mode: "manual",
						onready: function() { AgeCheckerAPI.show(); },
						onstatuschanged: function(verification) {
							if(verification.status == "accepted") {
								uuid = verification.uuid;
							}
						},
						defer_submit: true,
						onclosed: function(done) {
							orderData.agechecker_uuid = uuid;

							var xhttp = {};
							if (window.XDomainRequest) xhttp = new XDomainRequest();
							else if (window.XMLHttpRequest) xhttp = new XMLHttpRequest();
							else xhttp = new ActiveXObject("Microsoft.XMLHTTP");
							xhttp.open('POST', '<?php echo esc_url( site_url() ) ?>/?wc-ajax=square_digital_wallet_process_checkout');
							xhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
							xhttp.onload = function() {
								if (xhttp.status === 200) {
									let response = xhttp.response;
									try {
										response = JSON.parse(response);
										
										if ( response.result === 'success' ) {
											window.location = response.redirect;
											done();
										}
										else {
											console.log(response.messages);
											done();
										}	
									} catch(e) { console.log(e); }
								}
							};
							let entries = Object.entries(orderData);
							let formData = "";
							for(let i = 0; i < entries.length; i++) {
								let data = entries[i];
								formData += `${data[0]}=${data[1]}`;
								if(i < entries.length - 1) formData += "&";
							}
							formData = encodeURI(formData);
							xhttp.send(formData);
						},
						<?php echo $this->client_config; ?>
					};
					
					<?php
					if(!empty($this->load_script)) {
						echo $this->load_script;
					} else {
					?>
		w.AgeCheckerConfig=config;if(config.path&&(w.location.pathname+w.location.search).indexOf(config.path)) return;
		var h=d.getElementsByTagName("head")[0];var a=d.createElement("script");a.src="https://cdn.agechecker.net/static/popup/v1/popup.js";a.crossOrigin="anonymous";
		a.onerror=function(a){w.location.href="https://agechecker.net/loaderror";};h.insertBefore(a,h.firstChild);
					<?php } ?>		
				}
			}, 500);
			})(window, document);
			</script>
			<?php
		}

		function print_script_beforepayment() {
			if($this->workflow_type != "before_payment") return;

			if($this->enable_noscript == "enable_noscript") {
			?>
			<noscript><meta http-equiv="refresh" content="0; url=https://agechecker.net/noscript"></noscript>
			<?php } ?><script data-cfasync="false">
			(function(w,d) {
			if(location.href.indexOf("checkout/order-received") != -1 || w.AgeChecker_WC_Checkout_Loaded) return;
			w.AgeChecker_WC_Checkout_Loaded = true;
			<?php echo $this->before_script; ?>

			var config={key:'<?php echo $this->get_api_key(false); ?>',element:'<?php echo $this->element; ?>',name:'<?php echo esc_html($this->store_name); ?>',<?php echo $this->client_config; ?>};
			
			<?php
				if($this->customer_info == "shipping") {
					?> 
						var _ontrigger = config.ontrigger;
					    config.ontrigger = function() {
							var shipToDifferent = document.querySelector("#ship-to-different-address-checkbox");
							if (shipToDifferent && !shipToDifferent.checked) {
								this.fields = null;
							}

							if(_ontrigger)
								return _ontrigger();
						};
						config.fields = {
							first_name: "#shipping_first_name, #shipping-first_name",
							last_name: "#shipping_last_name, #shipping-last_name",
							address: "#shipping_address_1, #shipping-address_1",
							zip: "#shipping_postcode, #shipping-postcode",
							city: "#shipping_city, #shipping-city",
							country: "#shipping_country, #components-form-token-input-0",
							state: "#shipping_state, #components-form-token-input-1"
						}; 
					<?php
				}
			?>

			<?php
			$shipping_excluded = $this->excluded_shipping != '';
			$payment_excluded = $this->excluded_payment != '';
			$min_total  = $this->min_total != '' && $this->min_total != '0';
			$custom_form_trigger = !empty($this->get_option("form_trigger_name")) && !empty($this->get_option("form_trigger_value"));
			if($shipping_excluded || $payment_excluded || $min_total || $custom_form_trigger) {
			?>

			if(config.platform_features && config.platform_features.woocommerce) {
				<?php
				if($shipping_excluded) {
					echo "config.platform_features.woocommerce.shipping_methods = \"". $this->excluded_shipping . "\".split(\",\");";
				}
				?>

				<?php
				if($payment_excluded) {
					echo "config.platform_features.woocommerce.payment_methods = \"". $this->excluded_payment . "\".split(\",\");";
				}
				?>

				<?php
				if($min_total) {
					echo "config.platform_features.woocommerce.min_total = ". $this->min_total . ";";
				}
				?>

				<?php
				if ($custom_form_trigger) {
					echo "config.platform_features.woocommerce.form_trigger = {
						element: '". htmlspecialchars($this->get_option("form_trigger_name"), ENT_QUOTES) ."',
						value: '".  htmlspecialchars($this->get_option("form_trigger_value"), ENT_QUOTES) ."',
						mode: '". $this->get_option("form_trigger_mode") ."' };";
				}
				?>

			} else {
				config.platform_features = {
					woocommerce: {
						<?php
						if($shipping_excluded) {
							echo "shipping_methods: \"". $this->excluded_shipping ."\".split(\",\"),";
						}
						?>

						<?php
						if($payment_excluded) {
							echo "payment_methods: \"". $this->excluded_payment ."\".split(\",\"),";
						}
						?>

						<?php
						if($min_total) {
							echo "min_total: ". $this->min_total . ",";
						}
						?>

						<?php
						if ($custom_form_trigger) {
							echo "form_trigger: {
								element: '". htmlspecialchars($this->get_option("form_trigger_name"), ENT_QUOTES) ."',
								value: '".  htmlspecialchars($this->get_option("form_trigger_value"), ENT_QUOTES) ."',
								mode: '". $this->get_option("form_trigger_mode") ."' },";
						}
						?>

					}
				};
			}
			<?php
			}
				
			if(!empty($this->load_script)) {
				echo $this->load_script;
			} else {
			?>
w.AgeCheckerConfig=config;if(config.path&&(w.location.pathname+w.location.search).indexOf(config.path)) return;
var h=d.getElementsByTagName("head")[0];var a=d.createElement("script");a.src="https://cdn.agechecker.net/static/popup/v1/popup.js";a.crossOrigin="anonymous";
a.onerror=function(a){w.location.href="https://agechecker.net/loaderror";};h.insertBefore(a,h.firstChild);
			<?php } ?>		
			})(window, document);
			</script>
			<?php
		}

		function print_script_afterpayment($order_id) {
			if($this->workflow_type != "after_payment") return;

			$order = new WC_Order($order_id);
			$orderStatus = $order->get_status();

			if($orderStatus == "failed" && $this->trigger_afterpayment_on_failed == "disable") return;
			if($orderStatus == "completed" && $this->trigger_afterpayment_on_completed == "disable") return;

			$status = $order->get_meta('AgeChecker Status');
			if(!empty($status)) {
				$status = strip_tags(trim(substr($status, 0, strpos($status, "-"))));
				if($status != "Denied" && $status != "Photo ID Required" && $status != "Signature Required") {
					return;
				}
			}

			if($this->enable_noscript == "enable_noscript") {
			?>
			<noscript><meta http-equiv="refresh" content="0; url=https://agechecker.net/noscript"></noscript>
			<?php } ?><script data-cfasync="false">
			(function(w,d) {
			if(w.AgeChecker_WC_Checkout_Loaded) return;
			w.AgeChecker_WC_Checkout_Loaded = true;

			<?php echo $this->before_script; ?>

			var config={key:'<?php echo $this->get_api_key($order->get_items()); ?>',mode: "manual", onready: function() { AgeCheckerAPI.show(); },
			data: {
				first_name: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_first_name(); 
				} else {
					echo $order->get_billing_first_name(); 
				} ?>",
				last_name: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_last_name(); 
				} else {
					echo $order->get_billing_last_name(); 
				} ?>",
				address: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_address_1(); 
				} else {
					echo $order->get_billing_address_1(); 
				} ?>",
				zip: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_postcode(); 
				} else {
					echo $order->get_billing_postcode(); 
				} ?>",
				city: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_city(); 
				} else {
					echo $order->get_billing_city(); 
				} ?>",
				country: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_country(); 
				} else {
					echo $order->get_billing_country(); 
				} ?>",
				state: "<?php if($this->customer_info == "shipping") {
					echo $order->get_shipping_state(); 
				} else {
					echo $order->get_billing_state(); 
				} ?>",
			},
			platform_features: {
				woocommerce: {
					afterPayment: {
						url: "<?php echo esc_url( site_url() ) ?>/wp-json/agechecker-net/v1/verify",
						orderKey: "<?php echo $order->get_order_key() ?>"
					}
				}
			},
			name:'<?php echo esc_html($this->store_name); ?>',<?php echo $this->client_config; ?>}; 
			
			<?php
			if(!empty($this->load_script)) {
				echo $this->load_script;
			} else {
			?>
w.AgeCheckerConfig=config;if(config.path&&(w.location.pathname+w.location.search).indexOf(config.path)) return;
var h=d.getElementsByTagName("head")[0];var a=d.createElement("script");a.src="https://cdn.agechecker.net/static/popup/v1/popup.js";a.crossOrigin="anonymous";
a.onerror=function(a){w.location.href="https://agechecker.net/loaderror";};h.insertBefore(a,h.firstChild);
			<?php } ?>		
			})(window, document);
			</script>
			<?php
		}

		public function add_script_head() {
			if($this->workflow_type == "after_payment" && is_checkout() && is_wc_endpoint_url('order-received')) {
				global $wp;
				$order_id = absint($wp->query_vars['order-received']);
				if ($order_id) 
				{
					$this->add_script_afterpayment($order_id);
				}
			}

			if ($this->script_location == 'footer' || $this->is_excluded(false) || (!is_page($this->page) && $this->enable_all == 'dont_enable_all')) {
				return;
			}

			$this->print_script_beforepayment();
		}

		public function add_script_footer() {
			$this->print_script_dw();
			if ($this->script_location == 'head' || $this->is_excluded(false) || (!is_page($this->page) && $this->enable_all == 'dont_enable_all')) {
				return;
			}

			$this->print_script_beforepayment();
		}

		public function add_script_afterpayment($order_id) {
			if (empty(wc_get_order($order_id))) return;

			$order = new WC_Order($order_id);

			$shipping_methods = array();
			foreach ( $order->get_shipping_methods() as $shipping_method ) {
				$shipping_methods[] = $shipping_method->get_method_id() . ":" . $shipping_method->get_instance_id();
			}

			if ($this->is_excluded($order->get_items()) || $this->is_total_excluded($order->get_total()) || $this->is_shipping_excluded($shipping_methods) || $this->is_payment_excluded($order->get_payment_method())) {
				return;
			}

			// Set pending order status for after payment workflow is available
			// Check if pending status has already been set.
			// We also check to make sure there hasn't already been an age verification performed. (Implying this is an order that was placed before the pending status setting was set)
			$pendingMeta = $order->get_meta('agechecker_pending_status');
			$status = $order->get_meta('AgeChecker Status');
			if($this->workflow_type == "after_payment" && $this->set_status_pending != "do_not_set_status" && empty($pendingMeta) && empty($status)) {
				$order->update_status($this->set_status_pending);
				// Set this so the plugin doesn't set the order status multiple times (e.g. Customer reloads the Thank You page or comes back to it later)
				$order->update_meta_data('agechecker_pending_status', 'set');
				$order->save();
			}

			$this->print_script_afterpayment($order_id);
		}

		public function is_shipping_excluded($chosen_methods) {
			$excluded_shipping_methods = explode(",", $this->excluded_shipping);

			if (!(sizeof($excluded_shipping_methods) == 1 && $excluded_shipping_methods[0] == "")) {
				if($chosen_methods === null) {
					$chosen_methods = WC()->session->get('chosen_shipping_methods');
				}

				$all_shipping_methods = array();
				foreach(WC_Shipping_Zones::get_zones() as $zone_key => $zone ) {
					foreach($zone['shipping_methods'] as $method_key => $method ) {
						$str = "(".$zone['zone_name'].") ".$method->title;
						$str = str_replace('"', "", $str);
						$str = str_replace("'", "", $str);
						$str = str_replace("`", "", $str);
						$str = str_replace(",", "", $str);

						$all_shipping_methods[$method->instance_id] = $str;
					}
				}

				if($chosen_methods) {
					foreach ($chosen_methods as $shipping_method_key => $shipping_method) {
						$title = $all_shipping_methods[substr(strstr($shipping_method, ":"), 1)];

						foreach ($excluded_shipping_methods as $c) {
							if (strpos($title, $c) !== false) {
								return true;
							}
						}
					}
				}
			}

			return false;
		}

		public function is_payment_excluded($chosen_method) {
			$excluded_payment_methods = explode(",", $this->excluded_payment);

			if (!(sizeof($excluded_payment_methods) == 1 && $excluded_payment_methods[0] == "")) {
				if($chosen_method === null) {
					$chosen_method = WC()->session->get('chosen_payment_method');
				}
				if($chosen_method) {
					foreach ($excluded_payment_methods as $c) {
						if ($chosen_method == $c) {
							return true;
						}
					}
				}
			}

			return false;
		}

		public function is_total_excluded($order_total) {
			$min_total = floatval($this->min_total);
			if($min_total == 0) {
				return false;
			}

			$total = $order_total;
			if($order_total === false && !is_null(WC()->cart)) {
				$total = WC()->cart->total;
			}

			return $total < $min_total;
		}

		public function is_excluded($order_items) {
			$current_user = wp_get_current_user();
			if (!($current_user instanceof WP_User)) {
				return true;
			}

			$roles              = $current_user->roles;
			$user_role          = implode($roles);
			$excluded           = true;

			$categories_excluded = strpos($this->categories_mode, "exclude");
			$categories = explode(",", $this->excluded_categories);

			$product_categories = get_terms(array(
				'taxonomy'   => 'product_cat',
				'hide_empty' => false,
			));

			if (!(sizeof($categories) == 1 && $categories[0] == "")) {
				$cart_items = $order_items;
				if($order_items === false && !is_null(WC()->cart)) {
					$cart_items = WC()->cart->get_cart();
				}

				if($categories_excluded !== false) {
					foreach ($cart_items as $cart_item_key => $cart_item) {
						$prod_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : $cart_item['data']->id;

						$noExclude = true;
						foreach ($categories as $c) {
							$cat = $this->product_name_from_value($c, $product_categories);
							if (has_term($cat, 'product_cat', $prod_id)) {
								$noExclude = false;
								break;
							}
						}
						if ($noExclude) {
							$excluded = false;
						}
					}
				}
				else {
					foreach ($cart_items as $cart_item_key => $cart_item) {
						$prod_id = isset($cart_item['product_id']) ? $cart_item['product_id'] : $cart_item['data']->id;

						$noExclude = false;
						foreach ($categories as $c) {
							$cat = $this->product_name_from_value($c, $product_categories);
							if (has_term($cat, 'product_cat', $prod_id)) {
								$noExclude = true;
								break;
							}
						}
						if ($noExclude) {
							$excluded = false;
						}
					}
				}
			} else {
				$excluded = false;
			}

			$groups_excluded = strpos($this->groups_mode, "exclude");
			$groups = explode(",", $this->excluded_groups);
			if (!(sizeof($groups) == 1 && $groups[0] == "")) {
				if($groups_excluded !== false) {
					foreach ($groups as $c) {
						if (strpos($user_role, $c) !== false || current_user_can($c)) {
							$excluded = true;
							break;
						}
					}
				} else if(!$excluded) {
					$excluded = true;
					foreach ($groups as $c) {
						if (strpos($user_role, $c) !== false || current_user_can($c)) {
							$excluded = false;
							break;
						}
					}
				}
			}

			return $excluded;
		}

		public function set_verified_role($order) {
			if($this->set_customer_role == 'do_not_set_role') return;

			$user = $order->get_user();

			// Add user role if defined in settings, and user's logged in
			// Else, try to find a user account if setting is enabled
			if($user) {
				$user->add_role($this->set_customer_role);
			} else if($this->role_find_account == 'true') {
				$user = get_user_by('email', $order->get_billing_email());

				if($user) {
					if($order->get_billing_first_name() == $user->billing_first_name &&
					$order->get_billing_last_name() == $user->billing_last_name &&
					$order->get_billing_address_1() == $user->billing_address_1 &&
					$order->get_billing_city() == $user->billing_city &&
					$order->get_billing_state() == $user->billing_state &&
					$order->get_billing_postcode() == $user->billing_postcode &&
					$order->get_billing_country() == $user->billing_country) {
						$user->add_role($this->set_customer_role);
					}
				}
			}
		}

		public function get_status_wording($body) {
			if(isset($body['blocked'])) {
				return "Buyer Banned - This customer is currently banned from performing verifications.";
			} else if(isset($body['error'])) {
				return "Error - Error performing the verification. If you believe this is a mistake, please contact AgeChecker support.";
			}
			$status = $body['status'];
			$denyReasons = array(
				'invalid_id' => "The image uploaded was not a valid ID", 
				'info_missing' => "The name, DOB, or expiration was not visible in the image", 
				'info_mismatch' => "The name on the ID did not match the information submitted", 
				'blocked' => "The user was banned", 
				'fake_id' => "The ID was a fake, sample, or blocked ID", 
				'blank_id' => "The image uploaded was blank or corrupted", 
				'expired' => "The ID was expired", 
				'ip_blocked' => "Buyer\'s IP address was blocked", 
				'location_blocked' => "Buyer\'s location was blocked", 
				'selfie_mismatch' => "The selfie does not match the face on the ID", 
				'sms_failed' => "Incorrect phone validation code", 
				'selfie_id_missing' => "ID is missing or unclear in the selfie image", 
				'selfie_not_provided' => "A selfie image with matching ID was not provided", 
				'location_disabled' => "The customer ordered from a location that doesn\'t require age verification according to your age settings.",
				'verification_disabled' => "The customer ordered from a location that doesn\'t require age verification according to your age settings.",
				'both_sides_needed' => "Both sides of the ID are needed to see the required information",
				'id_validation_failed' => "Identity Validation failed",
				'id_number_missing' => "License # not found",
				'dropped_verification' => "The image could not be processed"
			);

			switch($status) {
				case 'accepted':
					return 'Accepted - Customer successfully age verified.';
				case 'denied':
					$denyReason = $denyReasons[$body['reason']];
					if(isset($denyReason)) {
						return 'Denied - Customer failed the age verification for the following reason: '. $denyReasons[$body['reason']];
					}
					return 'Denied - Customer failed the age verification.';
				case 'photo_id':
					return 'Photo ID Required - Customer was prompted with photo ID, and has not submitted one yet. The customer is either still in the process of submitting, or closed the window without finishing the verification.';
				case 'signature':
					return 'Signature Required - Customer was prompted to submit a signature, and has not submitted one yet. The customer is either still in the process of submitting, or closed the window without finishing the verification.';
				case 'not_created':
					$reason = $body['reason'];

					if($reason == 'location_blocked') {
						return 'Location Blocked - The customer ordered from a location that is blocked according to your age settings.';
					} else if($reason == 'verification_disabled') {
						return 'Location Disabled - The customer ordered from a location that doesn\'t require age verification according to your age settings.';
					}
					break;
				default:
					return $status;
			}

			return $status;
		}

		public function update_order_verification($order_id, $body) {
			$order = new WC_Order($order_id);

			$uuid = $body['uuid'];

			$status = $body['status'];

			$order->update_meta_data('AgeChecker Status', $this->get_status_wording($body) );

			if(isset($uuid)) {
				$order->update_meta_data('AgeChecker UUID', $uuid );
			}

			$order->save();

			if(isset($body['blocked']) || isset($body['error'])) {
				if($this->set_status_denied != "do_not_set_status") {
					$order->update_status($this->set_status_denied);
				}
			} 

			if(isset($status)) {
				if($status == "accepted") {
					if($this->set_status_accepted != "do_not_set_status") {
						$order->update_status($this->set_status_accepted);
					}

					$this->set_verified_role($order);
				} else if($status == "denied") {
					if($this->set_status_denied != "do_not_set_status") {
						$order->update_status($this->set_status_denied);
					}
				} else if($status == "photo_id" || $status == "signature") {
					if($this->set_status_pending != "do_not_set_status") {
						$order->update_status($this->set_status_pending);
					}
				} else if($status == "not_created") {
					$reason = $body['reason'];
					if($reason == 'location_blocked') {
						if($this->set_status_blocked != "do_not_set_status") {
							$order->update_status($this->set_status_blocked);
						}
					} else if($reason == 'verification_disabled') {
						if($this->set_status_disabled != "do_not_set_status") {
							$order->update_status($this->set_status_disabled);
						}
					}
				}
			}
		}

		public function initCors($value) {
			header('Access-Control-Allow-Origin: ' . esc_url_raw( site_url() ));
			return $value;
		}

		public function establish_api() {
			remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );

			add_filter( 'rest_pre_serve_request', array($this, 'initCors'));
			
			register_rest_route( 'agechecker-net/v1', '/verify', array(
				'methods' => 'POST',
				'callback' => array(
				  $this,
				  'handle_verification_v1'
				 ),
				 'permission_callback' => '__return_true'
			) );

			register_rest_route( 'agechecker-net/v1', '/callback', array(
				'methods' => 'POST',
				'callback' => array(
				  $this,
				  'handle_callback_v1'
				 ),
				 'permission_callback' => '__return_true'
			) );
		}

		public function handle_verification_v1($req) {
			if(!isset($req['key']) || !isset($req['data']) || !isset($req['orderKey'])
			  || empty(wc_get_order_id_by_order_key($req['orderKey']))) {
				return new WP_REST_Response(array('error' => 'Data is not valid'), 400);
			}

			$orderId = wc_get_order_id_by_order_key($req['orderKey']);

			$url = "https://api.agechecker.net/v1/create";

			$data = [
				'key'      => $req['key'],
				'secret'   => $this->secret,
				'data' 	   => $req['data'],
				'require'  => $req['require'],
				'options'  => [
					"callback_url" => esc_url_raw( site_url() ) ."/wp-json/agechecker-net/v1/callback",
					"metadata" => [
						"orderId" => strval($orderId),
						"callbackpost_83qfbg84" => "true"
					]
				]
			];

			if($_SERVER['REMOTE_ADDR'] != "::1" && $_SERVER['REMOTE_ADDR'] != "127.0.0.1" && $_SERVER['REMOTE_ADDR'] != "localhost") {
				$data['options']['customer_ip'] = $_SERVER['REMOTE_ADDR'];
			}

			$fields = json_encode($data);

			$ch = curl_init();

			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_POST, true);
			curl_setopt($ch,CURLOPT_POSTFIELDS, $fields);

			curl_setopt($ch,CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

			$result = curl_exec($ch);

			if ($result === false) {
				throw new Exception(curl_error($ch), curl_errno($ch));
			}

			$result = json_decode($result, true);

			$this->update_order_verification($orderId, $result);

			return new WP_REST_Response($result, 200);
		}

		public function handle_callback_v1($req) {
			$sig = $req->get_header('X-AgeChecker-Signature');
			if(!$sig) {
				return new WP_REST_Response(array('error' => 'Unauthorized request'), 403);
			}
			
			$encoded = base64_encode(hash_hmac('sha1', $req->get_body(), $this->secret, true));

			if(!hash_equals($encoded, $sig)) {
				return new WP_REST_Response(array('error' => 'Unauthorized request'), 403);
			}
			
			$body = json_decode($req->get_body(), true);

			$ch = curl_init();

			$url = "https://api.agechecker.net/v1/status/". $body['uuid'];

			curl_setopt($ch,CURLOPT_URL, $url);
			curl_setopt($ch,CURLOPT_HTTPHEADER, array('X-AgeChecker-Secret: '. $this->secret));

			curl_setopt($ch,CURLOPT_RETURNTRANSFER, true); 

			$verification_info = curl_exec($ch);

			if ($verification_info === false) {
				throw new Exception(curl_error($ch), curl_errno($ch));
			}

			$verification_info = json_decode($verification_info, true);

			$this->update_order_verification($verification_info['metadata']['orderid'], $body);

			return new WP_REST_Response(array("success" => true), 200);
		}
	}
endif;