(function () {
	'use strict';

	var authorKey = 'tender_book_author';
	var sig1Key = 'tender_book_sig1';
	var sig2Key = 'tender_book_sig2';

	function removeAccents(value) {
		return (value || '')
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	function generateSig1(author) {
		var clean = removeAccents(author).trim();
		if (!clean || clean.length < 3) {
			return '';
		}
		return clean.substring(0, 3).toUpperCase();
	}

	function generateSig2(title) {
		var clean = removeAccents(title).toLowerCase();
		clean = clean.replace(/^(el|lo|la|los|las|un|una|uno|unos|the|a|si)\b\s*/i, '');
		clean = clean.trim();
		if (!clean || clean.length < 3) {
			return '';
		}
		return clean.substring(0, 3).toLowerCase();
	}

	function findFieldByKey(key) {
		var selectors = [
			'input[name*="' + key + '"]',
			'textarea[name*="' + key + '"]',
			'[data-name="' + key + '"] input',
			'[data-name="' + key + '"] textarea'
		];

		for (var i = 0; i < selectors.length; i++) {
			var element = document.querySelector(selectors[i]);
			if (element) {
				return element;
			}
		}

		return null;
	}

	function getTitleField() {
		return document.getElementById('title');
	}

	function isEmptyField(field) {
		return !!field && !(field.value || '').trim();
	}

	function autofillSignatureIfEmpty() {
		var authorField = findFieldByKey(authorKey);
		var sig1Field = findFieldByKey(sig1Key);
		var sig2Field = findFieldByKey(sig2Key);
		var titleField = getTitleField();

		if (!sig1Field || !sig2Field) {
			return;
		}

		if (isEmptyField(sig1Field) && authorField && (authorField.value || '').trim()) {
			sig1Field.value = generateSig1(authorField.value);
			sig1Field.dispatchEvent(new Event('input', { bubbles: true }));
			sig1Field.dispatchEvent(new Event('change', { bubbles: true }));
		}

		if (isEmptyField(sig2Field) && titleField && (titleField.value || '').trim()) {
			sig2Field.value = generateSig2(titleField.value);
			sig2Field.dispatchEvent(new Event('input', { bubbles: true }));
			sig2Field.dispatchEvent(new Event('change', { bubbles: true }));
		}
	}

	function bindAutofillEvents() {
		var authorField = findFieldByKey(authorKey);
		var titleField = getTitleField();
		var postForm = document.getElementById('post');

		if (authorField) {
			authorField.addEventListener('input', autofillSignatureIfEmpty);
			authorField.addEventListener('blur', autofillSignatureIfEmpty);
		}

		if (titleField) {
			titleField.addEventListener('input', autofillSignatureIfEmpty);
			titleField.addEventListener('blur', autofillSignatureIfEmpty);
		}

		if (postForm) {
			postForm.addEventListener('submit', autofillSignatureIfEmpty);
		}
	}

	document.addEventListener('DOMContentLoaded', function () {
		autofillSignatureIfEmpty();
		bindAutofillEvents();
		window.setTimeout(autofillSignatureIfEmpty, 300);
		window.setTimeout(autofillSignatureIfEmpty, 800);
	});
})();
