# CondoLink – como rodar (XAMPP)

1. Copie a pasta `condolink` para `htdocs` e inicie Apache + MySQL.
2. No phpMyAdmin, com o banco `condolink` selecionado (dump original `database/condolink.sql` já importado), rode **`database/migracao_v2.sql`** (uma única vez).
3. Copie `includes/config.local.example.php` para `includes/config.local.php` e preencha:
   - `RESEND_API_KEY` e `MAIL_FROM` (remetente de um domínio verificado no Resend; `onboarding@resend.dev` só entrega para o e-mail dono da conta Resend);
   - `APP_URL` (ex.: `http://localhost/condolink`) para o link do e-mail apontar para o lugar certo;
   - `APP_DEBUG=1` grava o link de redefinição no log do PHP quando não há chave (xampp/apache/logs/error.log).
4. Cadastre-se pelo site e torne-se admin: `UPDATE usuarios SET tipo='Admin' WHERE email='seu@email.com';`
5. Pagamento: `PAGAMENTO_SIMULADO=1` (padrão) aprova na hora e **não cobra nada**. Para cobrar de verdade, implemente `processar_pagamento()` em `includes/gateway.php`.

## O que mudou
- Páginas protegidas (`painel`, `confirmar_email`, `pagamento`, `admin*`) exigem login (`require_login()`); `admin*` exige perfil Admin (`require_admin()`).
- Cadastro faz login automático e leva ao `painel.php`; exige aceite de Privacidade e Cookies (gravado no banco com data).
- `areas.php` foi removida e substituída por `faq.php` (accordion). `img/areas/` ficou sem uso e pode ser apagada.
- Novo `css/app.css` (tokens, menu hambúrguer, componentes e responsividade); `style.css` não foi alterado.
