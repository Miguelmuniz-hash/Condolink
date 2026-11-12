// Máscaras simples de CPF, telefone e CEP (a validação real é feita no servidor).
(function () {
  function ligar(id, formatar) {
    var campo = document.getElementById(id);
    if (!campo) return;
    campo.addEventListener('input', function () { campo.value = formatar(campo.value.replace(/\D/g, '')); });
  }
  ligar('cpf', function (d) {
    d = d.slice(0, 11);
    return d.replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d)/, '$1.$2').replace(/(\d{3})(\d{1,2})$/, '$1-$2');
  });
  ligar('cep', function (d) {
    d = d.slice(0, 8);
    return d.length > 5 ? d.slice(0, 5) + '-' + d.slice(5) : d;
  });
  ligar('telefone', function (d) {
    d = d.slice(0, 11);
    if (d.length > 6) return '(' + d.slice(0, 2) + ') ' + d.slice(2, d.length - 4) + '-' + d.slice(-4);
    if (d.length > 2) return '(' + d.slice(0, 2) + ') ' + d.slice(2);
    return d;
  });
})();
