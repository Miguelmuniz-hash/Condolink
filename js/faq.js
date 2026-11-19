// FAQ: abre uma pergunta por vez e filtra pelo texto digitado.
(function () {
  var itens = Array.prototype.slice.call(document.querySelectorAll('.faq-item'));
  var busca = document.getElementById('faq-busca');
  var vazio = document.getElementById('faq-vazio');

  itens.forEach(function (item) {
    item.addEventListener('toggle', function () {
      if (!item.open) return;
      itens.forEach(function (outro) { if (outro !== item) outro.open = false; });
    });
  });

  if (!busca) return;
  function normalizar(t) {
    return t.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
  }
  busca.addEventListener('input', function () {
    var termo = normalizar(busca.value.trim());
    var achou = 0;
    itens.forEach(function (item) {
      var bate = termo === '' || normalizar(item.textContent).indexOf(termo) !== -1;
      item.hidden = !bate;
      if (bate) achou++;
      if (termo !== '' && bate) item.open = false;
    });
    document.querySelectorAll('.faq-grupo').forEach(function (g) {
      g.hidden = !g.querySelector('.faq-item:not([hidden])');
    });
    vazio.hidden = achou !== 0;
  });
})();
