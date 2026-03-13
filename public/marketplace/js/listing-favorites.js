(function () {
  const FAVORITES_KEY = "mariachi_market_favorites_v1";

  function extractNumericId(value) {
    const match = String(value || "").match(/(\d+)$/);
    return match ? Number(match[1]) : 0;
  }

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

  function hasFavorite(favorites, id) {
    if (favorites.has(id)) {
      return true;
    }

    const numericId = extractNumericId(id);
    if (!numericId) {
      return false;
    }

    return Array.from(favorites).some(function (value) {
      return extractNumericId(value) === numericId;
    });
  }

  function removeFavoriteAliases(favorites, id) {
    const numericId = extractNumericId(id);

    Array.from(favorites).forEach(function (value) {
      if (value === id) {
        favorites.delete(value);
        return;
      }

      if (numericId && extractNumericId(value) === numericId) {
        favorites.delete(value);
      }
    });
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
        removeFavoriteAliases(favorites, id);
        favorites.add(id);
      }

      setButtonState(button, hasFavorite(favorites, id));

      button.addEventListener("click", function () {
        const nextActive = !hasFavorite(favorites, id);

        if (nextActive) {
          removeFavoriteAliases(favorites, id);
          favorites.add(id);
        } else {
          removeFavoriteAliases(favorites, id);
        }

        setStoredFavorites(favorites);
        document.querySelectorAll(`[data-listing-favorite="${id}"]`).forEach((node) => {
          setButtonState(node, nextActive);
        });
        document.dispatchEvent(new CustomEvent("favorites:changed"));
        syncFavorite(button, nextActive);
        showToast(nextActive ? "Guardado en favoritos" : "Quitado de favoritos");
      });
    });

    setStoredFavorites(favorites);
  });
})();
