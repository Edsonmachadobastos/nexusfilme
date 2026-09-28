<?php
session_start();

// Função para carregar as configurações do arquivo JSON
function loadConfig() {
    return json_decode(file_get_contents('config.json'), true);
}

// Carregar configurações
$config = loadConfig();

// Processo de autenticação do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    // Verifica se as credenciais estão corretas
    if ($username === $config['usuario'] && $password === $config['senha']) {
        $_SESSION["username"] = $username;
        header('Location: dashboard.php'); // Redireciona para o dashboard
        exit;
    } else {
        $error_message = 'Usuário ou senha inválidos!';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Painel Administrativo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --bg:          #050810;
            --surface:     rgba(15, 21, 38, 0.82);
            --border:      #1a2440;
            --accent:      #2f6fed;
            --accent2:     #1a4fd6;
            --accent-light:#5b8def;
            --accent-glow: rgba(47,111,237,.4);
            --input-bg:    #0f1526;
            --input-border:#1a2440;
            --text:        #eef2fb;
            --dim:         #92a0c4;
            --faint:       #576088;
            --red:         #f87171;
            --radius:      22px;
        }
        * { box-sizing: border-box; }
        html, body { height: 100%; margin: 0; padding: 0; }
        body {
            min-height: 100vh;
            background: var(--bg);
            background-image:
                radial-gradient(900px 520px at 12% 8%,  rgba(47,111,237,.20), transparent 55%),
                radial-gradient(760px 480px at 92% 92%, rgba(34,211,238,.08), transparent 50%),
                radial-gradient(700px 500px at 50% 50%, rgba(26,79,214,.10), transparent 60%);
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            color: var(--text);
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 24px;
        }
        .login-container {
            position: relative;
            z-index: 1;
            background: var(--surface);
            backdrop-filter: blur(22px) saturate(160%);
            -webkit-backdrop-filter: blur(22px) saturate(160%);
            box-shadow: 0 30px 80px -20px rgba(0,0,0,.6), 0 0 0 1px rgba(47,111,237,.08);
            border-radius: var(--radius);
            padding: 48px 42px 40px;
            width: 420px;
            max-width: 100%;
            text-align: center;
            border: 1px solid var(--border);
            animation: fadeIn .7s cubic-bezier(.2,.8,.2,1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(18px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .logo-badge {
            width: 68px; height: 68px;
            margin: 0 auto 20px;
            border-radius: 18px;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            display: flex; align-items: center; justify-content: center;
            box-shadow: 0 14px 32px -10px var(--accent-glow);
        }
        .logo-badge img { width: 100%; height: 100%; object-fit: contain; border-radius: 18px; }
        .logo-badge i { font-size: 1.7rem; color: #fff; }
        .eyebrow {
            font-size: .72rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
            color: var(--accent-light); margin-bottom: 8px;
        }
        .login-title {
            font-size: 1.65rem;
            font-weight: 800;
            letter-spacing: -.02em;
            margin-bottom: 8px;
            color: var(--text);
        }
        .login-sub {
            font-size: .92rem;
            color: var(--faint);
            margin-bottom: 28px;
            font-weight: 400;
            line-height: 1.5;
        }
        .error {
            color: var(--red);
            background: rgba(248,113,113,.1);
            border: 1px solid rgba(248,113,113,.28);
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 18px;
            font-weight: 600;
            font-size: .875rem;
            animation: shake .22s 2;
        }
        @keyframes shake {
            0% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            50% { transform: translateX(5px); }
            75% { transform: translateX(-3px); }
            100% { transform: translateX(0); }
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 16px;
            text-align: left;
        }
        label.field-label {
            font-size: .78rem; font-weight: 600; color: var(--dim);
            margin-bottom: -8px; padding-left: 2px;
        }
        .input-wrap { position: relative; }
        .input-wrap i {
            position: absolute; left: 16px; top: 50%; transform: translateY(-50%);
            color: var(--faint); font-size: .9rem;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            background: var(--input-bg);
            border: 1px solid var(--input-border);
            border-radius: 12px;
            padding: 14px 16px 14px 42px;
            color: var(--text);
            font-size: .95rem;
            font-family: inherit;
            outline: none;
            transition: border-color .18s ease, box-shadow .18s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(47,111,237,.18);
        }
        input::placeholder { color: var(--faint); }
        button[type="submit"] {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: #fff;
            border: none;
            border-radius: 12px;
            padding: 15px 0;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: -.005em;
            box-shadow: 0 14px 30px -10px var(--accent-glow);
            cursor: pointer;
            transition: transform .18s ease, box-shadow .18s ease, filter .18s ease;
            margin-top: 6px;
        }
        button[type="submit"]:hover, button[type="submit"]:focus {
            transform: translateY(-2px);
            filter: brightness(1.08);
            box-shadow: 0 18px 38px -10px var(--accent-glow);
        }
        .foot-note {
            margin-top: 26px;
            font-size: .74rem;
            color: var(--faint);
        }
        @media (max-width: 480px) {
            .login-container { padding: 36px 26px 30px; border-radius: 18px; }
            .login-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-badge">
            <?php if (!empty($config['logo'])): ?>
                <img src="<?php echo htmlspecialchars($config['logo']); ?>" alt="Logo">
            <?php else: ?>
                <i class="fas fa-shield-halved"></i>
            <?php endif; ?>
        </div>
        <p class="eyebrow">Acesso restrito</p>
        <div class="login-title">Painel Administrativo</div>
        <div class="login-sub">Entre com suas credenciais para gerenciar o sistema</div>
        <?php if (isset($error_message)) { echo '<p class="error"><i class="fas fa-circle-exclamation"></i> ' . htmlspecialchars($error_message) . '</p>'; } ?>
        <form method="post" action="">
            <label class="field-label">Usuário</label>
            <div class="input-wrap">
                <i class="fas fa-user"></i>
                <input type="text" name="username" placeholder="Digite seu usuário" required autocomplete="username">
            </div>
            <label class="field-label">Senha</label>
            <div class="input-wrap">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Digite sua senha" required autocomplete="current-password">
            </div>
            <button type="submit"><i class="fas fa-arrow-right-to-bracket"></i> Entrar</button>
        </form>
        <p class="foot-note">Ambiente protegido &middot; acesso apenas para administradores</p>
    </div>
</body>
</html>
