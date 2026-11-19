// Menu responsivo (hambúrguer/gaveta) e pequenos comportamentos globais.
(function () {
  var toggle = document.querySelector('.nav-toggle');
  var menu = document.getElementById('menu-principal');
  var overlay = document.querySelector('.nav-overlay');
  if (!toggle || !menu) return;

  function abrir(abrirMenu) {
    menu.classList.toggle('is-open', abrirMenu);
    toggle.classList.toggle('is-open', abrirMenu);
    toggle.setAttribute('aria-expanded', abrirMenu ? 'true' : 'false');
    toggle.setAttribute('aria-label', abrirMenu ? 'Fechar menu' : 'Abrir menu');
    document.body.classList.toggle('menu-aberto', abrirMenu);
    if (overlay) overlay.hidden = !abrirMenu;
  }

  toggle.addEventListener('click', function () {
    abrir(!menu.classList.contains('is-open'));
  });
  if (overlay) overlay.addEventListener('click', function () { abrir(false); });
  menu.addEventListener('click', function (ev) {
    if (ev.target.closest('a')) abrir(false);
  });
  document.addEventListener('keydown', function (ev) {
    if (ev.key === 'Escape') { abrir(false); toggle.focus(); }
  });
  window.matchMedia('(min-width: 861px)').addEventListener('change', function (m) {
    if (m.matches) abrir(false);
  });

  // Alertas podem ser dispensados com um clique
  document.querySelectorAll('.flash-area .alerta').forEach(function (a) {
    a.addEventListener('click', function () { a.remove(); });
  });
})();
