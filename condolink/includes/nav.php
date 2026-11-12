<nav class="navbar" aria-label="Navegação principal">
    <a class="navbar-logo" href="index.php">
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink">
    </a>

    <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="menu-principal" aria-label="Abrir menu">
        <span></span><span></span><span></span>
    </button>

    <div class="nav-menu" id="menu-principal">
        <div class="paginas_navbar">
            <a href="index.php">Início</a>
            <a href="index.php#nosso-proposito">Sobre nós</a>
            <a href="plano.php">Planos</a>
<?php if (usuario_logado()): ?>
            <a href="painel.php">Minha conta</a>
<?php endif; ?>
<?php if (usuario_admin()): ?>
            <a href="admin.php">Painel admin</a>
<?php endif; ?>
        </div>

        <div class="botoes_inicio">
<?php if (usuario_logado()): ?>
            <form action="logout.php" method="POST" class="form-sair">
                <?= csrf_field() ?>
                <button type="submit" class="botao_1">Sair</button>
            </form>
<?php else: ?>
            <a href="login.php" class="botao_1">Entrar</a>
            <a href="cadastro.php" class="botao_2">Cadastre-se</a>
<?php endif; ?>
        </div>
    </div>
</nav>
<div class="nav-overlay" hidden></div>
