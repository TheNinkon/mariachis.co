(function () {
  function getYouTubeThumb(url) {
    const match = String(url || "").match(/embed\/([^?&"'#/]+)/);
    return match && match[1] ? `https://i.ytimg.com/vi/${match[1]}/hqdefault.jpg` : "";
  }

  function parseSlides(root) {
    const source = root.querySelector("[data-gallery-slides]");
    if (!source) {
      return [];
    }

    try {
      const payload = JSON.parse(source.textContent || "[]");
      return payload
        .map((slide, index) => ({
          index,
          type: slide.type === "video" ? "video" : "image",
          src: String(slide.src || ""),
          thumb: String(slide.thumb || ""),
          title: String(slide.title || "Galeria del anuncio"),
        }))
        .filter((slide) => slide.src !== "");
    } catch (_error) {
      return [];
    }
  }

  function createInlineStageNode(slide) {
    if (slide.type === "video") {
      const wrap = document.createElement("div");
      wrap.className = "listing-showcase__video";

      const iframe = document.createElement("iframe");
      iframe.src = slide.src;
      iframe.title = slide.title;
      iframe.loading = "lazy";
      iframe.allow = "autoplay; encrypted-media; picture-in-picture";
      iframe.allowFullscreen = true;

      wrap.appendChild(iframe);
      return wrap;
    }

    const image = document.createElement("img");
    image.className = "listing-showcase__stage-image";
    image.src = slide.src;
    image.alt = slide.title;
    image.loading = "eager";
    return image;
  }

  function createModalStageNode(slide) {
    if (slide.type === "video") {
      const wrap = document.createElement("div");
      wrap.className = "gallery-modal__video";

      const iframe = document.createElement("iframe");
      iframe.src = slide.src;
      iframe.title = slide.title;
      iframe.allow = "autoplay; encrypted-media; picture-in-picture";
      iframe.allowFullscreen = true;

      wrap.appendChild(iframe);
      return wrap;
    }

    const figure = document.createElement("figure");
    const image = document.createElement("img");
    image.src = slide.src;
    image.alt = slide.title;
    image.loading = "eager";
    figure.appendChild(image);
    return figure;
  }

  document.addEventListener("DOMContentLoaded", function () {
    const root = document.querySelector('[data-gallery-experience="viator"]');
    if (!root) {
      return;
    }

    const slides = parseSlides(root);
    if (!slides.length) {
      return;
    }

    const heroCount = Math.max(
      1,
      Number(root.querySelector("[data-gallery-hero-count]")?.getAttribute("data-gallery-hero-count") || root.getAttribute("data-gallery-hero-count") || "4")
    );

    const itemButtons = Array.from(root.querySelectorAll("[data-gallery-item]"));
    const heroSlides = slides.slice(0, Math.min(slides.length, heroCount)).map((slide, heroIndex) => ({
      ...slide,
      heroIndex,
      button:
        itemButtons.find(
          (button) => Number(button.getAttribute("data-gallery-index") || "-1") === slide.index
        ) || null,
    }));

    const inlineStage = root.querySelector("[data-gallery-inline-stage]");
    const inlineMedia = root.querySelector("[data-gallery-inline-media]");
    const inlineCounter = root.querySelector("[data-gallery-inline-counter]");
    const inlinePrev = root.querySelector("[data-gallery-inline-prev]");
    const inlineNext = root.querySelector("[data-gallery-inline-next]");
    const inlineMoreButtons = Array.from(root.querySelectorAll("[data-gallery-inline-more]"));
    const openModalButtons = Array.from(root.querySelectorAll("[data-open-gallery-modal]"));
    const openOverflowButtons = Array.from(root.querySelectorAll("[data-open-gallery-overflow]"));
    const overlayControls = Array.from(
      root.querySelectorAll("[data-share-box], [data-listing-favorite], [data-share-toggle], [data-share-copy], [data-share-email]")
    );
    const modal = document.querySelector("[data-listing-gallery-modal]");

    if (!inlineStage || !inlineMedia || !inlineCounter || !inlinePrev || !inlineNext || !modal || !heroSlides.length) {
      return;
    }

    const modalStage = modal.querySelector("[data-gallery-stage]");
    const modalCounter = modal.querySelector("[data-gallery-counter]");
    const modalThumbs = modal.querySelector("[data-gallery-thumbs]");
    const modalCloseButtons = Array.from(modal.querySelectorAll("[data-gallery-close]"));
    const modalPrev = modal.querySelector("[data-gallery-prev]");
    const modalNext = modal.querySelector("[data-gallery-next]");
    const modalFilterButtons = Array.from(modal.querySelectorAll("[data-gallery-filter]"));

    if (!modalStage || !modalCounter || !modalThumbs || !modalPrev || !modalNext) {
      return;
    }

    let currentHeroIndex = 0;
    let currentModalFilter = "all";
    let currentModalIndex = 0;
    let previousBodyOverflow = "";
    const hasOverflowSlides = slides.length > heroSlides.length;

    function getHeroSlide(index) {
      return heroSlides[Math.max(0, Math.min(index, heroSlides.length - 1))] || null;
    }

    function filteredSlides() {
      if (currentModalFilter === "all") {
        return slides;
      }

      return slides.filter((slide) => slide.type === currentModalFilter);
    }

    function getFilteredPosition(fullIndex) {
      return filteredSlides().findIndex((slide) => slide.index === fullIndex);
    }

    function syncInlineThumbs() {
      itemButtons.forEach((button) => {
        const buttonIndex = Number(button.getAttribute("data-gallery-index") || "-1");
        const activeSlide = getHeroSlide(currentHeroIndex);
        button.classList.toggle("is-active", !!activeSlide && buttonIndex === activeSlide.index);
      });

      const activeButton = itemButtons.find((button) => {
        const buttonIndex = Number(button.getAttribute("data-gallery-index") || "-1");
        const activeSlide = getHeroSlide(currentHeroIndex);
        return !!activeSlide && buttonIndex === activeSlide.index;
      });

      if (activeButton) {
        activeButton.scrollIntoView({
          behavior: "smooth",
          block: "nearest",
          inline: "nearest",
        });
      }
    }

    function renderInline(index) {
      const slide = getHeroSlide(index);
      if (!slide) {
        return;
      }

      currentHeroIndex = slide.heroIndex;
      inlineMedia.innerHTML = "";
      inlineMedia.appendChild(createInlineStageNode(slide));
      inlineCounter.textContent = `${slide.heroIndex + 1} / ${heroSlides.length}`;
      inlinePrev.disabled = slide.heroIndex <= 0;
      inlineNext.disabled = !hasOverflowSlides && slide.heroIndex >= heroSlides.length - 1;

      const showMore = hasOverflowSlides && slide.heroIndex === heroSlides.length - 1;
      inlineStage.classList.toggle("is-overflow-ready", showMore);
      inlineMoreButtons.forEach((button) => {
        button.classList.toggle("hidden", !showMore);
        button.setAttribute("aria-hidden", showMore ? "false" : "true");
      });

      syncInlineThumbs();
    }

    function syncModalFilters() {
      modalFilterButtons.forEach((button) => {
        const filter = button.getAttribute("data-gallery-filter") || "all";
        const active = filter === currentModalFilter;
        button.classList.toggle("is-active", active);
        button.setAttribute("aria-pressed", active ? "true" : "false");
      });
    }

    function renderModalThumbs(availableSlides) {
      modalThumbs.innerHTML = "";

      availableSlides.forEach((slide, position) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "gallery-modal__thumb";
        button.setAttribute("data-gallery-thumb", String(slide.index));

        if (position === currentModalIndex) {
          button.classList.add("is-active");
        }

        if (slide.thumb || slide.type === "video") {
          const img = document.createElement("img");
          img.src = slide.thumb || getYouTubeThumb(slide.src);
          img.alt = slide.title;
          img.loading = "lazy";
          button.appendChild(img);
        } else {
          const fallback = document.createElement("span");
          fallback.className = "gallery-modal__thumb-fallback";
          fallback.textContent = slide.type === "video" ? "Video" : "Foto";
          button.appendChild(fallback);
        }

        if (slide.type === "video") {
          const badge = document.createElement("span");
          badge.className = "gallery-modal__thumb-badge";
          badge.textContent = "Video";
          button.appendChild(badge);
        }

        button.addEventListener("click", function () {
          openModal(slide.index, currentModalFilter);
        });

        modalThumbs.appendChild(button);
      });

      const activeThumb = modalThumbs.querySelector(".gallery-modal__thumb.is-active");
      if (activeThumb) {
        activeThumb.scrollIntoView({
          behavior: "smooth",
          block: "nearest",
          inline: "center",
        });
      }
    }

    function renderModal(fullIndex) {
      const availableSlides = filteredSlides();
      if (!availableSlides.length) {
        return;
      }

      const nextPosition = getFilteredPosition(fullIndex);
      currentModalIndex = nextPosition >= 0 ? nextPosition : 0;
      const slide = availableSlides[currentModalIndex];

      modalStage.innerHTML = "";
      modalStage.appendChild(createModalStageNode(slide));
      modalCounter.textContent = `${currentModalIndex + 1} / ${availableSlides.length}`;
      modalPrev.disabled = currentModalIndex <= 0;
      modalNext.disabled = currentModalIndex >= availableSlides.length - 1;
      renderModalThumbs(availableSlides);
      syncModalFilters();
    }

    function openModal(fullIndex, filter) {
      const wasHidden = modal.classList.contains("hidden");
      currentModalFilter = filter || "all";
      const availableSlides = filteredSlides();
      if (!availableSlides.length) {
        currentModalFilter = "all";
      }

      if (wasHidden) {
        previousBodyOverflow = document.body.style.overflow;
      }
      document.body.style.overflow = "hidden";
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      renderModal(fullIndex);
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = previousBodyOverflow;
      previousBodyOverflow = "";
      modalStage.innerHTML = "";
    }

    function navigateModal(direction) {
      const availableSlides = filteredSlides();
      if (!availableSlides.length) {
        return;
      }

      const nextPosition = Math.max(
        0,
        Math.min(currentModalIndex + direction, availableSlides.length - 1)
      );

      openModal(availableSlides[nextPosition].index, currentModalFilter);
    }

    itemButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const fullIndex = Number(button.getAttribute("data-gallery-index") || "-1");
        const slide = slides[fullIndex];
        if (!slide) {
          return;
        }

        if (slide.type === "video") {
          openModal(slide.index, "video");
          return;
        }

        const heroIndex = heroSlides.findIndex((heroSlide) => heroSlide.index === slide.index);
        if (heroIndex >= 0) {
          renderInline(heroIndex);
        }
      });
    });

    inlinePrev.addEventListener("click", function (event) {
      event.stopPropagation();
      renderInline(currentHeroIndex - 1);
    });

    inlineNext.addEventListener("click", function (event) {
      event.stopPropagation();

      if (hasOverflowSlides && currentHeroIndex >= heroSlides.length - 1) {
        const targetSlide = slides[heroSlides.length] || slides[slides.length - 1];
        if (targetSlide) {
          openModal(targetSlide.index, targetSlide.type === "video" ? "video" : "all");
        }

        return;
      }

      renderInline(currentHeroIndex + 1);
    });

    openModalButtons.forEach((button) => {
      button.addEventListener("click", function (event) {
        event.stopPropagation();
        const slide = getHeroSlide(currentHeroIndex);
        if (!slide) {
          return;
        }

        openModal(slide.index, slide.type === "video" ? "video" : "all");
      });
    });

    openOverflowButtons.forEach((button) => {
      button.addEventListener("click", function (event) {
        event.stopPropagation();
        const targetSlide = slides[heroSlides.length] || slides[slides.length - 1];
        if (!targetSlide) {
          return;
        }

        openModal(targetSlide.index, targetSlide.type === "video" ? "video" : "all");
      });
    });

    inlineMoreButtons.forEach((button) => {
      button.addEventListener("click", function (event) {
        event.stopPropagation();
        const targetSlide = slides[heroSlides.length] || slides[slides.length - 1];
        if (!targetSlide) {
          return;
        }

        openModal(targetSlide.index, targetSlide.type === "video" ? "video" : "all");
      });
    });

    inlineStage.addEventListener("click", function () {
      const slide = getHeroSlide(currentHeroIndex);
      if (!slide) {
        return;
      }

      openModal(slide.index, slide.type === "video" ? "video" : "all");
    });

    overlayControls.forEach((control) => {
      control.addEventListener("click", function (event) {
        event.stopPropagation();
      });
    });

    modalFilterButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const nextFilter = button.getAttribute("data-gallery-filter") || "all";
        const activeSlide = filteredSlides()[currentModalIndex] || slides[0];
        currentModalFilter = nextFilter;

        const nextSlides = filteredSlides();
        if (!nextSlides.length) {
          currentModalFilter = "all";
        }

        const preferredIndex =
          nextSlides.some((slide) => slide.index === activeSlide.index) && activeSlide
            ? activeSlide.index
            : (nextSlides[0] || slides[0]).index;

        openModal(preferredIndex, currentModalFilter);
      });
    });

    modalCloseButtons.forEach((button) => {
      button.addEventListener("click", closeModal);
    });

    modalPrev.addEventListener("click", function () {
      navigateModal(-1);
    });

    modalNext.addEventListener("click", function () {
      navigateModal(1);
    });

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (modal.classList.contains("hidden")) {
        return;
      }

      if (event.key === "Escape") {
        closeModal();
      }

      if (event.key === "ArrowRight") {
        event.preventDefault();
        navigateModal(1);
      }

      if (event.key === "ArrowLeft") {
        event.preventDefault();
        navigateModal(-1);
      }
    });

    renderInline(0);
  });
})();
