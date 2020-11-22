=== Affili IR ===
Contributors: davodsaraei
Tags: affili-ir, affili, affiliate-network, affiliate-marketing
Requires at least: 4.6
Requires PHP: 7.2.*
Tested up to: 5.5.3
Stable tag: 1.0.1
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

The Affili WordPress Plugin allows you to automatically track affiliate conversions.

== Description ==
The Affili WordPress Plugin allows you to automatically track affiliate conversions.

This plugin makes use of the Affili API to interact with affili.ir.


== Installation ==
1. You can either install this plugin from the WordPress Plugin Directory,
  or manually  [download the plugin](https://github.com/affili-ir/wordpress/releases) and upload it through the 'Plugins > Add New' menu in WordPress
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Register at https://affili.ir as a merchant to get an API key
1. Copy your API Key into the "Token" input in the plugin's settings

== How to Use ==
Once the plugin is properly configured it functions automatically. Every time affiliates send to you customer we we track links and when customer make an order
we set a conversion.

== Changelog ==
1.0.0
*Release Date - 24 September 2020*

* Initial setup
* MVP version.

1.1.0
*Release Date - 22 November 2020*

* First stable version
* Use wp_add_inline_script and wp_enqueue_script instead of inline code.
* Add changelog
* Add uninstall