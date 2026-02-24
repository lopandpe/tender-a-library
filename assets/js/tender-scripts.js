import "../scss/tender-styles.scss";

jQuery(document).ready(function ($) {
  /**
   * Limpia y abre el modal de confirmación con un payload auto-contenido.
   * @param {string} message - Texto a mostrar en el modal
   * @param {object} payload - Objeto con:
   *   - ajax_action: string (hook de admin-ajax.php)
   *   - ...otros params específicos (res_id, nonce, lending_id, action_type, etc.)
   *   - reloadOnSuccess?: boolean (opcional)
   */
  function openConfirmationModal(message, payload) {
    // Mensaje
    $("#modal-message").text(message);

    // Limpia estados previos
    $("#confirm-action").removeData();
    $("#accept-action").removeData();

    // Guarda el payload de esta operación
    $("#confirm-action").data("payload", payload);

    // Botones visibilidad
    $("#accept-action").hide().data("reload", false);
    $("#confirm-action, #cancel-action").show();

    // Abre modal
    $("#tal-confirmation-modal").show();
  }

  /**
   * FLUJO 1: Devolver / Renovar préstamo
   * Botones esperados:
   *   <button class="lending-return" data-id="123" data-action="return">...</button>
   *   <button class="lending-renew"  data-id="123" data-action="renew">...</button>
   */
  $(document).on("click", ".lending-return, .lending-renew", function () {
    const lendingId = $(this).data("id");
    const actionType = $(this).data("action"); // "return" | "renew"

    const message =
      actionType === "return"
        ? "¿Estás seguro de que quieres devolver este libro?"
        : "¿Estás seguro de que quieres renovar este préstamo?";

    openConfirmationModal(message, {
      ajax_action: "tender_handle_lending_action", // hook PHP
      lending_id: lendingId,
      action_type: actionType,
      nonce: tender.lending_action_nonce || "",
      reloadOnSuccess: true
    });
  });

  /**
   * FLUJO 2: Cancelar reserva
   * Botón esperado:
   *   <button class="tender-cancel-reservation" data-res-id="55" data-nonce="...">Cancelar</button>
   */
  $(document).on("click", ".tender-cancel-reservation", function () {
    const resId = $(this).data("res-id");
    const nonce = $(this).data("nonce");

    openConfirmationModal("¿Estás seguro de que quieres cancelar esta reserva?", {
      ajax_action: "tender_cancel_reservation", // hook PHP
      res_id: resId,
      nonce: nonce,
      reloadOnSuccess: true
    });
  });

  /**
   * CONFIRMAR (para cualquiera de los flujos)
   */
  $(document).on("click", "#confirm-action", function () {
    const payload = $(this).data("payload") || {};
    if (!payload.ajax_action) {
      $("#modal-message").text("Acción no definida.");
      $("#confirm-action, #cancel-action").hide();
      $("#accept-action").show();
      return;
    }

    // Construimos los datos de POST
    const postData = { action: payload.ajax_action };
    Object.keys(payload).forEach((k) => {
      if (k !== "ajax_action") postData[k] = payload[k];
    });

    // Evitar doble click
    $("#confirm-action").prop("disabled", true);

    $.post(tender.ajax_url, postData)
      .done(function (response) {
        const msg =
          response && response.data && response.data.message
            ? response.data.message
            : "Operación completada.";
        $("#modal-message").text(msg);

        const reloadFlag =
          (response && response.data && response.data.reload) ||
          payload.reloadOnSuccess ||
          false;

        $("#accept-action").data("reload", !!reloadFlag);
      })
      .fail(function (xhr) {
        const fallback =
          (xhr && xhr.responseText) || "Ocurrió un error. No se recibió respuesta del servidor.";
        $("#modal-message").text(fallback);
        $("#accept-action").data("reload", false);
      })
      .always(function () {
        $("#confirm-action").prop("disabled", false).hide();
        $("#cancel-action").hide();
        $("#accept-action").show();
      });
  });

  /**
   * CANCELAR modal
   */
  $(document).on("click", "#cancel-action", function () {
    $("#tal-confirmation-modal").hide();
    // Limpieza opcional
    $("#confirm-action").removeData();
    $("#accept-action").removeData();
  });

  /**
   * ACEPTAR después de la respuesta
   */
  $(document).on("click", "#accept-action", function () {
    const shouldReload = !!$(this).data("reload");
    $("#tal-confirmation-modal").hide();
    if (shouldReload) window.location.reload();
  });
});
