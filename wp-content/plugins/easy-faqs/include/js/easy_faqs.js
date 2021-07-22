var easy_faqs_js = function() {
	// init inline feedback widgets
	var init_feedback = function() {
		var widgets = jQuery('.easy-faqs-inline-feedback');
		if ( widgets.length > 0 ) {			
			widgets.each(function () {
				jQuery(this).on('click', '.easy_faqs_vote_link', function () {
					if ( 'no' == jQuery(this).data('vote') && jQuery(this).hasClass('easy_faqs_vote_link_no') ) {
						// show feedback form before submitting
						var inline_form = jQuery(this).parents('.easy-faqs-inline-feedback:first')
													  .find('.easy_faqs_vote_text');
						inline_form.css('display', 'block');
					} else {
						record_vote(this);
					}
				});
			});
		}
	};

	var record_vote = function(vote_link) {
		vote_link = jQuery(vote_link);
		var inline_form = jQuery(vote_link).parents('.easy-faqs-inline-feedback:first')
										   .find('.easy_faqs_vote_text_input');
						
		var params = {
			'action': 'easy_faqs_record_vote',
			'post_id': vote_link.data('post-id'),
			'vote': vote_link.data('vote'),
			'nonce': vote_link.data('nonce'),
			'text': inline_form.val(),
		};
		jQuery.post(easy_faqs_vars.ajaxurl, params, function (resp) {
			// hide vote link and show thank you message
			jQuery(vote_link).parents('.easy-faqs-inline-feedback:first').html('<p class="easy_faqs_feedback_thank_you">' + easy_faqs_vars.feedback_thank_you_message + '</p>');
		});
		
		
	};
	
	init_feedback();
	
	
};

jQuery(easy_faqs_js);