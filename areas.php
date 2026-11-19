<?php
$titulo = 'Áreas de lazer';
$cssExtra = ['areas.css'];

// Áreas cadastradas no banco (tabela areas_comuns). Se o banco estiver
// indisponível ou vazio, mostra exemplos para a página não ficar vazia.
$areas = [];
try {
    require_once __DIR__ . '/includes/conexao.php';
    $areas = $pdo->query('SELECT nome, capacidade, regras, status_areas FROM areas_comuns ORDER BY nome')->fetchAll();
} catch (Exception $erro) {
    $areas = [];
}

if (!$areas) {
    $areas = [
        ['nome' => 'Piscina',            'capacidade' => 40, 'regras' => 'Uso de traje de banho obrigatório. Menores de 12 anos acompanhados de um responsável.', 'status_areas' => 'Ativo'],
        ['nome' => 'Salão de Festas',    'capacidade' => 60, 'regras' => 'Reserva com antecedência mínima de 7 dias. Encerramento até as 23h.',                  'status_areas' => 'Ativo'],
        ['nome' => 'Churrasqueira',      'capacidade' => 20, 'regras' => 'Limpeza ao final do uso. Reserva por turno (tarde ou noite).',                         'status_areas' => 'Ativo'],
        ['nome' => 'Academia',           'capacidade' => 15, 'regras' => 'Uso de toalha e calçado fechado. Funcionamento das 6h às 22h.',                        'status_areas' => 'Ativo'],
        ['nome' => 'Quadra Poliesportiva', 'capacidade' => 24, 'regras' => 'Reserva de até 2 horas por morador. Proibido o uso de calçado de sola preta.',       'status_areas' => 'Manutenção'],
    ];
}

function imagem_area(string $nome): ?string
{
    $n = mb_strtolower($nome, 'UTF-8');
    if (str_contains($n, 'piscina')) {
        return 'img/areas/piscina.jpg';
    }
    if (preg_match('/sal[aã]o|hall|festa|lounge|sala/u', $n)) {
        return 'img/areas/hall.jpg';
    }
    return null;
}

require __DIR__ . '/includes/header.php';
?>

<section class="areas_hero">
    <h1>Áreas de <span>Lazer</span> e Convivência</h1>
    <p>Conheça os espaços que a CondoLink ajuda a organizar: <br>capacidade, regras e disponibilidade de cada área do seu condomínio.</p>
</section>

<section class="areas_secao">
    <h2>Espaços do condomínio</h2>
    <p>Cada área tem suas próprias regras e limite de pessoas, definidos pelo condomínio.</p>

    <div class="areas_grid">
<?php foreach ($areas as $area):
    $foto = imagem_area($area['nome']);
    $manutencao = $area['status_areas'] === 'Manutenção';
?>
        <article class="area_card">
            <div class="area_foto">
<?php if ($foto): ?>
                <img src="<?= e($foto) ?>" alt="<?= e($area['nome']) ?>">
<?php else: ?>
                <span class="area_inicial"><?= e(mb_substr($area['nome'], 0, 1, 'UTF-8')) ?></span>
<?php endif; ?>
            </div>
            <div class="area_corpo">
                <div class="area_topo">
                    <h3><?= e($area['nome']) ?></h3>
                    <span class="area_status <?= $manutencao ? 'status_manutencao' : 'status_ativo' ?>">
                        <?= $manutencao ? 'Em manutenção' : 'Disponível' ?>
                    </span>
                </div>
                <p class="area_capacidade">Até <?= (int) $area['capacidade'] ?> pessoas</p>
                <p class="area_regras"><?= e($area['regras']) ?></p>
            </div>
        </article>
<?php endforeach; ?>
    </div>
</section>

<section class="areas_secao">
    <h2>Acompanhe cada reserva</h2>
    <p>Todo pedido de reserva passa por um destes status.</p>

    <div class="reserva_passos">
        <div class="reserva_passo">
            <h4>Pendente</h4>
            <p>O morador escolheu área, data e horário. O pedido aguarda confirmação.</p>
        </div>
        <div class="reserva_passo">
            <h4>Confirmada</h4>
            <p>Horário garantido e registrado, sem conflito com outras reservas.</p>
        </div>
        <div class="reserva_passo">
            <h4>Cancelada</h4>
            <p>O horário volta a ficar livre para os demais moradores.</p>
        </div>
    </div>
</section>

<section class="section_cta">
    <div>
        <img src="https://www.image2url.com/r2/default/images/1791469493287-1eef621c-4465-4b5c-93a5-49bc0c4cbefe.png" alt="CondoLink">
    </div>

    <div class="banner_incial">
        <div class="cta_texto">
            <h2>Pronto para organizar o lazer?</h2>
            <p>Garanta mais praticidade, facilidade e segurança para os <br>momentos de lazer dos moradores.</p>
        </div>
    </div>

    <div class="botao_banner">
        <a href="plano.php">Contratar agora </a>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
