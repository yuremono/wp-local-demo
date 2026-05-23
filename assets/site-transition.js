(function () {
  const overlay = document.querySelector("[data-site-transition-overlay]");
  if (!(overlay instanceof HTMLElement)) return;

  const canvas = overlay.querySelector("[data-site-transition-canvas]");
  const probe = overlay.querySelector("[data-site-transition-probe]");
  if (!(canvas instanceof HTMLCanvasElement) || !(probe instanceof HTMLElement)) return;

  const VIEWED_KEY = "theme_initial_loading_viewed";
  const PENDING_KEY = "theme_page_transition_pending";
  const PENDING_LABEL_KEY = "theme_page_transition_label";
  const BODY_PENDING_CLASS = "SiteTransitionPending";
  const INITIAL_LOADING_ALWAYS_SHOW = false;
  const MINIMUM_MS_FALLBACK = 500;
  const TRANSITION_MS_FALLBACK = 500;
  const SQUARE_OVERLAP_PX = 1;

  let running = false;

  function parseTimeMs(value, fallback) {
    const trimmed = String(value ?? "").trim();
    if (!trimmed) return fallback;
    const parsed = Number.parseFloat(trimmed);
    if (!Number.isFinite(parsed)) return fallback;
    return trimmed.endsWith("ms") ? parsed : parsed * 1000;
  }

  function parseNumber(value, fallback) {
    const parsed = Number.parseFloat(String(value ?? "").trim());
    return Number.isFinite(parsed) ? parsed : fallback;
  }

  function getSession(key) {
    try {
      return window.sessionStorage.getItem(key);
    } catch {
      return null;
    }
  }

  function setSession(key, value) {
    try {
      window.sessionStorage.setItem(key, value);
    } catch {
      // Ignore storage failures in private mode or locked-down environments.
    }
  }

  function removeSession(key) {
    try {
      window.sessionStorage.removeItem(key);
    } catch {
      // Ignore storage failures in private mode or locked-down environments.
    }
  }

  function resolveCssColor(value, fallback, scope = document.documentElement) {
    const probeEl = document.createElement("span");
    probeEl.style.color = value || fallback;
    probeEl.style.position = "fixed";
    probeEl.style.left = "-9999px";
    probeEl.style.top = "-9999px";
    scope.appendChild(probeEl);
    const resolved = getComputedStyle(probeEl).color || fallback;
    probeEl.remove();
    return resolved;
  }

  function readOptions() {
    const style = getComputedStyle(overlay);
    return {
      color: resolveCssColor(
        style.getPropertyValue("--page-rect-bg").trim() ||
          style.getPropertyValue("--MC").trim() ||
          "#101010",
        "#101010",
        overlay,
      ),
      sizeFactor: parseNumber(
        style.getPropertyValue("--page-rect-size").trim(),
        0.01,
      ),
      stagger: parseTimeMs(style.getPropertyValue("--pageTR"), TRANSITION_MS_FALLBACK),
      minimumMs: parseTimeMs(
        style.getPropertyValue("--initial-loading-min"),
        MINIMUM_MS_FALLBACK,
      ),
      label: String(probe.textContent || "")
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter(Boolean)
        .join("\n"),
      probeStyle: getComputedStyle(probe),
    };
  }

  function fillCanvas(canvasElement, color, label, probeStyle) {
    const ctx = canvasElement.getContext("2d");
    if (!ctx) return;

    const width = Math.max(1, window.innerWidth);
    const height = Math.max(1, window.innerHeight);
    canvasElement.width = width;
    canvasElement.height = height;
    canvasElement.style.width = `${width}px`;
    canvasElement.style.height = `${height}px`;

    ctx.fillStyle = color;
    ctx.fillRect(0, 0, width, height);
    drawLabel(
      ctx,
      [
        {
          x: 0,
          y: 0,
          w: width,
          h: height,
          startAt: 0,
        },
      ],
      label,
      probeStyle,
    );
  }

  function wait(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
  }

  function nextFrame() {
    return new Promise((resolve) => window.requestAnimationFrame(() => resolve()));
  }

  async function waitForFonts(label, fontStyle) {
    if (!document.fonts) return;

    const font = `${fontStyle.fontStyle || "normal"} ${fontStyle.fontWeight || "300"} ${parseNumber(fontStyle.fontSize, 96)}px ${fontStyle.fontFamily || "sans-serif"}`;
    await Promise.race([
      document.fonts.load(font, label).then(() => undefined).catch(() => undefined),
      wait(250),
    ]);
  }

  async function waitForImageDecode(image) {
    if (image.complete && image.naturalWidth > 0) return;

    try {
      if (typeof image.decode === "function") {
        await image.decode();
        return;
      }
    } catch {
      // Ignore decode failures and fall back to a load/error race below.
    }

    await new Promise((resolve) => {
      const done = () => resolve();
      image.addEventListener("load", done, { once: true });
      image.addEventListener("error", done, { once: true });
    });
  }

  async function waitForVisualReadiness(root) {
    const scope = root instanceof Element ? root : document.body;
    if (!scope) return;

    const images = Array.from(scope.querySelectorAll("img"))
      .filter((image) => image.currentSrc || image.src)
      .slice(0, 16);

    await Promise.race([
      Promise.all([
        document.fonts?.ready.catch(() => undefined) ?? Promise.resolve(),
        Promise.all(images.map((image) => waitForImageDecode(image))),
        nextFrame(),
        nextFrame(),
      ]).then(() => undefined),
      wait(1400),
    ]);
  }

  function buildSquares(width, height, factor, stagger) {
    const cols = Math.max(1, Math.ceil(width * factor));
    const rows = Math.max(1, Math.ceil(height * factor));
    const squareSizeX = Math.ceil(width / cols);
    const squareSizeY = Math.ceil(height / rows);
    const squares = [];

    for (let y = 0; y < rows; y += 1) {
      for (let x = 0; x < cols; x += 1) {
        const w = x === cols - 1 ? width - x * squareSizeX : squareSizeX;
        const h = y === rows - 1 ? height - y * squareSizeY : squareSizeY;
        squares.push({
          x: x * squareSizeX,
          y: y * squareSizeY,
          w,
          h,
          startAt: 0,
        });
      }
    }

    const order = squares
      .map((_, index) => index)
      .sort(() => Math.random() - 0.5);

    order.forEach((squareIndex, orderIndex) => {
      squares[squareIndex].startAt =
        squares.length <= 1 ? 0 : (orderIndex / (squares.length - 1)) * stagger;
    });

    return squares;
  }

  function drawLabel(ctx, visibleSquares, label, probeStyle) {
    const lines = label.split(/\r?\n/).filter(Boolean);
    if (lines.length === 0 || visibleSquares.length === 0) return;

    const fontStyle = probeStyle ?? getComputedStyle(probe);
    const fontSize = Math.round(parseNumber(fontStyle.fontSize, 96));
    const rawLineHeight = parseNumber(fontStyle.lineHeight, fontSize);
    const lineHeight = Number.isFinite(rawLineHeight) ? rawLineHeight : fontSize;
    const firstLineY = ctx.canvas.height / 2 - ((lines.length - 1) * lineHeight) / 2;

    ctx.save();
    ctx.beginPath();
    visibleSquares.forEach((square) => {
      ctx.rect(
        square.x - SQUARE_OVERLAP_PX,
        square.y - SQUARE_OVERLAP_PX,
        square.w + SQUARE_OVERLAP_PX * 2,
        square.h + SQUARE_OVERLAP_PX * 2,
      );
    });
    ctx.clip();
    ctx.fillStyle = fontStyle.color || "#ffffff";
    ctx.font = `${fontStyle.fontStyle || "normal"} ${fontStyle.fontWeight || "300"} ${fontSize}px ${fontStyle.fontFamily || "sans-serif"}`;
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    lines.forEach((line, index) => {
      ctx.fillText(line, ctx.canvas.width / 2, firstLineY + index * lineHeight);
    });
    ctx.restore();
  }

  function drawFrame(ctx, squares, color, phase, elapsed, label, probeStyle) {
    ctx.clearRect(0, 0, ctx.canvas.width, ctx.canvas.height);

    let visibleCount = 0;
    const visibleSquares = [];
    squares.forEach((square) => {
      const visible = phase === "cover" ? elapsed >= square.startAt : elapsed < square.startAt;
      if (!visible) return;
      visibleSquares.push(square);
      ctx.fillStyle = color;
      ctx.fillRect(
        square.x - SQUARE_OVERLAP_PX,
        square.y - SQUARE_OVERLAP_PX,
        square.w + SQUARE_OVERLAP_PX * 2,
        square.h + SQUARE_OVERLAP_PX * 2,
      );
      visibleCount += 1;
    });

    if (label) {
      drawLabel(ctx, visibleSquares, label, probeStyle);
    }

    return phase === "cover" ? visibleCount === squares.length : visibleCount === 0;
  }

  function playMosaic(phase, options) {
    const ctx = canvas.getContext("2d");
    if (!ctx) return Promise.resolve();

    const width = Math.max(1, window.innerWidth);
    const height = Math.max(1, window.innerHeight);
    const factor = options.sizeFactor ?? 0.01875;
    const stagger = options.stagger ?? TRANSITION_MS_FALLBACK;
    const color = options.color ?? "#101010";
    const label = options.label || "";
    const squares = buildSquares(width, height, factor, stagger);

    canvas.width = width;
    canvas.height = height;
    canvas.style.width = `${width}px`;
    canvas.style.height = `${height}px`;

    if (phase === "reveal") {
      ctx.fillStyle = color;
      ctx.fillRect(0, 0, width, height);
    }

    return new Promise((resolve) => {
      const startTime = performance.now();
      let rafId = null;

      const finish = () => {
        if (phase === "reveal") {
          ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
        rafId = null;
        resolve();
      };

      const tick = (now) => {
        const complete = drawFrame(
          ctx,
          squares,
          color,
          phase,
          now - startTime,
          label,
          options.probeStyle,
        );
        if (complete) {
          finish();
          return;
        }
        rafId = window.requestAnimationFrame(tick);
      };

      rafId = window.requestAnimationFrame(tick);
      if (rafId == null) {
        resolve();
      }
    });
  }

  function showOverlay() {
    overlay.hidden = false;
    overlay.removeAttribute("aria-hidden");
  }

  function unlockDocumentContent() {
    document.body?.classList.remove(BODY_PENDING_CLASS);
  }

  function setOverlayMode(mode) {
    const isInitial = mode === "initial";
    const isTransition = mode === "transition";
    overlay.classList.toggle("InitialLoading", isInitial);
    overlay.classList.toggle("PageTransition", isTransition);
  }

  function hideOverlay() {
    overlay.hidden = true;
    overlay.setAttribute("aria-hidden", "true");
  }

  function getInternalNavigationTarget(event) {
    if (
      event.defaultPrevented ||
      event.button !== 0 ||
      event.metaKey ||
      event.altKey ||
      event.ctrlKey ||
      event.shiftKey
    ) {
      return null;
    }

    const target = event.target;
    if (!(target instanceof Element)) return null;

    const anchor = target.closest("a[href]");
    if (!anchor || anchor.target || anchor.hasAttribute("download")) return null;

    const rawHref = anchor.getAttribute("href");
    if (
      !rawHref ||
      rawHref.startsWith("#") ||
      rawHref.startsWith("mailto:") ||
      rawHref.startsWith("tel:")
    ) {
      return null;
    }

    const url = new URL(anchor.href, window.location.href);
    if (url.origin !== window.location.origin) return null;

    const samePage = url.pathname === window.location.pathname && url.search === window.location.search;
    if (samePage && url.hash) return null;

    return { href: anchor.href, anchor, url };
  }

  function defaultTransitionLabel(url) {
    const pathname = url.pathname.replace(/\/+$/, "") || "/";
    if (pathname === "/") return String(probe.textContent || "").trim();

    const leaf = pathname.split("/").filter(Boolean).pop() || "";
    if (!leaf) return String(probe.textContent || "").trim();

    return leaf
      .replace(/[-_]/g, " ")
      .replace(/\b\w/gu, (character) => character.toUpperCase());
  }

  function resolveTransitionLabel(anchor, url) {
    const raw = String(anchor.textContent || "").trim();
    if (raw) {
      const normalized = raw.replace(/\s+/g, " ");
      return normalized.includes(" ")
        ? normalized.replace(/^(\S+)\s+(.+)$/, "$1\n$2")
        : normalized;
    }

    return defaultTransitionLabel(url);
  }

  function getPendingLabel(fallback) {
    const raw = getSession(PENDING_LABEL_KEY);
    if (!raw) return fallback;
    return raw;
  }

  function waitForTimeout(ms) {
    return new Promise((resolve) => window.setTimeout(resolve, ms));
  }

  async function playIntroOrReveal() {
    if (running) return;
    running = true;

    const options = readOptions();
    const isPendingTransition = getSession(PENDING_KEY) === "1";
    const isFirstView =
      INITIAL_LOADING_ALWAYS_SHOW || getSession(VIEWED_KEY) !== "1";
    const label = isPendingTransition
      ? getPendingLabel(options.label)
      : options.label;

    if (!isPendingTransition && !isFirstView) {
      unlockDocumentContent();
      hideOverlay();
      running = false;
      return;
    }

    setOverlayMode(isPendingTransition ? "transition" : "initial");
    showOverlay();
    await waitForTimeout(0);
    await waitForFonts(label, options.probeStyle);

    if (isPendingTransition) {
      fillCanvas(canvas, options.color, label, options.probeStyle);
      unlockDocumentContent();
      await waitForVisualReadiness(document.body);
      await playMosaic("reveal", { ...options, label });
      removeSession(PENDING_KEY);
      removeSession(PENDING_LABEL_KEY);
      if (!INITIAL_LOADING_ALWAYS_SHOW) {
        setSession(VIEWED_KEY, "1");
      }
    } else {
      fillCanvas(canvas, options.color, label, options.probeStyle);
      unlockDocumentContent();
      await nextFrame();
      await waitForVisualReadiness(document.body);
      await waitForTimeout(options.minimumMs ?? MINIMUM_MS_FALLBACK);
      await playMosaic("reveal", { ...options, label });
      if (!INITIAL_LOADING_ALWAYS_SHOW) {
        setSession(VIEWED_KEY, "1");
      }
    }

    hideOverlay();
    running = false;
  }

  async function runTransition(target) {
    if (running) return;
    running = true;

    const options = readOptions();
    const label = resolveTransitionLabel(target.anchor, target.url);
    const transitionOptions = {
      color: options.color,
      sizeFactor: options.sizeFactor,
      stagger: options.stagger,
      label,
    };

    setOverlayMode("transition");
    showOverlay();
    await waitForTimeout(0);
    await waitForFonts(label, options.probeStyle);
    await playMosaic("cover", transitionOptions);

    setSession(PENDING_KEY, "1");
    setSession(PENDING_LABEL_KEY, label);
    setSession(VIEWED_KEY, "1");

    window.scrollTo({ top: 0, left: 0, behavior: "auto" });
    window.location.assign(target.href);
  }

  document.addEventListener(
    "click",
    (event) => {
      if (running) return;
      const target = getInternalNavigationTarget(event);
      if (!target) return;

      event.preventDefault();
      event.stopPropagation();
      void runTransition(target);
    },
    true,
  );

  window.addEventListener("pageshow", () => {
    if (getSession(PENDING_KEY) === "1") {
      showOverlay();
      void playIntroOrReveal();
      return;
    }

    void playIntroOrReveal();
  });

  if (document.readyState === "complete" || document.readyState === "interactive") {
    void playIntroOrReveal();
  } else {
    document.addEventListener("DOMContentLoaded", () => {
      void playIntroOrReveal();
    });
  }
})();
