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

eval("__webpack_require__.r(__webpack_exports__);\n/* harmony import */ var _scss_tender_styles_scss__WEBPACK_IMPORTED_MODULE_0__ = __webpack_require__(/*! ../scss/tender-styles.scss */ \"./assets/scss/tender-styles.scss\");\n\njQuery(document).ready(function ($) {\n  /**\n   * Limpia y abre el modal de confirmación con un payload auto-contenido.\n   * @param {string} message - Texto a mostrar en el modal\n   * @param {object} payload - Objeto con:\n   *   - ajax_action: string (hook de admin-ajax.php)\n   *   - ...otros params específicos (res_id, nonce, lending_id, action_type, etc.)\n   *   - reloadOnSuccess?: boolean (opcional)\n   */\n  function openConfirmationModal(message, payload) {\n    // Mensaje\n    $(\"#modal-message\").text(message);\n\n    // Limpia estados previos\n    $(\"#confirm-action\").removeData();\n    $(\"#accept-action\").removeData();\n\n    // Guarda el payload de esta operación\n    $(\"#confirm-action\").data(\"payload\", payload);\n\n    // Botones visibilidad\n    $(\"#accept-action\").hide().data(\"reload\", false);\n    $(\"#confirm-action, #cancel-action\").show();\n\n    // Abre modal\n    $(\"#tal-confirmation-modal\").show();\n  }\n\n  /**\n   * FLUJO 1: Devolver / Renovar préstamo\n   * Botones esperados:\n   *   <button class=\"lending-return\" data-id=\"123\" data-action=\"return\">...</button>\n   *   <button class=\"lending-renew\"  data-id=\"123\" data-action=\"renew\">...</button>\n   */\n  $(document).on(\"click\", \".lending-return, .lending-renew\", function () {\n    const lendingId = $(this).data(\"id\");\n    const actionType = $(this).data(\"action\"); // \"return\" | \"renew\"\n\n    const message = actionType === \"return\" ? \"¿Estás seguro de que quieres devolver este libro?\" : \"¿Estás seguro de que quieres renovar este préstamo?\";\n    openConfirmationModal(message, {\n      ajax_action: \"tender_handle_lending_action\",\n      // hook PHP\n      lending_id: lendingId,\n      action_type: actionType,\n      nonce: tender.lending_action_nonce || \"\",\n      reloadOnSuccess: true\n    });\n  });\n\n  /**\n   * FLUJO 2: Cancelar reserva\n   * Botón esperado:\n   *   <button class=\"tender-cancel-reservation\" data-res-id=\"55\" data-nonce=\"...\">Cancelar</button>\n   */\n  $(document).on(\"click\", \".tender-cancel-reservation\", function () {\n    const resId = $(this).data(\"res-id\");\n    const nonce = $(this).data(\"nonce\");\n    openConfirmationModal(\"¿Estás seguro de que quieres cancelar esta reserva?\", {\n      ajax_action: \"tender_cancel_reservation\",\n      // hook PHP\n      res_id: resId,\n      nonce: nonce,\n      reloadOnSuccess: true\n    });\n  });\n\n  /**\n   * CONFIRMAR (para cualquiera de los flujos)\n   */\n  $(document).on(\"click\", \"#confirm-action\", function () {\n    const payload = $(this).data(\"payload\") || {};\n    if (!payload.ajax_action) {\n      $(\"#modal-message\").text(\"Acción no definida.\");\n      $(\"#confirm-action, #cancel-action\").hide();\n      $(\"#accept-action\").show();\n      return;\n    }\n\n    // Construimos los datos de POST\n    const postData = {\n      action: payload.ajax_action\n    };\n    Object.keys(payload).forEach(k => {\n      if (k !== \"ajax_action\") postData[k] = payload[k];\n    });\n\n    // Evitar doble click\n    $(\"#confirm-action\").prop(\"disabled\", true);\n    $.post(tender.ajax_url, postData).done(function (response) {\n      const msg = response && response.data && response.data.message ? response.data.message : \"Operación completada.\";\n      $(\"#modal-message\").text(msg);\n      const reloadFlag = response && response.data && response.data.reload || payload.reloadOnSuccess || false;\n      $(\"#accept-action\").data(\"reload\", !!reloadFlag);\n    }).fail(function (xhr) {\n      const fallback = xhr && xhr.responseText || \"Ocurrió un error. No se recibió respuesta del servidor.\";\n      $(\"#modal-message\").text(fallback);\n      $(\"#accept-action\").data(\"reload\", false);\n    }).always(function () {\n      $(\"#confirm-action\").prop(\"disabled\", false).hide();\n      $(\"#cancel-action\").hide();\n      $(\"#accept-action\").show();\n    });\n  });\n\n  /**\n   * CANCELAR modal\n   */\n  $(document).on(\"click\", \"#cancel-action\", function () {\n    $(\"#tal-confirmation-modal\").hide();\n    // Limpieza opcional\n    $(\"#confirm-action\").removeData();\n    $(\"#accept-action\").removeData();\n  });\n\n  /**\n   * ACEPTAR después de la respuesta\n   */\n  $(document).on(\"click\", \"#accept-action\", function () {\n    const shouldReload = !!$(this).data(\"reload\");\n    $(\"#tal-confirmation-modal\").hide();\n    if (shouldReload) window.location.reload();\n  });\n});\n\n//# sourceURL=webpack://tender-a-library/./assets/js/tender-scripts.js?");

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