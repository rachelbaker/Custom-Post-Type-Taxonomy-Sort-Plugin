=== Plugin Name ===
Contributors: rachelbaker
Tags: custom post type, custom taxonomy, custom sort
Requires at least: 3.1
Tested up to: 3.3 beta



Custom Post Type Taxonomy Sort allows the Custom Taxonomies for Custom Post Types to be sorted based on given order values.  

= Acknowledgements =

* Custom Post Type Taxonomy Sort was based on the Custom Taxonomy Sort plugin by 'tollmanz' (http://www.zackdev.com) Thank you for your fantastic plugin that needed a little more functionality for me.


== Description ==

Surprisingly, WordPress does not provide a mechanism for sorting taxonomies by a custom defined order. Taxonomies can only be sorted by name or id. Custom Taxonomy Sort allows the average user a mechanism to define and display terms in a specified order. After installing Custom Taxonomy Sort, each taxonomy term will have the ability to have a "tax-order" value associated with it. This order is specified by doing the following:

1. Go to any custom post type taxonomy for your add or edit screen 
2. Fill in a numeric value for the "Order" field. By default, the order will be ascending, meaning it will sort from low to high (e.g., 1, 2, 3). These values can be added on the Add Taxonomy screen, the Edit Taxonomy screen, or through the Quick Edit panel.
3. Observe all of your terms automagically being sorted in the order you specified

Custom Post Type Taxonomy Sort automatically applies the sort order to all instances in which the terms are displayed. All you need to do is define that order. 




== Installation ==

1. Upload the `custom_post_type_taxonomy_sort` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress

== Frequently Asked Questions ==

= After installation, what do I need to do? =

After installing the plugin, all taxonomies will be automatically sorted by your custom order; however, you need to visit the individual taxonomy term edit pages to enter the order of the terms.

= What is "Automatic Sort" =

"Automatic Sort" has two states: "On" (default) and "Off". When "Automatic Sort" is set to "On", every call to "get_terms" will be sorted by the custom sort order. In this mode, you do not have to manually add parameters to the "get_terms" function calls to have the terms sorted by the custom sort order. You can think of the "On" state as allowing the plugin to do everything for you; all you need to do is tell it in what order you want the terms to be sorted. On the other hand, if you set "Automatic Sort" to "Off", you need to manually send parameters to the "get_terms" functions in order for the terms to be sorted by the custom sort order. The "Off" mode is best for developers who want absolute control over every time the terms are sorted and displayed.

= What is "Sort Order" =

"Sort Order" is the order in which the terms are sorted. "Ascending" will sort the terms from lowest to highest value (e.g., 1, 2, 3). "Descending" will sort the terms from highest to lowest value (e.g., 3, 2, 1).

