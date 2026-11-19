// Checkout: alterna cartão/PIX, recalcula o resumo e valida o cartão no navegador.
// Os campos do cartão NÃO têm atributo "name": nunca são enviados ao servidor.
(function () {
  var secao = document.querySelector('.checkout');
  var form = document.getElementById('form-pagamento');
  if (!secao || !form) return;

  var mensal = parseInt(secao.dataset.mensal, 10);   // centavos
  var anual = parseInt(secao.dataset.anual, 10);     // centavos
  var descPix = parseFloat(secao.dataset.pix) || 0;  // %

  var campoMetodo = document.getElementById('metodo');
  var areaCartao = document.getElementById('area_cartao');
  var areaPix = document.getElementById('area_PIX');
  var abas = document.querySelectorAll('.checkout-abas .aba');
  var erroCartao = document.getElementById('erro-cartao');

  var nome = document.getElementById('cc-nome');
  var numero = document.getElementById('cc-numero');
  var validade = document.getElementById('cc-validade');
  var cvv = document.getElementById('cc-cvv');

  function brl(centavos) {
    return (centavos / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  }

  function cicloAtual() {
    var marcado = form.querySelector('input[name="ciclo"]:checked');
    return marcado ? marcado.value : 'mensal';
  }

  function atualizarResumo() {
    var base = cicloAtual() === 'anual' ? anual : mensal;
    var pix = campoMetodo.value === 'pix';
    var desconto = pix ? Math.round(base * descPix / 100) : 0;
    document.getElementById('r-subtotal').textContent = brl(base);
    document.getElementById('r-desconto').textContent = '-' + brl(desconto);
    document.getElementById('r-total').textContent = brl(base - desconto);
    document.getElementById('linha-pix').hidden = !pix;
    document.getElementById('resumo-ciclo').textContent = cicloAtual() === 'anual' ? 'Faturamento anual' : 'Faturamento mensal';
  }

  function mostrar(metodo) {
    campoMetodo.value = metodo;
    areaCartao.classList.toggle('oculto', metodo !== 'cartao');
    areaPix.classList.toggle('oculto', metodo !== 'pix');
    abas.forEach(function (aba) {
      var ativa = aba.dataset.metodo === metodo;
      aba.classList.toggle('ativa', ativa);
      aba.setAttribute('aria-selected', ativa ? 'true' : 'false');
    });
    atualizarResumo();
  }

  abas.forEach(function (aba) {
    aba.addEventListener('click', function () { mostrar(aba.dataset.metodo); });
  });
  form.querySelectorAll('input[name="ciclo"]').forEach(function (r) {
    r.addEventListener('change', atualizarResumo);
  });

  // Máscaras
  numero.addEventListener('input', function () {
    var d = numero.value.replace(/\D/g, '').slice(0, 16);
    numero.value = d.replace(/(.{4})/g, '$1 ').trim();
  });
  validade.addEventListener('input', function () {
    var d = validade.value.replace(/\D/g, '').slice(0, 4);
    validade.value = d.length > 2 ? d.slice(0, 2) + '/' + d.slice(2) : d;
  });
  cvv.addEventListener('input', function () { cvv.value = cvv.value.replace(/\D/g, '').slice(0, 4); });

  // Algoritmo de Luhn
  function luhn(num) {
    var soma = 0, alterna = false;
    for (var i = num.length - 1; i >= 0; i--) {
      var n = parseInt(num.charAt(i), 10);
      if (alterna) { n *= 2; if (n > 9) n -= 9; }
      soma += n; alterna = !alterna;
    }
    return soma % 10 === 0;
  }

  function validarCartao() {
    var n = numero.value.replace(/\D/g, '');
    if (nome.value.trim().length < 3) return 'Informe o nome como está no cartão.';
    if (n.length < 13 || n.length > 16 || !luhn(n)) return 'Número de cartão inválido.';
    var m = /^(\d{2})\/(\d{2})$/.exec(validade.value);
    if (!m || +m[1] < 1 || +m[1] > 12) return 'Validade inválida (use MM/AA).';
    var hoje = new Date();
    var fim = new Date(2000 + (+m[2]), +m[1], 1);   // primeiro dia do mês seguinte à validade
    if (fim <= hoje) return 'Cartão vencido.';
    if (cvv.value.length < 3) return 'CVV inválido.';
    return '';
  }

  form.addEventListener('submit', function (ev) {
    if (campoMetodo.value !== 'cartao') return;
    var msg = validarCartao();
    erroCartao.hidden = !msg;
    erroCartao.textContent = msg;
    if (msg) ev.preventDefault();
  });

  mostrar(campoMetodo.value === 'pix' ? 'pix' : 'cartao');
})();
