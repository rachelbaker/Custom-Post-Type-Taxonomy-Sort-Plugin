jQuery(document).ready(function(){
	// Set up click event for handling when a user clicks the quick edit link
	jQuery('.editinline').live('click', function(){
		// Get the tag id
		var tag_id = jQuery(this).parents('tr').attr('id');		
		// Get the order number
		var order = jQuery('.order', '#'+tag_id).text();
		// Place order value in the form				
		jQuery(':input[name="tax-order"]', '.inline-edit-row').val(order);
		return false;
	});
});
