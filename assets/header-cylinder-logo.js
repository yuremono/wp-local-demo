/**
 * Header ModulationCylinderLogo (vanilla Three.js).
 * Ported from 0413portfolio src/components/three/ModulationCylinderLogo.tsx
 */
import * as THREE from "https://cdn.jsdelivr.net/npm/three@0.184.0/build/three.module.js";

  const SIDE_TEXT = "MODULATION";
  const LOGO_CONFIG = {
    cameraFov: 34,
    cameraPosition: [0, 4.8, 0.001],
    modelScale: 1,
    modelRotationY: 0.6,
    cylinderHeight: 1,
    cylinderSegments: 24,
    bodyColor: "transparent",
    textColor: "--TC",
    bodyToneMapped: false,
    partitionColor: "--BC",
    partitionRadius: 0.75,
    scrollTurnRange: 2400,
    sideSpinSpeed: 0.2,
    capRotationZ: -0.625,
    bottomCapRotationZ: 0.56,
    capRadius: 0.98,
    capSegments: 24,
    capFontFamily: '"Noto Serif JP", serif',
    capFontWeight: 200,
    capFontSize: 512,
    capTextRatio: 0.625,
    capTextureSize: 256,
    sideRadius: 1.026,
    sideOffsetY: -0.14,
    sideLetterWidth: 0.9,
    sideLetterHeight: 1,
    sideFontFamily: '"Jost"',
    sideFontWeight: 200,
    sideFontSize: 900,
    sideTextRatio: 0.75,
    sideTextureSize: 256,
    textStroke: false,
  };

  const HALF_HEIGHT = LOGO_CONFIG.cylinderHeight / 2;
  const SCROLL_TURN_FACTOR = (Math.PI * 2) / LOGO_CONFIG.scrollTurnRange;
  const SIDE_BASE_ROTATION = -LOGO_CONFIG.modelRotationY;

  const FALLBACK_PALETTE = {
    body: "oklch(99% 0.005 60)",
    ink: "oklch(99% 0.005 60)",
    partition: "oklch(99% 0.005 60)",
  };

  const TEXT_TEXTURE_CACHE = new Map();

  const cylinderGeometry = new THREE.CylinderGeometry(
    1,
    1,
    LOGO_CONFIG.cylinderHeight,
    LOGO_CONFIG.cylinderSegments,
    1,
    false
  );
  const capDiskGeometry = new THREE.CircleGeometry(LOGO_CONFIG.capRadius, LOGO_CONFIG.capSegments);
  const partitionDiskGeometry = new THREE.CircleGeometry(
    LOGO_CONFIG.partitionRadius,
    LOGO_CONFIG.capSegments
  );
  const sidePlaneGeometry = new THREE.PlaneGeometry(
    LOGO_CONFIG.sideLetterWidth,
    LOGO_CONFIG.sideLetterHeight
  );

  function readCssColor(style, color) {
    return color.startsWith("--") ? style.getPropertyValue(color).trim() : color;
  }

  function isTransparentColor(value) {
    const normalized = value.trim().toLowerCase();
    return (
      normalized === "transparent" ||
      normalized === "var(--tr)" ||
      normalized === "rgba(0, 0, 0, 0)" ||
      normalized === "rgba(0,0,0,0)" ||
      normalized === "oklch(0% 0 0 / 0)"
    );
  }

  function cssColorToRgb(value, fallback) {
    const source = value || fallback;
    const canvas = document.createElement("canvas");
    canvas.width = 1;
    canvas.height = 1;
    const ctx = canvas.getContext("2d");
    if (!ctx) return fallback;
    ctx.fillStyle = fallback;
    try {
      ctx.fillStyle = source;
    } catch {
      ctx.fillStyle = fallback;
    }
    ctx.fillRect(0, 0, 1, 1);
    const [r, g, b] = ctx.getImageData(0, 0, 1, 1).data;
    return `rgb(${r}, ${g}, ${b})`;
  }

  function readColorValue(style, color, fallback) {
    const source = readCssColor(style, color);
    if (isTransparentColor(source)) return "transparent";
    return cssColorToRgb(source, fallback);
  }

  function readPalette(element) {
    if (!element) return { ...FALLBACK_PALETTE };
    const style = getComputedStyle(element);
    return {
      body: readColorValue(style, LOGO_CONFIG.bodyColor, FALLBACK_PALETTE.body),
      ink: readColorValue(style, LOGO_CONFIG.textColor, FALLBACK_PALETTE.ink),
      partition: readColorValue(style, LOGO_CONFIG.partitionColor, FALLBACK_PALETTE.partition),
    };
  }

  function drawBalancedText(ctx, options) {
    const { text, textureSize, textRatio, fontWeight, fontSize, fontFamily, fillStyle } = options;
    const limit = textureSize * textRatio;
    const centerX = textureSize / 2;
    const centerY = textureSize / 2;
    ctx.clearRect(0, 0, textureSize, textureSize);
    ctx.textAlign = "center";
    ctx.textBaseline = "middle";
    let size = fontSize;
    ctx.font = `${fontWeight} ${size}px ${fontFamily}`;
    const measure = () => {
      const metrics = ctx.measureText(text);
      const width = metrics.width;
      const height =
        (metrics.actualBoundingBoxAscent ?? size * 0.8) +
        (metrics.actualBoundingBoxDescent ?? size * 0.2);
      return { width, height };
    };
    let { width, height } = measure();
    const scale = Math.min(1, limit / width, limit / height);
    size = Math.max(1, Math.floor(fontSize * scale));
    ctx.font = `${fontWeight} ${size}px ${fontFamily}`;
    ({ width, height } = measure());
    if (width > limit || height > limit) {
      const tighten = Math.min(limit / width, limit / height);
      size = Math.max(1, Math.floor(size * tighten));
      ctx.font = `${fontWeight} ${size}px ${fontFamily}`;
    }
    if (LOGO_CONFIG.textStroke) {
      ctx.lineJoin = "round";
      ctx.strokeStyle = "rgba(255, 255, 255, 0.26)";
      ctx.lineWidth = Math.max(2, size * 0.018);
      ctx.strokeText(text, centerX, centerY);
    }
    ctx.fillStyle = fillStyle;
    ctx.fillText(text, centerX, centerY);
  }

  function createTextTexture(options) {
    const cacheKey = Object.values(options).join("|");
    const cached = TEXT_TEXTURE_CACHE.get(cacheKey);
    if (cached) return cached;
    const canvas = document.createElement("canvas");
    canvas.width = options.textureSize;
    canvas.height = options.textureSize;
    const ctx = canvas.getContext("2d");
    if (ctx) drawBalancedText(ctx, options);
    const texture = new THREE.CanvasTexture(canvas);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.minFilter = THREE.LinearFilter;
    texture.magFilter = THREE.LinearFilter;
    texture.needsUpdate = true;
    TEXT_TEXTURE_CACHE.set(cacheKey, texture);
    return texture;
  }

  function requestFonts(onReady) {
    if (!document.fonts) {
      onReady();
      return;
    }
    const fonts = document.fonts;
    let ready = false;
    const markReady = () => {
      if (ready) return;
      ready = true;
      TEXT_TEXTURE_CACHE.clear();
      onReady();
    };
    const requestFont = (font) => {
      try {
        void fonts.load(font).then(markReady, markReady);
      } catch {
        markReady();
      }
    };
    requestFont(`${LOGO_CONFIG.capFontWeight} 160px ${LOGO_CONFIG.capFontFamily}`);
    requestFont(`${LOGO_CONFIG.sideFontWeight} 160px ${LOGO_CONFIG.sideFontFamily}`);
    void fonts.ready.then(markReady, markReady);
  }

  function createTextDisk(text, position, rotation, fillStyle) {
    const texture = createTextTexture({
      text,
      fontFamily: LOGO_CONFIG.capFontFamily,
      fontWeight: LOGO_CONFIG.capFontWeight,
      fillStyle,
      fontSize: LOGO_CONFIG.capFontSize,
      textureSize: LOGO_CONFIG.capTextureSize,
      textRatio: LOGO_CONFIG.capTextRatio,
    });
    const material = new THREE.MeshBasicMaterial({
      map: texture,
      transparent: true,
      alphaTest: 0.01,
      depthWrite: false,
      side: THREE.DoubleSide,
      toneMapped: false,
    });
    const mesh = new THREE.Mesh(capDiskGeometry, material);
    mesh.position.set(position[0], position[1], position[2]);
    mesh.rotation.set(rotation[0], rotation[1], rotation[2]);
    mesh.renderOrder = 4;
    return mesh;
  }

  function createTextPlane(text, position, rotation, fillStyle) {
    const texture = createTextTexture({
      text,
      fontFamily: LOGO_CONFIG.sideFontFamily,
      fontWeight: LOGO_CONFIG.sideFontWeight,
      fillStyle,
      fontSize: LOGO_CONFIG.sideFontSize,
      textureSize: LOGO_CONFIG.sideTextureSize,
      textRatio: LOGO_CONFIG.sideTextRatio,
    });
    const material = new THREE.MeshBasicMaterial({
      map: texture,
      transparent: true,
      alphaTest: 0.01,
      depthWrite: false,
      toneMapped: false,
    });
    const mesh = new THREE.Mesh(sidePlaneGeometry, material);
    mesh.position.set(position[0], position[1], position[2]);
    mesh.rotation.set(rotation[0], rotation[1], rotation[2]);
    mesh.renderOrder = 3;
    return mesh;
  }

  function mountLogo(host) {
    const palette = readPalette(host.closest("[data-portfolio-page]") ?? host);
    const size = Math.max(1, host.clientWidth || host.clientHeight || 120);

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: false, powerPreference: "low-power" });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
    renderer.setSize(size, size, false);
    renderer.domElement.style.width = "100%";
    renderer.domElement.style.height = "auto";
    renderer.domElement.style.display = "block";
    renderer.domElement.className = "LogoCylinderCanvas";
    renderer.domElement.setAttribute("role", "img");
    renderer.domElement.removeAttribute("aria-hidden");
    renderer.domElement.setAttribute(
      "aria-label",
      "上面に「ゆ」、側面にMODULATIONを配した円柱ロゴ"
    );
    host.replaceChildren(renderer.domElement);
    const surface = renderer.domElement;

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(LOGO_CONFIG.cameraFov, 1, 0.1, 50);
    camera.position.set(
      LOGO_CONFIG.cameraPosition[0],
      LOGO_CONFIG.cameraPosition[1],
      LOGO_CONFIG.cameraPosition[2]
    );
    camera.lookAt(0, 0, 0);
    camera.updateProjectionMatrix();

    const scrollGroup = new THREE.Group();
    scrollGroup.scale.setScalar(LOGO_CONFIG.modelScale);
    scene.add(scrollGroup);

    const modelGroup = new THREE.Group();
    modelGroup.rotation.y = LOGO_CONFIG.modelRotationY;
    scrollGroup.add(modelGroup);

    if (palette.body !== "transparent") {
      const bodyMaterial = new THREE.MeshBasicMaterial({
        color: palette.body,
        toneMapped: LOGO_CONFIG.bodyToneMapped,
      });
      const bodyMesh = new THREE.Mesh(cylinderGeometry, bodyMaterial);
      modelGroup.add(bodyMesh);
    }

    if (palette.partition !== "transparent") {
      const partitionMaterial = new THREE.MeshBasicMaterial({
        color: palette.partition,
        toneMapped: LOGO_CONFIG.bodyToneMapped,
        side: THREE.DoubleSide,
      });
      const partitionMesh = new THREE.Mesh(partitionDiskGeometry, partitionMaterial);
      partitionMesh.rotation.x = -Math.PI / 2;
      partitionMesh.renderOrder = 2;
      modelGroup.add(partitionMesh);
    }

    modelGroup.add(
      createTextDisk("ゆ", [0, HALF_HEIGHT + 0.018, 0], [-Math.PI / 2, 0, LOGO_CONFIG.capRotationZ], palette.ink)
    );
    modelGroup.add(
      createTextDisk(
        "れ",
        [0, -HALF_HEIGHT - 0.018, 0],
        [Math.PI / 2, 0, LOGO_CONFIG.bottomCapRotationZ],
        palette.ink
      )
    );

    const sideGroup = new THREE.Group();
    sideGroup.rotation.y = SIDE_BASE_ROTATION;
    SIDE_TEXT.split("").forEach((letter, index) => {
      const angle = (index / SIDE_TEXT.length) * Math.PI * 2;
      sideGroup.add(
        createTextPlane(
          letter,
          [
            Math.sin(angle) * LOGO_CONFIG.sideRadius,
            LOGO_CONFIG.sideOffsetY,
            Math.cos(angle) * LOGO_CONFIG.sideRadius,
          ],
          [0, angle, 0],
          palette.ink
        )
      );
    });
    modelGroup.add(sideGroup);

    const updateScrollRotation = () => {
      scrollGroup.rotation.x = -window.scrollY * SCROLL_TURN_FACTOR;
    };
    updateScrollRotation();
    window.addEventListener("scroll", updateScrollRotation, { passive: true });

    let rafId = 0;
    const clock = new THREE.Clock();
    const render = () => {
      sideGroup.rotation.y = SIDE_BASE_ROTATION - clock.getElapsedTime() * LOGO_CONFIG.sideSpinSpeed;
      renderer.render(scene, camera);
      rafId = window.requestAnimationFrame(render);
    };
    rafId = window.requestAnimationFrame(render);

    let lastWidth = size;
    let lastHeight = size;
    const resizeObserver = new ResizeObserver(() => {
      const w = Math.max(1, surface.clientWidth || 120);
      const h = Math.max(1, w);
      if (w === lastWidth && h === lastHeight) return;
      lastWidth = w;
      lastHeight = h;
      renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
      renderer.setSize(w, h, false);
      camera.aspect = 1;
      camera.updateProjectionMatrix();
    });
    resizeObserver.observe(surface);

    return () => {
      window.removeEventListener("scroll", updateScrollRotation);
      window.cancelAnimationFrame(rafId);
      resizeObserver.disconnect();
      renderer.dispose();
    };
  }

  function boot() {
    const hosts = document.querySelectorAll(
      "[data-portfolio-page] .HeaderCylinderLogo .LogoCylinder:not(canvas)"
    );
    if (!hosts.length) return;
    requestFonts(() => {
      hosts.forEach((host) => {
        if (!(host instanceof HTMLElement) || host.dataset.logoMounted === "true") return;
        host.dataset.logoMounted = "true";
        mountLogo(host);
      });
    });
  }

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
