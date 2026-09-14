(() => {
  const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
  const coarse = window.matchMedia("(hover: none), (pointer: coarse)").matches;
  const header = document.querySelector(".site-header");
  const toggle = document.querySelector(".nav-toggle");
  const nav = document.querySelector("#site-nav");

  const unveilWaiters = [];

  function whenUnveiled(fn) {
    if (reduce || !document.documentElement.classList.contains("is-booting")) {
      fn();
      return;
    }
    unveilWaiters.push(fn);
  }

  function flushUnveilWaiters() {
    while (unveilWaiters.length) unveilWaiters.shift()();
  }

  function openVeil() {
    document.body.classList.remove("is-leaving");
    window.setTimeout(() => {
      document.documentElement.classList.remove("is-booting");
      document.body.classList.add("is-ready");
      // Match .page-veil visibility/dim fade (0.5s) before page motion starts.
      window.setTimeout(flushUnveilWaiters, reduce ? 0 : 520);
    }, reduce ? 0 : 500);
  }

  openVeil();
  window.addEventListener("pageshow", openVeil);

  if (toggle && header && nav) {
    toggle.addEventListener("click", () => {
      const open = header.classList.toggle("is-open");
      toggle.setAttribute("aria-expanded", String(open));
    });
  }

  function samePage(href) {
    try {
      const url = new URL(href, window.location.href);
      return url.origin === window.location.origin;
    } catch {
      return false;
    }
  }

  function goTo(href) {
    document.body.classList.add("is-leaving");
    window.setTimeout(() => {
      window.location.href = href;
    }, reduce ? 0 : 900);
  }

  document.addEventListener(
    "click",
    (event) => {
      const card = event.target.closest("a.person-card[data-nav]");
      if (!card || reduce || !coarse) return;
      if (event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      if (card.classList.contains("is-tapping")) return;
      event.preventDefault();
      event.stopImmediatePropagation();
      card.classList.add("is-tapping");
      window.setTimeout(() => goTo(card.href), 420);
    },
    true
  );

  document.addEventListener("click", (event) => {
    const link = event.target.closest("a[data-nav]");
    if (!link || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
    if (link.target === "_blank" || link.hasAttribute("download")) return;
    if (!samePage(link.href)) return;
    if (link.getAttribute("href")?.startsWith("#")) return;
    if (reduce) return;
    if (link.classList.contains("is-tapping")) return;

    event.preventDefault();
    goTo(link.href);
  });

  document.querySelectorAll("[data-filter-group]").forEach((group) => {
    const buttons = [...group.querySelectorAll("[data-filter]")];
    const cards = [...document.querySelectorAll("[data-roles]")];
    const sections = [...document.querySelectorAll(".people-section")];

    function apply() {
      const role = group.querySelector("[data-filter].is-on")?.getAttribute("data-filter") || "tutti";
      cards.forEach((card) => {
        const roles = (card.getAttribute("data-roles") || "").split(",").filter(Boolean);
        const roleOk = role === "tutti" || roles.includes(role);
        card.classList.toggle("is-off", !roleOk);
      });
      sections.forEach((section) => {
        const visible = [...section.querySelectorAll(".person-card")].some((card) => !card.classList.contains("is-off"));
        section.hidden = !visible;
      });
    }

    buttons.forEach((button) => {
      button.addEventListener("click", () => {
        buttons.forEach((item) => item.classList.remove("is-on"));
        button.classList.add("is-on");
        apply();
      });
    });

    apply();
  });

  function initHomeStage() {
    const stage = document.querySelector("[data-home-stage]");
    if (!stage) return;

    const showreel = stage.querySelector("[data-showreel-video]");
    const master = stage.querySelector("[data-curtain-video]");
    const follow = stage.querySelector("[data-curtain-follow]");
    const sound = stage.querySelector("[data-showreel-sound]");
    const soundLabel = sound?.querySelector("[data-sound-label]");
    const hasCurtain = Boolean(master);

    function clamp(value, min, max) {
      return Math.min(max, Math.max(min, value));
    }

    function remap(value, start, end) {
      return clamp((value - start) / Math.max(0.0001, end - start), 0, 1);
    }

    function easeOut(t) {
      return 1 - Math.pow(1 - t, 3);
    }

    function easeInOut(t) {
      return t < 0.5 ? 4 * t * t * t : 1 - Math.pow(-2 * t + 2, 3) / 2;
    }

    function playVideo(video) {
      if (!video) return;
      video.play().catch(() => {});
    }

    function pauseVideo(video) {
      if (!video) return;
      video.pause();
    }

    function syncSound(on) {
      if (!showreel || !sound) return;
      showreel.muted = !on;
      sound.setAttribute("aria-pressed", String(on));
      if (soundLabel) soundLabel.textContent = on ? "Disattiva audio" : "Attiva audio";
    }

    function setOpen(open) {
      const split = easeInOut(remap(open, 0, 0.56));
      const logo = easeOut(remap(open, 0.56, 0.8));
      const nav = easeOut(remap(open, 0.76, 0.98));
      stage.style.setProperty("--open", split.toFixed(4));
      stage.style.setProperty("--logo-o", (1 - logo).toFixed(4));
      stage.style.setProperty("--logo-y", logo.toFixed(4));
      stage.style.setProperty("--shade", (1 - split).toFixed(4));
      document.body.style.setProperty("--nav", nav.toFixed(4));
      document.body.classList.toggle("is-nav-in", nav > 0.2);
      stage.classList.toggle("is-sound-ready", !hasCurtain || split >= 0.7);
      if (split < 0.55 && showreel && !showreel.muted) syncSound(false);
      if (split >= 0.08) playVideo(showreel);
      if (hasCurtain) {
        if (split >= 0.98) {
          pauseVideo(master);
          pauseVideo(follow);
        } else {
          playVideo(master);
          playVideo(follow);
        }
      }
    }

    if (follow && master) {
      const sync = () => {
        if (Math.abs(master.currentTime - follow.currentTime) > 0.18) {
          follow.currentTime = master.currentTime;
        }
      };
      master.addEventListener("timeupdate", sync);
      master.addEventListener("play", () => playVideo(follow));
      master.addEventListener("seeked", () => {
        follow.currentTime = master.currentTime;
      });
    }

    sound?.addEventListener("click", () => {
      if (!showreel) return;
      syncSound(showreel.muted);
      playVideo(showreel);
    });

    if (reduce || !hasCurtain) {
      setOpen(1);
      playVideo(showreel);
      stage.classList.add("is-sound-ready");
      document.body.classList.add("is-nav-in");
      return;
    }

    playVideo(master);
    playVideo(follow);

    function update() {
      const range = Math.max(1, stage.offsetHeight - window.innerHeight);
      const t = clamp(-stage.getBoundingClientRect().top / range, 0, 1);
      setOpen(t);
    }

    let ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        update();
        ticking = false;
      });
    }

    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
  }

  function initSplitHero() {
    const stage = document.querySelector("[data-split-hero]");
    if (!stage) return;

    function clamp(value, min, max) {
      return Math.min(max, Math.max(min, value));
    }

    function easeInOutSine(t) {
      return -(Math.cos(Math.PI * t) - 1) / 2;
    }

    function setHero(t) {
      const p = clamp((t - 0.06) / 0.94, 0, 1);
      const open = easeInOutSine(p);
      stage.style.setProperty("--hero", open.toFixed(4));
    }

    if (reduce) {
      setHero(0);
      return;
    }

    function update() {
      const available = Math.max(0, document.documentElement.scrollHeight - window.innerHeight);
      if (available < 4) {
        setHero(0);
        return;
      }
      const range = Math.max(1, Math.min(window.innerHeight * 1.15, available));
      const t = clamp(window.scrollY / range, 0, 1);
      setHero(t);
    }

    let ticking = false;
    function onScroll() {
      if (ticking) return;
      ticking = true;
      requestAnimationFrame(() => {
        update();
        ticking = false;
      });
    }

    update();
    window.addEventListener("scroll", onScroll, { passive: true });
    window.addEventListener("resize", onScroll);
  }

  function initAboutSlide() {
    const panel = document.querySelector("[data-about]");
    if (!panel) return;

    function update() {
      const rect = panel.getBoundingClientRect();
      const start = window.innerHeight * 0.95;
      const end = window.innerHeight * 0.38;
      const t = Math.min(1, Math.max(0, (start - rect.top) / Math.max(1, start - end)));
      panel.style.setProperty("--about", t.toFixed(3));
      panel.classList.toggle("is-landed", t >= 0.98);
    }

    if (reduce) {
      panel.style.setProperty("--about", "1");
      return;
    }

    update();
    window.addEventListener("scroll", update, { passive: true });
    window.addEventListener("resize", update);
  }

  function initMediaStream() {
    const stream = document.querySelector("[data-media-stream]");
    if (!stream) return;

    const kinds = ["wide", "offset", "tall"];
    const sentinel = document.querySelector("[data-media-sentinel]");
    const queueNode = document.querySelector("#media-queue");

    function revealShot(figure) {
      const img = figure.querySelector("img");
      const show = () => figure.classList.add("is-in");
      if (!img || img.complete) {
        show();
        return;
      }
      img.addEventListener("load", show, { once: true });
      img.addEventListener("error", show, { once: true });
    }

    const reveal = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          reveal.unobserve(entry.target);
          if (reduce) {
            entry.target.classList.add("is-in");
            return;
          }
          revealShot(entry.target);
        });
      },
      { threshold: 0.12, rootMargin: "0px 0px -6% 0px" }
    );

    stream.querySelectorAll(".media-shot").forEach((figure) => reveal.observe(figure));

    let queue = [];
    if (queueNode) {
      try {
        queue = JSON.parse(queueNode.textContent || "[]");
      } catch {
        queue = [];
      }
    }
    if (!sentinel || !Array.isArray(queue) || !queue.length) return;

    let nextIndex = stream.querySelectorAll(".media-shot").length;

    function appendBatch() {
      const batch = queue.splice(0, 3);
      const frag = document.createDocumentFragment();
      batch.forEach((src) => {
        if (typeof src !== "string" || src === "") return;
        const figure = document.createElement("figure");
        figure.className = "media-shot media-shot--" + kinds[nextIndex % 3];
        const img = document.createElement("img");
        img.src = src;
        img.alt = "";
        img.loading = "lazy";
        img.decoding = "async";
        figure.appendChild(img);
        frag.appendChild(figure);
        reveal.observe(figure);
        nextIndex += 1;
      });
      stream.appendChild(frag);
      if (!queue.length) {
        loader.disconnect();
        sentinel.remove();
      }
    }

    const loader = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          appendBatch();
        }
      },
      { rootMargin: "900px 0px" }
    );
    loader.observe(sentinel);
  }

  function initContactReveal() {
    const items = [...document.querySelectorAll("[data-contact-reveal]")];
    if (!items.length) return;

    if (reduce) {
      items.forEach((item) => item.classList.add("is-in"));
      return;
    }

    function show(item, delay) {
      if (item.classList.contains("is-in")) return;
      window.setTimeout(() => item.classList.add("is-in"), delay);
    }

    function check() {
      items.forEach((item) => {
        const rect = item.getBoundingClientRect();
        const visible = rect.top < window.innerHeight * 0.9 && rect.bottom > 64;
        if (!visible) return;
        const delay = item.classList.contains("contact-reveal--form") ? 180 : 40;
        show(item, delay);
      });
    }

    whenUnveiled(() => {
      window.requestAnimationFrame(() => {
        window.requestAnimationFrame(check);
      });
      window.addEventListener("scroll", check, { passive: true });
      window.addEventListener("resize", check);
    });
  }

  initHomeStage();
  initSplitHero();
  initAboutSlide();
  initMediaStream();
  initContactReveal();
})();
