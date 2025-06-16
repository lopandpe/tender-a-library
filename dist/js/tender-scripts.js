/*
 * ATTENTION: The "eval" devtool has been used (maybe by default in mode: "development").
 * This devtool is neither made for production nor for readable output files.
 * It uses "eval()" calls to create a separate source file in the browser devtools.
 * If you are trying to read the output file, select a different devtool (https://webpack.js.org/configuration/devtool/)
 * or disable the default devtool with "devtool: false".
 * If you are looking for production-ready output files, see mode: "production" (https://webpack.js.org/configuration/mode/).
 */
/******/ (() => { // webpackBootstrap
/******/ 	"use strict";
/******/ 	var __webpack_modules__ = ({

/***/ "./assets/js/tender-scripts.js":
/*!*************************************!*\
  !*** ./assets/js/tender-scripts.js ***!
  \*************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _scss_tender_styles_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/tender-styles.scss */ \"./assets/scss/tender-styles.scss\");\nconsole.log(\"¡Pedro es el mejor!\");\n\n\n\njQuery(document).ready(function ($) {\n\t$(document).on(\"click\", \".lending-return, .lending-renew\", function () {\n\t\tlet lendingId = $(this).data(\"id\");\n\t\tlet action = $(this).data(\"action\");\n\t\tlet message =\n\t\t\taction === \"return\"\n\t\t\t\t? \"¿Estás seguro de que quieres devolver este libro?\"\n\t\t\t\t: \"¿Estás seguro de que quieres renovar este préstamo?\";\n\n\t\t$(\"#modal-message\").text(message);\n\t\t$(\"#confirm-action\")\n\t\t\t.data(\"lending-id\", lendingId)\n\t\t\t.data(\"action\", action);\n\t\t$(\"#accept-action\").hide();\n\t\t$(\"#confirm-action, #cancel-action\").show();\n\t\t$(\"#confirmation-modal\").show();\n\t\tconsole.log(lendingId);\n\t});\n\n\t// Confirmar acción en el modal\n\t$(document).on(\"click\", \"#confirm-action\", function () {\n\t\tlet lendingId = $(this).data(\"lending-id\");\n\t\tlet action = $(this).data(\"action\");\n\n\t\t$.post(tender.ajax_url, {\n\t\t\taction: \"tender_handle_lending_action\",\n\t\t\tlending_id: lendingId,\n\t\t\taction_type: action,\n\t\t})\n\t\t\t.done(function (response) {\n\t\t\t\tconsole.log(response);\n\t\t\t\t$(\"#modal-message\").text(response.data.message);\n\t\t\t})\n\t\t\t.fail(function () {\n\t\t\t\t$(\"#modal-message\").text(\n\t\t\t\t\t\"Ocurrió un error. No se recibió respuesta del servidor.\"\n\t\t\t\t);\n\t\t\t})\n\t\t\t.always(function () {\n\t\t\t\t$(\"#confirm-action, #cancel-action\").hide();\n\t\t\t\t$(\"#accept-action\").show();\n\t\t\t});\n\t});\n\n\t// Cancelar acción en el modal\n\t$(document).on(\"click\", \"#cancel-action\", function () {\n\t\t$(\"#confirmation-modal\").hide();\n\t});\n\n\t// Aceptar acción después de la respuesta\n\t$(document).on(\"click\", \"#accept-action\", function () {\n\t\t$(\"#confirmation-modal\").hide();\n\t\tlocation.reload();\n\t});\n});\n\n\n//# sourceURL=webpack://tender-a-library/./assets/js/tender-scripts.js?");

/***/ }),

/***/ "./assets/scss/tender-styles.scss":
/*!****************************************!*\
  !*** ./assets/scss/tender-styles.scss ***!
  \****************************************/
/***/ ((__unused_webpack_module, __webpack_exports__, __webpack_require__) => {

eval("__webpack_require__.r(__webpack_exports__);\n// extracted by mini-css-extract-plugin\n\n\n//# sourceURL=webpack://tender-a-library/./assets/scss/tender-styles.scss?");

/***/ })

/******/ 	});
/************************************************************************/
/******/ 	// The module cache
/******/ 	var __webpack_module_cache__ = {};
/******/ 	
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/ 		// Check if module is in cache
/******/ 		var cachedModule = __webpack_module_cache__[moduleId];
/******/ 		if (cachedModule !== undefined) {
/******/ 			return cachedModule.exports;
/******/ 		}
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = __webpack_module_cache__[moduleId] = {
/******/ 			// no module.id needed
/******/ 			// no module.loaded needed
/******/ 			exports: {}
/******/ 		};
/******/ 	
/******/ 		// Execute the module function
/******/ 		__webpack_modules__[moduleId](module, module.exports, __webpack_require__);
/******/ 	
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/ 	
/************************************************************************/
/******/ 	/* webpack/runtime/make namespace object */
/******/ 	(() => {
/******/ 		// define __esModule on exports
/******/ 		__webpack_require__.r = (exports) => {
/******/ 			if(typeof Symbol !== 'undefined' && Symbol.toStringTag) {
/******/ 				Object.defineProperty(exports, Symbol.toStringTag, { value: 'Module' });
/******/ 			}
/******/ 			Object.defineProperty(exports, '__esModule', { value: true });
/******/ 		};
/******/ 	})();
/******/ 	
/************************************************************************/
/******/ 	
/******/ 	// startup
/******/ 	// Load entry module and return exports
/******/ 	// This entry module can't be inlined because the eval devtool is used.
/******/ 	var __webpack_exports__ = __webpack_require__("./assets/js/tender-scripts.js");
/******/ 	
/******/ })()
;