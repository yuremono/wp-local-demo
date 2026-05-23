(function () {
  const root = document.querySelector("[data-portfolio-page]");
  if (!root) return;

  const header = root.querySelector(".HeaderCylinder");
  const menuButton = root.querySelector("[data-menu-toggle]");
  const nav = root.querySelector("#HeaderNav");
  const themeToggle = root.querySelector("[data-theme-toggle]");
  const scrollXSections = root.querySelectorAll(".ScrollX");

  document.documentElement.classList.add("[--MC:--GR]");

  function setMenu(open) {
    if (!header || !menuButton || !nav) return;
    header.setAttribute("data-nav-open", open ? "true" : "false");
    menuButton.setAttribute("aria-expanded", open ? "true" : "false");
    menuButton.setAttribute("aria-label", open ? "Close menu" : "Open menu");
    nav.setAttribute("aria-hidden", open ? "false" : "true");
    if (open) {
      nav.removeAttribute("inert");
      return;
    }
    nav.setAttribute("inert", "");
  }

  function updateScrollX(section) {
    const track = section.querySelector(".ScrollTrack");
    const spacer = section.querySelector(".ScrollSpacer");
    if (!(track instanceof HTMLElement) || !(spacer instanceof HTMLElement)) return;
    const maxX = Math.max(0, track.scrollWidth - window.innerWidth);
    spacer.style.height = `${maxX}px`;
    const start = section.getBoundingClientRect().top + window.scrollY;
    const offset = Math.min(maxX, Math.max(0, window.scrollY - start));
    track.style.transform = `translate3d(${-offset}px,0,0)`;
  }

  function updateAllScrollX() {
    scrollXSections.forEach(updateScrollX);
  }

  menuButton?.addEventListener("click", () => {
    setMenu(header?.getAttribute("data-nav-open") !== "true");
  });

  nav?.addEventListener("click", (event) => {
    if (event.target instanceof Element && event.target.closest("a")) {
      setMenu(false);
    }
  });

  root.querySelectorAll(".NavDrop").forEach((drop) => {
    const popover = drop.querySelector(".DropUl");
    if (!(popover instanceof HTMLElement)) return;
    const show = () => {
      if ("showPopover" in popover && !popover.matches(":popover-open")) {
        const source = drop.querySelector(".DropA");
        if (source instanceof HTMLElement) {
          popover.showPopover({ source });
          return;
        }
        popover.showPopover();
      }
    };
    const hide = () => {
      if ("hidePopover" in popover && popover.matches(":popover-open")) {
        popover.hidePopover();
      }
    };
    drop.addEventListener("pointerenter", show);
    drop.addEventListener("pointerleave", (event) => {
      if (event.relatedTarget instanceof Node && drop.contains(event.relatedTarget)) return;
      hide();
    });
    popover.addEventListener("click", (event) => {
      if (event.target instanceof Element && event.target.closest("a")) {
        hide();
      }
    });
  });

  themeToggle?.addEventListener("click", () => {
    const dark = document.documentElement.classList.toggle("dark");
    root.classList.toggle("HasTheme", dark);
    const moon = themeToggle.querySelector(".theme_icon_moon");
    const sun = themeToggle.querySelector(".theme_icon_sun");
    moon?.classList.toggle("hidden", dark);
    sun?.classList.toggle("hidden", !dark);
  });

  root.querySelectorAll("[data-dialog-open]").forEach((button) => {
    button.addEventListener("click", () => {
      const id = button.getAttribute("data-dialog-open");
      const dialog = id ? document.getElementById(id) : null;
      if (dialog instanceof HTMLDialogElement) {
        dialog.showModal();
      }
    });
  });

  root.querySelectorAll("[data-dialog-close]").forEach((button) => {
    button.addEventListener("click", () => {
      button.closest("dialog")?.close();
    });
  });

  document.addEventListener("keyup", (event) => {
    if (event.key === "Escape") {
      setMenu(false);
    }
  });

  window.addEventListener("scroll", updateAllScrollX, { passive: true });
  window.addEventListener("resize", updateAllScrollX, { passive: true });
  window.addEventListener("load", updateAllScrollX);
  updateAllScrollX();
})();
