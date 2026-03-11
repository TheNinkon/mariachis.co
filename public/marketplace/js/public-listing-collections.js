(function () {
  const FAVORITES_KEY = "mariachi_market_favorites_v1";
  const RECENTS_KEY = "mariachi_market_recent_v1";
  const RECENTS_LIMIT = 18;

  function escapeHtml(value) {
    return String(value || "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getStoredFavorites() {
    try {
      const raw = window.localStorage.getItem(FAVORITES_KEY);
      const parsed = raw ? JSON.parse(raw) : [];
      return Array.isArray(parsed) ? parsed.map(String) : [];
    } catch (_error) {
      return [];
    }
  }

  function getStoredRecents() {
    try {
      const raw = window.localStorage.getItem(RECENTS_KEY);
      const parsed = raw ? JSON.parse(raw) : [];

      if (!Array.isArray(parsed)) {
        return [];
      }

      return parsed
        .map(function (item) {
          if (!item || typeof item !== "object") {
            return null;
          }

          const id = Number(item.id || 0);
          const slug = String(item.slug || "").trim();
          const ts = Number(item.ts || Date.now());

          if (!id || !slug) {
            return null;
          }

          return {
            id,
            slug,
            city: String(item.city || ""),
            title: String(item.title || ""),
            image_url: String(item.image_url || ""),
            price_label: String(item.price_label || ""),
            ts,
          };
        })
        .filter(Boolean)
        .sort(function (left, right) {
          return Number(right.ts || 0) - Number(left.ts || 0);
        });
    } catch (_error) {
      return [];
    }
  }

  function setStoredRecents(items) {
    try {
      window.localStorage.setItem(RECENTS_KEY, JSON.stringify(items.slice(0, RECENTS_LIMIT)));
    } catch (_error) {
      // noop
    }
  }

  function extractFavoriteIds() {
    const ids = [];

    getStoredFavorites().forEach(function (value) {
      const match = String(value).match(/(\d+)$/);
      const id = match ? Number(match[1]) : 0;

      if (id > 0 && !ids.includes(id)) {
        ids.push(id);
      }
    });

    return ids;
  }

  function updateRecentStorage() {
    const payloadNode = document.querySelector("[data-current-listing]");
    if (!payloadNode) {
      return;
    }

    try {
      const payload = JSON.parse(payloadNode.textContent || "{}");
      const id = Number(payload.id || 0);
      const slug = String(payload.slug || "").trim();

      if (!id || !slug) {
        return;
      }

      const current = {
        id,
        slug,
        city: String(payload.city || ""),
        title: String(payload.title || ""),
        image_url: String(payload.image_url || ""),
        price_label: String(payload.price_label || ""),
        ts: Date.now(),
      };

      const next = getStoredRecents().filter(function (item) {
        return Number(item.id) !== id && String(item.slug) !== slug;
      });
      next.unshift(current);
      setStoredRecents(next);
    } catch (_error) {
      // noop
    }
  }

  function resolveListings(endpoint, ids, slugs) {
    if (!endpoint || (!ids.length && !slugs.length)) {
      return Promise.resolve([]);
    }

    const url = new URL(endpoint, window.location.origin);
    if (ids.length) {
      url.searchParams.set("ids", ids.join(","));
    }
    if (slugs.length) {
      url.searchParams.set("slugs", slugs.join(","));
    }

    return fetch(url.toString(), {
      headers: {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
      },
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error("resolve_failed");
        }
        return response.json();
      })
      .then(function (payload) {
        return Array.isArray(payload?.data) ? payload.data : [];
      })
      .catch(function () {
        return [];
      });
  }

  function featuredCardMarkup(item) {
    const eventLabels = Array.isArray(item.event_labels) && item.event_labels.length
      ? item.event_labels.join(" · ")
      : "Disponible para eventos";

    return `
      <article class="featured-promo-card">
        <a class="featured-promo-media" href="${escapeHtml(item.detail_url)}">
          <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}" loading="lazy" />
          <span class="featured-promo-chip">${escapeHtml(item.city)}</span>
          <span class="featured-promo-score">${escapeHtml(item.completion || 100)}%</span>
        </a>
        <div class="featured-promo-body">
          <p class="featured-promo-kicker">${escapeHtml(eventLabels)}</p>
          <h3 class="featured-promo-title">${escapeHtml(item.description)}</h3>
        </div>
        <div class="featured-promo-footer">
          <p class="featured-promo-artist">${escapeHtml(item.title)}</p>
          <div class="featured-promo-bottom">
            <strong>${escapeHtml(item.price_label)}</strong>
            <a href="${escapeHtml(item.detail_url)}">Ver anuncio</a>
          </div>
        </div>
      </article>
    `;
  }

  function collectionCardMarkup(item, type) {
    const eventLabels = Array.isArray(item.event_labels) && item.event_labels.length
      ? item.event_labels.join(" · ")
      : "Disponible para eventos";
    const removeLabel = type === "favorites" ? "Quitar de la lista" : "Quitar del historial";

    return `
      <article class="public-collection-card" data-public-collection-item="${escapeHtml(item.slug)}">
        <a class="public-collection-card__media" href="${escapeHtml(item.detail_url)}">
          <img src="${escapeHtml(item.image_url)}" alt="${escapeHtml(item.title)}" loading="lazy" />
          <span class="public-collection-card__chip">${escapeHtml(item.city)}</span>
        </a>
        <div class="public-collection-card__body">
          <p class="public-collection-card__kicker">${escapeHtml(eventLabels)}</p>
          <h2 class="public-collection-card__title"><a href="${escapeHtml(item.detail_url)}">${escapeHtml(item.title)}</a></h2>
          <p class="public-collection-card__desc">${escapeHtml(item.description)}</p>
          <div class="public-collection-card__meta">
            <strong>${escapeHtml(item.price_label)}</strong>
            <button type="button" class="public-collection-card__remove" data-public-collection-remove="${escapeHtml(item.slug)}" data-public-collection-id="${escapeHtml(item.id)}">${removeLabel}</button>
          </div>
        </div>
      </article>
    `;
  }

  function hydrateListingRecents() {
    const shell = document.querySelector("[data-listing-recents-shell]");
    if (!shell || shell.getAttribute("data-has-server-items") === "true") {
      return;
    }

    const endpoint = shell.getAttribute("data-resolve-url") || "";
    const currentId = Number(shell.getAttribute("data-current-listing-id") || 0);
    const track = shell.querySelector("[data-recent-track]");
    const carouselWrap = shell.querySelector("[data-recent-carousel-wrap]");
    const emptyState = shell.querySelector("[data-listing-recents-empty]");

    if (!endpoint || !track || !carouselWrap || !emptyState) {
      return;
    }

    const recentEntries = getStoredRecents()
      .filter(function (item) {
        return Number(item.id) !== currentId;
      })
      .slice(0, 10);

    if (!recentEntries.length) {
      carouselWrap.classList.add("hidden");
      emptyState.classList.remove("hidden");
      return;
    }

    const ids = recentEntries.map(function (item) {
      return Number(item.id);
    });
    const slugs = recentEntries.map(function (item) {
      return String(item.slug);
    });

    resolveListings(endpoint, ids, slugs).then(function (items) {
      if (!items.length) {
        carouselWrap.classList.add("hidden");
        emptyState.classList.remove("hidden");
        return;
      }

      const byId = new Map(items.map(function (item) {
        return [Number(item.id), item];
      }));

      const ordered = recentEntries
        .map(function (entry) {
          return byId.get(Number(entry.id)) || null;
        })
        .filter(Boolean);

      if (!ordered.length) {
        carouselWrap.classList.add("hidden");
        emptyState.classList.remove("hidden");
        return;
      }

      track.innerHTML = ordered.map(featuredCardMarkup).join("");
      carouselWrap.classList.remove("hidden");
      emptyState.classList.add("hidden");
    });
  }

  function hydratePublicCollection() {
    const shell = document.querySelector("[data-public-collection]");
    if (!shell) {
      return;
    }

    const type = shell.getAttribute("data-collection-kind") || "favorites";
    const endpoint = shell.getAttribute("data-resolve-url") || "";
    const grid = shell.querySelector("[data-public-collection-grid]");
    const emptyState = shell.querySelector("[data-public-collection-empty]");
    const countNode = shell.querySelector("[data-public-collection-count]");
    const clearButton = shell.querySelector("[data-public-collection-clear]");

    if (!endpoint || !grid || !emptyState || !countNode || !clearButton) {
      return;
    }

    function setCount(total) {
      countNode.textContent = `${total} anuncio${total === 1 ? "" : "s"}`;
    }

    function renderEmpty() {
      grid.innerHTML = "";
      setCount(0);
      emptyState.classList.remove("hidden");
      clearButton.classList.add("hidden");
    }

    function getSourceItems() {
      if (type === "recents") {
        return getStoredRecents();
      }

      return extractFavoriteIds().map(function (id) {
        return { id };
      });
    }

    function attachActions() {
      grid.querySelectorAll("[data-public-collection-remove]").forEach(function (button) {
        button.addEventListener("click", function () {
          const target = button.getAttribute("data-public-collection-remove") || "";
          if (!target) {
            return;
          }

          if (type === "recents") {
            const nextRecents = getStoredRecents().filter(function (item) {
              return String(item.slug) !== target;
            });
            setStoredRecents(nextRecents);
          } else {
            const nextFavorites = getStoredFavorites().filter(function (value) {
              const match = String(value).match(/(\d+)$/);
              return !match || String(match[1]) !== String(button.getAttribute("data-public-collection-id") || "");
            });
            try {
              window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(nextFavorites));
              document.dispatchEvent(new CustomEvent("favorites:changed"));
            } catch (_error) {
              // noop
            }
          }

          render();
        });
      });
    }

    function render() {
      const sourceItems = getSourceItems();
      if (!sourceItems.length) {
        renderEmpty();
        return;
      }

      const ids = sourceItems
        .map(function (item) {
          return Number(item.id || 0);
        })
        .filter(function (id) {
          return id > 0;
        });
      const slugs = sourceItems
        .map(function (item) {
          return String(item.slug || "").trim();
        })
        .filter(Boolean);

      resolveListings(endpoint, ids, slugs).then(function (items) {
        if (!items.length) {
          renderEmpty();
          return;
        }

        const byId = new Map(items.map(function (item) {
          return [Number(item.id), item];
        }));
        const bySlug = new Map(items.map(function (item) {
          return [String(item.slug), item];
        }));

        const ordered = sourceItems
          .map(function (source) {
            if (source.id && byId.has(Number(source.id))) {
              return byId.get(Number(source.id));
            }

            if (source.slug && bySlug.has(String(source.slug))) {
              return bySlug.get(String(source.slug));
            }

            return null;
          })
          .filter(Boolean);

        if (!ordered.length) {
          renderEmpty();
          return;
        }

        grid.innerHTML = ordered.map(function (item) {
          return collectionCardMarkup(item, type);
        }).join("");
        setCount(ordered.length);
        emptyState.classList.add("hidden");
        clearButton.classList.remove("hidden");
        attachActions();
      });
    }

    clearButton.addEventListener("click", function () {
      if (type === "recents") {
        setStoredRecents([]);
      } else {
        try {
          window.localStorage.setItem(FAVORITES_KEY, JSON.stringify([]));
          document.dispatchEvent(new CustomEvent("favorites:changed"));
        } catch (_error) {
          // noop
        }
      }

      render();
    });

    render();
  }

  document.addEventListener("DOMContentLoaded", function () {
    updateRecentStorage();
    hydrateListingRecents();
    hydratePublicCollection();
  });
})();
