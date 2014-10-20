function wdf_inject_shortcode () {
	
	var iFrame = jQuery('#TB_iframeContent').contents();
	var cont = iFrame.find('.wdf_media_cont:first');
	var type = cont.attr('id');
	var form = cont.serializeArray();

	switch(type) {
		case 'media_fundraising' :
			var funder_select = iFrame.find('#wdf_funder_select');
			
			var shortcode = '[fundraiser_panel';
		
			jQuery.each(form, function(i,e) { 
				shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			
			shortcode = shortcode + ']';
			if(jQuery('#wp-content-wrap').hasClass('tmce-active')) {
				window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
				window.parent.tb_remove();
			} else {
				window.parent.edInsertContent('',shortcode);
				window.parent.tb_remove();
			}
		break;

		case 'media_pledges' :
			var funder_select = iFrame.find('#wdf_funder_select');
			
			var shortcode = '[pledges_panel';
		
			jQuery.each(form, function(i,e) { 
				shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			
			shortcode = shortcode + ']';
			if(jQuery('#wp-content-wrap').hasClass('tmce-active')) {
				window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
				window.parent.tb_remove();
			} else {
				window.parent.edInsertContent('',shortcode);
				window.parent.tb_remove();
			}
		break;
		
		case 'media_donate_button' :
			var shortcode = '[donate_button';
			jQuery.each(form, function(i,e) {
				if(e.value != '')
					shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			shortcode = shortcode + ']';
			if(jQuery('#wp-content-wrap').hasClass('tmce-active')) {
				window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
				window.parent.tb_remove();
			} else {
				window.parent.edInsertContent('',shortcode);
				window.parent.tb_remove();
			}
		break;
		
		case 'media_progress_bar' :
			var shortcode = '[progress_bar';
			jQuery.each(form, function(i,e) {
				if(parseInt(e.value) !== 0)
					shortcode = shortcode + ' '+e.name+'="'+e.value+'"';
			});
			shortcode = shortcode + ']';
			if(jQuery('#wp-content-wrap').hasClass('tmce-active')) {
				window.parent.tinyMCE.execCommand("mceInsertContent", true, shortcode);
				window.parent.tb_remove();
			} else {
				window.parent.edInsertContent('',shortcode);
				window.parent.tb_remove();
			}
		break;		
		
	}
}
function wdf_input_switch(elm) {
	elm = jQuery(elm);
	var iFrame = jQuery('#TB_iframeContent').contents();
	var rel = elm.attr('rel');
	var val = elm.val();
	
	/*$('select.wdf_toggle').on('change', function(e) {
		var rel = $(this).attr('rel');
		var val = $(this).val();
		//alert(rel + val);
		
		if(rel == 'wdf_panel_single' && val == '1') {
			var elm = $('*[rel="'+rel+'"]').not(this);
			elm.show();
		} else {
			var elm = $('*[rel="'+rel+'"]').not(this);
			elm.hide();
		}
	});*/
}