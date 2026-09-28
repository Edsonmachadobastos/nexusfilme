<?php
session_start();
if (!isset($_SESSION["username"])) {
    header('Location: painel.php');
    exit;
}

// Carrega config
$config = file_exists('config.json') ? (json_decode(file_get_contents('config.json'), true) ?? []) : [];
$logoPath = $config['logo'] ?? '';

// Carrega servidores
$json = file_exists('servidores.json') ? (json_decode(file_get_contents('servidores.json'), true) ?? []) : [];
$totalSlots = 6;
$servidoresEmUso = count(array_filter($json, fn($s) => !empty($s)));
$slotsLivres = $totalSlots - $servidoresEmUso;
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard</title>
  <?php include __DIR__ . '/includes/admin_style.php'; ?>
</head>
<body>

<?php $active = 'dashboard'; include __DIR__ . '/includes/admin_sidebar.php'; ?>

<!-- MAIN -->
<main class="main">

  <!-- TOPBAR -->
  <div class="topbar">
    <div class="topbar-left">
      <h1>Dashboard</h1>
      <p>Bem-vindo, <strong><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></strong></p>
    </div>
    <div class="topbar-right">
      <a href="login.php" class="btn btn-ghost" target="_blank"><i class="fas fa-play"></i> Abrir Player</a>
      <button class="btn btn-ghost hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <a href="servidores.php" class="stat-card">
      <div class="stat-icon icon-blue"><i class="fas fa-server"></i></div>
      <div class="stat-info">
        <div class="value"><?= $servidoresEmUso ?></div>
        <div class="label">Servidores Ativos</div>
      </div>
    </a>
    <div class="stat-card">
      <div class="stat-icon icon-teal"><i class="fas fa-database"></i></div>
      <div class="stat-info">
        <div class="value"><?= $totalSlots ?></div>
        <div class="label">Slots Totais</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon icon-amber"><i class="fas fa-circle-check"></i></div>
      <div class="stat-info">
        <div class="value"><?= $slotsLivres ?></div>
        <div class="label">Slots Disponíveis</div>
      </div>
    </div>
    <a href="admin.php" class="stat-card">
      <div class="stat-icon icon-red"><i class="fas fa-gear"></i></div>
      <div class="stat-info">
        <div class="value"><i class="fas fa-circle" style="font-size:.6rem;color:var(--teal);"></i></div>
        <div class="label">Configurações</div>
      </div>
    </a>
  </div>

  <!-- QUICK ACTIONS -->
  <div class="section-title">Ações Rápidas</div>
  <div class="actions-grid">
    <a href="servidores.php" class="action-card">
      <div class="action-icon icon-blue"><i class="fas fa-plus"></i></div>
      <div class="action-label">Gerenciar Servidores</div>
      <div class="action-desc">Adicionar ou editar servidores IPTV</div>
    </a>
    <a href="login.php" class="action-card" target="_blank">
      <div class="action-icon icon-teal"><i class="fas fa-play"></i></div>
      <div class="action-label">Abrir Web Player</div>
      <div class="action-desc">Acessar o player de conteúdo</div>
    </a>
    <a href="admin.php" class="action-card">
      <div class="action-icon icon-amber"><i class="fas fa-image"></i></div>
      <div class="action-label">Trocar Logo</div>
      <div class="action-desc">Atualiza logo em todo o sistema</div>
    </a>
    <a href="admin_jogos.php" class="action-card">
      <div class="action-icon icon-teal"><i class="fas fa-futbol"></i></div>
      <div class="action-label">Jogos do Dia</div>
      <div class="action-desc">Configurar a API de jogos ao vivo</div>
    </a>
    <a href="logoutvs.php" class="action-card">
      <div class="action-icon icon-red"><i class="fas fa-right-from-bracket"></i></div>
      <div class="action-label">Sair do Painel</div>
      <div class="action-desc">Encerrar sessão administrativa</div>
    </a>
  </div>

  <!-- INFO -->
  <div class="section-title">Informações do Sistema</div>
  <div class="info-card">
    <div class="info-row">
      <span class="info-key">Usuário logado</span>
      <span class="info-val"><?= htmlspecialchars($_SESSION['username'] ?? '—') ?></span>
    </div>
    <div class="info-row">
      <span class="info-key">Servidores em uso</span>
      <span class="info-val">
        <?= $servidoresEmUso ?> / <?= $totalSlots ?>
        <span class="badge <?= $servidoresEmUso > 0 ? 'badge-teal' : 'badge-amber' ?>" style="margin-left:8px;">
          <?= $servidoresEmUso > 0 ? 'Ativo' : 'Vazio' ?>
        </span>
      </span>
    </div>
    <div class="info-row">
      <span class="info-key">Logo atual</span>
      <span class="info-val"><?= !empty($logoPath) ? htmlspecialchars(basename($logoPath)) : 'Padrão' ?></span>
    </div>
    <div class="info-row">
      <span class="info-key">Logo do player</span>
      <span class="info-val">
        <span class="badge badge-teal">Sincronizada</span>
      </span>
    </div>
  </div>

</main>

<?php include __DIR__ . '/includes/admin_menu_script.php'; ?>

</body>
</html>
