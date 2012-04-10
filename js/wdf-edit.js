jQuery(document).ready( function($) {	
	if(WDF && WDF.hook == 'edit.php' && WDF.typenow == 'funder') {
		$('.wdf_goal_progress').progressbar({
			value: 0,
			create: function() {
				$(this).progressbar( "option", "value", Math.round( parseInt( $(this).attr('total') * 100) ) / parseInt( $(this).attr('goal') ) );
			}
		});
	}
	if(WDF && WDF.hook == 'edit.php' && WDF.typenow == 'donation') {
		$('#menu-posts-funder, #menu-posts-funder > a').removeClass('wp-not-current-submenu').addClass('wp-has-current-submenu wp-menu-open');
		$('#menu-posts-funder li a[href="edit.php?post_type=donation&post_status=donation_complete"]').addClass('current').parent().addClass('current');
	}
});