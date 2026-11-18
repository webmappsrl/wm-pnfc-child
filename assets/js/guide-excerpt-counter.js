/**
 * Contatore dei caratteri sotto il campo «Riassunto» delle guide (oc:8940).
 * Il Riassunto compare nella card dei contatti e Yoast lo usa come meta description: si consiglia
 * di restare intorno ai 150 caratteri, senza impedire di scriverne di più.
 *
 * In WordPress 6.8 il Riassunto si scrive in un popover della barra laterale, dove lo slot
 * PluginPostExcerpt non arriva, e il testo passa all'editor solo all'uscita dal campo: il
 * contatore si aggancia quindi al campo quando compare e conta quello che c'è scritto.
 */
(function (wp) {
	if (!wp || !wp.i18n || !wp.domReady) {
		return;
	}
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var RECOMMENDED = 150;
	var FIELD = 'textarea.editor-post-excerpt__textarea, .editor-post-excerpt textarea';

	function render(field) {
		var counter = field.parentNode.querySelector('.wm-pnfc-excerpt-counter');
		if (!counter) {
			counter = document.createElement('p');
			counter.className = 'wm-pnfc-excerpt-counter';
			counter.setAttribute('aria-live', 'polite');
			counter.style.margin = '8px 0 0';
			counter.style.fontSize = '12px';
			field.insertAdjacentElement('afterend', counter);
			field.addEventListener('input', function () {
				render(field);
			});
		}
		// Array.from conta i caratteri, non le unità UTF-16
		var length = Array.from(field.value || '').length;
		var text = sprintf(
			/* translators: 1: caratteri scritti, 2: caratteri consigliati */
			__('%1$d caratteri · consigliati circa %2$d', 'wm-pnfc-child'),
			length,
			RECOMMENDED
		);
		// Si scrive solo se cambia: ogni scrittura è una mutazione che il MutationObserver vede
		if (counter.textContent !== text) {
			counter.textContent = text;
			counter.style.color = length > RECOMMENDED ? '#b32d2e' : '#757575';
		}
	}

	function update() {
		document.querySelectorAll(FIELD).forEach(render);
	}

	wp.domReady(function () {
		new MutationObserver(update).observe(document.body, { childList: true, subtree: true });
		update();
	});
})(window.wp);
