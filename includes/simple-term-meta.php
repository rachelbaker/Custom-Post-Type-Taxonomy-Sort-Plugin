<?php
/**
    Plugin: Copyright 2009 C. Murray Consulting  (email : jake@cmurrayconsulting.com)

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

/**
 * activation - setup table, store db version for future updates
 */

/* This is unnecessary and this is handled in the parent plugin */
//register_activation_hook( __FILE__, 'simple_term_meta_install' );

function simple_term_meta_install()
{
	// setup custom table
	
	global $wpdb;
		
	$table_name = $wpdb->prefix . 'termmeta';
	
	if( $wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name ) :
	
		$sql = "CREATE TABLE " . $table_name . " (
		  meta_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		  term_id bigint(20) unsigned NOT NULL DEFAULT '0',
		  meta_key varchar(255) DEFAULT NULL,
		  meta_value longtext,
		  PRIMARY KEY (meta_id),
		  KEY term_id (term_id),
		  KEY meta_key (meta_key)	  
		);";
		
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		
	endif;
	
	update_option( "simple_term_meta_db_version", '0.9' );
}

/**
 * define postmeta table in wpdb
 */

add_action( 'init', 'simple_post_meta_define_table' );

function simple_post_meta_define_table() {
	global $wpdb;
	$wpdb->termmeta = $wpdb->prefix . 'termmeta';
}

/**
 * delete term meta table and db version option upon uninstall
 */
 
register_uninstall_hook( __FILE__, 'simple_term_meta_uninstall' );

function simple_term_meta_uninstall()
{
	global $wpdb;
		
	$table_name = $wpdb->prefix . 'termmeta';
	
	$wpdb->query("DROP TABLE IF EXISTS $table_name");
	
	delete_option( "simple_term_meta_db_version" );
}

/**
 * Updates metadata cache for list of term IDs.
 *
 * Performs SQL query to retrieve the metadata for the term IDs and updates the
 * metadata cache for the terms. Therefore, the functions, which call this
 * function, do not need to perform SQL queries on their own.
 *
 * @param array $term_ids List of post IDs.
 * @return bool|array Returns false if there is nothing to update or an array of metadata.
 */
function update_termmeta_cache($term_ids) {
	return update_meta_cache('term', $term_ids);
}

/**
 * Add meta data field to a term.
 *
 * @param int $term_id Term ID.
 * @param string $key Metadata name.
 * @param mixed $value Metadata value.
 * @param bool $unique Optional, default is false. Whether the same key should not be added.
 * @return bool False for failure. True for success.
 */
function add_term_meta( $term_id, $meta_key, $meta_value, $unique = false ) {
	return add_metadata('term', $term_id, $meta_key, $meta_value, $unique);
}

/**
 * Remove metadata matching criteria from a term.
 *
 * You can match based on the key, or key and value. Removing based on key and
 * value, will keep from removing duplicate metadata with the same key. It also
 * allows removing all metadata matching key, if needed.
 *
 * @param int $term_id Term ID
 * @param string $meta_key Metadata name.
 * @param mixed $meta_value Optional. Metadata value.
 * @return bool False for failure. True for success.
 */
function delete_term_meta( $term_id, $meta_key, $meta_value = '' ) {
	return delete_metadata('term', $term_id, $meta_key, $meta_value);
}

/**
 * Retrieve term meta field for a term.
 *
 * @param int $term_id Term ID.
 * @param string $key The meta key to retrieve.
 * @param bool $single Whether to return a single value.
 * @return mixed Will be an array if $single is false. Will be value of meta data field if $single
 *  is true.
 */
function get_term_meta( $term_id, $key, $single = false ) {
	return get_metadata('term', $term_id, $key, $single);
}

/**
 * Update term meta field based on term ID.
 *
 * Use the $prev_value parameter to differentiate between meta fields with the
 * same key and term ID.
 *
 * If the meta field for the term does not exist, it will be added.
 *
 * @param int $term_id Term ID.
 * @param string $key Metadata key.
 * @param mixed $value Metadata value.
 * @param mixed $prev_value Optional. Previous value to check before removing.
 * @return bool False on failure, true if success.
 */
function update_term_meta( $term_id, $meta_key, $meta_value, $prev_value = '' ) {
	return update_metadata('term', $term_id, $meta_key, $meta_value, $prev_value);
}

/**
 * Delete everything from term meta matching meta key.
 *
 * @param string $term_meta_key Key to search for when deleting.
 * @return bool Whether the term meta key was deleted from the database
 */
function delete_term_meta_by_key($term_meta_key) {
	if ( !$term_meta_key )
		return false;

	global $wpdb;
	$term_ids = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT term_id FROM $wpdb->termmeta WHERE meta_key = %s", $term_meta_key));
	if ( $term_ids ) {
		$termmetaids = $wpdb->get_col( $wpdb->prepare( "SELECT meta_id FROM $wpdb->termmeta WHERE meta_key = %s", $term_meta_key ) );
		$in = implode( ',', array_fill(1, count($termmetaids), '%d'));
		do_action( 'delete_termmeta', $termmetaids );
		$wpdb->query( $wpdb->prepare("DELETE FROM $wpdb->termmeta WHERE meta_id IN($in)", $termmetaids ));
		do_action( 'deleted_termmeta', $termmetaids );
		foreach ( $term_ids as $term_id )
			wp_cache_delete($term_id, 'term_meta');
		return true;
	}
	return false;
}

/**
 * Retrieve term meta fields, based on term ID.
 *
 * The term meta fields are retrieved from the cache, so the function is
 * optimized to be called more than once. It also applies to the functions, that
 * use this function.
 *
 * @param int $term_id term ID
 * @return array
 */
function get_term_custom( $term_id ) {
	$term_id = (int) $term_id;

	if ( ! wp_cache_get($term_id, 'term_meta') )
		update_termmeta_cache($term_id);

	return wp_cache_get($term_id, 'term_meta');
}

/**
 * Retrieve meta field names for a term.
 *
 * If there are no meta fields, then nothing (null) will be returned.
 *
 * @param int $term_id term ID
 * @return array|null Either array of the keys, or null if keys could not be retrieved.
 */
function get_term_custom_keys( $term_id ) {
	$custom = get_term_custom( $term_id );

	if ( !is_array($custom) )
		return;

	if ( $keys = array_keys($custom) )
		return $keys;
}

/**
 * Retrieve values for a custom term field.
 *
 * The parameters must not be considered optional. All of the term meta fields
 * will be retrieved and only the meta field key values returned.
 *
 * @param string $key Meta field key.
 * @param int $term_id Term ID
 * @return array Meta field values.
 */
function get_term_custom_values( $key = '', $term_id ) {
	if ( !$key )
		return null;

	$custom = get_term_custom($term_id);

	return isset($custom[$key]) ? $custom[$key] : null;
}
?>