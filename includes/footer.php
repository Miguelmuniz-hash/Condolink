<?php
// Opcional na página, antes de incluir: $jsExtra = ['js/arquivo.js', 'https://cdn...'];
$jsExtra = $jsExtra ?? [];
?>
<footer class="site-footer">
  <div class="footer-container">

    <div class="footer-column">
      <div class="iamgem_logo_footer">
        <img src="https://www.image2url.com/r2/default/images/1793729008704-cca144e8-6c45-4656-b8cb-d4772507c1a5.png" alt="CondoLink">
      </div>
      <p class="textinho"> Seu lar com mais segurança<br>e qualidade de vida.</p>
    </div>

    <div class="footer-column">
      <h3>Institucional</h3>
      <ul>
        <li><a href="index.php#nosso-proposito">Sobre nós</a></li>
        <li><a href="estrutura.php">Estrutura</a></li>
        <li><a href="plano.php">Planos</a></li>
        <li><a href="faq.php">FAQ</a></li>

      </ul>
    </div>

    <div class="footer-column">
      <h3>Suporte</h3>
      <ul>
        <li><a href="https://wa.me" target="_blank">Contato</a></li>
        <li><a href="termos.php#privacidade">Política de privacidade</a></li>
        <li><a href="termos.php#termos">Termos de uso</a></li>
        <li><a href="termos.php#cookies">Política de cookies</a></li>
      </ul>
    </div>

    <div class="footer-column footer-social-right">
      <h3>Redes sociais</h3>
      <a href="https://www.instagram.com/condolink_/"><img src="https://www.image2url.com/r2/default/images/1787158696740-61a27c41-8caf-474b-8f1f-8d8800867159.png" alt="Instagram"></a>
      <a href="https://wa.me" target="_blank"><img src="https://www.image2url.com/r2/default/images/1787158819012-42bbd0f4-7840-47e8-a556-c63e373cecd1.png" alt="WhatsApp"></a>
    </div>

    <div class="footer-bottom">
      <p>&copy; <?= date('Y') ?> Condomínio Bela Vista. Todos os direitos reservados.</p>
    </div>

  </div>
</footer>
<script src="js/app.js"></script>
<?php foreach ($jsExtra as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
