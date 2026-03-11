(function () {
  const FAVORITES_KEY = "mariachi_market_favorites_v1";

  function getStoredFavorites() {
    try {
      const raw = window.localStorage.getItem(FAVORITES_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return new Set(Array.isArray(parsed) ? parsed.map(String) : []);
    } catch (_error) {
      return new Set();
    }
  }

  function setStoredFavorites(favorites) {
    try {
      window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(Array.from(favorites)));
    } catch (_error) {
      // noop
    }
  }

  function ensureToast() {
    let toast = document.querySelector("[data-listing-favorite-toast]");
    if (toast) {
      return toast;
    }

    toast = document.createElement("div");
    toast.className = "listing-favorite-toast";
    toast.setAttribute("data-listing-favorite-toast", "true");
    document.body.appendChild(toast);
    return toast;
  }

  function showToast(message) {
    const toast = ensureToast();
    toast.textContent = message;
    toast.classList.add("is-visible");

    window.clearTimeout(showToast.timeoutId);
    showToast.timeoutId = window.setTimeout(function () {
      toast.classList.remove("is-visible");
    }, 1800);
  }

  function syncFavorite(button, active) {
    const storeUrl = button.getAttribute("data-sync-store-url") || "";
    const destroyUrl = button.getAttribute("data-sync-destroy-url") || "";
    const url = active ? storeUrl : destroyUrl;

    if (!url) {
      return;
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content") || "";

    fetch(url, {
      method: active ? "POST" : "DELETE",
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
        "X-CSRF-TOKEN": token,
      },
    }).catch(function () {
      showToast(active ? "Guardado en este navegador" : "Actualizado en este navegador");
    });
  }

  function setButtonState(button, active) {
    const label = button.querySelector("[data-listing-favorite-label]");
    const icon = button.querySelector("[data-listing-favorite-icon]");

    button.classList.toggle("is-active", active);
    button.setAttribute("aria-pressed", active ? "true" : "false");
    button.setAttribute("aria-label", active ? "Quitar de favoritos" : "Guardar en favoritos");

    if (label) {
      label.textContent = active ? "Guardado" : "Guardar";
    }

    if (icon) {
      icon.setAttribute("fill", active ? "currentColor" : "none");
    }
  }

  document.addEventListener("DOMContentLoaded", function () {
    const buttons = Array.from(document.querySelectorAll("[data-listing-favorite]"));
    if (!buttons.length) {
      return;
    }

    const favorites = getStoredFavorites();

    buttons.forEach((button) => {
      const id = button.getAttribute("data-listing-favorite");
      if (!id) {
        return;
      }

      if (button.getAttribute("data-initial-favorited") === "true") {
        favorites.add(id);
      }

      setButtonState(button, favorites.has(id));

      button.addEventListener("click", function () {
        const nextActive = !favorites.has(id);

        if (nextActive) {
          favorites.add(id);
        } else {
          favorites.delete(id);
        }

        setStoredFavorites(favorites);
        document.querySelectorAll(`[data-listing-favorite="${id}"]`).forEach((node) => {
          setButtonState(node, nextActive);
        });
        syncFavorite(button, nextActive);
        showToast(nextActive ? "Guardado en favoritos" : "Quitado de favoritos");
      });
    });

    setStoredFavorites(favorites);
  });
})();
