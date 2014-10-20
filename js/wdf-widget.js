jQuery(document).ready( function($) {
	
	// Use jQuery to find fundraising widgets and add some extra classes to bring out the associated widgets
	$.each($('div.widget'), function(i,e) {
		var id = $(e).attr('id');
		if(id.indexOf('_wdf_') != -1) {
			$('#'+id).addClass('wdf_widget_highlite');
		}
	});
	$(document).on('change', '.autosave_widget', function(e) {
		$(this).parents('form').find('input[type="submit"]').trigger('click');
		return false;
	});
	$(document).on('change', 'select.wdf_toggle', function(e) {
		var rel = $(this).attr('rel');
		var val = $(this).val();
			if((rel == 'wdf_panel_single' || rel == 'wdf_panel_single_pledges') && val == '1') {
				var elm = $('*[rel="'+rel+'"]').not(this);
				elm.show();
			} else {
				var elm = $('*[rel="'+rel+'"]').not(this);
				elm.hide();
			}
	});
	
});