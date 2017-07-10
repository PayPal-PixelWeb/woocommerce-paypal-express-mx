function ppexpress_remove_img(id_input, id_img) {
	jQuery(id_input).val('');
	jQuery('*', jQuery(id_img).closest( 'div' )).remove();
}
jQuery(document).ready( function($) {
	var pplatam_mark_fields      = '#woocommerce_ppexpress_latam_title, #woocommerce_ppexpress_latam_description';
	var pplatam_live_fields      = '#woocommerce_ppexpress_latam_api_username, #woocommerce_ppexpress_latam_api_password, #woocommerce_ppexpress_latam_api_signature, #woocommerce_ppexpress_latam_api_certificate, #woocommerce_ppexpress_latam_api_subject';
	var pplatam_sandbox_fields   = '#woocommerce_ppexpress_latam_sandbox_api_username, #woocommerce_ppexpress_latam_sandbox_api_password, #woocommerce_ppexpress_latam_sandbox_api_signature, #woocommerce_ppexpress_latam_sandbox_api_certificate, #woocommerce_ppexpress_latam_sandbox_api_subject';

	var enable_toggle         = $( 'a.ppexpress_latam-toggle-settings' ).length > 0 && jQuery('#woocommerce_ppexpress_latam_api_username').val() == '';
	var enable_sandbox_toggle = $( 'a.ppexpress_latam-toggle-sandbox-settings' ).length > 0 && jQuery('#woocommerce_ppexpress_latam_sandbox_api_username').val() == '';

	$( '#woocommerce_ppexpress_latam_environment' ).change(function() {
		$( pplatam_sandbox_fields + ',' + pplatam_live_fields ).closest( 'tr' ).hide();

		if ( 'live' === $( this ).val() ) {
			$( '#woocommerce_ppexpress_latam_api_credentials, #woocommerce_ppexpress_latam_api_credentials + p' ).show();
			$( '#woocommerce_ppexpress_latam_sandbox_api_credentials, #woocommerce_ppexpress_latam_sandbox_api_credentials + p' ).hide();
			if ( jQuery('#woocommerce_ppexpress_latam_api_username').val() != '' ) {
				$( '#ppexpress_display' ).hide();
			} else {
				$( '#ppexpress_display' ).show();
			}
			if ( ! enable_toggle ) {
				$( pplatam_live_fields ).closest( 'tr' ).show();
			}
		} else {
			$( '#woocommerce_ppexpress_latam_api_credentials, #woocommerce_ppexpress_latam_api_credentials + p' ).hide();
			$( '#woocommerce_ppexpress_latam_sandbox_api_credentials, #woocommerce_ppexpress_latam_sandbox_api_credentials + p' ).show();
			if ( jQuery('#woocommerce_ppexpress_latam_sandbox_api_username').val() != '' ) {
				$( '#ppexpress_display_sandbox' ).hide();
			} else {
				$( '#ppexpress_display_sandbox' ).show();
			}
			if ( ! enable_sandbox_toggle ) {
				$( pplatam_sandbox_fields ).closest( 'tr' ).show();
			}
		}
	}).change();

	$( '#woocommerce_ppexpress_latam_mark_enabled' ).change(function(){
		if ( $( this ).is( ':checked' ) ) {
			$( pplatam_mark_fields ).closest( 'tr' ).show();
		} else {
			$( pplatam_mark_fields ).closest( 'tr' ).hide();
		}
	}).change();

	$( '#woocommerce_ppexpress_latam_paymentaction' ).change(function(){
		if ( 'sale' === $( this ).val() ) {
			$( '#woocommerce_ppexpress_latam_instant_payments' ).closest( 'tr' ).show();
		} else {
			$( '#woocommerce_ppexpress_latam_instant_payments' ).closest( 'tr' ).hide();
		}
	}).change();

	if ( enable_toggle ) {
		$( document ).on( 'click', '.ppexpress_latam-toggle-settings', function( e ) {
			$( pplatam_live_fields ).closest( 'tr' ).toggle( 'fast' );
			e.preventDefault();
		} );
	}
	if ( enable_sandbox_toggle ) {
		$( document ).on( 'click', '.ppexpress_latam-toggle-sandbox-settings', function( e ) {
			$( pplatam_sandbox_fields ).closest( 'tr' ).toggle( 'fast' );
			e.preventDefault();
		} );
	}
	jQuery('input.pp_media_manager').click(function(e) {
		e.preventDefault();
		var image_frame = $(this).data('media_manager');
		var image_frame_id = $(this).attr('data-dest-id');
		var image_max_width = $(this).attr('data-max-width');
		if (!image_frame) {
			// Define image_frame as wp.media object
			image_frame = wp.media({
				title: 'Select Media',
				multiple : false,
				library : {
					type : 'image',
				}
			});
			image_frame.on('close',function() {
				// On close, get selections and save to the hidden input
				// plus other AJAX stuff to refresh the image preview
				var selection =  image_frame.state().get('selection');
				var gallery_ids = new Array();
				var gallery_urls = new Array();
				var my_index = 0;
				selection.each(function(attachment) {
					gallery_ids[my_index] = attachment['id'];
					gallery_urls[my_index] = attachment.attributes.url;
					my_index++;
				});
				var ids = gallery_ids.join(",");
				jQuery('input#'+image_frame_id).val(ids);
				jQuery('div#'+image_frame_id+'-preview img').remove();
				if (gallery_urls.length > 0 && ids != '' && gallery_urls[0] != '')
					jQuery('div#'+image_frame_id+'-preview').append('<img id="img_'+image_frame_id+'" style="max-width:'+(image_max_width!=''?image_max_width:190)+'px" src="'+gallery_urls[0]+'" /><br /><a style="color: #bc0b0b;" href="javascript:void(ppexpress_remove_img(\'input#'+image_frame_id+'\',\'img#img_'+image_frame_id+'\'));">'+ppexpress_lang_remove+'</a>');
				else {
					jQuery('input#'+image_frame_id).val('');
					jQuery('div#'+image_frame_id+'-preview *').remove();
				}
			});
			image_frame.on('open',function() {
				// On open, get the id from the hidden input
				// and select the appropiate images in the media manager
				var selection =  image_frame.state().get('selection');
				ids = jQuery('input#'+image_frame_id).val().split(',');
				ids.forEach(function(id) {
					attachment = wp.media.attachment(id);
					attachment.fetch();
					selection.add( attachment ? [ attachment ] : [] );
				});
			});
			$(this).data('media_manager', image_frame);
		}
		image_frame.open();
	 });

});