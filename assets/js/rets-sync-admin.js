(function( $ ){

	'use strict';

	var RETSSyncAdmin = window.RETSSyncAdmin || {};

	RETSSyncAdmin = {

		/**
		 * Document Ready Events
		 */
		init: function(){
			this.image_sync_button();
			this.details_sync_button();
			this.update_queue_total();
			this.credentials_toggler();
			this.images_toggler();
		}, // init

		image_sync_button: function(){
			if( ! $('.rets-sync-images').length )
				return;

			$('.rets-sync-images').on('click', function(e){
				e.preventDefault();
				var post_id = $('#post_ID').val(),
					$button = $(this),
					original_text = $button.html();

				$button.html( $('<span class="spinner" />').css('visibility','visible') );

				$.ajax({
					url: rets_sync_vars.ajax_url,
					type: 'post',
					data: { action: 'rets_sync_image', post_id: post_id },
					success: function( response ){
						if( response.result ){
							$button.html( '<strong>Success!</strong> Attempting to refresh page...' );
							location.reload();
						} else if( response.result === 0 ) {
							$button.html( 'No valid images found.' );
						} else {
							if( response.error ){
								$button.html( response.error );
							} else {
								$button.html( 'Sync failed.' );
							}
						}
					}
				});
			});
		}, // image_sync_button

		details_sync_button: function(){
			if( ! $('.rets-sync-details').length )
				return;

			$('.rets-sync-details').on('click', function(e){
				e.preventDefault();
				var post_id = $('#post_ID').val(),
					$button = $(this),
					original_text = $button.html();

				$button.html( $('<span class="spinner" />').css('visibility','visible') );

				$.ajax({
					url: rets_sync_vars.ajax_url,
					type: 'post',
					data: { action: 'rets_sync_details', post_id: post_id },
					success: function( response ){
						if( response.result ){
							$button.html( '<strong>Success!</strong> Attempting to refresh page...' );
							location.reload();
						} else {
							if( response.error ){
								$button.html( response.error );
							} else {
								$button.html( 'Sync failed.' );
							}
						}
					}
				});
			});
		}, // details_sync_button

		update_queue_total: function(){
			if( ! $('.queue-total').length )
				return;

			var _self = this;
			$.ajax({
				url: rets_sync_vars.ajax_url,
				type: 'get',
				data: { action: 'rets_sync_get_queue_total' },
				success: function( response ){
					if( response && ! response.error ){
						$('.queue-total').html( response.total );
						if( response.last_sync ){
							$('.last-sync').html( response.last_sync );
						}
					}

					setTimeout( function(){
						_self.update_queue_total();
					}, 3000);
				}
			});
		}, // update_queue_total

		credentials_toggler: function(){
			if( ! $('.credentials-type').length )
				return;

			$('.credentials-type').on('click', function(){
				if( $('.credentials-type:checked').val() == 'custom' ){
					$('.custom-db').show();
				} else {
					$('.custom-db').hide();
				}
			});
		}, // credentials_toggler

		images_toggler: function(){
			if( ! $('.images-source').length )
				return;

			$('.images-source').on('click', function(){
				$('p.images-' + $(this).val() ).toggle();
			});
		}, // images_toggler
	};

	$(function(){
		RETSSyncAdmin.init();
	});

})( jQuery );
