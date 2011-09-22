=== Plugin Name ===
Contributors: rachelbaker
Tags: custom post type, custom taxonomy, custom sort
Requires at least: 3.1
Tested up to: 3.3 beta



Custom Post Type Taxonomy Sort allows the Custom Taxonomies for Custom Post Types to be sorted manually based on order values.  



== Description ==

Surprisingly, WordPress does not provide a mechanism for sorting taxonomies by a custom defined order. Taxonomies can only be sorted by name or id. Custom Taxonomy Sort allows the average user a mechanism to define and display terms in a specified order. After installing Custom Taxonomy Sort, each taxonomy term will have the ability to have a "tax-order" value associated with it. This order is specified by doing the following:

1. Go to any taxonomy add or edit screen (e.g., wp-admin/edit-tags.php?taxonomy=category)
2. Fill in a numeric value for the "Order" field. By default, the order will be ascending, meaning it will sort from low to high (e.g., 1, 2, 3). These values can be added on the Add Taxonomy screen, the Edit Taxonomy screen, or through the Quick Edit panel.
3. Observe all of your terms automagically being sorted in the order you specified

Custom Taxonomy Sort automatically applies the sort order to all instances in which the terms are displayed. All you need to do is define that order. 

= Manual Mode =

In addition to automatically sorting the terms, Custom Taxonomy Sort allows developers to override the automatic sort to offer finer control over how terms are displayed in different parts of WordPress. Manual mode can be started by changing "Automatic Sort" to "Off" in the Custom Taxonomy Sort Settings page (wp-admin/options-general.php?page=custom-taxonomy-sort-settings). Once "Automatic Sort" is switched to "Off", the terms will no longer be sorted automatically by the custom order. Instead, the custom sort order can be envoked with a new parameter for the "orderby" argument. Custom Taxonomy Sort allows you to use the following argument to access a custom sorted list of terms using "get_terms"

`<?php get_terms('orderby=custom_sort'); ?>`

Voila! Now, your terms will only be sorted by the specified order in the places that you want it sorted in this order. All other instances of displaying terms will revert to WordPress' default of sorting the terms by name. Additionally, you can specifically have the terms sort in ascending ('ASC'; default; e.g., 1, 2, 3) or descending ('DESC'; e.g., 3, 2, 1) order by adding the "order" argument.

`<?php get_terms('orderby=custom_sort&order=ASC'); ?>`

`<?php get_terms('orderby=custom_sort&order=DESC'); ?>`


= Acknowledgements =

* Custom Post Type Taxonomy Sort was based on the Custom Taxonomy Sort plugin by 'tollmanz' (http://www.zackdev.com) Thank you for your fantastic plugin that needed a little more functionality for me.


== Installation ==

1. Upload the `custom_taxonomy_sort` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= After installation, what do I need to do? =

After installing the plugin, all taxonomies will be automatically sorted by your custom order; however, you need to vist the individual taxonomy term edit pages to enter the order of the terms.

= What is "Automatic Sort" =

"Automatic Sort" has two states: "On" (default) and "Off". When "Autmatic Sort" is set to "On", every call to "get_terms" will be sorted by the custom sort order. In this mode, you do not have to manually add parameters to the "get_terms" function calls to have the terms sorted by the custom sort order. You can think of the "On" state as allowing the plugin to do everything for you; all you need to do is tell it in what order you want the terms to be sorted. On the other hand, if you set "Automatic Sort" to "Off", you need to manually send parameters to the "get_terms" functions in order for the terms to be sorted by the custom sort order. The "Off" mode is best for developers who want absolute control over every time the terms are sorted and displayed.

= What is "Sort Order" =

"Sort Order" is the order in which the terms are sorted. "Ascending" will sort the terms from lowest to highest value (e.g., 1, 2, 3). "Descending" will sort the terms from highest to lowest value (e.g., 3, 2, 1).

== Screenshots ==

1. Order field in add taxonomy term page
2. Order field in Quick Edit
3. Order field in edit taxonomy term page
4. Settings page

== Changelog ==

= 1.1.2 =
* Works for more custom taxonomies in more situations
* Fixed column alignment
* Localization fixes
* Initial groundwork for column sorting

= 1.1.1 =
* Fixed error message displayed in Post, Page, and Custom Post Type Quick Edit screens
* Javascript and CSS only displays on taxonomy pages

= 1.1 =
* Added full support for Quick Edit

= 1.0.1 =
* Custom Sort is applied in more places (added filter to wp_get_object_terms)
* Removed full support for WordPress 3.0 (will work in WordPress 3.0, but not in all places)

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.1.2 =
Works for more taxonomies

= 1.1.1 =
Small bug fixes

= 1.1 =
Added support for Quick Edit

= 1.0.1 =
Custom sort is applied in more places

= 1.0 =
Initial release