console.log("¡Pedro es el mejor!");

import "../scss/tender-styles.scss";

jQuery(document).ready(function ($) {
	$(document).on("click", ".lending-return, .lending-renew", function () {
		let lendingId = $(this).data("id");
		let action = $(this).data("action");
		let message =
			action === "return"
				? "¿Estás seguro de que quieres devolver este libro?"
				: "¿Estás seguro de que quieres renovar este préstamo?";

		$("#modal-message").text(message);
		$("#confirm-action")
			.data("lending-id", lendingId)
			.data("action", action);
		$("#accept-action").hide();
		$("#confirm-action, #cancel-action").show();
		$("#confirmation-modal").show();
		console.log(lendingId);
	});

	// Confirmar acción en el modal
	$(document).on("click", "#confirm-action", function () {
		let lendingId = $(this).data("lending-id");
		let action = $(this).data("action");

		$.post(tender.ajax_url, {
			action: "tender_handle_lending_action",
			lending_id: lendingId,
			action_type: action,
		})
			.done(function (response) {
				console.log(response);
				$("#modal-message").text(response.data.message);
			})
			.fail(function () {
				$("#modal-message").text(
					"Ocurrió un error. No se recibió respuesta del servidor."
				);
			})
			.always(function () {
				$("#confirm-action, #cancel-action").hide();
				$("#accept-action").show();
			});
	});

	// Cancelar acción en el modal
	$(document).on("click", "#cancel-action", function () {
		$("#confirmation-modal").hide();
	});

	// Aceptar acción después de la respuesta
	$(document).on("click", "#accept-action", function () {
		$("#confirmation-modal").hide();
		location.reload();
	});
});
