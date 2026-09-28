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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['salvar_planos'])) {
    $nomes       = $_POST['nome']              ?? [];
    $precos      = $_POST['preco']             ?? [];
    $periodos    = $_POST['periodo']           ?? [];
    $descricoes  = $_POST['descricao']         ?? [];
    $featuresRaw = $_POST['features']          ?? [];
    $populares   = $_POST['popular']           ?? []; // índices marcados
    $mensagens   = $_POST['mensagem_whatsapp'] ?? [];
    $ids         = $_POST['plano_id']          ?? [];

    $novosPlanos = [];
    foreach ($nomes as $i => $nome) {
        $nome = trim($nome);
        if ($nome === '') {
            continue; // ignora linhas vazias (ex: template não preenchido)
        }

        $features = array_values(array_filter(array_map('trim', explode("\n", $featuresRaw[$i] ?? ''))));

        $id = trim($ids[$i] ?? '');
        if ($id === '') {
            $id = 'plano-' . preg_replace('/[^a-z0-9]+/', '-', strtolower($nome)) . '-' . substr(md5($nome . $i), 0, 5);
        }

        $novosPlanos[] = [
            'id' => $id,
            'nome' => $nome,
            'preco' => trim($precos[$i] ?? ''),
            'periodo' => trim($periodos[$i] ?? ''),
            'descricao' => trim($descricoes[$i] ?? ''),
            'features' => $features,
            'popular' => in_array((string) $i, $populares, true),
            'mensagem_whatsapp' => trim($mensagens[$i] ?? '')
        ];
    }

    if (!empty($novosPlanos)) {
        $config['planos'] = $novosPlanos;
        pwb_save_config($config);
        $_SESSION['success_message'] = 'Planos salvos com sucesso!';
        header('Location: admin_planos.php');
        exit;
    } else {
        $error_message = 'Você precisa manter ao menos um plano preenchido.';
    }
}

$planos = $config['planos'];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Planos - Painel Admin</title>
  <?php include __DIR__ . '/includes/admin_style.php'; ?>
  <style>
    .plano-card { background: var(--surface); border: 1px solid var(--border); border-radius: 12px; padding: 22px 24px; position: relative; margin-bottom: 20px; }
    .remove-plano { position:absolute; top:18px; right:18px; background:none; border:none; color: var(--red); font-size:.8rem; font-weight:600; cursor:pointer; }
    .remove-plano:hover { text-decoration: underline; }
    .checkbox-row { display:flex; align-items:center; gap:10px; margin-top:14px; }
    .checkbox-row input { width:16px; height:16px; }
    .checkbox-row label { font-size:.85rem; font-weight:600; color: var(--dim); }
  </style>
</head>
<body>

<?php $active = 'planos'; $logoPath = $config['logo'] ?? ''; include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Planos</h1>
      <p>Bem-vindo, <strong><?php echo htmlspecialchars($config['usuario']); ?></strong></p>
    </div>
    <div class="topbar-right">
      <button class="btn btn-ghost hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <div class="panel">
    <h2>Planos</h2>
    <p class="panel-desc">
        Estes planos aparecem tanto na página <strong>Assine Já</strong> (premium.php) quanto na página
        <strong>Renovar</strong> (renovar.php). O botão de WhatsApp de cada plano usa o número configurado em
        <a href="admin.php" style="color:var(--accent); text-decoration:underline;">Admin → WhatsApp de vendas/renovação</a>,
        a menos que você escreva uma mensagem personalizada abaixo.
    </p>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="alert alert-success" id="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>
  </div>

  <form method="post" action="" id="planos-form">
    <div id="planos-container">
      <?php foreach ($planos as $i => $plano): ?>
        <?php include __DIR__ . '/includes/admin_plano_card.php'; ?>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; flex-wrap:wrap; gap:12px;">
      <button type="button" id="add-plano" class="btn btn-ghost">+ Adicionar plano</button>
      <button type="submit" name="salvar_planos" value="1" class="btn btn-primary">Salvar Planos</button>
    </div>
  </form>
</main>

  <!-- Template para novo plano (usado via JS) -->
  <template id="plano-template">
    <?php
      $i = '__INDEX__';
      $plano = ['id' => '', 'nome' => '', 'preco' => '', 'periodo' => '', 'descricao' => '', 'features' => [], 'popular' => false, 'mensagem_whatsapp' => ''];
      include __DIR__ . '/includes/admin_plano_card.php';
    ?>
  </template>

  <?php include __DIR__ . '/includes/admin_menu_script.php'; ?>

  <script>
    // Adicionar / remover planos dinamicamente
    let planoIndex = <?php echo count($planos); ?>;
    const container = document.getElementById('planos-container');
    const template = document.getElementById('plano-template');

    document.getElementById('add-plano').addEventListener('click', () => {
      const html = template.innerHTML.replace(/__INDEX__/g, planoIndex);
      const wrapper = document.createElement('div');
      wrapper.innerHTML = html.trim();
      container.appendChild(wrapper.firstElementChild);
      planoIndex++;
    });

    container.addEventListener('click', (e) => {
      if (e.target.closest('.remove-plano')) {
        const card = e.target.closest('.plano-card');
        if (card) card.remove();
      }
    });
  </script>

</body>
</html>
