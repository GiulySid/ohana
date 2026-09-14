(() => {
  document.querySelectorAll("[data-add-row]").forEach((button) => {
    const target = document.querySelector(button.getAttribute("data-add-row"));
    const template = document.querySelector(button.getAttribute("data-template"));
    if (!target || !template) return;
    button.addEventListener("click", () => {
      const node = template.content.cloneNode(true);
      target.appendChild(node);
    });
  });

  document.addEventListener("click", (event) => {
    const remove = event.target.closest("[data-remove-row]");
    if (!remove) return;
    remove.closest("[data-row]")?.remove();
  });

  document.addEventListener("submit", (event) => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    const submitter = event.submitter;
    const message = submitter?.getAttribute("data-confirm") || form.getAttribute("data-confirm");
    if (message && !window.confirm(message)) {
      event.preventDefault();
    }
  });
})();
