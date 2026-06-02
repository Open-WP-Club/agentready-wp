/* Agent Ready admin JS */
(function ($) {
	'use strict';

	// Auto-format JSON textarea on blur
	$(document).on('blur', '.ar-textarea--json', function () {
		var ta = this;
		try {
			var parsed = JSON.parse(ta.value);
			ta.value = JSON.stringify(parsed, null, 2);
		} catch (e) {
			// leave as-is; server will report the error
		}
	});

})(jQuery);
