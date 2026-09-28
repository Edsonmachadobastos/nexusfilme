<?php
include "session.php";

// Carrega logo do config.json
$_cfg_logo = '';
if (file_exists(__DIR__ . '/config.json')) {
    $_cfg = json_decode(file_get_contents(__DIR__ . '/config.json'), true);
    $_cfg_logo = $_cfg['logo'] ?? '';
}

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

function makeApiCall($url, $userAgent) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, $userAgent);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo 'Error de CURL: ' . curl_error($ch);
        curl_close($ch);
        return [];
    }

    curl_close($ch);

    if ($httpCode !== 200) {
        echo "Error: No se pudo obtener la informaciÃ³n de la API. CÃ³digo HTTP: " . $httpCode;
        return [];
    }

    return json_decode($response, true);
}

function getMovies($username, $password, $get_dns, $userAgent) {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
    return makeApiCall($url, $userAgent);
}

function getSeries($username, $password, $get_dns, $userAgent) {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
    return makeApiCall($url, $userAgent);
}

function searchContent($search_query, $username, $password, $get_dns, $userAgent) {
    $movie_results = [];
    $series_results = [];

    $movies_api = getMovies($username, $password, $get_dns, $userAgent);
    $series_api = getSeries($username, $password, $get_dns, $userAgent);

    foreach ($movies_api as $movie) {
        if (stripos($movie['name'], $search_query) !== false) {
            $movie_results[] = [
                'title' => htmlspecialchars($movie['name']),
                'id' => htmlspecialchars($movie['stream_id']),
                'poster' => filter_var($movie['stream_icon'], FILTER_VALIDATE_URL) ? $movie['stream_icon'] : "https://i.imgur.com/Mn7aXQD.jpg"
            ];
        }
    }

    foreach ($series_api as $series) {
        if (stripos($series['name'], $search_query) !== false) {
            $series_results[] = [
                'title' => htmlspecialchars($series['name']),
                'id' => htmlspecialchars($series['series_id']),
                'poster' => filter_var($series['cover'], FILTER_VALIDATE_URL) ? $series['cover'] : "https://i.imgur.com/Mn7aXQD.jpg"
            ];
        }
    }

    return ['movies' => $movie_results, 'series' => $series_results];
}

$search_query = '';
$search_results = [];

if (isset($_GET['q']) && !empty($_GET['q'])) {
    $search_query = $_GET['q'];
    $search_results = searchContent($search_query, $username, $password, $get_dns, $userAgent);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WEBPLAYER</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --header-bg: linear-gradient(90deg, #0a1e8f 0%, #141b29 60%, #1a2233 100%);
            --header-glass: rgba(20,27,41,0.75);
            --accent: #00a8e1;
            --cta: #ff0057;
            --radius: 20px;
            --shadow: 0 4px 24px rgba(0,0,0,0.18);
            --header-height: 70px;
        }
        * {
            box-sizing: border-box;
        }
        body {
            background: #141b29;
            color: #fff;
            font-family: 'Roboto', 'Segoe UI', Arial, sans-serif;
            margin: 0;
        }
        .navbar {
            position: sticky;
            top: 0;
            width: 100%;
            height: var(--header-height);
            background: var(--header-bg);
            box-shadow: var(--shadow);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 32px;
            z-index: 1000;
            border-radius: 0 0 var(--radius) var(--radius);
            backdrop-filter: blur(12px) saturate(1.4);
            transition: background 0.4s, box-shadow 0.4s, padding 0.4s;
            animation: fadeInDown 0.7s;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-40px);}
            to { opacity: 1; transform: translateY(0);}
        }
        .navbar .logo {
            flex-shrink: 0;
        }
        .navbar .logo img {
            height: 48px;
            filter: drop-shadow(0 2px 8px #0007);
            transition: transform 0.2s;
        }
        .navbar .logo img:hover {
            transform: scale(1.1) rotate(-2deg);
        }
        .navbar .nav-links {
            display: flex;
            align-items: center;
            gap: 22px;
            margin-left: 28px;
            flex: 1 1 auto;
            min-width: 0;
            overflow-x: auto;
            scrollbar-width: none;
        }
        .navbar .nav-links::-webkit-scrollbar {
            display: none;
        }
        .navbar .nav-links a {
            white-space: nowrap;
        }
        .navbar .nav-links a {
            color: #fff;
            font-size: 1.1rem;
            font-weight: 500;
            text-decoration: none;
            padding: 10px 18px;
            border-radius: 8px;
            transition: background 0.18s, color 0.18s, box-shadow 0.18s;
            position: relative;
            letter-spacing: 0.01em;
            background: none;
        }
        .navbar .nav-links a:hover, .navbar .nav-links a:focus {
            background: rgba(0,168,225,0.10);
            color: var(--accent);
            box-shadow: 0 2px 12px rgba(0,168,225,0.09);
        }
        .navbar .nav-links a.cta {
            background: var(--cta);
            color: #fff;
            font-weight: bold;
            box-shadow: 0 4px 24px rgba(255,0,87,0.16);
            margin-left: 10px;
            transition: background 0.2s, box-shadow 0.2s;
        }
        .navbar .nav-links a.cta:hover {
            background: #fff;
            color: var(--cta);
            box-shadow: 0 6px 32px rgba(255,0,87,0.22);
        }
        .navbar .search-area {
            display: flex;
            align-items: center;
            position: relative;
            margin-left: 24px;
            flex-shrink: 0;
        }
        .navbar .search-icon {
            font-size: 1.25rem;
            color: #fff;
            background: none;
            border: none;
            cursor: pointer;
            padding: 10px;
            border-radius: 50%;
            transition: background 0.2s;
        }
        .navbar .search-icon:hover {
            background: rgba(0,168,225,0.12);
        }
        .navbar .search-container {
            display: none;
            position: absolute;
            top: 110%;
            right: 0;
            background: var(--header-glass);
            border-radius: 14px;
            box-shadow: var(--shadow);
            padding: 14px;
            z-index: 2000;
            animation: fadeInDown 0.35s;
        }
        .navbar .search-container input {
            padding: 10px 18px;
            font-size: 1rem;
            border-radius: 20px;
            border: none;
            background: #232b3e;
            color: #fff;
            width: 190px;
            outline: none;
            transition: width 0.3s;
        }
        .navbar .search-container input:focus {
            width: 250px;
            background: #2c3650;
        }
        .navbar .user-area {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: 24px;
            position: relative;
            flex-shrink: 0;
        }
        .navbar .avatar {
            width: 42px; height: 42px;
            border-radius: 50%;
            background: #00a8e1;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            overflow: hidden;
            box-shadow: 0 2px 8px #0005;
            border: 2px solid #fff2;
            transition: box-shadow 0.2s;
            color: #fff;
            font-size: 1.15rem;
            font-weight: 800;
            letter-spacing: 0;
        }
        .navbar .avatar:hover { box-shadow: 0 6px 24px #00a8e1aa; }
        .navbar .dropdown-menu {
            display: none;
            position: absolute;
            right: 0; top: 110%;
            background: var(--header-glass);
            border-radius: 12px;
            box-shadow: var(--shadow);
            min-width: 180px;
            padding: 10px 0;
            animation: fadeInDown 0.25s;
        }
        .navbar .dropdown-menu.open { display: block; }
        .navbar .dropdown-menu a {
            display: flex; align-items: center; gap: 10px;
            color: #fff; text-decoration: none;
            padding: 10px 24px;
            font-size: 1rem;
            transition: background 0.18s, color 0.18s;
        }
        .navbar .dropdown-menu a:hover {
            background: rgba(0,168,225,0.09);
            color: var(--accent);
        }
        .navbar .hamburger {
            display: none;
            flex-direction: column;
            gap: 5px;
            cursor: pointer;
            margin-left: 16px;
            background: none;
            border: none;
            z-index: 1101;
        }
        .navbar .hamburger span {
            width: 32px;
            height: 4px;
            background: #fff;
            border-radius: 2px;
            transition: all 0.3s;
        }
        .mobile-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(20,27,41,0.93);
            z-index: 1200;
            transition: opacity 0.3s;
        }
        .mobile-overlay.open { display: block; opacity: 1; }
        .mobile-menu {
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0; right: 0;
            height: 100vh;
            width: 85vw;
            max-width: 340px;
            background: var(--header-bg);
            box-shadow: -2px 0 40px rgba(10,30,143,0.16);
            transform: translateX(100%);
            transition: transform 0.3s cubic-bezier(.4,0,.2,1);
            z-index: 1300;
            padding: 48px 24px 24px 24px;
            border-radius: 0 0 0 var(--radius);
            backdrop-filter: blur(12px) saturate(1.2);
        }
        .mobile-menu.open { transform: translateX(0); }
        .close-mobile {
            background: none; border: none; color: #fff; font-size: 2rem; align-self: flex-end; margin-bottom: 24px; cursor: pointer;
        }
        .mobile-menu a {
            color: #fff;
            font-size: 1.2rem;
            padding: 16px 0;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: background 0.2s, color 0.2s;
        }
        .mobile-menu a.cta {
            background: var(--cta);
            color: #fff;
            border-radius: 8px;
            margin: 18px 0;
            justify-content: center;
            font-weight: bold;
            box-shadow: 0 4px 24px rgba(255,0,87,0.16);
        }
        .mobile-menu a:hover {
            background: rgba(0,168,225,0.08);
            color: #00a8e1;
        }
        @media (max-width: 1100px) {
            .navbar { padding: 0 10px; }
            .navbar .nav-links { gap: 10px; margin-left: 8px; }
        }
        @media (max-width: 900px) {
            .navbar .nav-links,
            .navbar .search-area,
            .navbar .user-area {
                display: none;
            }
            .navbar .hamburger {
                display: flex;
            }
        }
        @media (max-width: 550px) {
            .navbar { height: 56px; padding: 0 2px; }
            .mobile-menu { width: 100vw; max-width: none; padding: 24px 8px; }
        }
        .main-content { padding-top: 90px; }
        .search-results {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px;
            margin-top: 10px;
        }
        .result-item {
            background-color: #1f2a44;
            border-radius: 10px;
            text-align: center;
            color: #fff;
            box-shadow: 0 2px 8px rgba(0,0,0,0.14);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .result-item:hover {
            transform: translateY(-4px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0,168,225,0.14);
        }
        .result-item img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }
        .result-item p {
            font-size: 1.1rem;
            margin: 12px 0 0 0;
        }
        .section-title {
            font-size: 28px;
            text-align: center;
            color: var(--accent);
            margin-top: 20px;
        }
        .navbar .nav-links a {
            position: relative;
            overflow: hidden;
        }
        .navbar .nav-links a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, #00a8e1, #1a2233);
            opacity: 0.3;
            z-index: -1;
            transition: opacity 0.3s;
        }
        .navbar .nav-links a:hover::before {
            opacity: 0.5;
        }
        .navbar .nav-links a i {
            color: white; /* Define a cor do Ã­cone */
        }
    </style>
    <link rel="stylesheet" href="assets/css/netflix-theme.css">
</head>
<body oncontextmenu="return false;">
<script>
document.addEventListener('selectstart', function(e){ e.preventDefault(); });
</script>

<!-- HEADER -->
<div class="navbar" id="navbar">
    <div class="logo">
        <a href="homex.php">
            <?php if (!empty($_cfg_logo)): ?><img src="<?php echo htmlspecialchars($_cfg_logo); ?>" alt="Logo"><?php else: ?><img src="assets/img/logo.png" alt="Logo"><?php endif; ?>
        </a>
    </div>
    <!-- MENU DESKTOP -->
    <div class="nav-links">
        <a href="homex.php"><i class="fas fa-home"></i> Início</a>
        <a href="moviesondemand.php"><i class="fas fa-film"></i> Filmes</a>
        <a href="seriesondemand.php"><i class="fas fa-tv"></i> Séries</a>
        <a href="tvguide.php"><i class="fas fa-broadcast-tower"></i> TV ao Vivo</a>
        <a href="sportsschedule.php"><i class="fas fa-futbol"></i> Jogos do Dia</a>
        <a href="painel.php"><i class="fas fa-bookmark"></i> Minha Lista</a>
        <a href="renovar.php"><i class="fas fa-rotate"></i> Renovar</a>
        <a href="premium.php" class="cta"><i class="fas fa-bolt"></i> Assine Já</a>
    </div>
    <!-- PESQUISA DESKTOP -->
    <div class="search-area">
        <button class="search-icon" id="search-icon" aria-label="Abrir busca">
            <i class="fas fa-search"></i>
        </button>
        <div class="search-container" id="search-container">
            <form id="search-form" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="GET">
                <input type="text" placeholder="Buscar..." name="q" value="<?php echo htmlspecialchars($search_query); ?>" required autocomplete="off">
            </form>
        </div>
    </div>
    <!-- AVATAR USUÃRIO -->
    <div class="user-area">
        <div class="avatar" id="avatar" title="Perfil">P</div>
        <div class="dropdown-menu" id="dropdown-menu">
            <a href="myaccount.php"><i class="fas fa-user-circle"></i> Minha Conta</a>
            <a href="settings.php"><i class="fas fa-cog"></i> Configurações</a>
            <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
        </div>
    </div>
    <!-- HAMBURGUER MOBILE -->
    <button class="hamburger" id="hamburger" aria-label="Abrir menu">
        <span></span>
        <span></span>
        <span></span>
    </button>
</div>

<!-- Overlay e Menu Mobile -->
<div class="mobile-overlay" id="mobile-overlay"></div>
<nav class="mobile-menu" id="mobile-menu">
    <button class="close-mobile" id="close-mobile" aria-label="Fechar menu">&times;</button>
    <a href="homex.php"><i class="fas fa-home"></i> Início</a>
    <a href="moviesondemand.php"><i class="fas fa-film"></i> Filmes</a>
    <a href="seriesondemand.php"><i class="fas fa-tv"></i> Séries</a>
    <a href="tvguide.php"><i class="fas fa-broadcast-tower"></i> TV ao Vivo</a>
    <a href="sportsschedule.php"><i class="fas fa-futbol"></i> Jogos do Dia</a>
    <a href="mylist.php"><i class="fas fa-bookmark"></i> Minha Lista</a>
    <a href="renovar.php"><i class="fas fa-rotate"></i> Renovar</a>
    <a href="premium.php" class="cta"><i class="fas fa-bolt"></i> Assine Já</a>
    <a href="myaccount.php"><i class="fas fa-user-circle"></i> Conta</a>
    <a href="settings.php"><i class="fas fa-cog"></i> Configurações</a>
    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Sair</a>
</nav>

<div class="main-content">
<?php if (!empty($search_query)): ?>
    <h1 class="section-title">Resultados para "<?php echo htmlspecialchars($search_query); ?>"</h1>
    <?php if (!empty($search_results['movies'])): ?>
        <h2 class="section-title">Filmes</h2>
        <div class="search-results">
            <?php foreach ($search_results['movies'] as $movie): ?>
                <div class="result-item">
                    <a href="Movie_description.php?id=<?php echo $movie['id']; ?>">
                        <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>">
                        <p><?php echo $movie['title']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($search_results['series'])): ?>
        <h2 class="section-title">SÃ©ries</h2>
        <div class="search-results">
            <?php foreach ($search_results['series'] as $series): ?>
                <div class="result-item">
                    <a href="seriesvideo.php?id=<?php echo $series['id']; ?>">
                        <img src="<?php echo $series['poster']; ?>" alt="<?php echo $series['title']; ?>">
                        <p><?php echo $series['title']; ?></p>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if (empty($search_results['movies']) && empty($search_results['series'])): ?>
        <p class="section-title">Nenhum resultado encontrado para "<?php echo htmlspecialchars($search_query); ?>"</p>
    <?php endif; ?>
<?php endif; ?>
</div>

<script>
    // Sticky header hide/show ao rolar
    let lastScroll = 0;
    const navbar = document.getElementById('navbar');
    window.addEventListener('scroll', () => {
        const curr = window.pageYOffset;
        if(curr > lastScroll && curr > 80) {
            navbar.style.transform = 'translateY(-100%)';
        } else {
            navbar.style.transform = 'translateY(0)';
        }
        lastScroll = curr;
    });

    // Barra de busca desktop
    const searchIcon = document.getElementById('search-icon');
    const searchContainer = document.getElementById('search-container');
    let searchOpen = false;
    searchIcon.addEventListener('click', function(e) {
        e.stopPropagation();
        searchContainer.style.display = searchOpen ? 'none' : 'block';
        searchOpen = !searchOpen;
        if (searchOpen) {
            setTimeout(() => {
                searchContainer.querySelector('input').focus();
            }, 100);
        }
    });
    document.addEventListener('click', function(e) {
        if (searchOpen && !searchContainer.contains(e.target) && e.target !== searchIcon) {
            searchContainer.style.display = 'none';
            searchOpen = false;
        }
    });

    // Avatar dropdown animado
    const avatar = document.getElementById('avatar');
    const dropdownMenu = document.getElementById('dropdown-menu');
    avatar.addEventListener('click', function(e) {
        e.stopPropagation();
        dropdownMenu.classList.toggle('open');
    });
    document.addEventListener('click', function(e) {
        if(dropdownMenu.classList.contains('open')) dropdownMenu.classList.remove('open');
    });

    // Menu mobile
    const hamburger = document.getElementById('hamburger');
    const mobileMenu = document.getElementById('mobile-menu');
    const mobileOverlay = document.getElementById('mobile-overlay');
    const closeMobile = document.getElementById('close-mobile');
    hamburger.addEventListener('click', () => {
        mobileMenu.classList.add('open');
        mobileOverlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    });
    closeMobile.addEventListener('click', closeMenu);
    mobileOverlay.addEventListener('click', closeMenu);

    function closeMenu() {
        mobileMenu.classList.remove('open');
        mobileOverlay.classList.remove('open');
        document.body.style.overflow = '';
    }
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeMenu();
    });
</script>
<script>
    (function(){
        var nav = document.querySelector('.navbar');
        if (!nav) return;
        function onScroll(){
            if (window.scrollY > 40) nav.classList.add('nf-scrolled');
            else nav.classList.remove('nf-scrolled');
        }
        window.addEventListener('scroll', onScroll, { passive: true });
        onScroll();
    })();
</script>

</body>
</html>