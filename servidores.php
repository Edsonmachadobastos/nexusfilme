<?php
session_start();

// Verifica se o usuário está autenticado
if (!isset($_SESSION["username"])) {
    header('Location: painel.php'); // Redireciona para o painel de login
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Servidores - Painel Admin</title>
  <?php
    $__configForSidebar = file_exists(__DIR__ . '/config.json') ? (json_decode(file_get_contents(__DIR__ . '/config.json'), true) ?? []) : [];
    include __DIR__ . '/includes/admin_style.php';
  ?>
</head>
<body>

<?php $active = 'servidores'; $logoPath = $__configForSidebar['logo'] ?? ''; include __DIR__ . '/includes/admin_sidebar.php'; ?>

<main class="main">

  <div class="topbar">
    <div class="topbar-left">
      <h1>Servidores</h1>
      <p>Painel de gerenciamento DNS</p>
    </div>
    <div class="topbar-right">
      <button class="btn btn-ghost hamburger" id="menuToggle"><i class="fas fa-bars"></i></button>
    </div>
  </div>

  <div class="panel">
        <?php
        // Incluindo o arquivo de configuração
        include 'config.php';

        session_start();

        // Variável para armazenar mensagem de notificação
        $notification = '';

        // Função para salvar as alterações no arquivo JSON
        function save_json($data) {
            global $json_file;
            file_put_contents($json_file, json_encode($data, JSON_PRETTY_PRINT));
        }

        // Carregar servidores do JSON
        $json = json_decode(file_get_contents($json_file), true);

        // Processando o envio do formulário para adicionar servidor
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_server'])) {
            $serverName = trim($_POST['server_name']);
            $serverAddress = trim($_POST['server_address']);

            // Normaliza o endereço: adiciona http:// se faltar esquema, remove barra final
            if ($serverAddress !== '' && !preg_match('#^https?://#i', $serverAddress)) {
                $serverAddress = 'http://' . $serverAddress;
            }
            $serverAddress = rtrim($serverAddress, '/');

            if ($serverName && $serverAddress) {
                // Encontra o maior índice de servidor JÁ EXISTENTE (ignora chaves como
                // default_color/dns_number e não colide com índices usados por servidores
                // que ainda estão cadastrados, mesmo que outros tenham sido excluídos)
                $maxIndex = 0;
                foreach ($json as $key => $value) {
                    if (preg_match('/^server(\d+)$/', (string) $key, $m)) {
                        $maxIndex = max($maxIndex, (int) $m[1]);
                    }
                }
                $newIndex = $maxIndex + 1;
                $json["server{$newIndex}_name"] = $serverName;
                $json["server{$newIndex}"] = $serverAddress;
                save_json($json);
                $notification = 'Servidor adicionado com sucesso!';
            } else {
                $notification = 'Por favor, preencha ambos os campos.';
            }
        }

        // Processando o envio do formulário para excluir servidor
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_server'])) {
            $serverIndex = $_POST['delete_server'];
            unset($json[$serverIndex . '_name']);
            unset($json[$serverIndex]);
            save_json($json);
            $notification = 'Servidor excluído com sucesso!';
        }

        // Processando o envio do formulário para editar servidor
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_server'])) {
            $serverIndex = $_POST['server_index'];
            $serverName = trim($_POST['edit_server_name']);
            $serverAddress = trim($_POST['edit_server_address']);

            if ($serverAddress !== '' && !preg_match('#^https?://#i', $serverAddress)) {
                $serverAddress = 'http://' . $serverAddress;
            }
            $serverAddress = rtrim($serverAddress, '/');

            if ($serverName && $serverAddress) {
                $json["{$serverIndex}_name"] = $serverName;
                $json[$serverIndex] = $serverAddress;
                save_json($json);
                $notification = 'Servidor editado com sucesso!';
            } else {
                $notification = 'Por favor, preencha ambos os campos.';
            }
        }
        ?>

        <?php if ($notification): ?>
            <div id="notification" class="alert alert-success" role="alert" aria-live="assertive"><?php echo htmlspecialchars($notification); ?></div>
        <?php endif; ?>

        <h2 style="margin-bottom:16px;">Adicionar Servidor</h2>
        <form method="post" action="">
            <div class="form-group">
                <label for="server_name" class="form-label">Nome do Servidor</label>
                <input type="text" id="server_name" name="server_name" placeholder="Ex: Google DNS" class="form-input" required />
            </div>
            <div class="form-group">
                <label for="server_address" class="form-label">Endereço do Servidor</label>
                <input type="text" id="server_address" name="server_address" placeholder="Ex: 8.8.8.8" class="form-input" required />
            </div>
            <button type="submit" name="add_server" aria-label="Adicionar Servidor" class="btn btn-primary btn-block">Adicionar Servidor</button>
        </form>
  </div>

  <div class="panel">
        <h3 style="font-size:1.1rem; font-weight:700; margin-bottom:14px;">Servidores Adicionados</h3>
        <div class="table-wrap">
          <table class="admin-table">
            <thead>
              <tr>
                <th>Nome</th>
                <th>Endereço</th>
                <th>Ações</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($json as $key => $value): ?>
                <?php if (strpos($key, '_name') !== false): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($value); ?></td>
                    <td><?php echo htmlspecialchars($json[str_replace('_name', '', $key)]); ?></td>
                    <td>
                      <div style="display:flex; gap:8px;">
                        <button onclick="editServer('<?php echo str_replace('_name', '', $key); ?>', '<?php echo htmlspecialchars($value); ?>', '<?php echo htmlspecialchars($json[str_replace('_name', '', $key)]); ?>')" class="btn btn-ghost">Editar</button>
                        <form method="post" action="" style="display:inline;">
                          <input type="hidden" name="delete_server" value="<?php echo str_replace('_name', '', $key); ?>">
                          <button type="submit" class="btn btn-danger">Excluir</button>
                        </form>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
  </div>

  <!-- Overlay para fundo escurecido (modal de edição) -->
  <div id="modalOverlay" class="overlay" onclick="closeEditForm()"></div>

  <!-- Formulário de Edição -->
  <div id="editForm" class="modal">
        <h4>Editar Servidor</h4>
        <form method="post" action="">
            <input type="hidden" id="server_index" name="server_index" />
            <div class="form-group">
                <label for="edit_server_name" class="form-label">Nome do Servidor</label>
                <input type="text" id="edit_server_name" name="edit_server_name" class="form-input" required />
            </div>
            <div class="form-group">
                <label for="edit_server_address" class="form-label">Endereço do Servidor</label>
                <input type="text" id="edit_server_address" name="edit_server_address" class="form-input" required />
            </div>
            <button type="submit" name="edit_server" class="btn btn-primary btn-block" style="margin-bottom:10px;">Salvar Alterações</button>
            <button type="button" onclick="closeEditForm()" class="btn btn-ghost btn-block">Cancelar</button>
        </form>
  </div>

</main>

<?php include __DIR__ . '/includes/admin_menu_script.php'; ?>

<script>
    // Função para ocultar a notificação após 5 segundos
    if (document.getElementById('notification')) {
      setTimeout(() => {
        const notification = document.getElementById('notification');
        notification.style.display = 'none';
      }, 5000);
    }

    // Função para preencher e mostrar o formulário de edição
    function editServer(index, name, address) {
      document.getElementById('server_index').value = index;
      document.getElementById('edit_server_name').value = name;
      document.getElementById('edit_server_address').value = address;
      document.getElementById('editForm').classList.add('active');
      document.getElementById('modalOverlay').style.display = 'block'; // Exibir fundo escurecido
    }

    // Função para fechar o formulário de edição
    function closeEditForm() {
      document.getElementById('editForm').classList.remove('active');
      document.getElementById('modalOverlay').style.display = 'none'; // Ocultar fundo escurecido
    }
  </script>

</body>
</html>