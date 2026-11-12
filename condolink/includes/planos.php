<?php
// Catálogo de planos. Lê da tabela `planos`; se o banco/tabela não existir,
// usa o catálogo padrão abaixo para a página não quebrar.
require_once __DIR__ . '/init.php';

function planos_padrao(): array
{
    return [
        ['id' => 0, 'slug' => 'essencial', 'nome' => 'Essencial', 'preco_mensal' => '99.00',
         'descricao' => 'Para condomínios pequenos que estão começando a organizar o uso das áreas comuns.',
         'max_unidades' => 50, 'max_areas' => 3, 'destaque' => 0,
         'recursos' => "Reservas de áreas comuns pelo site\nMural de comunicados\nGestão de horários e regras de cada área\nSuporte por e-mail"],
        ['id' => 0, 'slug' => 'premium', 'nome' => 'Premium', 'preco_mensal' => '199.00',
         'descricao' => 'Para condomínios médios que querem mais controle, histórico e comunicação segmentada.',
         'max_unidades' => 200, 'max_areas' => 8, 'destaque' => 1,
         'recursos' => "Tudo do plano Essencial\nComunicados por bloco ou categoria\nControle de convidados\nRelatórios de uso das áreas\nSuporte prioritário por WhatsApp"],
        ['id' => 0, 'slug' => 'pro', 'nome' => 'Pro', 'preco_mensal' => '399.00',
         'descricao' => 'Para grandes complexos residenciais e administradoras com vários condomínios.',
         'max_unidades' => null, 'max_areas' => null, 'destaque' => 0,
         'recursos' => "Tudo do plano Premium\nVários condomínios na mesma conta\nSuporte prioritário 24h\nGerente de conta dedicado"],
    ];
}

function listar_planos(): array
{
    try {
        require_once __DIR__ . '/conexao.php';
        $planos = db()->query('SELECT * FROM planos WHERE ativo = 1 ORDER BY preco_mensal')->fetchAll();
        if ($planos) {
            return $planos;
        }
    } catch (Throwable $erro) {
        error_log('listar_planos: ' . $erro->getMessage());
    }
    return planos_padrao();
}

function buscar_plano(string $slug): ?array
{
    foreach (listar_planos() as $plano) {
        if ($plano['slug'] === $slug) {
            return $plano;
        }
    }
    return null;
}

/** Linhas de "limites + recursos" prontas para exibir como lista. */
function itens_do_plano(array $plano): array
{
    $itens = [];
    $itens[] = $plano['max_unidades'] === null ? 'Unidades ilimitadas' : 'Até ' . (int) $plano['max_unidades'] . ' unidades';
    $itens[] = $plano['max_areas'] === null ? 'Áreas comuns ilimitadas' : 'Até ' . (int) $plano['max_areas'] . ' áreas comuns';
    foreach (preg_split('/\R/', trim((string) $plano['recursos'])) as $linha) {
        if (trim($linha) !== '') {
            $itens[] = trim($linha);
        }
    }
    return $itens;
}

/**
 * Calcula os valores (em centavos, para evitar erro de arredondamento).
 * Retorna: base, desc_pix, total, meses.
 */
function calcular_valores(array $plano, string $ciclo, string $metodo): array
{
    $mensal = (int) round(((float) $plano['preco_mensal']) * 100);
    if ($ciclo === 'anual') {
        $base = (int) round($mensal * 12 * (1 - ((float) DESCONTO_ANUAL) / 100));
        $meses = 12;
    } else {
        $base = $mensal;
        $meses = 1;
    }
    $descPix = $metodo === 'pix' ? (int) round($base * ((float) DESCONTO_PIX) / 100) : 0;
    return ['mensal' => $mensal, 'base' => $base, 'desc_pix' => $descPix, 'total' => $base - $descPix, 'meses' => $meses];
}

function centavos(int $c): string
{
    return moeda($c / 100);
}
