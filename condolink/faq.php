<?php
$titulo = 'Perguntas frequentes';
require_once __DIR__ . '/includes/init.php';

$faq = [
    'Sobre a CondoLink' => [
        ['O que é a CondoLink?',
         'É uma plataforma web de gestão condominial. Ela centraliza reservas de áreas comuns, comunicados e informações importantes para síndicos, administradoras e moradores.'],
        ['Para quem a plataforma foi feita?',
         'Para condomínios residenciais de qualquer porte, síndicos profissionais e administradoras. Cada perfil enxerga apenas o que faz sentido para o seu papel.'],
        ['Preciso instalar algum aplicativo?',
         'Não. A CondoLink funciona direto no navegador, no computador, tablet ou celular.'],
    ],
    'Reservas e áreas comuns' => [
        ['Como funciona a reserva de uma área comum?',
         'O morador escolhe a área, a data e o horário. O pedido entra como pendente, é confirmado sem conflito com outras reservas e, se necessário, pode ser cancelado, liberando o horário para os demais moradores.'],
        ['Cada área pode ter regras próprias?',
         'Sim. Cada espaço tem capacidade, regras de uso e status (disponível ou em manutenção) definidos pelo condomínio.'],
        ['O que acontece se uma área estiver em manutenção?',
         'Ela aparece como indisponível e não recebe novas reservas até voltar a ficar ativa.'],
    ],
    'Planos e pagamento' => [
        ['Quais são os planos disponíveis?',
         'Temos os planos Essencial, Premium e Pro, que variam em limite de unidades, de áreas comuns e de recursos. Compare tudo na página de Planos.'],
        ['Quais formas de pagamento são aceitas?',
         'Cartão de crédito e PIX. No PIX há desconto sobre o valor do plano, mostrado no resumo antes de você finalizar.'],
        ['Posso escolher entre cobrança mensal e anual?',
         'Sim. Você escolhe o ciclo na tela de pagamento e vê o valor atualizado no resumo do pedido.'],
        ['Posso trocar de plano?',
         'Pode. Basta contratar outro plano na página de Planos: a nova assinatura passa a valer e a anterior é encerrada.'],
        ['Como cancelo minha assinatura?',
         'Fale com o nosso suporte pelo WhatsApp ou e-mail e cuidaremos do cancelamento.'],
    ],
    'Conta e segurança' => [
        ['Esqueci minha senha. E agora?',
         'Na tela de login, clique em "Esqueci minha senha", informe seu e-mail e enviaremos um link para criar uma nova senha. O link vale por 1 hora e só pode ser usado uma vez.'],
        ['Meus dados estão seguros?',
         'Senhas são guardadas de forma criptografada (hash), as sessões expiram por inatividade e as páginas restritas exigem login. Saiba mais na página Estrutura.'],
        ['Como a CondoLink trata meus dados pessoais?',
         'Seguimos a LGPD. Você pode ler tudo nos Termos de Privacidade e na Política de Cookies, disponíveis na página de Termos.'],
        ['Posso falar com o encarregado de dados (DPO)?',
         'Pode. O contato do DPO está ao final da página de Termos.'],
    ],
];

require __DIR__ . '/includes/header.php';
?>

<section class="faq-hero">
    <h1>Perguntas <span>Frequentes</span></h1>
    <p>Tire suas dúvidas sobre reservas, planos, pagamentos e segurança.</p>
</section>

<section class="faq-corpo">
    <label class="faq-busca" for="faq-busca">
        <span class="sr-only">Buscar nas perguntas</span>
        <input type="search" id="faq-busca" placeholder="Buscar uma dúvida..." autocomplete="off">
    </label>

<?php foreach ($faq as $categoria => $itens): ?>
    <div class="faq-grupo">
        <h2><?= e($categoria) ?></h2>
<?php foreach ($itens as [$pergunta, $resposta]): ?>
        <details class="faq-item">
            <summary><?= e($pergunta) ?></summary>
            <p><?= e($resposta) ?></p>
        </details>
<?php endforeach; ?>
    </div>
<?php endforeach; ?>

    <p class="faq-vazio" id="faq-vazio" hidden>Nenhuma pergunta encontrada. Tente outras palavras.</p>
</section>

<section class="section_cta">
    <div>
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink">
    </div>
    <div class="banner_incial">
        <div class="cta_texto">
            <h2>Ainda com dúvidas?</h2>
            <p>Escolha um plano e comece a organizar o seu condomínio hoje.</p>
        </div>
    </div>
    <div class="botao_banner">
        <a href="plano.php">Ver planos</a>
    </div>
</section>

<?php
$jsExtra = ['js/faq.js'];
require __DIR__ . '/includes/footer.php';
