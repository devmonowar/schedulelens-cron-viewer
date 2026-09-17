document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('.schedulelens-act').forEach(function (el) {
		el.addEventListener('click', function (e) {
			var kind = el.getAttribute('data-confirm');
			var msg = '';
			if (window.schedulelensData) {
				if ('pause' === kind) {
					msg = window.schedulelensData.confirmPause;
				} else if ('delete' === kind) {
					msg = window.schedulelensData.confirmDelete;
				} else if ('run' === kind) {
					msg = window.schedulelensData.confirmRun;
				}
			}
			if (msg && !window.confirm(msg)) {
				e.preventDefault();
			}
		});
	});
});
