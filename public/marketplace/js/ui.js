(function () {
  const CITY_LABELS = {
    bogota: "Bogotá",
    medellin: "Medellín",
    cali: "Cali",
    barranquilla: "Barranquilla",
    cartagena: "Cartagena",
    bucaramanga: "Bucaramanga",
    pereira: "Pereira",
    manizales: "Manizales",
  };

  const FAVORITES_KEY = "mariachi_market_favorites_v1";
  const THEME_KEY = "mariachi_market_theme_v1";
  const TYPOGRAPHY_KEY = "mariachi_market_typography_v1";
  const ENABLE_THEME_SWITCHER = false;
  const HOME_PREVIEW_LOCK = {
    enabled: false,
    password: "9098",
    sessionKey: "mariachis_home_preview_unlock_v1",
  };
  const THEMES = [
    {
      id: "claro",
      label: "Claro",
      description: "Limpio y moderno",
      swatches: ["#f8f5f2", "#00563b", "#ffffff"],
    },
    {
      id: "mariachi",
      label: "Mariachi",
      description: "Negro + dorado + rojo",
      swatches: ["#0f0f0f", "#c9a227", "#b22222"],
    },
    {
      id: "mexico",
      label: "Mexico",
      description: "Verde + rojo + crema",
      swatches: ["#006847", "#ce1126", "#f5e6c8"],
    },
  ];
  const ARTIST_PROFILES = {
    "mariachi-imperial": {
      artistSlug: "mariachi-imperial",
      listingSlug: "mariachi-imperial",
      groupName: "Mariachi Imperial",
      name: "Juan Macias",
      role: "Director musical",
      city: "Bogotá",
      bio: "15 años liderando serenatas sorpresa, cumpleaños y aniversarios con repertorio clásico y ranchero moderno.",
      photo: "img/6.jpeg",
      website: "https://mariachis.co/artistas/mariachi-imperial",
      websiteLabel: "mariachis.co/mariachi-imperial",
      rating: 4.9,
      reviewsCount: 32,
      response: "8 min",
      experience: "15 años",
      eventsCompleted: "540+ eventos",
      phone: "+57 300 111 2233",
      announcements: [
        {
          title: "Mariachi Imperial - Serenata Clasica",
          slug: "mariachi-imperial",
          city: "bogota",
          price: "$280.000",
          rating: "4.9",
          image: "img/2.webp",
          tag: "Top ventas",
        },
        {
          title: "Mariachi Imperial - Pack Aniversario",
          slug: "mariachi-imperial",
          city: "bogota",
          price: "$340.000",
          rating: "4.9",
          image: "img/3.webp",
          tag: "Aniversarios",
        },
        {
          title: "Mariachi Imperial - Boda Premium",
          slug: "mariachi-imperial",
          city: "bogota",
          price: "$520.000",
          rating: "4.8",
          image: "img/1.webp",
          tag: "Bodas",
        },
      ],
      reviews: [
        {
          author: "Laura M.",
          event: "Cumpleaños sorpresa",
          rating: 5,
          text: "Puntuales, organizados y con muy buena energia. Volveria a contratarlos.",
        },
        {
          author: "Andres P.",
          event: "Aniversario",
          rating: 5,
          text: "Coordinacion rapida por WhatsApp y excelente repertorio para la familia.",
        },
        {
          author: "Camila R.",
          event: "Cena empresarial",
          rating: 4.8,
          text: "Muy profesionales, cuidaron volumen y tiempos del evento.",
        },
      ],
    },
    "sol-de-oro": {
      artistSlug: "sol-de-oro",
      listingSlug: "sol-de-oro",
      groupName: "Sol de Oro",
      name: "Camilo Rojas",
      role: "Voz principal",
      city: "Bogotá",
      bio: "Especialista en repertorio para bodas y eventos corporativos premium, con formato de 7 a 9 integrantes.",
      photo: "img/7.jpeg",
      website: "https://mariachis.co/artistas/sol-de-oro",
      websiteLabel: "mariachis.co/sol-de-oro",
      rating: 4.8,
      reviewsCount: 21,
      response: "10 min",
      experience: "12 años",
      eventsCompleted: "410+ eventos",
      phone: "+57 301 555 8899",
      announcements: [
        {
          title: "Sol de Oro - Show Corporativo",
          slug: "sol-de-oro",
          city: "bogota",
          price: "$350.000",
          rating: "4.8",
          image: "img/6.jpeg",
          tag: "Empresarial",
        },
        {
          title: "Sol de Oro - Boda Elegante",
          slug: "sol-de-oro",
          city: "bogota",
          price: "$480.000",
          rating: "4.8",
          image: "img/3.webp",
          tag: "Bodas",
        },
        {
          title: "Sol de Oro - Serenata de Lujo",
          slug: "sol-de-oro",
          city: "bogota",
          price: "$420.000",
          rating: "4.9",
          image: "img/4.webp",
          tag: "Premium",
        },
      ],
      reviews: [
        {
          author: "Nicolas T.",
          event: "Boda civil",
          rating: 5,
          text: "El mejor mariachi de la noche. Sonido impecable y trato excelente.",
        },
        {
          author: "Mariana L.",
          event: "Evento corporativo",
          rating: 4.8,
          text: "Nos ayudaron a mantener un ambiente elegante y alegre.",
        },
        {
          author: "Santiago V.",
          event: "Serenata",
          rating: 4.7,
          text: "Buena coordinacion y mucha paciencia para ajustar el repertorio.",
        },
      ],
    },
    "mariachi-amanecer": {
      artistSlug: "mariachi-amanecer",
      listingSlug: "mariachi-amanecer",
      groupName: "Mariachi Amanecer",
      name: "Andres Valderrama",
      role: "Coordinador del grupo",
      city: "Bogotá",
      bio: "Enfocado en serenatas intimas con repertorio flexible para familias y celebraciones pequeñas.",
      photo: "img/8.jpg",
      website: "https://mariachis.co/artistas/mariachi-amanecer",
      websiteLabel: "mariachis.co/mariachi-amanecer",
      rating: 4.7,
      reviewsCount: 16,
      response: "12 min",
      experience: "9 años",
      eventsCompleted: "290+ eventos",
      phone: "+57 304 111 6677",
      announcements: [
        {
          title: "Amanecer - Serenata Familiar",
          slug: "mariachi-amanecer",
          city: "bogota",
          price: "$240.000",
          rating: "4.7",
          image: "img/8.jpg",
          tag: "Familiar",
        },
        {
          title: "Amanecer - Misas y Homenajes",
          slug: "mariachi-amanecer",
          city: "bogota",
          price: "$280.000",
          rating: "4.7",
          image: "img/9.jpg",
          tag: "Misas",
        },
        {
          title: "Amanecer - Pack Cumpleaños",
          slug: "mariachi-amanecer",
          city: "bogota",
          price: "$320.000",
          rating: "4.8",
          image: "img/7.jpeg",
          tag: "Cumpleaños",
        },
      ],
      reviews: [
        {
          author: "Patricia H.",
          event: "Cumpleaños mamá",
          rating: 5,
          text: "Muy amables y supieron conectar con toda la familia.",
        },
        {
          author: "Julian C.",
          event: "Aniversario",
          rating: 4.7,
          text: "Buena voz principal y excelente trato antes y durante el evento.",
        },
        {
          author: "Marta P.",
          event: "Homenaje",
          rating: 4.8,
          text: "Respetuosos, puntuales y repertorio muy acorde al momento.",
        },
      ],
    },
    "tradicion-nortena": {
      artistSlug: "tradicion-nortena",
      listingSlug: "tradicion-nortena",
      groupName: "Tradicion Nortena",
      name: "Esteban Torres",
      role: "Manager de eventos",
      city: "Bogotá",
      bio: "Coordina shows de alto impacto para eventos empresariales y celebraciones con tiempos de respuesta rapidos.",
      photo: "img/9.jpg",
      website: "https://mariachis.co/artistas/tradicion-nortena",
      websiteLabel: "mariachis.co/tradicion-nortena",
      rating: 4.9,
      reviewsCount: 27,
      response: "7 min",
      experience: "11 años",
      eventsCompleted: "470+ eventos",
      phone: "+57 315 444 9911",
      announcements: [
        {
          title: "Tradicion Nortena - Evento Empresarial",
          slug: "tradicion-nortena",
          city: "bogota",
          price: "$320.000",
          rating: "4.9",
          image: "img/4.webp",
          tag: "Empresarial",
        },
        {
          title: "Tradicion Nortena - Pack Aniversario",
          slug: "tradicion-nortena",
          city: "bogota",
          price: "$360.000",
          rating: "4.9",
          image: "img/6.jpeg",
          tag: "Aniversario",
        },
        {
          title: "Tradicion Nortena - Serenata Express",
          slug: "tradicion-nortena",
          city: "bogota",
          price: "$300.000",
          rating: "4.8",
          image: "img/8.jpg",
          tag: "Express",
        },
      ],
      reviews: [
        {
          author: "Carlos B.",
          event: "Evento de marca",
          rating: 5,
          text: "Muy cumplidos, buen repertorio y actitud impecable con invitados.",
        },
        {
          author: "Diana M.",
          event: "Aniversario de empresa",
          rating: 4.9,
          text: "Lograron excelente energia sin interrumpir el cronograma.",
        },
        {
          author: "Felipe S.",
          event: "Serenata sorpresa",
          rating: 4.8,
          text: "Llegaron rapido y resolvieron todo con poco tiempo de anticipacion.",
        },
      ],
    },
  };
  const LISTING_BASE_PRICES = {
    "mariachi-imperial": 280000,
    "sol-de-oro": 350000,
    "mariachi-amanecer": 240000,
    "tradicion-nortena": 320000,
  };
  const FONT_PRESETS = [
    {
      id: "playfair-jakarta",
      label: "Playfair + Jakarta",
      description: "Elegante y moderno",
      display: "Playfair Display",
      sans: "Plus Jakarta Sans",
      url: "https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700;800&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "cormorant-inter",
      label: "Cormorant + Inter",
      description: "Editorial limpio",
      display: "Cormorant Garamond",
      sans: "Inter",
      recommended: true,
      url: "https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600;700&family=Inter:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "abril-lato",
      label: "Abril + Lato",
      description: "Fuerte y comercial",
      display: "Abril Fatface",
      sans: "Lato",
      url: "https://fonts.googleapis.com/css2?family=Abril+Fatface&family=Lato:wght@400;700;900&display=swap",
    },
    {
      id: "prata-nunito",
      label: "Prata + Nunito",
      description: "Clásico suave",
      display: "Prata",
      sans: "Nunito Sans",
      url: "https://fonts.googleapis.com/css2?family=Prata&family=Nunito+Sans:wght@400;600;700;800&display=swap",
    },
    {
      id: "merriweather-source",
      label: "Merriweather + Source",
      description: "Tradicional y legible",
      display: "Merriweather",
      sans: "Source Sans 3",
      url: "https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700;900&family=Source+Sans+3:wght@400;600;700;800&display=swap",
    },
    {
      id: "dmserif-manrope",
      label: "DM Serif + Manrope",
      description: "Actual con contraste",
      display: "DM Serif Display",
      sans: "Manrope",
      recommended: true,
      url: "https://fonts.googleapis.com/css2?family=DM+Serif+Display:ital@0;1&family=Manrope:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "libre-work",
      label: "Libre + Work Sans",
      description: "Premium neutral",
      display: "Libre Baskerville",
      sans: "Work Sans",
      url: "https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Work+Sans:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "lora-poppins",
      label: "Lora + Poppins",
      description: "Amigable y sólida",
      display: "Lora",
      sans: "Poppins",
      url: "https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "cinzel-noto",
      label: "Cinzel + Noto Sans",
      description: "Ceremonial elegante",
      display: "Cinzel",
      sans: "Noto Sans",
      url: "https://fonts.googleapis.com/css2?family=Cinzel:wght@500;600;700&family=Noto+Sans:wght@400;500;600;700;800&display=swap",
    },
    {
      id: "cardo-mulish",
      label: "Cardo + Mulish",
      description: "Vintage moderno",
      display: "Cardo",
      sans: "Mulish",
      url: "https://fonts.googleapis.com/css2?family=Cardo:wght@400;700&family=Mulish:wght@400;500;600;700;800&display=swap",
    },
  ];

  function getQueryParams() {
    return new URLSearchParams(window.location.search);
  }

  function getStoredTheme() {
    try {
      return window.localStorage.getItem(THEME_KEY) || "";
    } catch (_error) {
      return "";
    }
  }

  function getStoredTypography() {
    try {
      return window.localStorage.getItem(TYPOGRAPHY_KEY) || "";
    } catch (_error) {
      return "";
    }
  }

  function setStoredTheme(themeId) {
    try {
      window.localStorage.setItem(THEME_KEY, themeId);
    } catch (_error) {
      // localStorage can fail in private mode; fallback is in-memory state.
    }
  }

  function setStoredTypography(typographyId) {
    try {
      window.localStorage.setItem(TYPOGRAPHY_KEY, typographyId);
    } catch (_error) {
      // localStorage can fail in private mode; fallback is in-memory state.
    }
  }

  function resolveTheme(themeId) {
    const exists = THEMES.some((theme) => theme.id === themeId);
    return exists ? themeId : "claro";
  }

  function applyTheme(themeId) {
    document.documentElement.setAttribute("data-theme", resolveTheme(themeId));
  }

  function resolveTypography(typographyId) {
    const exists = FONT_PRESETS.some((preset) => preset.id === typographyId);
    const fallback =
      (FONT_PRESETS.find((preset) => preset.recommended)?.id || FONT_PRESETS[0].id);
    return exists ? typographyId : fallback;
  }

  function ensureFontPresetLoaded(presetId) {
    const preset = FONT_PRESETS.find((font) => font.id === presetId);
    if (!preset || !preset.url) {
      return;
    }

    const linkId = `font-preset-${preset.id}`;
    if (document.getElementById(linkId)) {
      return;
    }

    const link = document.createElement("link");
    link.id = linkId;
    link.rel = "stylesheet";
    link.href = preset.url;
    document.head.appendChild(link);
  }

  function applyTypography(typographyId) {
    const resolved = resolveTypography(typographyId);
    const preset = FONT_PRESETS.find((font) => font.id === resolved);

    if (!preset) {
      return;
    }

    ensureFontPresetLoaded(resolved);
    document.documentElement.style.setProperty("--font-display", `"${preset.sans}", sans-serif`);
    document.documentElement.style.setProperty("--font-sans", `"${preset.sans}", sans-serif`);
  }

  function getCityFromQuery() {
    const params = getQueryParams();
    const slug = (params.get("city") || "bogota").toLowerCase();
    return {
      slug,
      label: CITY_LABELS[slug] || slug.charAt(0).toUpperCase() + slug.slice(1),
    };
  }

  function getListingFromQuery() {
    const slug = getListingSlugFromQuery();
    return slug
      .split("-")
      .filter(Boolean)
      .map((part) => part.charAt(0).toUpperCase() + part.slice(1))
      .join(" ");
  }

  function getListingSlugFromQuery() {
    const params = getQueryParams();
    return (params.get("listing") || "mariachi-imperial").toLowerCase();
  }

  function getCategoryFromQuery() {
    const params = getQueryParams();
    return (params.get("cat") || "").toLowerCase().trim();
  }

  function getArtistSlugFromQuery() {
    const params = getQueryParams();
    return (
      params.get("artist") ||
      params.get("listing") ||
      "mariachi-imperial"
    ).toLowerCase();
  }

  function getArtistProfileBySlug(artistSlug) {
    const profile = ARTIST_PROFILES[artistSlug];
    if (profile) {
      return profile;
    }

    return {
      artistSlug: "mariachi-imperial",
      listingSlug: "mariachi-imperial",
      groupName: "Mariachi Imperial",
      name: "Representante del grupo",
      role: "Atencion comercial",
      city: "Bogotá",
      bio: "Gestiona disponibilidad, repertorio y coordinacion del evento.",
      photo: "img/6.jpeg",
      website: "https://mariachis.co/artistas",
      websiteLabel: "mariachis.co/artistas",
      rating: 4.8,
      reviewsCount: 12,
      response: "12 min",
      experience: "8 años",
      eventsCompleted: "200+ eventos",
      phone: "+57 300 111 2233",
      announcements: [],
      reviews: [],
    };
  }

  function getArtistProfile() {
    return getArtistProfileBySlug(getArtistSlugFromQuery());
  }

  function getStoredFavorites() {
    try {
      const raw = window.localStorage.getItem(FAVORITES_KEY);
      if (!raw) {
        return new Set();
      }
      const parsed = JSON.parse(raw);
      if (!Array.isArray(parsed)) {
        return new Set();
      }
      return new Set(parsed);
    } catch (_error) {
      return new Set();
    }
  }

  function setStoredFavorites(favorites) {
    try {
      const values = Array.from(favorites);
      window.localStorage.setItem(FAVORITES_KEY, JSON.stringify(values));
    } catch (_error) {
      // localStorage can fail in private mode; UI still works in-memory.
    }
  }

  const state = {
    favorites: getStoredFavorites(),
    theme: ENABLE_THEME_SWITCHER ? resolveTheme(getStoredTheme()) : "claro",
    typography: ENABLE_THEME_SWITCHER ? resolveTypography(getStoredTypography()) : resolveTypography("playfair-jakarta"),
  };

  applyTheme(state.theme);
  applyTypography(state.typography);

  function brandLogo(variant) {
    const isFooter = variant === "footer";
    const logoClass = isFooter
      ? "brand-logo brand-logo--footer"
      : "brand-logo brand-logo--header";
    const subtitle = isFooter
      ? '<span class="brand-logo-sub">marketplace colombiano</span>'
      : "";

    return `
      <a class="${logoClass}" href="/" aria-label="mariachis.co">
        <span class="brand-logo-copy">
          <span class="brand-logo-word">
            <img src="assets/logo-wordmark.png" alt="Mariachis.co" class="brand-logo-image" />
          </span>
          ${subtitle}
        </span>
      </a>
    `;
  }

  function themeSwitcherTemplate() {
    const colorOptions = THEMES.map((theme) => {
      const swatches = theme.swatches
        .map((color) => `<span style="background:${color}"></span>`)
        .join("");

      return `
        <button class="theme-option" type="button" data-theme-option="${theme.id}">
          <div class="theme-option-head">
            <div>
              <p class="theme-option-name">${theme.label}</p>
              <p class="theme-option-desc">${theme.description}</p>
            </div>
            <span class="theme-swatches">${swatches}</span>
          </div>
        </button>
      `;
    }).join("");

    const sortedFonts = [...FONT_PRESETS].sort((a, b) => {
      const scoreA = a.recommended ? 1 : 0;
      const scoreB = b.recommended ? 1 : 0;
      return scoreB - scoreA;
    });

    const fontOptions = sortedFonts.map((font) => {
      const badge = font.recommended ? '<span class="font-reco-badge">Final</span>' : "";

      return `
        <button class="theme-option font-option" type="button" data-font-option="${font.id}">
          <div class="theme-option-head">
            <div>
              <p class="theme-option-name">${font.label}${badge}</p>
              <p class="theme-option-desc">${font.description}</p>
            </div>
          </div>
          <p class="font-option-sample" style="font-family:'${font.display}', serif;">mariachis.co</p>
        </button>
      `;
    }).join("");

    return `
      <div class="theme-switcher" data-theme-switcher>
        <button class="theme-switcher-toggle" type="button" data-theme-toggle aria-expanded="false" aria-label="Cambiar estilo">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m4.95 1.05l-2.12 2.12M21 12h-3m-1.05 4.95l-2.12-2.12M12 21v-3m-4.95-1.05l2.12-2.12M3 12h3m1.05-4.95l2.12 2.12M12 7a5 5 0 100 10 5 5 0 000-10z" />
          </svg>
          Estilo
        </button>
        <div class="theme-switcher-panel" data-theme-panel>
          <p class="theme-section-title">Color</p>
          ${colorOptions}
          <p class="theme-section-title theme-section-title--fonts">Tipografia</p>
          <div class="font-options-scroll">
            ${fontOptions}
          </div>
        </div>
      </div>
    `;
  }

  function initHomePasswordGate() {
    if (!HOME_PREVIEW_LOCK.enabled || document.body.dataset.page !== "home") {
      return;
    }

    let hasAccess = false;
    try {
      hasAccess = window.sessionStorage.getItem(HOME_PREVIEW_LOCK.sessionKey) === "1";
    } catch (_error) {
      hasAccess = false;
    }

    if (hasAccess) {
      return;
    }

    const overlay = document.createElement("div");
    overlay.className = "home-password-gate";
    overlay.innerHTML = `
      <div class="home-password-gate__backdrop" aria-hidden="true"></div>
      <section class="home-password-gate__card" role="dialog" aria-modal="true" aria-labelledby="home-lock-title">
        <p class="home-password-gate__eyebrow">Acceso temporal</p>
        <h2 id="home-lock-title" class="home-password-gate__title">Vista privada de mariachis.co</h2>
        <p class="home-password-gate__text">Ingresa la contraseña para ver la home.</p>
        <form data-home-lock-form class="home-password-gate__form">
          <label for="home-lock-input">Contraseña</label>
          <input id="home-lock-input" data-home-lock-input type="password" inputmode="numeric" autocomplete="current-password" required />
          <p data-home-lock-error class="home-password-gate__error" hidden>Contraseña incorrecta.</p>
          <button type="submit">Entrar</button>
        </form>
      </section>
    `;

    document.body.appendChild(overlay);
    document.body.classList.add("home-lock-active");

    const form = overlay.querySelector("[data-home-lock-form]");
    const input = overlay.querySelector("[data-home-lock-input]");
    const error = overlay.querySelector("[data-home-lock-error]");

    if (!form || !input || !error) {
      return;
    }

    window.setTimeout(function () {
      input.focus();
    }, 40);

    form.addEventListener("submit", function (event) {
      event.preventDefault();
      const value = String(input.value || "").trim();

      if (value !== HOME_PREVIEW_LOCK.password) {
        error.hidden = false;
        input.value = "";
        input.focus();
        return;
      }

      try {
        window.sessionStorage.setItem(HOME_PREVIEW_LOCK.sessionKey, "1");
      } catch (_error) {
        // Ignore storage issues. Unlock still works for this view.
      }

      document.body.classList.remove("home-lock-active");
      overlay.remove();
    });
  }

  function initThemeSwitcher() {
    if (!ENABLE_THEME_SWITCHER) {
      return;
    }

    const wrapper = document.createElement("div");
    wrapper.innerHTML = themeSwitcherTemplate();
    const switcher = wrapper.firstElementChild;

    if (!switcher) {
      return;
    }

    document.body.appendChild(switcher);

    const toggle = switcher.querySelector("[data-theme-toggle]");
    const panel = switcher.querySelector("[data-theme-panel]");
    const themeOptions = Array.from(switcher.querySelectorAll("[data-theme-option]"));
    const fontOptions = Array.from(switcher.querySelectorAll("[data-font-option]"));

    if (!toggle || !panel || !themeOptions.length || !fontOptions.length) {
      return;
    }

    function setActiveThemeOption(themeId) {
      themeOptions.forEach((option) => {
        const isActive = option.getAttribute("data-theme-option") === themeId;
        option.classList.toggle("is-active", isActive);
        option.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    }

    function setActiveFontOption(fontId) {
      fontOptions.forEach((option) => {
        const isActive = option.getAttribute("data-font-option") === fontId;
        option.classList.toggle("is-active", isActive);
        option.setAttribute("aria-pressed", isActive ? "true" : "false");
      });
    }

    function closePanel() {
      switcher.classList.remove("is-open");
      toggle.setAttribute("aria-expanded", "false");
    }

    function openPanel() {
      switcher.classList.add("is-open");
      toggle.setAttribute("aria-expanded", "true");
    }

    setActiveThemeOption(state.theme);
    setActiveFontOption(state.typography);

    toggle.addEventListener("click", function () {
      const isOpen = switcher.classList.contains("is-open");
      if (isOpen) {
        closePanel();
      } else {
        openPanel();
      }
    });

    themeOptions.forEach((option) => {
      option.addEventListener("click", function () {
        const selectedTheme = resolveTheme(option.getAttribute("data-theme-option") || "");
        state.theme = selectedTheme;
        applyTheme(selectedTheme);
        setStoredTheme(selectedTheme);
        setActiveThemeOption(selectedTheme);
      });
    });

    fontOptions.forEach((option) => {
      option.addEventListener("click", function () {
        const selectedFont = resolveTypography(option.getAttribute("data-font-option") || "");
        state.typography = selectedFont;
        applyTypography(selectedFont);
        setStoredTypography(selectedFont);
        setActiveFontOption(selectedFont);
      });
    });

    document.addEventListener("click", function (event) {
      if (!switcher.contains(event.target)) {
        closePanel();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closePanel();
      }
    });
  }

  function getClientAuthState() {
    const fallback = {
      isAuthenticated: false,
      firstName: "",
      lastName: "",
      initials: "",
      dashboardUrl: "/mi-cuenta/solicitudes",
      csrfToken: "",
    };

    const raw = window.__MM_AUTH__;
    if (!raw || typeof raw !== "object") {
      return fallback;
    }

    return {
      isAuthenticated: Boolean(raw.isAuthenticated),
      firstName: String(raw.firstName || ""),
      lastName: String(raw.lastName || ""),
      initials: String(raw.initials || ""),
      dashboardUrl: String(raw.dashboardUrl || "/mi-cuenta/solicitudes"),
      csrfToken: String(raw.csrfToken || ""),
    };
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  function getFooterState() {
    const fallback = { cities: [] };
    const raw = window.__MM_FOOTER__;
    if (!raw || typeof raw !== "object") {
      return fallback;
    }

    const cities = Array.isArray(raw.cities) ? raw.cities : [];
    return {
      cities: cities.slice(0, 5).map((item) => ({
        name: String(item.name || ""),
        slug: String(item.slug || ""),
        count: Number(item.count || 0),
      })),
    };
  }

  function headerTemplate(currentPage) {
    const authState = getClientAuthState();
    const authName = authState.firstName || "Mi cuenta";
    const authInitials = authState.initials || "C";

    if (authState.isAuthenticated) {
      return `
        <header class="sticky top-0 z-50 border-b border-rose-100/80 bg-white/85 backdrop-blur-xl">
          <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 md:px-8">
            ${brandLogo("header")}
            <div class="flex items-center gap-2 md:gap-3">
              <a href="${escapeHtml(authState.dashboardUrl)}" class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 transition hover:border-slate-300 hover:text-slate-900" aria-label="Solicitudes y mensajería">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.9">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 8.25v8.25a2.25 2.25 0 01-2.25 2.25h-15A2.25 2.25 0 012.25 16.5V8.25m19.5 0L15.75 3.75h-7.5L2.25 8.25m19.5 0v.75A2.25 2.25 0 0119.5 11.25h-3.621a2.25 2.25 0 00-2.122 1.5l-.27.81a2.25 2.25 0 01-2.122 1.5H8.636a2.25 2.25 0 01-2.122-1.5l-.27-.81a2.25 2.25 0 00-2.122-1.5H2.25A2.25 2.25 0 010 9V8.25" />
                </svg>
              </a>
              <span class="hidden h-8 w-px bg-slate-200 md:block" aria-hidden="true"></span>
              <div class="relative" data-header-account>
                <button type="button" data-header-account-btn aria-expanded="false" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 text-slate-700 shadow-[0_10px_18px_-14px_rgba(15,23,42,0.45)] transition hover:border-slate-300">
                  <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-brand-100 text-[11px] font-extrabold tracking-[0.03em] text-brand-700">${escapeHtml(authInitials)}</span>
                  <span class="hidden max-w-[7rem] truncate text-xs font-semibold text-slate-700 md:inline">${escapeHtml(authName)}</span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                  </svg>
                </button>
                <div data-header-account-menu class="absolute right-0 top-[calc(100%+0.6rem)] z-50 hidden w-[17rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_28px_42px_-28px_rgba(15,23,42,0.6)]">
                  <a href="/mi-cuenta/solicitudes" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Mi cuenta</a>
                  <a href="/mi-cuenta/solicitudes" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Solicitudes / mensajería</a>
                  <a href="/mi-cuenta/favoritos" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Lista de deseos</a>
                  <a href="/mi-cuenta/vistos" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Vistos recientemente</a>
                  <a href="/mi-cuenta/perfil" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Perfil</a>
                  <a href="/mi-cuenta/seguridad" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Seguridad</a>
                  <form method="POST" action="/auth/logout" class="border-t border-slate-100">
                    <input type="hidden" name="_token" value="${escapeHtml(authState.csrfToken)}" />
                    <button type="submit" class="w-full px-4 py-3 text-left text-sm font-semibold text-brand-700 transition hover:bg-brand-100">Cerrar sesión</button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </header>
      `;
    }

    return `
      <header class="sticky top-0 z-50 border-b border-rose-100/80 bg-white/85 backdrop-blur-xl">
        <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-4 md:px-8">
          ${brandLogo("header")}
          <div class="flex items-center gap-2 md:gap-3">
            <div class="relative" data-header-account>
              <button type="button" data-header-account-btn aria-expanded="false" class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-2.5 py-1.5 text-slate-700 shadow-[0_10px_18px_-14px_rgba(15,23,42,0.45)] transition hover:border-slate-300">
                <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-slate-300 bg-slate-100 text-slate-400">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6.75a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0" />
                  </svg>
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                  <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                </svg>
              </button>
              <div data-header-account-menu class="absolute right-0 top-[calc(100%+0.6rem)] z-50 hidden w-[17rem] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_28px_42px_-28px_rgba(15,23,42,0.6)]">
                <a href="/login" class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Iniciar sesión / Registrarse</a>
                <a href="/mi-cuenta/favoritos" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Lista de deseos</a>
                <a href="#" class="flex items-center gap-3 border-t border-slate-100 px-4 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50">Ayuda</a>
              </div>
            </div>
          </div>
        </div>
      </header>
    `;
  }

  function footerTemplate() {
    const footerState = getFooterState();
    const cityItems = footerState.cities.length
      ? footerState.cities
          .map((city) => `<li><a href="/mariachis/${escapeHtml(city.slug)}" class="hover:text-white">Mariachis en ${escapeHtml(city.name)}</a></li>`)
          .join("")
      : `<li><span class="text-slate-400">Sin ciudades activas todavía</span></li>`;

    return `
      <footer class="mt-20 border-t border-slate-200 bg-slate-950 text-slate-200">
        <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-14 md:grid-cols-3 md:px-8">
          <div class="space-y-4">
            ${brandLogo("footer")}
            <p class="max-w-lg text-sm text-slate-300">Marketplace para contratar mariachis en Colombia con perfiles reales, contacto directo y búsqueda por ciudad.</p>
            <div class="flex flex-wrap gap-2 text-xs font-semibold uppercase tracking-[0.13em]">
              <span class="rounded-full border border-white/20 px-3 py-1">SEO local</span>
              <span class="rounded-full border border-white/20 px-3 py-1">WhatsApp first</span>
              <span class="rounded-full border border-white/20 px-3 py-1">Mobile</span>
            </div>
          </div>
          <div>
            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-400">Ciudades populares</h3>
            <ul class="mt-4 grid gap-2 text-sm text-slate-200">
              ${cityItems}
            </ul>
          </div>
          <div>
            <h3 class="text-sm font-bold uppercase tracking-[0.18em] text-slate-400">Marketplace</h3>
            <ul class="mt-4 grid gap-2 text-sm text-slate-200">
              <li><a href="/#como-funciona" class="hover:text-white">Cómo funciona</a></li>
              <li><a href="/#soy-mariachi" class="hover:text-white">Publica tu anuncio</a></li>
              <li><a href="/mariachis/bogota" class="hover:text-white">Anuncios en tu ciudad</a></li>
              <li><a href="/blog" class="hover:text-white">Blog</a></li>
              <li><a href="#" class="hover:text-white">Centro de ayuda</a></li>
            </ul>
          </div>
        </div>
      </footer>
    `;
  }

  function injectSharedComponents() {
    const currentPage = document.body.dataset.page || "";
    const headerTarget = document.querySelector('[data-component="site-header"]');
    const footerTarget = document.querySelector('[data-component="site-footer"]');

    if (headerTarget) {
      headerTarget.innerHTML = headerTemplate(currentPage);
    }

    if (footerTarget) {
      footerTarget.innerHTML = footerTemplate();
    }
  }

  function initMobileMenu() {
    const button = document.querySelector("[data-mobile-menu-btn]");
    const menu = document.querySelector("[data-mobile-menu]");

    if (!button || !menu) {
      return;
    }

    button.addEventListener("click", function () {
      const isOpen = button.getAttribute("aria-expanded") === "true";
      button.setAttribute("aria-expanded", String(!isOpen));
      menu.classList.toggle("hidden");
    });
  }

  function initHeaderAccountMenu() {
    const root = document.querySelector("[data-header-account]");
    const button = document.querySelector("[data-header-account-btn]");
    const menu = document.querySelector("[data-header-account-menu]");

    if (!root || !button || !menu) {
      return;
    }

    function closeMenu() {
      menu.classList.add("hidden");
      button.setAttribute("aria-expanded", "false");
    }

    function openMenu() {
      menu.classList.remove("hidden");
      button.setAttribute("aria-expanded", "true");
    }

    button.addEventListener("click", function (event) {
      event.preventDefault();
      event.stopPropagation();
      const isOpen = button.getAttribute("aria-expanded") === "true";
      if (isOpen) {
        closeMenu();
      } else {
        openMenu();
      }
    });

    menu.addEventListener("click", function (event) {
      event.stopPropagation();
    });

    document.addEventListener("click", function (event) {
      if (!root.contains(event.target)) {
        closeMenu();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeMenu();
      }
    });
  }

  function hydrateCityContext() {
    const city = getCityFromQuery();
    const listing = getListingFromQuery();
    const artist = getArtistProfile();

    document.querySelectorAll("[data-city-context-name]").forEach((node) => {
      node.textContent = city.label;
    });

    document.querySelectorAll("[data-city-context-slug]").forEach((node) => {
      node.textContent = city.slug;
    });

    document.querySelectorAll("[data-listing-context-name]").forEach((node) => {
      node.textContent = listing;
    });

    document.querySelectorAll("[data-city-context-link]").forEach((node) => {
      node.setAttribute("href", `city.html?city=${city.slug}`);
    });

    document.querySelectorAll("[data-listing-context-link]").forEach((node) => {
      const listingSlug = node.getAttribute("data-listing-context-link");
      if (listingSlug) {
        node.setAttribute("href", `listing.html?city=${city.slug}&listing=${listingSlug}`);
      }
    });

    document.querySelectorAll("[data-city-context-input]").forEach((node) => {
      node.value = city.label;
    });

    document.querySelectorAll("[data-artist-context-name]").forEach((node) => {
      node.textContent = artist.name;
    });

    document.querySelectorAll("[data-artist-context-role]").forEach((node) => {
      node.textContent = artist.role;
    });

    document.querySelectorAll("[data-artist-bio]").forEach((node) => {
      node.textContent = artist.bio;
    });

    document.querySelectorAll("[data-artist-photo]").forEach((node) => {
      node.setAttribute("src", artist.photo);
      node.setAttribute("alt", `Foto de ${artist.name}`);
    });

    document.querySelectorAll("[data-artist-website]").forEach((node) => {
      node.setAttribute("href", artist.website);
      node.textContent = artist.websiteLabel || artist.website;
    });

    document.querySelectorAll("[data-artist-link]").forEach((node) => {
      node.setAttribute("href", `artist.html?artist=${artist.artistSlug}&city=${city.slug}`);
    });

    document.querySelectorAll("[data-artist-group]").forEach((node) => {
      node.textContent = artist.groupName || listing;
    });

    document.querySelectorAll("[data-artist-rating]").forEach((node) => {
      node.textContent = String(artist.rating || "4.8");
    });

    document.querySelectorAll("[data-artist-reviews-count]").forEach((node) => {
      node.textContent = String(artist.reviewsCount || "0");
    });

    document.querySelectorAll("[data-artist-response]").forEach((node) => {
      node.textContent = artist.response || "10 min";
    });

    document.querySelectorAll("[data-artist-experience]").forEach((node) => {
      node.textContent = artist.experience || "8 años";
    });

    document.querySelectorAll("[data-artist-events]").forEach((node) => {
      node.textContent = artist.eventsCompleted || "200+ eventos";
    });

    const phoneRaw = (artist.phone || "").replace(/\s+/g, "");
    const phoneDigits = (artist.phone || "").replace(/\D/g, "");

    document.querySelectorAll("[data-listing-phone-link]").forEach((node) => {
      if (phoneRaw) {
        node.setAttribute("href", `tel:${phoneRaw}`);
      }
    });

    document.querySelectorAll("[data-listing-whatsapp-link]").forEach((node) => {
      if (!phoneDigits) {
        return;
      }

      const message = encodeURIComponent(
        `Hola, vi ${listing} en ${city.label} en mariachis.co y quiero cotizar.`
      );
      node.setAttribute("href", `https://wa.me/${phoneDigits}?text=${message}`);
      node.setAttribute("target", "_blank");
      node.setAttribute("rel", "noopener noreferrer");
    });
  }

  function hydrateArtistPage() {
    if (document.body.dataset.page !== "artist") {
      return;
    }

    const city = getCityFromQuery();
    const artist = getArtistProfile();
    const announcements = Array.isArray(artist.announcements) ? artist.announcements : [];
    const reviews = Array.isArray(artist.reviews) ? artist.reviews : [];
    const artistDigits = (artist.phone || "").replace(/\D/g, "");

    document.title = `${artist.groupName || artist.name} | Perfil del artista`;

    document.querySelectorAll("[data-artist-phone-link]").forEach((node) => {
      const phone = (artist.phone || "").replace(/\s+/g, "");
      if (phone) {
        node.setAttribute("href", `tel:${phone}`);
      }
    });

    document.querySelectorAll("[data-artist-whatsapp-link]").forEach((node) => {
      const digits = (artist.phone || "").replace(/\D/g, "");
      if (!digits) {
        return;
      }
      const message = encodeURIComponent(
        `Hola ${artist.name}, vi tu perfil en mariachis.co y quiero cotizar un show en ${city.label}.`
      );
      node.setAttribute("href", `https://wa.me/${digits}?text=${message}`);
      node.setAttribute("target", "_blank");
      node.setAttribute("rel", "noopener noreferrer");
    });

    document.querySelectorAll("[data-artist-main-listing-link]").forEach((node) => {
      node.setAttribute(
        "href",
        `listing.html?city=${city.slug}&listing=${artist.listingSlug || "mariachi-imperial"}`
      );
    });

    document.querySelectorAll("[data-artist-listings]").forEach((container) => {
      if (!announcements.length) {
        container.innerHTML =
          '<p class="text-sm text-slate-600">Este artista aún no tiene anuncios publicados.</p>';
        return;
      }

      container.innerHTML = announcements
        .map((item) => {
          const listingSlug = item.slug || artist.listingSlug || "mariachi-imperial";
          const listingCity = item.city || city.slug;
          const link = `listing.html?city=${listingCity}&listing=${listingSlug}`;
          const favoriteId = `${artist.artistSlug}-${listingSlug}-${item.title
            .toLowerCase()
            .replace(/\s+/g, "-")
            .replace(/[^a-z0-9\-]/g, "")}`;
          const whatsappMessage = encodeURIComponent(
            `Hola ${artist.name}, vi ${item.title} en mariachis.co y quiero cotizar en ${city.label}.`
          );
          const whatsappLink = artistDigits
            ? `https://wa.me/${artistDigits}?text=${whatsappMessage}`
            : "#";

          return `
            <article class="listing-card">
              <div class="listing-cover">
                <img src="${item.image || artist.photo}" alt="${item.title}" class="h-44 object-cover" />
                <span class="badge-soft absolute left-3 top-3 z-10 bg-brand-100 text-brand-700">${item.tag || "Anuncio"}</span>
                <button data-favorite="${favoriteId}" class="favorite-btn absolute right-3 top-3 z-10" aria-label="Guardar en favoritos" aria-pressed="false">
                  <svg data-fav-icon xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
                  </svg>
                </button>
              </div>
              <div class="space-y-3 p-4">
                <h3 class="text-lg font-extrabold text-slate-900">${item.title}</h3>
                <div class="listing-meta-grid">
                  <span class="listing-meta-pill">${item.rating || "4.8"} ★</span>
                  <span class="listing-meta-pill">${item.price || "Consultar"}</span>
                </div>
                <p class="urgency-note"><span class="presence-dot"></span>Última solicitud hace pocas horas</p>
                <div class="flex flex-wrap gap-2">
                  <a href="${link}" class="inline-flex rounded-xl bg-slate-900 px-3 py-2 text-xs font-bold text-white hover:bg-slate-700">Ver anuncio</a>
                  <a href="${whatsappLink}" target="_blank" rel="noopener noreferrer" class="inline-flex rounded-xl bg-emerald-500 px-3 py-2 text-xs font-bold text-white hover:bg-emerald-600">WhatsApp</a>
                </div>
              </div>
            </article>
          `;
        })
        .join("");
    });

    document.querySelectorAll("[data-artist-reviews]").forEach((container) => {
      if (!reviews.length) {
        container.innerHTML =
          '<p class="text-sm text-slate-600">Aún no hay opiniones publicadas para este artista.</p>';
        return;
      }

      container.innerHTML = reviews
        .map((review) => {
          return `
            <article class="surface rounded-2xl p-4">
              <div class="flex items-center justify-between gap-3">
                <p class="text-sm font-extrabold text-slate-900">${review.author}</p>
                <span class="rounded-full bg-brand-100 px-2.5 py-1 text-xs font-bold text-brand-700">${review.rating} ★</span>
              </div>
              <p class="mt-1 text-xs font-semibold uppercase tracking-[0.1em] text-slate-500">${review.event}</p>
              <p class="mt-3 text-sm leading-relaxed text-slate-600">${review.text}</p>
            </article>
          `;
        })
        .join("");
    });
  }

  function normalizeSlug(value) {
    return (value || "")
      .trim()
      .toLowerCase()
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .replace(/\s+/g, "-");
  }

  function resolveCategoryFromKeyword(value, scopeRoot = document) {
    const keyword = normalizeSlug(value);
    if (!keyword) {
      return { slug: "", type: "" };
    }

    const options = Array.from(scopeRoot.querySelectorAll("[data-event-option]"));
    const exactMatch = options.find((option) => {
      const label = normalizeSlug(option.getAttribute("data-label") || option.textContent || "");
      return label === keyword;
    });

    if (exactMatch) {
      return {
        slug: (exactMatch.getAttribute("data-cat") || "").trim(),
        type: (exactMatch.getAttribute("data-cat-type") || "").trim(),
      };
    }

    const partialMatch = options.find((option) => {
      const label = normalizeSlug(option.getAttribute("data-label") || option.textContent || "");
      return label && (label.includes(keyword) || keyword.includes(label));
    });

    if (!partialMatch) {
      return { slug: "", type: "" };
    }

    return {
      slug: (partialMatch.getAttribute("data-cat") || "").trim(),
      type: (partialMatch.getAttribute("data-cat-type") || "").trim(),
    };
  }

  function initEventMegaMenu() {
    document.querySelectorAll("[data-event-menu]").forEach((menuRoot) => {
      const input = menuRoot.querySelector("[data-event-input]");
      const catInput = menuRoot.querySelector("[data-event-cat]");
      const catTypeInput = menuRoot.querySelector("[data-event-cat-type]");
      const dropdown = menuRoot.querySelector("[data-event-dropdown]");
      const options = Array.from(menuRoot.querySelectorAll("[data-event-option]"));

      if (!input || !catInput || !dropdown) {
        return;
      }

      function openMenu() {
        dropdown.classList.remove("hidden");
        input.setAttribute("aria-expanded", "true");
      }

      function closeMenu() {
        dropdown.classList.add("hidden");
        input.setAttribute("aria-expanded", "false");
      }

      input.addEventListener("focus", openMenu);
      input.addEventListener("click", openMenu);

      input.addEventListener("input", () => {
        catInput.value = "";
        if (catTypeInput) {
          catTypeInput.value = "";
        }
      });

      options.forEach((option) => {
        option.addEventListener("click", () => {
          const label = option.getAttribute("data-label") || option.textContent || "";
          const cat = option.getAttribute("data-cat") || "";
          const catType = option.getAttribute("data-cat-type") || "";
          input.value = label.trim();
          catInput.value = cat;
          if (catTypeInput) {
            catTypeInput.value = catType.trim();
          }
          closeMenu();
        });
      });

      document.addEventListener("click", (event) => {
        if (!(event.target instanceof Node)) {
          return;
        }
        if (menuRoot.contains(event.target)) {
          return;
        }
        closeMenu();
      });

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeMenu();
        }
      });
    });
  }

  function initCityDropdownMenu() {
    document.querySelectorAll("[data-city-menu]").forEach((menuRoot) => {
      const input = menuRoot.querySelector("[data-city-input-menu]");
      const zoneInput = menuRoot.querySelector("[data-city-zone]");
      const dropdown = menuRoot.querySelector("[data-city-dropdown]");
      const tabs = Array.from(menuRoot.querySelectorAll("[data-city-tab]"));
      const panels = Array.from(menuRoot.querySelectorAll("[data-city-panel]"));

      if (!input || !dropdown || !tabs.length || !panels.length) {
        return;
      }

      function dedupeLocationTree() {
        const seenCities = new Set();
        const trees = Array.from(menuRoot.querySelectorAll(".city-dropdown-tree"));

        trees.forEach((tree) => {
          const cityButton = tree.querySelector(".city-dropdown-item--city[data-city-option]");
          if (!(cityButton instanceof HTMLElement)) {
            return;
          }

          const cityValue = (cityButton.getAttribute("data-city-value") || cityButton.textContent || "").trim();
          const citySlug = normalizeSlug(cityButton.getAttribute("data-city-option-slug") || cityValue);
          const cityKey = normalizeSlug(cityValue) || citySlug;
          if (!citySlug) {
            tree.remove();
            return;
          }

          if (seenCities.has(cityKey)) {
            tree.remove();
            return;
          }
          seenCities.add(cityKey);

          if (!cityButton.getAttribute("data-city-option-slug")) {
            cityButton.setAttribute("data-city-option-slug", citySlug);
          }

          const seenZones = new Set();
          tree.querySelectorAll(".city-dropdown-item--zone[data-city-option]").forEach((zoneButton) => {
            if (!(zoneButton instanceof HTMLElement)) {
              return;
            }
            const zoneValue = (zoneButton.getAttribute("data-zone-slug") || zoneButton.textContent || "").trim();
            const zoneSlug = normalizeSlug(zoneValue);
            if (!zoneSlug) {
              zoneButton.remove();
              return;
            }

            if (seenZones.has(zoneSlug)) {
              zoneButton.remove();
              return;
            }
            seenZones.add(zoneSlug);
          });

          const children = tree.querySelector("[data-city-children]");
          if (children) {
            const hasZones = children.querySelector(".city-dropdown-item--zone[data-city-option]");
            if (!hasZones) {
              children.remove();
              tree.classList.remove("is-open");
              const arrow = tree.querySelector("[data-city-expand-arrow]");
              if (arrow) {
                arrow.remove();
              }
            }
          }
        });
      }

      dedupeLocationTree();
      const options = Array.from(menuRoot.querySelectorAll("[data-city-option]"));

      function openMenu() {
        dropdown.classList.remove("hidden");
        input.setAttribute("aria-expanded", "true");
      }

      function closeMenu() {
        dropdown.classList.add("hidden");
        input.setAttribute("aria-expanded", "false");
      }

      function closeAllTrees() {
        menuRoot.querySelectorAll(".city-dropdown-tree").forEach((tree) => {
          tree.classList.remove("is-open");
          const children = tree.querySelector("[data-city-children]");
          if (children) {
            children.classList.add("hidden");
          }
        });
      }

      function setActiveTab(tabId) {
        tabs.forEach((tab) => {
          const isActive = tab.getAttribute("data-city-tab") === tabId;
          tab.classList.toggle("active", isActive);
          tab.setAttribute("aria-selected", isActive ? "true" : "false");
        });

        panels.forEach((panel) => {
          const isActive = panel.getAttribute("data-city-panel") === tabId;
          panel.classList.toggle("active", isActive);
        });
      }

      input.addEventListener("focus", openMenu);
      input.addEventListener("click", openMenu);
      input.addEventListener("input", () => {
        menuRoot.dataset.selectedCitySlug = normalizeSlug(input.value || "");
        if (zoneInput) {
          zoneInput.value = "";
        }
      });

      tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
          const tabId = tab.getAttribute("data-city-tab");
          if (!tabId) {
            return;
          }
          setActiveTab(tabId);
        });
      });

      options.forEach((option) => {
        option.addEventListener("click", (event) => {
          const tree = option.closest(".city-dropdown-tree");
          const children = tree ? tree.querySelector("[data-city-children]") : null;
          const isCityRow = option.classList.contains("city-dropdown-item--city");
          const isArrowClick =
            event.target instanceof Element &&
            Boolean(event.target.closest("[data-city-expand-arrow]"));

          if (children && (isArrowClick || isCityRow)) {
            const nextState = children.classList.contains("hidden");
            closeAllTrees();
            tree.classList.toggle("is-open", nextState);
            children.classList.toggle("hidden", !nextState);
            openMenu();
            return;
          }

          const cityValue = (option.getAttribute("data-city-value") || "").trim();
          const citySlug = (option.getAttribute("data-city-option-slug") || normalizeSlug(cityValue)).trim();
          const zoneSlug = (option.getAttribute("data-zone-slug") || "").trim();
          const zoneLabel = (option.getAttribute("data-zone-label") || "").trim();

          if (zoneSlug && zoneLabel) {
            input.value = `${zoneLabel}, ${cityValue}`;
          } else {
            input.value = cityValue;
          }

          menuRoot.dataset.selectedCitySlug = citySlug;
          if (zoneInput) {
            zoneInput.value = zoneSlug;
          }

          closeAllTrees();
          closeMenu();
        });
      });

      document.addEventListener("click", (event) => {
        if (!(event.target instanceof Node)) {
          return;
        }
        if (menuRoot.contains(event.target)) {
          return;
        }
        closeAllTrees();
        closeMenu();
      });

      document.addEventListener("keydown", (event) => {
        if (event.key === "Escape") {
          closeAllTrees();
          closeMenu();
        }
      });
    });
  }

  function initSearchForms() {
    document.querySelectorAll("[data-search-form]").forEach((form) => {
      form.addEventListener("submit", function (event) {
        event.preventDefault();
        const cityInput = form.querySelector("[name='city']");
        const cityMenuRoot = form.querySelector("[data-city-menu]");
        const keywordInput = form.querySelector("[name='keyword']");
        const catInput = form.querySelector("[name='cat']");
        const catTypeInput = form.querySelector("[name='cat_type']");
        const zoneInput = form.querySelector("[name='zone']");
        const defaultLandingSlug = (form.getAttribute("data-default-landing-slug") || "").trim() || "colombia";
        const selectedCitySlug = cityMenuRoot ? (cityMenuRoot.dataset.selectedCitySlug || "").trim() : "";
        const citySlug = selectedCitySlug || normalizeSlug(cityInput ? cityInput.value : "");
        const zoneSlug = normalizeSlug(zoneInput ? zoneInput.value : "");
        const detectedCategory = resolveCategoryFromKeyword(keywordInput ? keywordInput.value : "", form);
        const category = (catInput && catInput.value ? catInput.value.trim() : "") || detectedCategory.slug;
        const categoryType = (catTypeInput && catTypeInput.value ? catTypeInput.value.trim() : "") || detectedCategory.type;
        const queryParamMap = {
          service: "service",
          group: "group",
          budget: "budget",
        };
        const categoryQueryKey = queryParamMap[categoryType] || "";
        const isDirectLandingCategory = categoryType === "event" || categoryQueryKey === "";

        if (citySlug && zoneSlug && category && isDirectLandingCategory) {
          window.location.href = `/mariachis/${citySlug}/${category}?zone=${encodeURIComponent(zoneSlug)}`;
          return;
        }

        if (citySlug && zoneSlug) {
          const url = categoryQueryKey
            ? `/mariachis/${citySlug}/${zoneSlug}?${encodeURIComponent(categoryQueryKey)}=${encodeURIComponent(category)}`
            : `/mariachis/${citySlug}/${zoneSlug}`;
          window.location.href = url;
          return;
        }

        if (citySlug && category) {
          if (isDirectLandingCategory) {
            window.location.href = `/mariachis/${citySlug}/${category}`;
            return;
          }

          window.location.href = `/mariachis/${citySlug}?${encodeURIComponent(categoryQueryKey)}=${encodeURIComponent(category)}`;
          return;
        }

        if (citySlug) {
          window.location.href = `/mariachis/${citySlug}`;
          return;
        }

        if (category) {
          if (isDirectLandingCategory) {
            window.location.href = `/mariachis/${category}`;
            return;
          }

          window.location.href = `/mariachis/${defaultLandingSlug}?${encodeURIComponent(categoryQueryKey)}=${encodeURIComponent(category)}`;
          return;
        }

        window.location.href = `/mariachis/${defaultLandingSlug}`;
      });
    });
  }

  function getShareUrl() {
    const page = document.body.dataset.page || "";
    const city = getCityFromQuery();
    const listingSlug = getListingSlugFromQuery();
    const artist = getArtistProfile();

    if (window.location.protocol === "file:") {
      if (page === "listing") {
        return `https://mariachis.co/${city.slug}/${listingSlug}`;
      }
      if (page === "artist") {
        return `https://mariachis.co/artistas/${artist.artistSlug}?city=${city.slug}`;
      }
      if (page === "city") {
        return `https://mariachis.co/${city.slug}`;
      }
      return "https://mariachis.co";
    }

    return window.location.href;
  }

  function initShareButtons() {
    const shareButtons = Array.from(document.querySelectorAll("[data-share-btn]"));
    const copyButtons = Array.from(document.querySelectorAll("[data-share-copy]"));
    const nativeButtons = Array.from(document.querySelectorAll("[data-share-native]"));

    if (!shareButtons.length && !copyButtons.length && !nativeButtons.length) {
      return;
    }

    const page = document.body.dataset.page || "";
    const city = getCityFromQuery();
    const listingName = getListingFromQuery();
    const artist = getArtistProfile();
    const shareUrl = getShareUrl();
    const shareText =
      page === "artist"
        ? `Mira el perfil de ${artist.groupName || artist.name} en ${city.label} en mariachis.co`
        : page === "city"
          ? `Mira mariachis disponibles en ${city.label} en mariachis.co`
          : `Mira ${listingName} en ${city.label} en mariachis.co`;
    const shareTitle =
      page === "artist" ? `${artist.groupName || artist.name} | mariachis.co` : `${listingName} | mariachis.co`;
    const encodedUrl = encodeURIComponent(shareUrl);
    const encodedText = encodeURIComponent(shareText);

    const shareMap = {
      whatsapp: `https://wa.me/?text=${encodeURIComponent(`${shareText} ${shareUrl}`)}`,
      facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodedUrl}`,
      x: `https://twitter.com/intent/tweet?text=${encodedText}&url=${encodedUrl}`,
      telegram: `https://t.me/share/url?url=${encodedUrl}&text=${encodedText}`,
    };

    shareButtons.forEach((button) => {
      const platform = button.getAttribute("data-share-btn");
      const targetUrl = platform ? shareMap[platform] : "";
      if (!targetUrl) {
        return;
      }

      button.setAttribute("href", targetUrl);
      button.setAttribute("target", "_blank");
      button.setAttribute("rel", "noopener noreferrer");
    });

    function showShareStatus(button, message) {
      const shareBox = button.closest("[data-share-box]");
      const status = shareBox ? shareBox.querySelector("[data-share-status]") : null;
      if (!status) {
        return;
      }

      status.textContent = message;
      status.classList.remove("hidden");
      window.setTimeout(function () {
        status.classList.add("hidden");
      }, 2200);
    }

    async function copyShareUrl() {
      if (navigator.clipboard && navigator.clipboard.writeText) {
        await navigator.clipboard.writeText(shareUrl);
        return;
      }

      const helper = document.createElement("textarea");
      helper.value = shareUrl;
      helper.style.position = "fixed";
      helper.style.opacity = "0";
      document.body.appendChild(helper);
      helper.focus();
      helper.select();
      document.execCommand("copy");
      document.body.removeChild(helper);
    }

    copyButtons.forEach((button) => {
      button.addEventListener("click", async function () {
        try {
          await copyShareUrl();
          showShareStatus(button, "Enlace copiado al portapapeles");
        } catch (_error) {
          showShareStatus(button, "No se pudo copiar, intenta manualmente");
        }
      });
    });

    nativeButtons.forEach((button) => {
      button.addEventListener("click", async function () {
        if (navigator.share) {
          try {
            await navigator.share({
              title: shareTitle,
              text: shareText,
              url: shareUrl,
            });
            showShareStatus(button, "Contenido compartido");
            return;
          } catch (error) {
            if (error && error.name === "AbortError") {
              return;
            }
          }
        }

        try {
          await copyShareUrl();
          showShareStatus(button, "Enlace copiado al portapapeles");
        } catch (_error) {
          showShareStatus(button, "No se pudo compartir");
        }
      });
    });
  }

  function formatCop(value) {
    try {
      return new Intl.NumberFormat("es-CO", {
        style: "currency",
        currency: "COP",
        maximumFractionDigits: 0,
      }).format(value);
    } catch (_error) {
      return `$${Math.round(value).toLocaleString("es-CO")}`;
    }
  }

  function initQuoteBuilder() {
    const builders = Array.from(document.querySelectorAll("[data-quote-builder]"));

    if (!builders.length) {
      return;
    }

    const listingSlug = getListingSlugFromQuery();
    const city = getCityFromQuery();
    const listingName = getListingFromQuery();
    const basePrice = LISTING_BASE_PRICES[listingSlug] || 280000;

    builders.forEach((builder) => {
      const durationSelect = builder.querySelector("select[name='duration']");
      const zoneSelect = builder.querySelector("select[name='zone']");
      const daySelect = builder.querySelector("select[name='day']");
      const extras = Array.from(builder.querySelectorAll("[data-quote-extra]"));
      const totalNode = builder.querySelector("[data-quote-total]");
      const breakdownNode = builder.querySelector("[data-quote-breakdown]");
      const quoteWhatsapp = builder.querySelector("[data-quote-whatsapp]");

      function recalculate() {
        const durationMultiplier = parseFloat(durationSelect ? durationSelect.value : "1") || 1;
        const zoneExtra = parseInt(zoneSelect ? zoneSelect.value : "0", 10) || 0;
        const dayMultiplier = parseFloat(daySelect ? daySelect.value : "1") || 1;
        const extrasTotal = extras.reduce((acc, extra) => {
          if (extra instanceof HTMLInputElement && extra.checked) {
            return acc + (parseInt(extra.value || "0", 10) || 0);
          }
          return acc;
        }, 0);

        const baseWithDuration = Math.round(basePrice * durationMultiplier);
        const withDay = Math.round((baseWithDuration + zoneExtra) * dayMultiplier);
        const total = withDay + extrasTotal;

        if (totalNode) {
          totalNode.textContent = formatCop(total);
        }

        if (breakdownNode) {
          breakdownNode.textContent = `Base ${formatCop(basePrice)} + ajustes ${formatCop(
            total - basePrice
          )}.`;
        }

        if (quoteWhatsapp) {
          const message = encodeURIComponent(
            `Hola, quiero cotizar ${listingName} en ${city.label}. Estimado visual: ${formatCop(
              total
            )}.`
          );
          quoteWhatsapp.setAttribute("href", `https://wa.me/573001112233?text=${message}`);
        }
      }

      if (durationSelect) {
        durationSelect.addEventListener("change", recalculate);
      }
      if (zoneSelect) {
        zoneSelect.addEventListener("change", recalculate);
      }
      if (daySelect) {
        daySelect.addEventListener("change", recalculate);
      }
      extras.forEach((extra) => {
        extra.addEventListener("change", recalculate);
      });

      recalculate();
    });
  }

  function initReadMore() {
    const blocks = Array.from(document.querySelectorAll("[data-readmore]"));

    if (!blocks.length) {
      return;
    }

    blocks.forEach((block) => {
      const content = block.querySelector("[data-readmore-content]");
      const toggle = block.querySelector("[data-readmore-toggle]");

      if (!content || !toggle) {
        return;
      }

      const collapsedHeight = 212;
      const hasOverflow = content.scrollHeight > collapsedHeight + 8;

      if (!hasOverflow) {
        block.setAttribute("data-expanded", "true");
        toggle.classList.add("hidden");
        return;
      }

      block.setAttribute("data-expanded", "false");

      toggle.addEventListener("click", function () {
        const expanded = block.getAttribute("data-expanded") === "true";
        block.setAttribute("data-expanded", expanded ? "false" : "true");
        toggle.textContent = expanded ? "Leer más" : "Leer menos";
      });
    });
  }

  function initListingAnchorNav() {
    const nav = document.querySelector("[data-listing-anchor-nav]");
    if (!nav) {
      return;
    }
    const stickyShell = nav.closest("[data-listing-anchor-shell]");
    if (!stickyShell) {
      return;
    }
    const budgetCard = document.querySelector(".listing-budget-card");

    const links = Array.from(nav.querySelectorAll('a[href^="#"]'));
    if (!links.length) {
      return;
    }

    const items = links
      .map((link) => {
        const href = link.getAttribute("href") || "";
        const id = href.startsWith("#") ? href.slice(1) : "";
        if (!id) {
          return null;
        }
        const section = document.getElementById(id);
        if (!section) {
          return null;
        }
        return { id, link, section };
      })
      .filter(Boolean);

    if (!items.length) {
      return;
    }

    function syncAnchorLayout() {
      const header = document.querySelector("header");
      const topOffset = header ? Math.max(0, Math.round(header.getBoundingClientRect().bottom)) : 0;

      if (window.matchMedia("(max-width: 767px)").matches) {
        stickyShell.classList.remove("is-docked");
        document.body.classList.remove("listing-anchor-docked");
        if (budgetCard) {
          budgetCard.classList.remove("is-inline-form-active");
        }
        stickyShell.style.removeProperty("--listing-anchor-bleed");
        return;
      }

      const shellTop = stickyShell.getBoundingClientRect().top;
      const docked = shellTop <= topOffset + 1;
      stickyShell.classList.toggle("is-docked", docked);
      document.body.classList.toggle("listing-anchor-docked", docked);
      if (budgetCard) {
        budgetCard.classList.toggle("is-inline-form-active", docked);
      }

      if (!docked) {
        stickyShell.style.removeProperty("--listing-anchor-bleed");
        return;
      }

      const parent = stickyShell.parentElement;
      const left = parent ? parent.getBoundingClientRect().left : stickyShell.getBoundingClientRect().left;
      stickyShell.style.setProperty("--listing-anchor-bleed", `${Math.max(0, left)}px`);
    }

    function getScrollOffset() {
      const header = document.querySelector("header");
      const headerHeight = header ? Math.max(0, Math.round(header.getBoundingClientRect().height)) : 0;
      const anchorHeight = stickyShell
        ? stickyShell.getBoundingClientRect().height
        : nav.getBoundingClientRect().height;
      return headerHeight + anchorHeight + 12;
    }

    function setActive(id) {
      items.forEach((item) => {
        const active = item.id === id;
        item.link.classList.toggle("is-active", active);
        if (active) {
          item.link.setAttribute("aria-current", "page");
        } else {
          item.link.removeAttribute("aria-current");
        }
      });
    }

    function syncActiveSection() {
      const threshold = window.scrollY + getScrollOffset();
      let activeId = items[0].id;

      items.forEach((item) => {
        if (threshold >= item.section.offsetTop) {
          activeId = item.id;
        }
      });

      setActive(activeId);
    }

    items.forEach((item) => {
      item.link.addEventListener("click", function (event) {
        event.preventDefault();
        const top = item.section.getBoundingClientRect().top + window.scrollY - getScrollOffset() + 8;
        window.scrollTo({
          top: Math.max(top, 0),
          behavior: "smooth",
        });
        setActive(item.id);
      });
    });

    window.addEventListener(
      "scroll",
      function () {
        syncAnchorLayout();
        syncActiveSection();
      },
      { passive: true }
    );
    window.addEventListener("resize", function () {
      syncAnchorLayout();
      syncActiveSection();
    });
    syncAnchorLayout();
    syncActiveSection();
  }

  function initBudgetInlineForm() {
    const forms = Array.from(document.querySelectorAll("[data-budget-inline-form]"));
    if (!forms.length) {
      return;
    }

    const mobileModalTrigger = document.querySelector(".mobile-cta [data-open-budget-modal]");
    const anchorShell = document.querySelector("[data-listing-anchor-shell]");

    forms.forEach((form) => {
      const status = form.querySelector("[data-budget-inline-status]");
      const firstField = form.querySelector("input");
      const budgetCard = form.closest(".listing-budget-card");

      form.addEventListener("submit", function (event) {
        event.preventDefault();

        const data = new FormData(form);
        const name = String(data.get("name") || "").trim();
        const email = String(data.get("email") || "").trim();
        const phone = String(data.get("phone") || "").trim();
        const date = String(data.get("event_date") || "").trim();

        if (!name || !email || !phone || !date) {
          return;
        }

        const city = getCityFromQuery();
        const listing = getListingFromQuery();
        const contactNode = document.querySelector("[data-listing-whatsapp-link]");
        let phoneDigits = "573001112233";

        if (contactNode) {
          const href = contactNode.getAttribute("href") || "";
          const digits = href.match(/wa\.me\/(\d+)/);
          if (digits && digits[1]) {
            phoneDigits = digits[1];
          }
        }

        const message = encodeURIComponent(
          [
            `Hola, quiero solicitar presupuesto para ${listing} en ${city.label}.`,
            `Nombre: ${name}`,
            `Email: ${email}`,
            `Telefono: ${phone}`,
            `Fecha del evento: ${date}`,
          ].join("\n")
        );

        if (status) {
          status.classList.remove("hidden");
        }

        window.open(`https://wa.me/${phoneDigits}?text=${message}`, "_blank", "noopener,noreferrer");
      });

      document.querySelectorAll("[data-open-inline-form]").forEach((button) => {
        button.addEventListener("click", function () {
          if (window.matchMedia("(max-width: 767px)").matches && mobileModalTrigger) {
            mobileModalTrigger.click();
            return;
          }

          if (budgetCard && !budgetCard.classList.contains("is-inline-form-active")) {
            const anchorTop = anchorShell
              ? anchorShell.getBoundingClientRect().top + window.scrollY
              : window.scrollY + 260;

            window.scrollTo({
              top: Math.max(anchorTop + 1, 0),
              behavior: "smooth",
            });

            if (firstField) {
              window.setTimeout(function () {
                if (budgetCard.classList.contains("is-inline-form-active")) {
                  firstField.focus();
                }
              }, 360);
            }
            return;
          }

          const top = form.getBoundingClientRect().top + window.scrollY - 140;
          window.scrollTo({
            top: Math.max(top, 0),
            behavior: "smooth",
          });

          if (firstField) {
            window.setTimeout(function () {
              firstField.focus();
            }, 260);
          }
        });
      });
    });
  }

  function initBudgetModal() {
    const modal = document.querySelector("[data-budget-modal]");
    const openButtons = Array.from(document.querySelectorAll("[data-open-budget-modal]"));

    if (!modal || !openButtons.length) {
      return;
    }

    const closeButtons = Array.from(modal.querySelectorAll("[data-budget-modal-close]"));
    const form = modal.querySelector("[data-budget-form]");
    const status = modal.querySelector("[data-budget-status]");

    function openModal() {
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("overflow-hidden");
    }

    function closeModal() {
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("overflow-hidden");
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
      if (event.key === "Escape") {
        closeModal();
      }
    });

    if (!form) {
      return;
    }

    form.addEventListener("submit", function (event) {
      event.preventDefault();

      const data = new FormData(form);
      const name = String(data.get("name") || "").trim();
      const email = String(data.get("email") || "").trim();
      const phone = String(data.get("phone") || "").trim();
      const date = String(data.get("event_date") || "").trim();

      if (!name || !email || !phone || !date) {
        return;
      }

      const city = getCityFromQuery();
      const listing = getListingFromQuery();
      const contactNode = document.querySelector("[data-listing-whatsapp-link]");
      let phoneDigits = "573001112233";

      if (contactNode) {
        const href = contactNode.getAttribute("href") || "";
        const digits = href.match(/wa\.me\/(\d+)/);
        if (digits && digits[1]) {
          phoneDigits = digits[1];
        }
      }

      const message = encodeURIComponent(
        [
          `Hola, quiero solicitar presupuesto para ${listing} en ${city.label}.`,
          `Nombre: ${name}`,
          `Email: ${email}`,
          `Telefono: ${phone}`,
          `Fecha del evento: ${date}`,
        ].join("\n")
      );
      const whatsappUrl = `https://wa.me/${phoneDigits}?text=${message}`;

      if (status) {
        status.classList.remove("hidden");
      }

      window.setTimeout(function () {
        window.open(whatsappUrl, "_blank", "noopener,noreferrer");
        closeModal();
      }, 260);
    });
  }

  function initAccordion() {
    document.querySelectorAll("[data-accordion]").forEach((group) => {
      const triggers = Array.from(group.querySelectorAll("[data-accordion-trigger]"));

      function setOpen(trigger, open) {
        const panelId = trigger.getAttribute("aria-controls");
        const panel = panelId ? document.getElementById(panelId) : null;
        const wrapper = trigger.closest("[data-accordion-item]") || trigger.parentElement;
        const icon = trigger.querySelector("[data-accordion-icon]");

        trigger.setAttribute("aria-expanded", open ? "true" : "false");
        if (panel) {
          panel.classList.toggle("hidden", !open);
        }
        if (wrapper) {
          wrapper.classList.toggle("faq-open", open);
        }
        if (icon) {
          icon.textContent = open ? "−" : "+";
        }
      }

      triggers.forEach((trigger) => {
        setOpen(trigger, false);

        trigger.addEventListener("click", function () {
          const wasOpen = trigger.getAttribute("aria-expanded") === "true";

          triggers.forEach((otherTrigger) => {
            setOpen(otherTrigger, false);
          });

          if (!wasOpen) {
            setOpen(trigger, true);
          }
        });
      });
    });
  }

  function initTabs() {
    document.querySelectorAll("[data-tabs]").forEach((tabsRoot) => {
      const buttons = Array.from(tabsRoot.querySelectorAll("[data-tab-target]"));
      const panels = Array.from(tabsRoot.querySelectorAll("[data-tab-panel]"));

      if (!buttons.length) {
        return;
      }

      function activate(targetName) {
        buttons.forEach((button) => {
          const isActive = button.getAttribute("data-tab-target") === targetName;
          button.classList.toggle("tab-active", isActive);
          button.classList.toggle("tab-idle", !isActive);
        });

        panels.forEach((panel) => {
          panel.classList.toggle("hidden", panel.getAttribute("data-tab-panel") !== targetName);
        });
      }

      buttons.forEach((button) => {
        button.addEventListener("click", function () {
          const target = button.getAttribute("data-tab-target");
          if (target) {
            activate(target);
          }
        });
      });

      const firstTarget = buttons[0].getAttribute("data-tab-target") || "";
      activate(firstTarget);
    });
  }

  function initGalleryModal() {
    if (document.querySelector('[data-gallery-experience="viator"]')) {
      return;
    }

    const items = Array.from(document.querySelectorAll("[data-gallery-item]")).filter((item) => {
      return (item.getAttribute("data-src") || "").trim() !== "";
    });

    if (!items.length) {
      return;
    }

    function escapeHtml(value) {
      return String(value)
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#39;");
    }

    function getYouTubeId(url) {
      const match = String(url || "").match(/embed\/([^?&"'#/]+)/);
      return match ? match[1] : "";
    }

    function getSlideThumb(slide) {
      if (slide.type === "image") {
        return slide.src;
      }

      const videoId = getYouTubeId(slide.src);
      return videoId ? `https://i.ytimg.com/vi/${videoId}/hqdefault.jpg` : "";
    }

    const slides = [];
    const slideIndexByKey = new Map();

    items.forEach((item) => {
      const type = item.getAttribute("data-type") || "image";
      const src = item.getAttribute("data-src") || "";
      const title = item.getAttribute("data-title") || "Galeria";
      const key = `${type}::${src}`;

      let slideIndex = slideIndexByKey.get(key);
      if (typeof slideIndex !== "number") {
        slideIndex = slides.length;
        slideIndexByKey.set(key, slideIndex);
        slides.push({
          type,
          src,
          title,
          thumb: getSlideThumb({ type, src }),
        });
      }

      item.setAttribute("data-gallery-index", String(slideIndex));
    });

    if (!slides.length) {
      return;
    }

    const modal = document.createElement("div");
    modal.id = "gallery-modal";
    modal.className = "gallery-modal hidden";
    modal.setAttribute("aria-hidden", "true");

    modal.innerHTML = `
      <div class="gallery-modal__card" role="dialog" aria-modal="true" aria-label="Galeria del anuncio">
        <div class="gallery-modal__top">
          <div class="gallery-modal__meta">
            <span data-gallery-counter class="gallery-modal__counter"></span>
            <p data-gallery-title class="gallery-modal__title"></p>
          </div>
          <button type="button" data-gallery-close class="gallery-modal__close" aria-label="Cerrar galeria">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
          </button>
        </div>

        <div class="gallery-modal__stage-shell">
          <button type="button" data-gallery-prev class="gallery-modal__nav gallery-modal__nav--prev" aria-label="Anterior">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <div data-gallery-stage class="gallery-modal__stage"></div>
          <button type="button" data-gallery-next class="gallery-modal__nav gallery-modal__nav--next" aria-label="Siguiente">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
              <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>

        <div data-gallery-thumbs class="gallery-modal__thumbs" aria-label="Miniaturas de la galeria"></div>
      </div>
    `;

    document.body.appendChild(modal);

    const stage = modal.querySelector("[data-gallery-stage]");
    const thumbs = modal.querySelector("[data-gallery-thumbs]");
    const title = modal.querySelector("[data-gallery-title]");
    const counter = modal.querySelector("[data-gallery-counter]");
    const closeBtn = modal.querySelector("[data-gallery-close]");
    const nextBtn = modal.querySelector("[data-gallery-next]");
    const prevBtn = modal.querySelector("[data-gallery-prev]");

    let currentIndex = 0;

    if (!stage || !thumbs || !title || !counter || !closeBtn || !nextBtn || !prevBtn) {
      modal.remove();
      return;
    }

    thumbs.innerHTML = slides
      .map((slide, index) => {
        const imageMarkup = slide.thumb
          ? `<img src="${escapeHtml(slide.thumb)}" alt="${escapeHtml(slide.title)}" loading="lazy" />`
          : '<span class="gallery-modal__thumb-fallback">Media</span>';
        const badgeMarkup =
          slide.type === "video"
            ? '<span class="gallery-modal__thumb-badge">Video</span>'
            : "";

        return `
          <button
            type="button"
            class="gallery-modal__thumb"
            data-gallery-thumb="${index}"
            aria-label="Abrir elemento ${index + 1}"
          >
            ${imageMarkup}
            ${badgeMarkup}
          </button>
        `;
      })
      .join("");

    const thumbButtons = Array.from(modal.querySelectorAll("[data-gallery-thumb]"));

    function renderStage(slide) {
      if (!slide) {
        return;
      }

      if (slide.type === "video") {
        stage.innerHTML = `
          <div class="gallery-modal__video">
            <iframe src="${escapeHtml(slide.src)}" title="${escapeHtml(slide.title)}" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
          </div>
        `;
      } else {
        stage.innerHTML = `
          <figure>
            <img src="${escapeHtml(slide.src)}" alt="${escapeHtml(slide.title)}" />
          </figure>
        `;
      }
    }

    function syncThumbs() {
      thumbButtons.forEach((button, index) => {
        button.classList.toggle("is-active", index === currentIndex);
      });

      const activeThumb = thumbButtons[currentIndex];
      if (activeThumb) {
        activeThumb.scrollIntoView({
          block: "nearest",
          inline: "center",
          behavior: "smooth",
        });
      }
    }

    function syncNavButtons() {
      prevBtn.disabled = currentIndex <= 0;
      nextBtn.disabled = currentIndex >= slides.length - 1;
    }

    function render(index) {
      const slide = slides[index];
      if (!slide) {
        return;
      }

      counter.textContent = `${index + 1} / ${slides.length}`;
      title.textContent = slide.title;
      renderStage(slide);
      syncThumbs();
      syncNavButtons();
    }

    function setCurrentIndex(index) {
      currentIndex = Math.max(0, Math.min(index, slides.length - 1));
      render(currentIndex);
    }

    function open(index) {
      setCurrentIndex(index);
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      document.body.style.overflow = "hidden";
    }

    function close() {
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.style.overflow = "";
    }

    function next() {
      if (currentIndex < slides.length - 1) {
        setCurrentIndex(currentIndex + 1);
      }
    }

    function prev() {
      if (currentIndex > 0) {
        setCurrentIndex(currentIndex - 1);
      }
    }

    items.forEach((item) => {
      item.addEventListener("click", function () {
        const targetIndex = Number.parseInt(item.getAttribute("data-gallery-index") || "0", 10);
        open(Number.isNaN(targetIndex) ? 0 : targetIndex);
      });
    });

    thumbButtons.forEach((button) => {
      button.addEventListener("click", function () {
        const targetIndex = Number.parseInt(button.getAttribute("data-gallery-thumb") || "0", 10);
        setCurrentIndex(Number.isNaN(targetIndex) ? 0 : targetIndex);
      });
    });

    closeBtn.addEventListener("click", close);
    nextBtn.addEventListener("click", next);
    prevBtn.addEventListener("click", prev);

    modal.addEventListener("click", function (event) {
      if (event.target === modal) {
        close();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (modal.classList.contains("hidden")) {
        return;
      }

      if (event.key === "Escape") {
        close();
      }

      if (event.key === "ArrowRight") {
        event.preventDefault();
        next();
      }

      if (event.key === "ArrowLeft") {
        event.preventDefault();
        prev();
      }
    });
  }

  function initCityChips() {
    document.querySelectorAll("[data-city-chip]").forEach((chip) => {
      chip.addEventListener("click", function () {
        const slug = chip.getAttribute("data-city-chip");
        if (slug) {
          window.location.href = `/mariachis/${slug}`;
        }
      });
    });
  }

  function initZoneCarousel() {
    const track = document.querySelector("[data-zone-carousel]");
    const leftButton = document.querySelector('[data-zone-scroll="left"]');
    const rightButton = document.querySelector('[data-zone-scroll="right"]');

    if (!track || !leftButton || !rightButton) {
      return;
    }

    function getStep() {
      const firstCard = track.querySelector(".zone-card");
      if (!firstCard) {
        return 260;
      }
      return firstCard.getBoundingClientRect().width + 12;
    }

    function updateButtons() {
      const maxScroll = Math.max(track.scrollWidth - track.clientWidth, 0);
      const current = track.scrollLeft;
      leftButton.disabled = current <= 3;
      rightButton.disabled = current >= maxScroll - 3;
    }

    leftButton.addEventListener("click", function () {
      track.scrollBy({
        left: -getStep(),
        behavior: "smooth",
      });
    });

    rightButton.addEventListener("click", function () {
      track.scrollBy({
        left: getStep(),
        behavior: "smooth",
      });
    });

    track.addEventListener("scroll", updateButtons, { passive: true });
    window.addEventListener("resize", updateButtons);
    updateButtons();
  }

  function initHomeCityShowcase() {
    document.querySelectorAll("[data-home-city-showcase]").forEach((showcase) => {
      const tabs = Array.from(showcase.querySelectorAll("[data-city-showcase-tab]"));
      const panels = Array.from(showcase.querySelectorAll("[data-city-showcase-panel]"));

      if (!tabs.length || !panels.length) {
        return;
      }

      const hasPanel = (city) =>
        panels.some((panel) => panel.getAttribute("data-city-showcase-panel") === city);

      function setActive(city) {
        tabs.forEach((tab) => {
          const isActive = tab.getAttribute("data-city-showcase-tab") === city;
          tab.classList.toggle("is-active", isActive);
          tab.setAttribute("aria-selected", isActive ? "true" : "false");
          tab.tabIndex = isActive ? 0 : -1;
        });

        panels.forEach((panel) => {
          panel.classList.toggle(
            "is-active",
            panel.getAttribute("data-city-showcase-panel") === city
          );
        });
      }

      const initialCity =
        (tabs.find((tab) => tab.classList.contains("is-active")) || tabs[0]).getAttribute(
          "data-city-showcase-tab"
        ) || "";

      if (initialCity && hasPanel(initialCity)) {
        setActive(initialCity);
      }

      tabs.forEach((tab, index) => {
        tab.addEventListener("click", function () {
          const city = tab.getAttribute("data-city-showcase-tab") || "";
          if (!city || !hasPanel(city)) {
            return;
          }
          setActive(city);
        });

        tab.addEventListener("keydown", function (event) {
          if (event.key !== "ArrowRight" && event.key !== "ArrowLeft") {
            return;
          }

          event.preventDefault();
          const direction = event.key === "ArrowRight" ? 1 : -1;
          const nextIndex = (index + direction + tabs.length) % tabs.length;
          const nextTab = tabs[nextIndex];
          if (!nextTab) {
            return;
          }

          const city = nextTab.getAttribute("data-city-showcase-tab") || "";
          if (!city || !hasPanel(city)) {
            return;
          }

          setActive(city);
          nextTab.focus();
        });
      });
    });
  }

  function initFeaturedCarousel() {
    const track = document.querySelector("[data-featured-carousel]");
    const leftButton = document.querySelector('[data-featured-scroll="left"]');
    const rightButton = document.querySelector('[data-featured-scroll="right"]');

    if (!track || !leftButton || !rightButton) {
      return;
    }

    function getStep() {
      const firstVisible =
        track.querySelector(".featured-promo-card:not(.hidden)") ||
        track.querySelector(".featured-promo-card");
      if (!firstVisible) {
        return 300;
      }
      return firstVisible.getBoundingClientRect().width + 16;
    }

    function updateButtons() {
      const maxScroll = Math.max(track.scrollWidth - track.clientWidth, 0);
      const current = track.scrollLeft;
      leftButton.disabled = current <= 3;
      rightButton.disabled = current >= maxScroll - 3;
    }

    leftButton.addEventListener("click", function () {
      track.scrollBy({
        left: -getStep(),
        behavior: "smooth",
      });
    });

    rightButton.addEventListener("click", function () {
      track.scrollBy({
        left: getStep(),
        behavior: "smooth",
      });
    });

    const mutationObserver = new MutationObserver(function () {
      window.requestAnimationFrame(updateButtons);
    });
    mutationObserver.observe(track, {
      subtree: true,
      attributes: true,
      attributeFilter: ["class"],
    });

    track.addEventListener("scroll", updateButtons, { passive: true });
    window.addEventListener("resize", updateButtons);
    updateButtons();
  }

  function updateFavoriteCount() {
    document.querySelectorAll("[data-favorite-count]").forEach((node) => {
      node.textContent = String(state.favorites.size);
    });
  }

  function setFavoriteButtonState(button, active) {
    button.classList.toggle("is-active", active);
    button.setAttribute("aria-pressed", active ? "true" : "false");
    button.setAttribute("aria-label", active ? "Quitar de favoritos" : "Guardar en favoritos");

    const icon = button.querySelector("[data-fav-icon]");
    if (icon) {
      icon.setAttribute("fill", active ? "currentColor" : "none");
    }
  }

  function initFavoriteButtons() {
    const buttons = Array.from(document.querySelectorAll("[data-favorite]"));

    if (!buttons.length) {
      return;
    }

    buttons.forEach((button) => {
      const id = button.getAttribute("data-favorite");
      if (!id) {
        return;
      }

      setFavoriteButtonState(button, state.favorites.has(id));

      button.addEventListener("click", function (event) {
        event.preventDefault();
        event.stopPropagation();

        if (state.favorites.has(id)) {
          state.favorites.delete(id);
        } else {
          state.favorites.add(id);
        }

        setStoredFavorites(state.favorites);
        document.querySelectorAll(`[data-favorite="${id}"]`).forEach((node) => {
          setFavoriteButtonState(node, state.favorites.has(id));
        });
        updateFavoriteCount();
        document.dispatchEvent(new CustomEvent("favorites:changed"));
      });
    });

    updateFavoriteCount();
  }

  function initClickableCards() {
    const cards = Array.from(document.querySelectorAll("[data-card-url]"));

    if (!cards.length) {
      return;
    }

    const interactiveSelector =
      "a, button, input, select, textarea, summary, label, [role='button'], [data-favorite], [data-compare-toggle]";

    function isInteractiveTarget(target) {
      if (!(target instanceof Element)) {
        return false;
      }

      return Boolean(target.closest(interactiveSelector));
    }

    function navigateToCard(card) {
      const url = (card.getAttribute("data-card-url") || "").trim();
      if (!url) {
        return;
      }

      window.location.href = url;
    }

    cards.forEach((card) => {
      card.classList.add("is-clickable-card");

      card.addEventListener("click", function (event) {
        if (isInteractiveTarget(event.target)) {
          return;
        }

        navigateToCard(card);
      });

      card.addEventListener("keydown", function (event) {
        if (event.key !== "Enter" && event.key !== " ") {
          return;
        }

        if (isInteractiveTarget(event.target)) {
          return;
        }

        event.preventDefault();
        navigateToCard(card);
      });
    });
  }

  function initFilterChips() {
    document.querySelectorAll("[data-filter-wrap]").forEach((wrap) => {
      const group = wrap.getAttribute("data-filter-wrap");
      const chips = Array.from(wrap.querySelectorAll("[data-filter-chip]"));
      const cards = Array.from(document.querySelectorAll(`[data-filter-card="${group}"]`));
      const queryFilter = getCategoryFromQuery();
      const activeRow = document.querySelector(`[data-active-filter-row="${group}"]`);
      const activeChipNode = activeRow
        ? activeRow.querySelector("[data-active-filter-chip]")
        : null;
      const clearButtons = Array.from(document.querySelectorAll(`[data-clear-filter="${group}"]`));

      if (!group || !chips.length || !cards.length) {
        return;
      }

      let currentFilter =
        (chips.find((chip) => chip.classList.contains("is-active")) || chips[0]).getAttribute(
          "data-filter-chip"
        ) || "all";
      if (queryFilter && chips.some((chip) => chip.getAttribute("data-filter-chip") === queryFilter)) {
        currentFilter = queryFilter;
      }

      function syncFilterInUrl(filter) {
        if (group !== "city-results") {
          return;
        }

        try {
          const url = new URL(window.location.href);
          if (filter === "all") {
            url.searchParams.delete("cat");
          } else {
            url.searchParams.set("cat", filter);
          }
          window.history.replaceState({}, "", url.toString());
        } catch (_error) {
          // URL sync is optional in static prototype mode.
        }
      }

      function updateActiveRow(filter) {
        if (!activeRow || !activeChipNode) {
          return;
        }

        if (filter === "all") {
          activeRow.classList.add("hidden");
          return;
        }

        const currentChip = chips.find((chip) => chip.getAttribute("data-filter-chip") === filter);
        activeChipNode.textContent = currentChip ? currentChip.textContent || filter : filter;
        activeRow.classList.remove("hidden");
      }

      function applyFilter(filter) {
        let visible = 0;

        cards.forEach((card) => {
          const tags = (card.getAttribute("data-card-tags") || "")
            .split(",")
            .map((tag) => tag.trim().toLowerCase())
            .filter(Boolean);
          const favoriteId = card.getAttribute("data-favorite-id") || "";

          const matchesTag = filter === "all" || filter === "favoritos" || tags.includes(filter);
          const matchesFavorite =
            filter !== "favoritos" || (favoriteId && state.favorites.has(favoriteId));

          const show = matchesTag && matchesFavorite;
          card.classList.toggle("hidden", !show);
          if (show) {
            visible += 1;
          }
        });

        document.querySelectorAll(`[data-filter-count="${group}"]`).forEach((node) => {
          node.textContent = String(visible);
        });

        const emptyState = document.querySelector(`[data-empty-state="${group}"]`);
        if (emptyState) {
          emptyState.classList.toggle("hidden", visible !== 0);
        }

        updateActiveRow(filter);
        document.dispatchEvent(
          new CustomEvent("filters:applied", {
            detail: {
              group,
              visible,
            },
          })
        );
      }

      function setActiveChip(activeChip) {
        chips.forEach((chip) => {
          chip.classList.toggle("is-active", chip === activeChip);
        });
        currentFilter = activeChip.getAttribute("data-filter-chip") || "all";
        syncFilterInUrl(currentFilter);
        applyFilter(currentFilter);
      }

      chips.forEach((chip) => {
        chip.addEventListener("click", function () {
          setActiveChip(chip);
        });
      });

      clearButtons.forEach((button) => {
        button.addEventListener("click", function () {
          const defaultChip =
            chips.find((chip) => chip.getAttribute("data-filter-chip") === "all") || chips[0];
          setActiveChip(defaultChip);
        });
      });

      const initialChip =
        chips.find((chip) => chip.getAttribute("data-filter-chip") === currentFilter) || chips[0];
      setActiveChip(initialChip);

      document.addEventListener("favorites:changed", function () {
        applyFilter(currentFilter);
      });
    });
  }

  function initMobileFilterDrawer() {
    const drawer = document.querySelector("[data-filter-drawer]");
    const openButtons = Array.from(document.querySelectorAll("[data-mobile-filter-toggle]"));
    const closeButtons = Array.from(document.querySelectorAll("[data-filter-drawer-close]"));

    if (!drawer || !openButtons.length) {
      return;
    }

    function openDrawer() {
      drawer.classList.remove("hidden");
      drawer.setAttribute("aria-hidden", "false");
      document.body.classList.add("overflow-hidden");
    }

    function closeDrawer() {
      drawer.classList.add("hidden");
      drawer.setAttribute("aria-hidden", "true");
      document.body.classList.remove("overflow-hidden");
    }

    openButtons.forEach((button) => {
      button.addEventListener("click", openDrawer);
    });

    closeButtons.forEach((button) => {
      button.addEventListener("click", closeDrawer);
    });

    drawer.addEventListener("click", function (event) {
      if (event.target === drawer) {
        closeDrawer();
      }
    });

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeDrawer();
      }
    });
  }

  function initCityResultsView() {
    const panel = document.querySelector("[data-results-panel]");

    if (!panel) {
      return;
    }

    const buttons = Array.from(panel.querySelectorAll("[data-results-view-btn]"));
    const cardsWrap = panel.querySelector("[data-results-cards]");
    const mapWrap = panel.querySelector("[data-results-map]");
    const closeMapButton = panel.querySelector("[data-results-map-close]");
    const markers = Array.from(panel.querySelectorAll("[data-map-marker]"));
    const mapItems = Array.from(panel.querySelectorAll("[data-map-list-item]"));
    const mapEmpty = panel.querySelector("[data-map-empty]");
    const cards = Array.from(panel.querySelectorAll('[data-filter-card="city-results"]'));
    const viewQuery = getQueryParams().get("view");
    const allowedModes = new Set(["list", "gallery", "map"]);
    let currentMode = panel.getAttribute("data-view-mode") || "gallery";

    if (!buttons.length) {
      return;
    }

    if (viewQuery && allowedModes.has(viewQuery)) {
      currentMode = viewQuery;
    }
    if (!allowedModes.has(currentMode)) {
      currentMode = "gallery";
    }

    function syncModeInUrl(mode) {
      try {
        const url = new URL(window.location.href);
        if (mode === "gallery") {
          url.searchParams.delete("view");
        } else {
          url.searchParams.set("view", mode);
        }
        window.history.replaceState({}, "", url.toString());
      } catch (_error) {
        // URL sync is optional for static prototype mode.
      }
    }

    function updateMapNodes() {
      if (!markers.length && !mapItems.length) {
        return;
      }

      let visibleCards = 0;

      cards.forEach((card) => {
        const id =
          card.getAttribute("data-compare-id") ||
          card.getAttribute("data-favorite-id") ||
          "";
        const isVisible = !card.classList.contains("hidden");

        if (isVisible) {
          visibleCards += 1;
        }

        markers.forEach((marker) => {
          if (marker.getAttribute("data-marker-for") === id) {
            marker.classList.toggle("hidden", !isVisible);
          }
        });

        mapItems.forEach((item) => {
          if (item.getAttribute("data-marker-for") === id) {
            item.classList.toggle("hidden", !isVisible);
          }
        });
      });

      if (mapEmpty) {
        mapEmpty.classList.toggle("hidden", visibleCards !== 0);
      }
    }

    function setMode(mode, syncUrl) {
      const nextMode = allowedModes.has(mode) ? mode : "gallery";
      const shouldSync = syncUrl !== false;

      currentMode = nextMode;
      panel.setAttribute("data-view-mode", nextMode);

      buttons.forEach((button) => {
        const isActive = button.getAttribute("data-results-view-btn") === nextMode;
        button.classList.toggle("is-active", isActive);
        button.setAttribute("aria-selected", isActive ? "true" : "false");
      });

      if (cardsWrap) {
        cardsWrap.classList.toggle("hidden", nextMode === "map");
      }

      if (mapWrap) {
        const isMap = nextMode === "map";
        mapWrap.classList.toggle("hidden", !isMap);
        mapWrap.setAttribute("aria-hidden", isMap ? "false" : "true");
      }

      if (shouldSync) {
        syncModeInUrl(nextMode);
      }
    }

    buttons.forEach((button) => {
      button.addEventListener("click", function () {
        const mode = button.getAttribute("data-results-view-btn") || "gallery";
        setMode(mode, true);
      });
    });

    if (closeMapButton) {
      closeMapButton.addEventListener("click", function () {
        setMode("gallery", true);
      });
    }

    document.addEventListener("filters:applied", function (event) {
      if (!event.detail || event.detail.group !== "city-results") {
        return;
      }
      updateMapNodes();
    });

    document.addEventListener("favorites:changed", function () {
      updateMapNodes();
    });

    setMode(currentMode, false);
    updateMapNodes();
  }

  function initCompareExperience() {
    const cards = Array.from(document.querySelectorAll("[data-compare-item]"));
    const checkboxes = Array.from(document.querySelectorAll("[data-compare-checkbox]"));

    if (!cards.length || !checkboxes.length) {
      return;
    }

    const city = getCityFromQuery();
    const selected = new Set();
    const bar = document.querySelector("[data-compare-bar]");
    const modal = document.querySelector("[data-compare-modal]");
    const modalContent = modal ? modal.querySelector("[data-compare-content]") : null;
    const openButtons = Array.from(document.querySelectorAll("[data-open-compare]"));
    const closeButtons = Array.from(document.querySelectorAll("[data-compare-close]"));
    const clearButtons = Array.from(document.querySelectorAll("[data-compare-clear]"));
    const countNodes = Array.from(document.querySelectorAll("[data-compare-count]"));
    const statusNodes = Array.from(document.querySelectorAll("[data-compare-status]"));

    function setStatus(message) {
      statusNodes.forEach((node) => {
        node.textContent = message;
      });
    }

    function updateCounter() {
      countNodes.forEach((node) => {
        node.textContent = String(selected.size);
      });

      if (bar) {
        bar.classList.toggle("hidden", selected.size === 0);
      }

      if (selected.size >= 2) {
        setStatus("Listo para comparar");
      } else {
        setStatus("Selecciona 2 o 3 para comparar.");
      }
    }

    function getCardById(id) {
      return cards.find((card) => card.getAttribute("data-compare-id") === id) || null;
    }

    function renderModal() {
      if (!modalContent) {
        return;
      }

      const selectedCards = Array.from(selected)
        .map((id) => getCardById(id))
        .filter(Boolean);

      if (selectedCards.length < 2) {
        modalContent.innerHTML =
          '<p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-sm text-slate-600">Selecciona al menos dos anuncios para usar el comparador.</p>';
        return;
      }

      modalContent.innerHTML = selectedCards
        .map((card) => {
          const name = card.getAttribute("data-compare-name") || "Mariachi";
          const price = card.getAttribute("data-compare-price") || "Consultar";
          const rating = card.getAttribute("data-compare-rating") || "4.8";
          const response = card.getAttribute("data-compare-response") || "10 min";
          const type = card.getAttribute("data-compare-type") || "General";
          const listingSlug = card.getAttribute("data-compare-listing") || "";
          const artistSlug = card.getAttribute("data-compare-artist") || listingSlug;
          const imageNode = card.querySelector("img");
          const imageSrc = imageNode ? imageNode.getAttribute("src") || "" : "";
          const listingHref = listingSlug
            ? `listing.html?city=${city.slug}&listing=${listingSlug}`
            : "#";
          const artistHref = artistSlug
            ? `artist.html?city=${city.slug}&artist=${artistSlug}`
            : "#";

          return `
            <article class="compare-item">
              <img src="${imageSrc}" alt="${name}" />
              <div class="compare-item-body">
                <p class="text-sm font-extrabold text-slate-900">${name}</p>
                <p class="compare-item-meta">${type} · ${rating} ★</p>
                <p class="compare-item-meta">Desde ${price}</p>
                <p class="compare-item-meta">Respuesta: ${response}</p>
                <a href="${listingHref}" class="text-xs font-bold text-brand-700 hover:text-brand-600">Ver anuncio</a>
                <a href="${artistHref}" class="text-xs font-bold text-slate-700 hover:text-slate-900">Ver artista</a>
              </div>
            </article>
          `;
        })
        .join("");
    }

    function openModal() {
      if (!modal) {
        return;
      }

      if (selected.size < 2) {
        setStatus("Selecciona al menos 2 anuncios para comparar.");
        if (bar && selected.size === 0) {
          bar.classList.remove("hidden");
          window.setTimeout(function () {
            if (selected.size === 0) {
              bar.classList.add("hidden");
            }
          }, 2200);
        }
        return;
      }

      renderModal();
      modal.classList.remove("hidden");
      modal.setAttribute("aria-hidden", "false");
      document.body.classList.add("overflow-hidden");
    }

    function closeModal() {
      if (!modal) {
        return;
      }
      modal.classList.add("hidden");
      modal.setAttribute("aria-hidden", "true");
      document.body.classList.remove("overflow-hidden");
    }

    function clearSelection() {
      selected.clear();
      checkboxes.forEach((checkbox) => {
        checkbox.checked = false;
      });
      cards.forEach((card) => {
        card.classList.remove("is-compare-selected");
      });
      updateCounter();
      renderModal();
    }

    checkboxes.forEach((checkbox) => {
      const id = checkbox.value || "";
      if (!id) {
        return;
      }

      checkbox.addEventListener("change", function () {
        const card = checkbox.closest("[data-compare-item]");

        if (checkbox.checked) {
          if (selected.size >= 3) {
            checkbox.checked = false;
            setStatus("Máximo 3 anuncios en comparación.");
            return;
          }
          selected.add(id);
        } else {
          selected.delete(id);
        }

        if (card) {
          card.classList.toggle("is-compare-selected", selected.has(id));
        }

        updateCounter();
        if (modal && !modal.classList.contains("hidden")) {
          renderModal();
        }
      });
    });

    openButtons.forEach((button) => {
      button.addEventListener("click", openModal);
    });

    clearButtons.forEach((button) => {
      button.addEventListener("click", clearSelection);
    });

    closeButtons.forEach((button) => {
      button.addEventListener("click", closeModal);
    });

    if (modal) {
      modal.addEventListener("click", function (event) {
        if (event.target === modal) {
          closeModal();
        }
      });
    }

    document.addEventListener("keydown", function (event) {
      if (event.key === "Escape") {
        closeModal();
      }
    });

    updateCounter();
  }

  function initReveal() {
    const revealNodes = Array.from(document.querySelectorAll("[data-reveal]"));

    if (!revealNodes.length) {
      return;
    }

    const markVisible = () => {
      revealNodes.forEach((node) => {
        node.classList.add("is-visible");
      });
    };

    revealNodes.forEach((node, index) => {
      node.classList.add("reveal");
      const delay = Math.min(index * 45, 260);
      node.style.transitionDelay = `${delay}ms`;
    });

    if (!("IntersectionObserver" in window)) {
      markVisible();
      return;
    }

    const observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            entry.target.classList.add("is-visible");
            observer.unobserve(entry.target);
          }
        });
      },
      {
        threshold: 0.12,
        rootMargin: "0px 0px -40px 0px",
      }
    );

    revealNodes.forEach((node) => {
      observer.observe(node);
    });

    // Fallback defensivo para navegadores/entornos donde el observer no dispara.
    window.setTimeout(markVisible, 1400);
  }

  document.addEventListener("DOMContentLoaded", function () {
    initHomePasswordGate();
    initThemeSwitcher();
    injectSharedComponents();
    hydrateCityContext();
    hydrateArtistPage();
    initMobileMenu();
    initHeaderAccountMenu();
    initEventMegaMenu();
    initCityDropdownMenu();
    initSearchForms();
    initShareButtons();
    initQuoteBuilder();
    initReadMore();
    initListingAnchorNav();
    initBudgetInlineForm();
    initBudgetModal();
    initAccordion();
    initTabs();
    initGalleryModal();
    initCityChips();
    initZoneCarousel();
    initHomeCityShowcase();
    initFavoriteButtons();
    initClickableCards();
    initFilterChips();
    initCityResultsView();
    initFeaturedCarousel();
    initMobileFilterDrawer();
    initCompareExperience();
    initReveal();
  });
})();
