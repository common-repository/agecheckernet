<?php
/*
Plugin Name: Age Verification for eCommerce
Plugin URI:  https://agechecker.net
Description: AgeChecker.Net seamlessly and securely verifies the age of your customers directly on your website during the checkout process. Keep your site up to date on the latest age regulations for your industry while ensuring that purchasing is frustration-free for your site users.
Version:     1.20.0
Author:      AgeChecker.Net
Author URI:  https://agechecker.net
*/

if (!class_exists('WC_Integration_AgeChecker')):
	class WC_Integration_AgeChecker {
		public function __construct() {
			add_action('plugins_loaded', array(
				$this,
				'init'
			));

			add_filter('plugin_action_links', array($this, 'plugin_action_links'), 10, 2);
		}

		public function init() {
			if (class_exists('WC_Integration')) {
				include_once 'class-wc-integration-agechecker-integration.php';

				add_filter('woocommerce_integrations', array(
					$this,
					'add_integration'
				));
			}
		}

		public function plugin_action_links($links, $file) {
			if (plugin_basename(__FILE__) !== $file) {
				return $links;
			}

			$settings_link = '<a href="admin.php?page=wc-settings&tab=integration&section=integration-agechecker">Settings</a>';

			array_unshift($links, $settings_link);

			return $links;
		}

		public function add_integration($integrations) {
			$integrations[] = 'WC_Integration_AgeChecker_Integration';

			return $integrations;
		}
	}

	$WC_Integration_AgeChecker = new WC_Integration_AgeChecker();
endif;
