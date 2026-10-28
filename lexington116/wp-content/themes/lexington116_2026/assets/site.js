(() => {
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".primary-navigation");
  const closeMenu = () => {
    toggle?.setAttribute("aria-expanded", "false");
    nav?.classList.remove("is-open");
  };
  toggle?.addEventListener("click", () => {
    const open = toggle.getAttribute("aria-expanded") !== "true";
    toggle.setAttribute("aria-expanded", String(open));
    nav?.classList.toggle("is-open", open);
  });
  nav?.addEventListener("click", (event) => {
    if (event.target.closest("a, button")) closeMenu();
  });
  document.addEventListener("keydown", (event) => {
    if (
      event.key === "Escape" &&
      toggle?.getAttribute("aria-expanded") === "true"
    ) {
      closeMenu();
      toggle.focus();
    }
  });
  const dialog = document.querySelector("#booking-dialog");
  document.querySelectorAll("[data-open-booking]").forEach((button) => {
    button.addEventListener("click", () => dialog?.showModal());
  });
  dialog
    ?.querySelector(".dialog-close")
    ?.addEventListener("click", () => dialog.close());
  dialog?.addEventListener("click", (event) => {
    if (event.target !== dialog) return;
    const bounds = dialog.getBoundingClientRect();
    if (
      event.clientX < bounds.left ||
      event.clientX > bounds.right ||
      event.clientY < bounds.top ||
      event.clientY > bounds.bottom
    )
      dialog.close();
  });
  // Move the same controls on mobile, preserving their desktop position.
  const heroActions = document.querySelector('.hero-actions');
  const heroInner = document.querySelector('.hero-inner');
  if (heroActions && heroInner) {
    const originalParent = heroActions.parentNode;
    const originalNextSibling = heroActions.nextSibling;
    const mobileHero = window.matchMedia('(max-width: 800px)');
    const positionHeroActions = () => {
      if (mobileHero.matches) heroInner.prepend(heroActions);
      else originalParent.insertBefore(heroActions, originalNextSibling);
    };
    positionHeroActions();
    mobileHero.addEventListener('change', positionHeroActions);
  }
  const video = document.querySelector('.hero-video');
  const videoToggle = document.querySelector('.video-toggle');
  if (video && videoToggle) {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    video.muted = true;
    videoToggle.hidden = false;
    const updateVideoButton = () => {
      videoToggle.textContent = video.paused ? 'Play video' : 'Pause video';
    };
    video.addEventListener('play', updateVideoButton);
    video.addEventListener('pause', updateVideoButton);
    const playVideo = () => {
      video.preload = 'auto';
      return video.play().catch(updateVideoButton);
    };
    videoToggle.addEventListener('click', () => {
      if (video.paused) playVideo();
      else video.pause();
    });
    reducedMotion.addEventListener('change', () => {
      if (reducedMotion.matches) video.pause();
    });
    if (!reducedMotion.matches) playVideo();
  }
})();
