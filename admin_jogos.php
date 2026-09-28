<?php
session_start();
require_once __DIR__ . '/includes/planos_helpers.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION["username"])) {
    header('Location: painel.php');
    exit;
}

$config = pwb_load_config();

$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_jogos'])) {
    $novaUrl = trim($_POST['sports_api_url'] ?? '');

    if ($novaUrl !== '' && !preg_match('#^https?://#i', $novaUrl)) {
        $error_message = 'O endereço da API precisa começar com http:// ou https://.';
    } else {
        $config['sports_api_url'] = $novaUrl;
        pwb_save_config($config);
        $_SESSION['success_message'] = 'API de Jogos do Dia salva com sucesso!';
        header('Location: admin_jogos.php');
        exit;
    }
}

$sportsApiUrl = $config['sports_api_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Jogos do Dia - Painel Admin</title>
  <?php include __DIR__ . '/includes/admin_style.php'; ?>
  <style>
    .jogos-preview {
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid var(--border);
      background: var(--surface2);
      min-height: 420px;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .jogos-preview iframe { width: 100%; height: 480px; border: 0; display: block; }
    .jogos-preview .empty { color: var(--faint); font-size: .9rem; padding: 40px 20px; text-align: center; }
  </style>
</head>
<body>

<?php $active = 'jogos'; $logoPath = $config['logo'] ?? ''; include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Jogos do Dia</h1>
      <p>Configure a API/link exibido na página "Jogos do Dia" do web player</p>
    </div>
    <div class="topbar-right">
      <a href="sportsschedule.php" class="btn btn-ghost" target="_blank"><i class="fas fa-arrow-up-right-from-square"></i> Ver página do usuário</a>
      <button class="btn btn-ghost hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <div class="panel">
    <h2><i class="fas fa-futbol" style="color: var(--teal); margin-right:6px;"></i> API de Jogos do Dia</h2>
    <p class="panel-desc">
      Esse endereço é carregado dentro de um iframe na página "Jogos do Dia" (sportsschedule.php) para todos os usuários do web player.
      Se ficar vazio, os usuários verão um aviso pedindo para falar com o administrador.
    </p>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success" id="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <div class="form-group">
        <label class="form-label">Endereço da API de jogos</label>
        <input type="text" name="sports_api_url" value="<?php echo htmlspecialchars($sportsApiUrl); ?>"
               placeholder="https://painelwb.com.br/api/link.php?token=..." class="form-input">
        <p class="form-hint">Cole aqui o link fornecido pelo seu provedor da API de jogos ao vivo.</p>
      </div>
      <button type="submit" name="salvar_jogos" value="1" class="btn btn-primary">Salvar</button>
    </form>
  </div>

  <div class="panel">
    <h2>Pré-visualização</h2>
    <p class="panel-desc">É exatamente isso que os usuários verão na página "Jogos do Dia".</p>
    <div class="jogos-preview">
      <?php if (!empty($sportsApiUrl)): ?>
        <iframe src="<?php echo htmlspecialchars($sportsApiUrl); ?>" loading="lazy" title="Pré-visualização Jogos do Dia"></iframe>
      <?php else: ?>
        <div class="empty">
          <i class="fas fa-triangle-exclamation" style="font-size:1.4rem; display:block; margin-bottom:10px;"></i>
          Nenhum endereço configurado ainda.
        </div>
      <?php endif; ?>
    </div>
  </div>

</main>

<?php include __DIR__ . '/includes/admin_menu_script.php'; ?>

</body>
</html>
