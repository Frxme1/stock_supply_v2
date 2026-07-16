(function($) {

	var FLBuilderAddonsInstaller = {

		init: function() {

			$('.fl-installer-addon-activate').on('click', function(e) {
				e.preventDefault();
				var wrap = $(this),
					type = $(this).data('type'),
					slug = $(this).data('slug');

				data = {
					'action': 'fl_addons_activate',
					'type': type,
					'slug': slug,
					'_wpnonce': $('.subscription-downloads').find('#_wpnonce').val()
				}
				wrap.html('<strong>Please Wait</strong>');
				$.post(ajaxurl, data, function(response) {
					if (response.success) {
						wrap.fadeOut();
						wrap.replaceWith('<em>' + bb_addon_data.installed + '</em>').fadeIn();
						new Notify({
							status: 'success',
							title: bb_addon_data.activated,
							autoclose: true,
							autotimeout: 1000,
						}, location.reload() );
					}
				});
			});

			$('.fl-installer-addon').each(function() {
				$(this).on('click', function(e) {
					e.preventDefault();
					var wrap = $(this),
						type = $(this).data('type'),
						slug = $(this).data('slug'),
						url = 'plugin' === type ? bb_addon_data.plugins_url : bb_addon_data.themes_url
					data = {
						'action': 'fl_addons_install',
						'type': type,
						'slug': slug,
						'_wpnonce': $('.subscription-downloads').find('#_wpnonce').val()
					}
					wrap.html('<strong>' + bb_addon_data.wait + '</strong>');
					$.post(ajaxurl, data, function(response) {
							if (response.success) {
								wrap.fadeOut();

								if ('plugin' === type) {
									if ( 'bb-theme-builder' === data.slug ) {
										wrap.replaceWith('<a class="fl-installer-addon-activate" data-type="plugin" data-slug="bb-theme-builder/bb-theme-builder.php" href="#">Activate</a>');
									} else {
										wrap.replaceWith('<em>' + bb_addon_data.installed + '</em>').fadeIn()
									}
								} else {
									wrap.replaceWith('<a href="' + url + '">' + bb_addon_data.activate + '</a>').fadeIn();
								}

								new Notify({
									status: 'success',
									title: bb_addon_data.installed,
									autoclose: true,
									autotimeout: 1000,
								});
								if ('bb-theme-child' === data.slug) {
									setTimeout(function(){
										window.location.reload();
									}, 1500);
								}
							} else {
								// build message
								var msg = '';
								$.each(response.data, function(i, e) {
									msg += e.message
								})
								new Notify({
									status: 'error',
									title: msg,
									autoclose: true,
									autotimeout: 5000,
								});
								wrap.html(bb_addon_data.install);
							}
						})
						.fail(function() {
							new Notify({
								status: 'error',
								title: 'Install Error',
								autoclose: true,
								autotimeout: 5000,
							});
							wrap.html(bb_addon_data.install);
						});
				})
			});
		},
	}

	$(function() {
		new FLBuilderAddonsInstaller.init();
	})
})(jQuery);
