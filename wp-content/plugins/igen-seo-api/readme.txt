=== IGen SEO API ===
Contributors: igenai
Tags: yoast, seo, rest-api, meta-fields
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Register Yoast SEO meta fields to make them accessible through REST API for reading and writing.

== Description ==

IGen SEO API plugin allows you to access Yoast SEO meta fields through WordPress REST API. This plugin registers the following Yoast SEO fields to the REST API:

* SEO Title (_yoast_wpseo_title)
* Meta Description (_yoast_wpseo_metadesc)  
* Focus Keyword (_yoast_wpseo_focuskw)

**Key Features:**
* Automatically checks if Yoast SEO plugin is installed
* Shows installation prompt if Yoast SEO is not installed
* Only registers meta fields when Yoast SEO is active
* Provides secure admin notification system

**About IGen**

This plugin is developed by [IGen](https://i-gen.ai/), a leading AI-powered content generation platform. Visit our website to learn more about our innovative AI solutions for content creation and SEO optimization.

== Installation ==

1. Upload the `igen-seo-api` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress admin
3. The plugin will automatically register Yoast SEO meta fields to the REST API

== Frequently Asked Questions ==

= Does this plugin require Yoast SEO plugin? =

Yes, this plugin requires Yoast SEO plugin to be installed and activated to function properly.

= Which post types are supported? =

Currently supports `page` and `post` post types.

= How to access these fields through REST API? =

After activating the plugin, these fields will automatically appear in WordPress REST API post and page endpoints.

== Screenshots ==

1. After plugin activation, Yoast SEO fields are automatically registered to REST API

== Changelog ==

= 1.0.0 =
* Initial release
* Register Yoast SEO meta fields to REST API
* Support for page and post post types
* Added permission control mechanism

== Upgrade Notice ==

= 1.0.0 =
Initial release, recommend upgrading immediately for full functionality.
