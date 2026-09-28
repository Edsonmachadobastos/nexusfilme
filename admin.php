<?php
session_start();
require_once __DIR__ . '/includes/planos_helpers.php';

// Verifica se o usuário está autenticado
if (!isset($_SESSION["username"])) {
    header('Location: painel.php'); // Redireciona para o painel de login
    exit;
}

// Função para carregar as configurações do arquivo JSON
function loadConfig() {
    return pwb_load_config();
}

// Função para salvar as configurações no arquivo JSON
function saveConfig($config) {
    pwb_save_config($config);
}

// Carregar configurações
$config = loadConfig();

// Verifique se há mensagem de sucesso na sessão
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['success_message']); // Limpar a mensagem após usá-la

// Processo de autenticação do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Verifica se as credenciais estão corretas
    if ($username === $config['usuario'] && $password === $config['senha']) {
        $_SESSION["username"] = $username;
        header('Location: admin.php'); // Redireciona para o painel de servidores
        exit;
    } else {
        $error_message = 'Usuário ou senha inválidos!';
    }
}

// Verifique se o formulário de configuração foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['new_username']) && isset($_POST['new_password'])) {
    $config['usuario'] = $_POST['new_username'];
    $config['senha'] = $_POST['new_password'];
    $config['nome_painel'] = trim($_POST['nome_painel'] ?? $config['nome_painel']);
    $config['whatsapp_numero'] = trim($_POST['whatsapp_numero'] ?? $config['whatsapp_numero']);
    $config['tmdb_api_key'] = trim($_POST['tmdb_api_key'] ?? $config['tmdb_api_key']);
    // A API de Jogos do Dia agora é configurada em admin_jogos.php

    $hasError = false;

    // Processar o upload da logo, se fornecido
    if (isset($_FILES['new_logo']) && $_FILES['new_logo']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "img/";
        $target_file = $target_dir . basename($_FILES["new_logo"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["new_logo"]["tmp_name"]);
        if ($check === false) {
            $error_message = "O arquivo de logo não é uma imagem.";
            $uploadOk = 0;
        }

        if ($_FILES["new_logo"]["size"] > 500000) {
            $error_message = "Desculpe, a logo é muito grande (máx. 500KB).";
            $uploadOk = 0;
        }

        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" && $imageFileType != "webp") {
            $error_message = "Desculpe, apenas arquivos JPG, JPEG, PNG, WEBP e GIF são permitidos para a logo.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (!empty($config['logo']) && file_exists($config['logo'])) {
                unlink($config['logo']);
            }
            if (move_uploaded_file($_FILES["new_logo"]["tmp_name"], $target_file)) {
                $config['logo'] = $target_file;
                // Sincroniza as logos do web player automaticamente
                @copy($target_file, 'img/xmas.webp');
                @copy($target_file, 'img/offvs.png.png');
                @copy($target_file, 'assets/img/logo.png');
            } else {
                $error_message = "Desculpe, houve um erro ao fazer o upload da logo.";
                $hasError = true;
            }
        } else {
            $hasError = true;
        }
    }

    // Processar o upload da imagem de fundo (tela de login), se fornecido
    if (!$hasError && isset($_FILES['new_background']) && $_FILES['new_background']['error'] == UPLOAD_ERR_OK) {
        $target_dir = "img/";
        $target_file = $target_dir . basename($_FILES["new_background"]["name"]);
        $uploadOk = 1;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $check = getimagesize($_FILES["new_background"]["tmp_name"]);
        if ($check === false) {
            $error_message = "O arquivo de fundo não é uma imagem.";
            $uploadOk = 0;
        }

        if ($_FILES["new_background"]["size"] > 4000000) {
            $error_message = "Desculpe, a imagem de fundo é muito grande (máx. 4MB).";
            $uploadOk = 0;
        }

        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "webp") {
            $error_message = "Desculpe, apenas arquivos JPG, JPEG, PNG e WEBP são permitidos para o fundo.";
            $uploadOk = 0;
        }

        if ($uploadOk == 1) {
            if (!empty($config['background']) && file_exists($config['background'])) {
                unlink($config['background']);
            }
            if (move_uploaded_file($_FILES["new_background"]["tmp_name"], $target_file)) {
                $config['background'] = $target_file;
            } else {
                $error_message = "Desculpe, houve um erro ao fazer o upload da imagem de fundo.";
                $hasError = true;
            }
        } else {
            $hasError = true;
        }
    }

    if (!$hasError) {
        saveConfig($config);
        $_SESSION['success_message'] = "Configurações salvas com sucesso!";
        header("Location: admin.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configurações - Painel Admin</title>
  <?php include __DIR__ . '/includes/admin_style.php'; ?>
</head>
<body>

<?php $active = 'admin'; $logoPath = $config['logo'] ?? ''; include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Configurações</h1>
      <p>Bem-vindo, <strong><?php echo htmlspecialchars($config['usuario']); ?></strong></p>
    </div>
    <div class="topbar-right">
      <button class="btn btn-ghost hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <div class="panel">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <?php if ($success_message): ?>
        <div class="alert alert-success" id="success-message"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form method="post" action="" enctype="multipart/form-data">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Usuário</label>
            <input type="text" name="new_username" placeholder="Novo Usuário" value="<?php echo htmlspecialchars($config['usuario']); ?>" required class="form-input">
          </div>
          <div class="form-group">
            <label class="form-label">Senha</label>
            <input type="text" name="new_password" placeholder="Nova Senha" value="<?php echo htmlspecialchars($config['senha']); ?>" required class="form-input">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nome do painel</label>
          <input type="text" name="nome_painel" placeholder="Nome exibido nas páginas de planos" value="<?php echo htmlspecialchars($config['nome_painel']); ?>" class="form-input">
        </div>

        <div class="form-group">
          <label class="form-label">WhatsApp de vendas/renovação</label>
          <input type="text" name="whatsapp_numero" placeholder="Ex: 5511981173992 (DDI+DDD+número)" value="<?php echo htmlspecialchars($config['whatsapp_numero']); ?>" class="form-input">
          <p class="form-hint">Usado nos botões de WhatsApp da página "Assine Já" e da página "Renovar".</p>
        </div>

        <div class="form-group">
          <label class="form-label">Chave da API TMDB</label>
          <input type="text" name="tmdb_api_key" placeholder="Chave da API do The Movie Database" value="<?php echo htmlspecialchars($config['tmdb_api_key']); ?>" class="form-input">
          <p class="form-hint">Usada para buscar automaticamente capa, elenco e trailer de filmes e séries. Gratuita em <a href="https://www.themoviedb.org/settings/api" target="_blank" rel="noopener">themoviedb.org/settings/api</a>.</p>
        </div>

        <div class="form-group">
          <label class="form-label">Logo</label>
          <input type="file" name="new_logo" accept="image/*" class="form-input form-file">
          <img src="<?php echo htmlspecialchars($config['logo']); ?>" alt="Logo" class="preview-thumb">
        </div>

        <div class="form-group">
          <label class="form-label">Imagem de fundo da tela de login</label>
          <input type="file" name="new_background" accept="image/*" class="form-input form-file">
          <p class="form-hint">JPG, PNG ou WEBP — até 4MB. Recomendado: imagem larga (ex: 1920x1080) para preencher bem a tela.</p>
          <?php if (!empty($config['background'])): ?>
          <img src="<?php echo htmlspecialchars($config['background']); ?>?t=<?php echo time(); ?>" alt="Fundo do login" class="preview-wide">
          <?php else: ?>
          <p class="form-hint" style="color:var(--amber);">Nenhuma imagem de fundo definida ainda — a tela de login está usando o fundo padrão.</p>
          <?php endif; ?>
        </div>

        <button type="submit" class="btn btn-primary btn-block">Salvar</button>
    </form>
  </div>

</main>

<?php include __DIR__ . '/includes/admin_menu_script.php'; ?>

</body>
</html>
