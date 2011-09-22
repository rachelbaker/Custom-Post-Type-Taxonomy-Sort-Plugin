<?php
/*
Plugin Name: Custom Post Type Taxonomy Sort
Plugin URI: http://www.pluggedinconsulting.com
Description: Custom Post Type Taxonomy Sort allows you to customize the order of appearence of your custom taxonomy in custom post type queries
Author: Rachel Baker
Author URI: http://www.pluggedinconsulting.com
Version: 1.0

**This plugin is a based on Custom Taxonomy Sort by Zack Tollman (email: zack [at] zackdev [dot] com)**

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Version check
global $wp_version;
$exit_msg = __("The Custom Post Type Taxonomy Sort plugin requires the use of Wordpress 3.0 or higher. Please update!", 'custom-post-type-taxonomy-sort');
if(version_compare($wp_version, "3.0", "<")) exit($exit_msg);

// Avoid name collision
if(!class_exists('CustomPostTypeTaxonomySort')) :

/**
 * CustomPostTypeTaxonomySort class.
 */
class CustomPostTypeTaxonomySort
{
	/**
	 * Variable will hold the control types
	 */
	var $control_types;

	/**
	 * Variable will hold the sort orders
	 */
	var $sort_orders;
	
	/**
	 * Name of plugin
	 */	
	var $plugin_name = 'custom-post-type-taxonomy-sort';
	
	/**
	 * Name of variable in options table
	 */
	var $options_name = 'custom-post-type-taxonomy-sort-settings';

	/**
	 * Name of new 'orderby' parameter that envokes the custom sort order
	 */
	var $orderby_parameter = 'custom_sort';
	
	/**
	 * Current plugin version
	 */
	var $version = 1.0;

	/**
	 * __construct function.
	 * 
	 * @access public
	 * @return void
	 */
	function __construct()
	{
		// Include the Simple Term Meta plugin which is necessary for the UI only if the plugin isn't already installed
		if(!function_exists('simple_term_meta_install'))
			include(plugin_dir_path(__FILE__).'/includes/simple-term-meta.php');

		// Install the plugin
		register_activation_hook( __FILE__, array(&$this, 'install'));
		
		// Prepare the class variables
		$this->process_class_vars();
					
		// Call taxonomy metadata functions
		add_action('init', array(&$this, 'add_taxonomy_actions'), 100, 0);
		
		// Add settings page
		add_action('admin_menu', array(&$this, 'add_settings_actions'));
		
		// Setup settings fields
		add_action('admin_init', array(&$this, 'settings_init'));
		
		// Apply the filter that changes the sort order
		add_filter('get_terms', array(&$this, 'get_terms'), 10, 3);

		// Apply the filter that changes the sort order
		add_filter('wp_get_object_terms', array(&$this, 'wp_get_object_terms'), 10, 4);
		
		// Apply the filter to catch the custom sort
		add_filter('get_terms_orderby', array(&$this, 'get_terms_orderby'), 10, 2);
		
		// Add JS for admin
		add_action('admin_enqueue_scripts', array(&$this, 'add_admin_scripts'));
		
		// Set up form elements in quick edit
		add_action('admin_init', array(&$this, 'add_quick_edit_action'));
		
		//add_filter('posts_clauses', array(&$this, 'order_clauses'), 10, 2);
	}
	
	/**
	 * PHP 4 constructor.
	 * 
	 * @access public
	 * @return void
	 */
	function CustomPostTypeTaxonomySort()
	{
		$this->__construct();
	}
	
	/**
	 * Installs the simple term meta plugin and adds default options
	 * 
	 * @access public
	 * @return void
	 */
	function install()
	{
		// Fire the Simple Term Meta installation function on activation
		simple_term_meta_install();
		
		// Set initial options if not already set
		$options['control_type'] = $this->control_types[0]['key'];
		$options['sort_order'] = $this->sort_orders[0]['key'];
		if(!get_option($this->options_name))
			update_option($this->options_name, $options);
	}
	
	/**
	 * Sets up class variable arrays. Primary purpose is to make these arrays l18n as this function cannt be run on 
	 * initialization
	 * 
	 * @access public
	 * @return void
	 */
	function process_class_vars()
	{
		// Set up control types. First value is the default
		$this->control_types = array(
			array(
				'key' => 'on',
				'value' => __('On', $this->plugin_name)
			),
			array(
				'key' => 'off',
				'value' => __('Off', $this->plugin_name)
			)
		);
		
		// Set up sort orders. First value is the default
		$this->sort_orders = array(
			array(
				'key' => 'ASC',
				'value' => __('Ascending', $this->plugin_name)
			),
			array(
				'key' => 'DESC',
				'value' => __('Descending', $this->plugin_name)
			)
		);
	}	

	/**
	 * Calls functions that add fields to add and edit taxonomy term screens.
	 * 
	 * @access public
	 * @return void
	 */
	function add_taxonomy_actions()
	{
		// Add actions for adding and editing order for all taxonomies
		foreach(get_taxonomies() as $taxonomy => $name)
		{				
			// Custom data for taxonomy
			add_action($name.'_add_form_fields', array(&$this, 'metabox_add'), 10, 1);
			add_action($name.'_edit_form_fields', array(&$this, 'metabox_edit'), 10, 1);
			add_action('created_'.$name, array(&$this, 'save_meta_data'), 10, 1);	
			add_action('edited_'.$name, array(&$this, 'save_meta_data'), 10, 1);
			
			// Adds columns for taxonomy pages
			add_filter("manage_edit-{$name}_columns", array(&$this, 'column_header'), 10, 1);
			//add_filter("manage_edit-{$name}_sortable_columns", array(&$this, 'column_header_sortable'), 10, 1);
			add_filter("manage_{$name}_custom_column", array(&$this, 'column_value'), 10, 3);
		}
	}
	
	/**
	 * Enqueues JS and CSS for admin
	 * 
	 * @access public
	 * @return void
	 */
	function add_admin_scripts()
	{
		global $pagenow;
		
		// Only add JS and CSS on the edit-tags page
		if($pagenow == 'edit-tags.php')
		{
			// Register JS
			wp_register_script(
				'custom-post-type-taxonomy-sort-js',
				plugins_url('/js/custom-post-type-taxonomy-sort.js', __FILE__),
				array('jquery'),
				$this->version
			);
			wp_enqueue_script('custom-post-type-taxonomy-sort-js');
			
			// Register CSS
			wp_register_style(
				'custom-post-type-taxonomy-sort-css',
				plugins_url('/css/custom-post-type-taxonomy-sort.css', __FILE__),
				false,
				$this->version,
				false
			);
			wp_enqueue_style('custom-post-type-taxonomy-sort-css');
		}
	}
	
	/**
	 * Defines the form for the meta for the taxonomy term add screen.
	 * 
	 * @access public
	 * @param mixed $tag Object with term data
	 * @return void
	 */
	function metabox_add($tag) 
	{
	?>
		<div class="form-field">
			<label for="tax-order"><?php _e('Order', $this->plugin_name) ?></label>
			<input name="tax-order" id="tax-order" type="text" value="" size="40" aria-required="true" />
			<p class="description"><?php _e('Determines the order in which the term is displayed.', $this->plugin_name); ?></p>
		</div>
	<?php
	} 	

	/**
	 * Defines the form for the meta for the taxonomy term edit screen.
	 * 
	 * @access public
	 * @param mixed $tag Object with term data
	 * @return void
	 */	
	function metabox_edit($tag) 
	{
	?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="tax-order"><?php _e('Order', $this->plugin_name); ?></label>
			</th>
			<td>
				<input name="tax-order" id="tax-order" type="text" value="<?php echo get_term_meta($tag->term_id, 'tax-order', true); ?>" size="40" aria-required="true" />
				<p class="description"><?php _e('Determines the order in which the term is displayed.', $this->plugin_name); ?></p>
			</td>
		</tr>
	<?php
	}

	/**
	 * Save the metadata.
	 * 
	 * @access public
	 * @param mixed $term_id ID of the term
	 * @return void
	 */
	function save_meta_data($term_id)
	{
		// Only save the value if it is present and it is a number
	    if(isset($_POST['tax-order']) && is_numeric($_POST['tax-order'])) 
			$order = $_POST['tax-order']; 
		elseif($current_order = get_term_meta($term_id, 'tax-order', true))
			$order = $current_order;
		else
			$order = 0;
			
		update_term_meta( $term_id, 'tax-order', $order);     
	}

	/**
	 * Filters the terms selected with the get_terms function. Adds the tax_order value to the term objects and sorts.
	 * 
	 * @access public
	 * @param mixed $terms
	 * @param mixed $taxonomies
	 * @param mixed $args
	 * @return void
	 */
	function get_terms($terms, $taxonomies, $args)
	{		
		// If the current control type is not automatic, return the terms unless it's explicitly set to be sorted by custom_sort
		if($this->get_control_type() == 'off' && $args['orderby'] != $this->orderby_parameter) return $terms;
	
		// Controls behavior when get_terms is called at unusual times resulting in a terms array without objects
		$empty = false;
		
		// Create collector arrays
		$ordered_terms = array();
		$unordered_terms = array();

		// Add taxonomy order to terms
		foreach($terms as $term)
		{
			// Only set tax_order if value is an object
			if(is_object($term))
			{
				if($taxonomy_sort = get_term_meta($term->term_id, 'tax-order', true))
				{
					$term->tax_order = (int) $taxonomy_sort;
					$ordered_terms[] = $term;
				}
				else
				{
					// This catches any terms that don't have tax-order set
					$term->tax_order = (int) 0;
					$unordered_terms[] = $term;
				}
			}
			else
				$empty = true;
		}
		
		// Only sort by tax_order if there are items to sort, otherwise return the original array
		if(!$empty && count($ordered_terms) > 0)
			$this->quickSort($ordered_terms);
		else
			return $terms;

		// By default, the array is sorted ASC; sort DESC if needed
		if(
			($args['orderby'] == $this->orderby_parameter && $args['order'] == 'DESC') ||
			($args['orderby'] != $this->orderby_parameter && $this->get_sort_order() == 'DESC' )
		) krsort($ordered_terms);

		// Combine the newly ordered items with the unordered items and return
		return array_merge($ordered_terms, $unordered_terms);	
	}
	
	/**
	 * Filters the terms selected with the wp_get_object_terms function by using the get_terms function.
	 * 
	 * @access public
	 * @param mixed $terms
	 * @param mixed $object_ids
	 * @param mixed $taxonomies
	 * @param mixed $args
	 * @return void
	 */
	function wp_get_object_terms($terms, $object_ids, $taxonomies, $args)
	{
		// At this point, the $object_ids have already been used to get terms associated with the desired objects
		// As such, the resulting terms just need to be run through the custom sorting routine
		return $this->get_terms($terms, $taxonomies, $args);
	}

	/**
	 * This function is meant to intercept the orderby=custom_sort argument. If it goes in unaltered
	 * there will be a database error. As such, this filter tries to identify if the orderby argument 
	 * is custom_sort and changes the order value to ''. However, since the $args array still contains the 
	 * custom_sort value, it can later be read and acted upon in the later filter.
	 * 
	 * @access public
	 * @param $orderby string
	 * @param $args array
	 * @return string New orderby text
	 */
	function get_terms_orderby($orderby, $args)
	{
		if($orderby == $this->orderby_parameter) 
			return '';
		else
			return $orderby;
	}

	/**
	 * Thanks to Paul Dixon (http://stackoverflow.com/questions/1462503/sort-array-by-object-property-in-php).
	 * Function sorts an array of objects by an object property 
	 *
	 * @access public
	 * @param mixed &$array
	 * @return void
	 */
	function quickSort(&$array)
	{
		$cur = 1;
		$stack[1]['l'] = 0;
		$stack[1]['r'] = count($array)-1;
		
		do
		{
			$l = $stack[$cur]['l'];
			$r = $stack[$cur]['r'];
			$cur--;
		
			do
			{
				$i = $l;
				$j = $r;
				$tmp = $array[(int)( ($l+$r)/2 )];
			
				// partion the array in two parts.
				// left from $tmp are with smaller values,
				// right from $tmp are with bigger ones
				do
				{
					while( $array[$i]->tax_order < $tmp->tax_order )
					$i++;
				
					while( $tmp->tax_order < $array[$j]->tax_order )
				 	$j--;
				
					// swap elements from the two sides
					if( $i <= $j)
					{
						 $w = $array[$i];
						 $array[$i] = $array[$j];
						 $array[$j] = $w;
				
				 		$i++;
				 		$j--;
					}
				
				}while( $i <= $j );
				
				if( $i < $r )
				{
					$cur++;
					$stack[$cur]['l'] = $i;
					$stack[$cur]['r'] = $r;
				}
				$r = $j;
				
			}while( $l < $r );
				
		}while( $cur != 0 );
	}

	/**
	 * Sets up the submenu page
	 *
	 * @access public
	 * @return void
	 */
	function add_settings_actions()
	{
		// Add the options page
		add_submenu_page('options-general.php', __('Custom Post Type Taxonomy Sort Settings', $this->plugin_name), __('Custom Post Type Taxonomy Sort', $this->plugin_name), 'manage_options', 'custom-post-type-taxonomy-sort-settings', array(&$this, 'settings_page'));	
	}

	/**
	 * Defines the settings page
	 *
	 * @access public
	 * @return void
	 */
	function settings_page() 
	{
	?>
		<div class="wrap"> 
		<div id="icon-options-general" class="icon32"><br /></div><h2><?php _e('Custom Post Type Taxonomy Sort Settings', $this->plugin_name); ?></h2>
		<form action="options.php" method="post">
		<?php settings_fields('custom-post-type-taxonomy-sort-settings'); ?>
		<?php do_settings_sections('custom-post-type-taxonomy-sort-fields'); ?>
		<p class="submit"><input type="submit" name="submit" id="submit" class="button-primary" value="<?php _e('Save Changes', $this->plugin_name); ?>" /></p>
		</form></div>
	<?php
	}
	
	/**
	 * Registers settings and adds settings fields
	 *
	 * @access public
	 * @return void
	 */
	function settings_init()
	{
		register_setting('custom-post-type-taxonomy-sort-settings', $this->options_name, array(&$this, 'settings_validate'));
		add_settings_section('custom-post-type-taxonomy-sort-options', __('General', $this->plugin_name), array(&$this, 'settings_page_text'), 'custom-post-type-taxonomy-sort-fields');
		
		// Add control types inputs
		add_settings_field('custom-post-type-taxonomy-sort-control-type', __('Automatic Sort', $this->plugin_name), array(&$this, 'control_type_string'), 'custom-post-type-taxonomy-sort-fields', 'custom-post-type-taxonomy-sort-options');
		
		// Add sort orders inputs
		add_settings_field('custom-post-type-taxonomy-sort-orders', __('Sort Order', $this->plugin_name), array(&$this, 'sort_orders_string'), 'custom-post-type-taxonomy-sort-fields', 'custom-post-type-taxonomy-sort-options');
	}
	
	/**
	 * Validates the settings before adding them to the database
	 *
	 * @access public
	 * @param $input array Values that will be sent to the database
	 * @return void
	 */
	function settings_validate($input)
	{
		// Make sure control_type is a valid value
		if(isset($input['control_type']))
		{
			$valid_control_type = false;
			foreach($this->control_types as $key => $value) {
				if($value['key'] == $input['control_type']) $valid_control_type = true;
			}
			if(!$valid_control_type) $input['control_type'] = $this->control_type_default[0]['key'];
		}

		// Make sure sort_order is a valid value
		if(isset($input['sort_order']))
		{
			$valid_sort_order = false;
			foreach($this->sort_orders as $key => $value) {
				if($value['key'] == $input['sort_order']) $valid_sort_order = true;
			}
			if(!$valid_sort_order) $input['sort_order'] = $this->sort_orders[0]['key'];
		}
		return $input;
	}
	
	/**
	 * Controls the text for the Sort Management section
	 *
	 * @access public
	 * @return void
	 */
	function settings_page_text()
	{
	?>
		<p><?php _e('Sort Management', $this->plugin_name); ?></p>
	<?php
	}
	
	/**
	 * Controls inputs for control types
	 *
	 * @access public
	 * @return void
	 */
	function control_type_string()
	{
		// Get the current control type setting
		$current_control_type = $this->get_control_type();
	?>
		<fieldset><legend class="screen-reader-text"><span><?php _e('Control Type', $this->plugin_name); ?></span></legend> 
		<?php foreach ($this->control_types as $key => $value) : ?>
			<label title="<?php echo $value['key']; ?>"><input type="radio" name="custom-post-type-taxonomy-sort-settings[control_type]" value="<?php echo $value['key']; ?>" <?php if($current_control_type == $value['key']) : ?>checked='checked'<?php endif; ?> /> <span><?php echo $value['value']; ?></span></label><br />
		<?php endforeach; ?>
		</fieldset>
	<?php
	}

	/**
	 * Controls the inputs for sort orders
	 *
	 * @access public
	 * @return void
	 */
	function sort_orders_string()
	{
		// Get the current control type setting
		$current_control_type = $this->get_sort_order();
	?>
		<fieldset><legend class="screen-reader-text"><span><?php _e('Sort Order', $this->plugin_name); ?></span></legend> 
		<?php foreach ($this->sort_orders as $key => $value) : ?>
			<label title="<?php echo $value['key']; ?>"><input type="radio" name="custom-post-type-taxonomy-sort-settings[sort_order]" value="<?php echo $value['key']; ?>" <?php if($current_control_type == $value['key']) : ?>checked='checked'<?php endif; ?> /> <span><?php echo $value['value']; ?></span></label><br />
		<?php endforeach; ?>
		</fieldset>
	<?php
	}
	
	/**
	 * Get the current control_type setting
	 *
	 * @access public
	 * @return string Current value of the control_type setting
	 */
	function get_control_type()
	{
		$options = get_option($this->options_name);
		return $options['control_type'];
	}
	
	/**
	 * Get the current sort_order setting
	 *
	 * @access public
	 * @return string Current value of the sort_order setting
	 */
	function get_sort_order()
	{
		$options = get_option($this->options_name);
		return $options['sort_order'];
	}
	
	/**
	 * Runs the action to add forms to Quick Edit area
	 *
	 * @access public
	 * @return none
	 */
	function add_quick_edit_action()
	{
		global $pagenow;
		
		// Set up form elements in quick edit only on the edit-tags page
		if($pagenow == 'edit-tags.php')
			add_action('quick_edit_custom_box', array(&$this, 'quick_edit_custom_box'), 10, 3);	
	}	
	
	/**
	 * Defines the Order column
	 *
	 * @access public
	 * @param $columns array
	 * @return array
	 */
	function column_header($columns)
	{
		$columns['order'] = __('Order', $this->plugin_name);
		return $columns;
	}

	/**
	 * Defines the Order column as sortable
	 *
	 * @access public
	 * @param $columns array
	 * @return array
	 */
	function column_header_sortable($columns)
	{
		$columns['order'] = 'order';
		return $columns;
	}
	
	/**
	 * Sorts the Order column
	 *
	 * @access public
	 * @param $clauses array
	 * @param $wp_query array
	 * @return array
	 */
	function order_clauses($clauses, $wp_query)
	{
		global $wpdb;

		if(isset($wp_query->query['orderby']) && 'order' == $wp_query->query['orderby']) 
		{
			
			$clauses['join'] .= "
		LEFT OUTER JOIN {$wpdb->term_relationships} ON {$wpdb->posts}.ID={$wpdb->term_relationships}.object_id
		LEFT OUTER JOIN {$wpdb->term_taxonomy} USING (term_taxonomy_id)
		LEFT OUTER JOIN {$wpdb->terms} USING (term_id)
		SQL";
		
			$clauses['where'] .= " AND (taxonomy = 'color' OR taxonomy IS NULL)";
			$clauses['groupby'] = "object_id";
			$clauses['orderby']  = "GROUP_CONCAT({$wpdb->terms}.name ORDER BY name ASC) ";
			$clauses['orderby'] .= ( 'ASC' == strtoupper( $wp_query->get('order') ) ) ? 'ASC' : 'DESC';
		}
		
		return $clauses;
	}
	
	/**
	 * Gets value of current row's tax-order
	 *
	 * @access public
	 * @param $empty string deprecated value
	 * @param $custom_column string current column value
	 * @param $term_id int
	 * @return string Current value of the sort_order setting
	 */
	function column_value($empty = '', $custom_column, $term_id) 
	{
		return get_term_meta($term_id, 'tax-order', true);		
	}
	
	/**
	 * Prints out form for quick edit
	 *
	 * @access public
	 * @param $column_name string
	 * @param $screen string
	 * @param $tax string
	 * @return string Current value of the sort_order setting
	 */
	function quick_edit_custom_box($column_name, $screen, $name)
	{	
		// The link_category pages will cause this function to be called twice, outputting two separate fieldsets.
		// This statement keeps it from printing out the call for the "links" column.
		// This occurs because "links" is not considered a core column and thus, this filter runs on it 
		// around line 359 of class-wp-terms-list-table.php
		if($column_name == 'order') :
	?>
		<fieldset><div class="inline-edit-col">
			<label>
				<span class="title"><?php _e('Order'); ?></span>
				<span class="input-text-wrap"><input type="text" name="tax-order" class="ptitle" value=""></span>
			</label>
		</div></fieldset>
	<?php endif;
	}
}

else :
	exit(__('Class CustomPostTypeTaxonomySort already exists.', 'custom-post-type-taxonomy-sort'));
endif;

// Instantiate the class
$CustomPostTypeTaxonomySort = new CustomPostTypeTaxonomySort();
?>