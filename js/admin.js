jQuery(document).ready( function($) {
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
				if (gallery_urls.length > 0)
					jQuery('div#'+image_frame_id+'-preview').append('<img style="max-width:'+(image_max_width!=''?image_max_width:190)+'px" src="'+gallery_urls[0]+'" />');
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