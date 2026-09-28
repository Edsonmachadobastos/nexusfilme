<?php
session_start();

// Include config file
require_once('config.php');

// Carregar configuração
$config = json_decode(file_get_contents('config.json'), true);


// Load DNS configuration
$dns_data = json_decode(file_get_contents("./includes/dns/dns.json"), true);
$available_servers = [];

// Collect available servers
for ($i = 1; $i <= 1000; $i++) {
    if (!empty($dns_data["server{$i}"]) && !empty($dns_data["server{$i}_name"])) {
        $available_servers[] = [
            'name' => $dns_data["server{$i}_name"],
            'url' => $dns_data["server{$i}"]
        ];
    }
}

// Processo de autenticação do usuário
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST["username"]) && isset($_POST["password"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $auth_success = false;
    $error_message = '';

    foreach ($available_servers as $server) {
        $server_url = $server['url'];
        // urlencode() é essencial aqui: se o usuário ou a senha tiverem caracteres
        // especiais (@, +, %, espaço, &, etc. — comuns em senha de Xtream), sem isso a
        // URL fica corrompida e o servidor recebe um usuário/senha diferente do que foi
        // digitado, dando "credenciais incorretas" mesmo quando elas estão certas.
        $authUrl = rtrim($server_url, '/') . "/player_api.php?username=" . urlencode($username) . "&password=" . urlencode($password);
        $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';
        // cURL em vez de file_get_contents: em boa parte das hospedagens
        // compartilhadas o allow_url_fopen vem desligado por padrão, e nesse
        // caso file_get_contents falha sempre (mesmo com o servidor
        // funcionando perfeitamente), aparecendo como "erro de conexão" pra
        // todo mundo. cURL não depende dessa configuração.
        $ch = curl_init($authUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["User-Agent: $userAgent"],
            CURLOPT_TIMEOUT => 8,
            CURLOPT_CONNECTTIMEOUT => 8,
            // Muitos provedores de Xtream/IPTV menores usam certificado
            // autoassinado ou vencido — sem isso, qualquer servidor HTTPS
            // "não perfeito" derrubava a conexão com "erro de conexão".
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
            CURLOPT_FOLLOWLOCATION => true,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === FALSE) {
            $error_message = 'Erro de conexão com o servidor: ' . htmlspecialchars($server['name']);
            continue; // Tentar o próximo servidor se houver erro
        }

        $api = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($api)) {
            // O servidor respondeu, mas não devolveu um JSON válido (comum quando a
            // URL está mal formada, o player_api.php não existe naquele caminho, ou o
            // servidor devolveu uma página de erro em HTML). Mostramos um pedaço da
            // resposta bruta pra ajudar a identificar a causa exata nesse servidor.
            $preview = trim(preg_replace('/\s+/', ' ', substr(strip_tags($response), 0, 160)));
            $error_message = 'Resposta inválida do servidor: ' . htmlspecialchars($server['name'])
                . ($preview !== '' ? ' — o servidor respondeu: "' . htmlspecialchars($preview) . '..."' : ' — resposta vazia.');
            continue;
        }

        $authOk = isset($api['user_info']['auth']) && (int) $api['user_info']['auth'] === 1;
        // Comparação de status sem diferenciar maiúsculas/minúsculas: servidores
        // Xtream diferentes retornam "Active", "active", "ACTIVE" etc. — antes, a
        // comparação exata (=== "Active") rejeitava contas válidas só por causa disso.
        $statusOk = isset($api['user_info']['status']) && strcasecmp((string) $api['user_info']['status'], 'Active') === 0;

        if ($authOk && $statusOk) {
            $_SESSION["username"] = $username;
            $_SESSION["password"] = $password;
            $_SESSION["server_url"] = $server_url;

            $auth_success = true;
            echo json_encode(['success' => true, 'redirect' => 'homex.php']);
            break; // Saia do loop se a autenticação for bem-sucedida
        } else {
            $error_message = 'Credenciais incorretas ou conta inativa para ' . htmlspecialchars($server['name']);
        }
    }

    if (!$auth_success) {
        echo json_encode(['success' => false, 'message' => $error_message]);
    }
    exit;
}

// Logo e imagem de fundo, ambas editáveis em admin.php -> Configurações
$logoPath = !empty($config['logo']) ? $config['logo'] : '';
$bgPath = (!empty($config['background']) && file_exists($config['background'])) ? $config['background'] : '';
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="theme-color" content="#0a0508">
    <title>WebPlay - GLOBAL WEB</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --brand: #e50914;
            --brand-2: #ff3b3b;
            --panel: rgba(13, 12, 17, .82);
            --panel-border: rgba(255, 255, 255, .1);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, .66);
            --text-muted: rgba(255, 255, 255, .42);
            --error-color: #ff5c5c;
            --radius: 18px;
            --transition: all .22s cubic-bezier(.25, .46, .45, .94);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        html, body { height: 100%; }
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            color: var(--text-primary);
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
            overflow-x: hidden;
        }

        /* ===== Fundo (editável em admin.php) ===== */
        .backdrop { position: fixed; inset: 0; z-index: 0; overflow: hidden; background: #0a0508; }
        <?php if ($bgPath): ?>
        .backdrop-img {
            position: absolute; inset: 0;
            background-image: url('<?= htmlspecialchars($bgPath) ?>');
            background-size: cover;
            background-position: center;
        }
        <?php else: ?>
        /* Fundo padrão (usado até o admin enviar uma imagem em Configurações) */
        .backdrop-img {
            position: absolute; inset: 0;
            background:
                radial-gradient(1100px 700px at 18% 82%, rgba(229,9,20,.16), transparent 60%),
                radial-gradient(900px 600px at 85% 20%, rgba(229,9,20,.10), transparent 55%),
                linear-gradient(160deg, #14090c 0%, #0a0508 55%, #0a0d14 100%);
        }
        <?php endif; ?>
        .backdrop-scrim {
            position: absolute; inset: 0;
            background:
                linear-gradient(90deg, rgba(4,3,6,.88) 0%, rgba(4,3,6,.55) 30%, rgba(4,3,6,.25) 52%, rgba(4,3,6,.6) 76%, rgba(4,3,6,.9) 100%),
                linear-gradient(180deg, rgba(4,3,6,.5) 0%, rgba(4,3,6,.1) 20%, rgba(4,3,6,.1) 60%, rgba(4,3,6,.75) 100%);
        }

        /* ===== Palco central ===== */
        .stage {
            position: relative; z-index: 5;
            min-height: 100vh;
            display: flex; align-items: center; justify-content: center;
            padding: 46px 24px 40px;
        }

        .stage-grid {
            width: 100%; max-width: 1180px;
            display: grid; grid-template-columns: 1.05fr .95fr;
            align-items: center; gap: 64px;
        }

        /* ===== Painel de informações (lado esquerdo) ===== */
        .info-panel { color: #fff; padding-right: 8px; }
        .info-panel .brand-row { display: flex; flex-direction: row; align-items: center; margin-bottom: 30px; text-align: left; }
        .info-panel .brand-row img { height: 62px; width: auto; margin: 0 14px 0 0; filter: drop-shadow(0 10px 26px rgba(229,9,20,.35)); }
        .info-panel .wordmark { font-size: 26px; font-weight: 800; letter-spacing: .01em; line-height: 1; }
        .info-panel .wordmark .play { color: var(--brand-2); }
        .info-panel .tagline { margin-top: 6px; font-size: 11.5px; font-weight: 600; letter-spacing: .24em; color: var(--text-muted); text-transform: uppercase; }

        .info-headline { font-size: 40px; font-weight: 800; line-height: 1.16; margin-bottom: 16px; letter-spacing: -.01em; }
        .info-headline .accent { color: var(--brand-2); }
        .info-bar { width: 46px; height: 4px; border-radius: 99px; background: var(--brand); margin-bottom: 18px; }
        .info-sub { font-size: 15.5px; color: var(--text-secondary); line-height: 1.6; max-width: 440px; margin-bottom: 36px; }

        .info-features { list-style: none; display: grid; gap: 18px; }
        .info-features li { display: flex; align-items: flex-start; gap: 15px; }
        .info-features .feat-ico {
            width: 44px; height: 44px; flex: none; border-radius: 13px;
            background: rgba(229,9,20,.14); border: 1px solid rgba(229,9,20,.3);
            display: flex; align-items: center; justify-content: center;
            color: var(--brand-2); font-size: 16px;
        }
        .info-features strong { display: block; font-size: 14.5px; font-weight: 700; margin-bottom: 2px; }
        .info-features span { font-size: 13px; color: var(--text-secondary); line-height: 1.4; }

        /* ===== Card de login ===== */
        .login-card {
            width: 100%; max-width: 460px; margin: 0 auto;
            background: var(--panel);
            backdrop-filter: blur(20px) saturate(140%);
            border: 1px solid var(--panel-border);
            border-radius: var(--radius);
            padding: 38px 40px 32px;
            box-shadow: 0 34px 90px -24px rgba(0,0,0,.75);
        }

        .card-logo { display: flex; justify-content: center; margin-bottom: 18px; }
        .card-logo img { height: 76px; width: auto; filter: drop-shadow(0 10px 26px rgba(229,9,20,.35)); }

        .card-head { text-align: center; margin-bottom: 20px; }
        .card-head h1 { font-size: 21px; font-weight: 700; margin-bottom: 4px; }
        .card-head p { font-size: 13px; color: var(--text-secondary); }

        .input-field { position: relative; margin-bottom: 13px; }
        .field-input {
            width: 100%;
            padding: 15px 44px 15px 44px;
            background: rgba(255,255,255,.05);
            border: 1.5px solid rgba(255,255,255,.1);
            border-radius: 11px;
            color: var(--text-primary);
            font-size: 14.5px; font-weight: 500;
            transition: var(--transition);
        }
        .field-input:focus {
            outline: none;
            background: rgba(255,255,255,.08);
            border-color: var(--brand-2);
            box-shadow: 0 0 0 3px rgba(229,9,20,.16);
        }
        .field-input::placeholder { color: rgba(255,255,255,.38); font-weight: 400; }

        .field-icon {
            position: absolute; left: 15px; top: 50%; transform: translateY(-50%);
            color: rgba(255,255,255,.42); font-size: 14px; pointer-events: none; transition: var(--transition);
        }
        .field-input:focus ~ .field-icon { color: var(--brand-2); }

        .toggle-eye {
            position: absolute; right: 11px; top: 50%; transform: translateY(-50%);
            width: 28px; height: 28px; border: 0; background: none; cursor: pointer;
            display: flex; align-items: center; justify-content: center;
            color: rgba(255,255,255,.4); border-radius: 8px; font-size: 13.5px;
        }
        .toggle-eye:hover { color: #fff; }

        .quantum-button {
            width: 100%; padding: 14.5px;
            background: linear-gradient(135deg, var(--brand) 0%, #b8070f 100%);
            border: none; border-radius: 11px; color: #fff;
            font-size: 15px; font-weight: 700; font-family: inherit;
            cursor: pointer; margin-top: 5px;
            transition: var(--transition);
            display: flex; align-items: center; justify-content: center; gap: 9px;
            box-shadow: 0 16px 34px -14px rgba(229,9,20,.75);
        }
        .quantum-button:hover { filter: brightness(1.08); transform: translateY(-1px); }
        .quantum-button:disabled { opacity: .75; cursor: not-allowed; transform: none; }

        .cyber-spinner {
            width: 17px; height: 17px; border: 2.5px solid rgba(255,255,255,.35);
            border-top-color: #fff; border-radius: 50%;
            animation: spin .8s linear infinite; display: none;
        }
        .quantum-button.loading .cyber-spinner { display: inline-block; }
        .quantum-button.loading .button-text { opacity: .85; }
        @keyframes spin { to { transform: rotate(360deg); } }

        .error-portal {
            background: rgba(255, 92, 92, .1);
            border: 1px solid rgba(255, 92, 92, .3);
            border-radius: 10px;
            padding: 11px 15px;
            color: var(--error-color);
            font-size: 13px; font-weight: 500;
            margin-top: 14px;
            display: none;
            animation: errorSlide .3s ease-out;
        }
        @keyframes errorSlide { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }

        /* ===== Acesso rápido ===== */
        .quick-divider { display: flex; align-items: center; gap: 12px; margin: 22px 0 16px; }
        .quick-divider .line { flex: 1; height: 1px; background: rgba(255,255,255,.14); }
        .quick-divider span { font-size: 11.5px; font-weight: 700; letter-spacing: .06em; color: var(--text-muted); text-transform: uppercase; white-space: nowrap; }

        .quick-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
        .quick-item { display: flex; flex-direction: column; align-items: center; text-align: center; gap: 7px; }
        .quick-item i { color: var(--brand-2); font-size: 17px; }
        .quick-item span { font-size: 11px; font-weight: 600; color: var(--text-secondary); line-height: 1.3; }

        .card-foot { margin-top: 18px; font-size: 12.5px; color: var(--text-muted); text-align: center; line-height: 1.5; }

        @media (max-width: 1100px) {
            .stage-grid { grid-template-columns: 1fr; max-width: 520px; gap: 0; }
            .info-panel { display: none; }
        }
        @media (max-width: 640px) {
            .login-card { padding: 26px 22px 22px; }
            .card-logo img { height: 58px; }
            .quick-item span { font-size: 10px; }
        }
    </style>
</head>
<body>
    <div class="backdrop">
        <div class="backdrop-img"></div>
        <div class="backdrop-scrim"></div>
    </div>

    <main class="stage">
        <div class="stage-grid">
            <section class="info-panel">
                <div class="brand-row">
                    <?php if ($logoPath): ?><img src="<?php echo htmlspecialchars($logoPath); ?>" alt="WebPlay"><?php endif; ?>
                    <div>
                        <div class="wordmark">WEB<span class="play">PLAY</span></div>
                        <p class="tagline">Seu entretenimento sem limites</p>
                    </div>
                </div>

                <h2 class="info-headline">Filmes, séries e<br><span class="accent">canais ao vivo</span></h2>
                <div class="info-bar"></div>
                <p class="info-sub">Tudo o que você ama, em um só lugar. Entre com sua conta e aproveite o melhor do streaming, sem limites.</p>

                <ul class="info-features">
                    <li>
                        <span class="feat-ico"><i class="fas fa-bolt"></i></span>
                        <div><strong>Qualidade HD e 4K</strong><span>Imagem nítida em qualquer conteúdo</span></div>
                    </li>
                    <li>
                        <span class="feat-ico"><i class="fas fa-tv"></i></span>
                        <div><strong>Em todos os seus dispositivos</strong><span>TV, celular, tablet e computador</span></div>
                    </li>
                    <li>
                        <span class="feat-ico"><i class="fas fa-shield-halved"></i></span>
                        <div><strong>Seguro e confiável</strong><span>Sua conta sempre protegida</span></div>
                    </li>
                    <li>
                        <span class="feat-ico"><i class="fas fa-clapperboard"></i></span>
                        <div><strong>Catálogo completo</strong><span>Séries, filmes, canais e muito mais</span></div>
                    </li>
                </ul>
            </section>

            <section class="login-card">
            <?php if ($logoPath): ?>
            <div class="card-logo">
                <img src="<?php echo htmlspecialchars($logoPath); ?>" alt="WebPlay">
            </div>
            <?php endif; ?>
            <div class="card-head">
                <h1>Entrar na sua conta</h1>
                <p>Acesse agora e continue de onde parou.</p>
            </div>

            <form id="quantum-form" class="quantum-form" method="POST">
                <div class="input-field">
                    <i class="fas fa-user field-icon"></i>
                    <input type="text" class="field-input" placeholder="Usuário" name="username" required autocomplete="username" spellcheck="false">
                </div>

                <div class="input-field">
                    <i class="fas fa-lock field-icon"></i>
                    <input type="password" class="field-input" id="pass-input" placeholder="Senha" name="password" required autocomplete="current-password">
                    <button type="button" class="toggle-eye" id="toggle-pass" aria-label="Mostrar senha">
                        <i class="fas fa-eye-slash"></i>
                    </button>
                </div>

                <button type="submit" class="quantum-button" id="access-button">
                    <span class="cyber-spinner"></span>
                    <span class="button-text">Entrar</span>
                    <i class="fas fa-arrow-right"></i>
                </button>

                <div class="error-portal" id="error-display"></div>
            </form>

            <p class="card-foot">Ainda não tem acesso? Entre em contato com seu revendedor.</p>
            </section>
        </div>
    </main>

    <script>
    // Alternar visibilidade da senha
    document.getElementById('toggle-pass').addEventListener('click', function () {
        const input = document.getElementById('pass-input');
        const icon = this.querySelector('i');
        const show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        icon.classList.toggle('fa-eye-slash', !show);
        icon.classList.toggle('fa-eye', show);
    });

    document.getElementById('quantum-form').addEventListener('submit', async function (event) {
        event.preventDefault();

        const button = document.getElementById('access-button');
        const errorDisplay = document.getElementById('error-display');
        errorDisplay.style.display = 'none';
        button.classList.add('loading');
        button.disabled = true;

        try {
            const formData = new FormData(this);
            const response = await fetch('', {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await response.json();

            if (data.success) {
                window.location.href = data.redirect;
                return; // mantém o loading até a navegação acontecer
            }

            errorDisplay.textContent = data.message;
            errorDisplay.style.display = 'block';
        } catch (err) {
            errorDisplay.textContent = 'Não foi possível conectar ao servidor. Tente novamente.';
            errorDisplay.style.display = 'block';
        } finally {
            button.classList.remove('loading');
            button.disabled = false;
        }
    });
    </script>
</body>
</html>