(function () {
  function firstErrorMessage(payload) {
    if (!payload || typeof payload !== "object") {
      return "No fue posible enviar tu solicitud. Inténtalo de nuevo.";
    }

    if (typeof payload.message === "string" && payload.message.trim() !== "") {
      return payload.message;
    }

    if (payload.errors && typeof payload.errors === "object") {
      const firstKey = Object.keys(payload.errors)[0];
      if (firstKey && Array.isArray(payload.errors[firstKey]) && payload.errors[firstKey][0]) {
        return String(payload.errors[firstKey][0]);
      }
    }

    return "No fue posible enviar tu solicitud. Inténtalo de nuevo.";
  }

  document.addEventListener("DOMContentLoaded", function () {
    const modal = document.querySelector("[data-lead-modal]");
    const openButtons = Array.from(document.querySelectorAll("[data-open-lead-modal]"));

    if (!modal || !openButtons.length) {
      return;
    }

    const form = modal.querySelector("[data-lead-form]");
    const closeButtons = Array.from(modal.querySelectorAll("[data-lead-modal-close]"));
    const errorNode = modal.querySelector("[data-lead-error]");
    const successNode = modal.querySelector("[data-lead-success]");
    const submitButton = modal.querySelector("[data-lead-submit]");

    if (!form || !submitButton) {
      return;
    }

    const tokenField = form.querySelector('input[name="_token"]');
    const defaults = {
      name: String(form.elements.name?.value || ""),
      email: String(form.elements.email?.value || ""),
      phone: String(form.elements.phone?.value || ""),
      event_city: String(form.elements.event_city?.value || ""),
    };

    function setMessage(node, message) {
      if (!node) {
        return;
      }

      node.textContent = message;
      node.classList.toggle("hidden", message.trim() === "");
    }

    function resetFormToDefaults() {
      form.reset();
      form.elements.name.value = defaults.name;
      form.elements.email.value = defaults.email;
      form.elements.phone.value = defaults.phone;
      if (form.elements.event_city) {
        form.elements.event_city.value = defaults.event_city;
      }
    }

    function openModal() {
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
      setMessage(errorNode, "");
      setMessage(successNode, "");

      window.setTimeout(function () {
        const firstField = form.querySelector("input, textarea");
        if (firstField) {
          firstField.focus();
        }
      }, 60);
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }

    function validateForm() {
      const name = String(form.elements.name?.value || "").trim();
      const email = String(form.elements.email?.value || "").trim();
      const phone = String(form.elements.phone?.value || "").trim();
      const eventDate = String(form.elements.event_date?.value || "").trim();
      const message = String(form.elements.message?.value || "").trim();

      if (!name) return "Escribe tu nombre.";
      if (!email) return "Escribe tu correo.";
      if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) return "Escribe un correo válido.";
      if (!phone) return "Escribe tu teléfono.";
      if (!eventDate) return "Selecciona la fecha de tu evento.";
      if (!message) return "Cuéntanos qué necesitas para tu evento.";

      return "";
    }

    openButtons.forEach((button) => {
      button.addEventListener("click", openModal);
    });

    closeButtons.forEach((button) => {
      button.addEventListener("click", closeModal);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (!modal.classList.contains("hidden") && event.key === "Escape") {
        closeModal();
      }
    });

    form.addEventListener("submit", async function (event) {
      event.preventDefault();

      const validationError = validateForm();
      if (validationError) {
        setMessage(errorNode, validationError);
        setMessage(successNode, "");
        return;
      }

      setMessage(errorNode, "");
      setMessage(successNode, "");
      submitButton.disabled = true;
      submitButton.textContent = "Enviando...";

      try {
        const response = await fetch(form.action, {
          method: "POST",
          headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": tokenField ? tokenField.value : "",
          },
          body: new FormData(form),
        });

        const payload = await response.json().catch(function () {
          return {};
        });

        if (!response.ok) {
          throw new Error(firstErrorMessage(payload));
        }

        resetFormToDefaults();
        setMessage(successNode, String(payload.message || "Tu solicitud fue enviada correctamente."));
      } catch (error) {
        setMessage(errorNode, error instanceof Error ? error.message : "No fue posible enviar tu solicitud.");
      } finally {
        submitButton.disabled = false;
        submitButton.textContent = "Enviar solicitud";
      }
    });
  });
})();
