/*!
* jQuery Bullseye v1.0
* http://pixeltango.com
*
* Copyright 2010, Mickel Andersson
* Dual licensed under the MIT or GPL Version 2 licenses.
*
* Date: Fri Aug 31 19:09:11 2010 +0100
*/
(function($){
jQuery.fn.bullseye = function (b, h) { b = jQuery.extend({ offsetTop: 0, offsetHeight: 0, extendDown: false }, b); return this.each(function () { var a = $(this), c = $(h == null ? window : h), g = function () { var d = a.outerWidth(), e = a.outerHeight() + b.offsetHeight; c.width(); var f = c.height(), i = c.scrollTop(), j = c.scrollLeft() + d; f = i + f; var k = a.offset().left; d = k + d; var l = a.offset().top + b.offsetTop; e = l + e; if (f < l || (b.extendDown ? false : i > e) || j < k || j > d) { if (a.data("is-focused")) { a.data("is-focused", false); a.trigger("leaveviewport") } } else if (!a.data("is-focused")) { a.data("is-focused", true); a.trigger("enterviewport") } }; c.scroll(g).resize(g); g() }) };
})(jQuery);

jQuery(document).ready( function($) {
	var prog_default = {
		value: 0,
		create: function() {
			var value = Math.round( parseInt( $(this).attr('total') * 100) ) / parseInt( $(this).attr('goal') );
			if(value > 100) { value = 100 }
			$(this).find('.ui-progressbar-value').animate({ width: value + '%'},4000,'swing');
			$(this).progressbar( "option", "value", value);
		}
	};

	$('.wdf_rewards .wdf_reward_item').on('click', function(e) {
		var _this = $(this);
		if (!_this.hasClass("wdf_reward_item_disabled")) {
			var rel = _this.find('.wdf_level_amount').attr('rel');
			if($.isNumeric( rel ))
				_this.parent().find('input.wdf_pledge_amount').val(rel);

			_this.find('input:radio').prop('checked', true);
		}
	});

	$('.wdf_pledge_amount').on('focusout', function(e) {
		var _this = $(this);
		var pledge_amount = Math.round(_this.val());
		//_this.val(pledge_amount);

		var reward_amount = _this.parents('.wdf_rewards').find(".wdf_reward_item input:checked").parent().find('.wdf_level_amount').attr('rel');
		if(pledge_amount < reward_amount)
			_this.parents('.wdf_rewards').find(".wdf_reward_item input:checked").prop('checked', false);

		_this.find('input:radio').prop('checked', true);
	});

	$('.wdf_goal_progress').progressbar(prog_default).on('enterviewport', function() {
		if($(this).hasClass('not-seen')) {
			var value = Math.round( parseInt( $(this).attr('total') * 100) ) / parseInt( $(this).attr('goal') );
			if(value > 100) { value = 100 }
			$(this).find('.ui-progressbar-value').width(0).animate({ width: value + '%'},4000,'swing');
			$(this).removeClass('not-seen').addClass('seen');
			$(this).progressbar( "option", "value", value);
		} else {
			//Do Nothing
		}
	}).bullseye();

	$('.wdf_donate_btn.oneclick').click( function() {
		$(this).parents('form').trigger('submit');
		return false;
	});
});