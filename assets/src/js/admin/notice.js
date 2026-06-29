document.addEventListener('DOMContentLoaded', function () {
	var notice = document.querySelector('.plausible-analytics-multilang-notice .notice-dismiss');

	if (!notice) {
		return;
	}

	notice.addEventListener('click', function () {
		var form = new FormData();
		form.append('action', 'plausible_analytics_dismiss_multilang_notice');
		form.append('_nonce', plausible_analytics_notice.nonce);
		fetch(ajaxurl, {method: 'POST', body: form});
	});
});
