(function($) {
	function setEachTrNum(e, row = null) {
		var _num = 0;

		$('#attrib-fields table tbody > tr').each(function() {
			if (e.type == 'subform-row-remove') {
				if ($(this).attr('data-group') == $(row).attr('data-group')) --_num;
			}

			$(this).find('td:first-child').html(_num++);
		});
	}

	$(document).on('ready subform-row-add subform-row-remove', function(e, row) {
		if (e.type == 'subform-row-add') {
			$(row).find('td:nth-last-of-type(2) input[type="checkbox"]').prop('checked', true);
		}

		setEachTrNum(e, row);
	});

	$(document).on('mousedown', '#attrib-fields .group-move', function(e) {
		$('body').addClass('on-fields-drag');
	});

	$(document).on('mouseup', 'body.on-fields-drag', function(e) {
		$('body').removeClass('on-fields-drag');

		setTimeout(function() {
			setEachTrNum(e);
		}, 100);
	});

	$(document).ready(function() {
		$('#log-select-all').change(function() {
			var checkboxes = $('#attrib-logs [name="log-select\[\]"]');

			if ($(this).is(':checked')) {
				checkboxes.prop('checked', true);
			} else {
				checkboxes.prop('checked', false);
			}
		});

		$('#log-remove-selected').click(function(e) {
			e.preventDefault();

			var btn = $(this), action,
				base_url = window.location.protocol + '//' + window.location.hostname,
				btn_text_orig = btn.html(),
				selected = $('#attrib-logs [name="log-select\[\]"]:checked');
				list = selected.map(function() {
					return this.value;
				}).get();

			if (list.length > 0) {
				var confirm = window.confirm('REALLY DELETE THIS ELEMENT(S)?'); // TODO: Add lang const

				if (confirm) {
					btn.prop('disabled', true).html('Подождите...'); // TODO: Add lang const

					var request = {
						'option' : 'com_ajax',
						'module' : 'uk_fos_doc',
						'format' : 'raw',
						'method' : 'removeLogs',
						'data'   : list
					};

					$.ajax({
						cache	: false,
						type	: 'post',
						data	: request,
						url		: base_url
					})
					.done(function(data, textStatus, jqXHR) {
						if (data == 'success') {
							selected.each(function(){
								$(this).closest('tr').hide(300, function(){
									$(this).remove();
								});
							});
						} else {
							alert('DATABASE_ERROR'); // TODO: Add lang const
						}

						btn.prop('disabled', false).html(btn_text_orig);
					})
					.fail(function(jqXHR, textStatus, errorThrown) {
						alert(errorThrown);
						btn.prop('disabled', false).html(btn_text_orig);
					});
				}
			}
		});

		$.fn.isInViewport = function() {
			var elTop     = $(this).offset().top;
			var elBottom  = elTop + $(this).outerHeight();
			var vpTop     = $(window).scrollTop() + 100;
			var vpBottom  = vpTop + 30;

			return elBottom > vpTop && elTop < vpBottom;
		};

		var helpID = '#attrib-help';

		$('body').on('shown', '[href="' + helpID + '"]', function(e) {

			var helpPos = $(helpID).offset().top;
			var nav = $('#help-sidebar-menu .nav-list');

			$(window).scroll(function() {

				$(helpID + ' section').each(function() {
					var id = $(this).attr('id');
					var cur_pos = $(this).isInViewport();
					var link = $(helpID + ' a[href="#' + id + '"]').parent('li');

					if (cur_pos && link.not('.active')) {
						$(helpID + ' li').removeClass('active');
						link.addClass('active');
					}
				});

				if ($(document).scrollTop() > helpPos - 100 && nav.not('.affix')) {
					nav.addClass('affix');
				} else {
					nav.removeClass('affix');
				}

			});
		});

		$('body').on('click', '#help-sidebar-menu [href^=#]', function(e) {
			e.preventDefault();

			var hash = $(this).attr('href');
			history.pushState(null, null, hash);

			$('html, body').animate({
				scrollTop: $(hash).offset().top - 100
			}, 700, function() {});

		});
	});
})(jQuery);