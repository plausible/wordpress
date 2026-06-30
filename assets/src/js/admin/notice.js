document.addEventListener('DOMContentLoaded', function () {
	document.addEventListener('click', function (e) {
		if (!e.target.closest('.plausible-analytics-multilang-notice .notice-dismiss')) {
			return;
		}

		var form = new FormData();

		form.append('action', 'plausible_analytics_dismiss_multilang_notice');
		form.append('_nonce', plausible_analytics_notice.nonce);
		
		fetch(ajaxurl, {method: 'POST', body: form});
	});
});
