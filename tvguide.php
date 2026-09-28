<?php
include "session.php";
$is_ajax_category = isset($_GET['category_id']) && !isset($_GET['slug']);
$is_ajax_play = isset($_GET['ajax_play']);
if (!$is_ajax_category && !$is_ajax_play) {
    include "header.php";
}
include "config.php";

// ... Funções utilitárias e lógica original ...
function getXtreamData($endpoint, $params = []) {
    global $get_dns, $username, $password;
    $url = $get_dns . "/player_api.php?username=" . urlencode($username) . "&password=" . urlencode($password) . "&action=" . urlencode($endpoint);
    if (!empty($params)) $url .= '&' . http_build_query($params);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) { curl_close($ch); return []; }
    curl_close($ch);
    if ($httpCode !== 200) return [];
    $decoded = json_decode($response, true);
    return is_array($decoded) ? $decoded : [];
}
function decodeBase64IfNeeded($data) {
    if (!is_string($data)) return '';
    if (base64_encode(base64_decode($data, true)) === $data) return base64_decode($data);
    return $data;
}
function formatEPGTime($timestamp) {
    if (is_numeric($timestamp)) {
        if ($timestamp > 10000000000) $timestamp = intval($timestamp / 1000);
        return date('H:i', $timestamp);
    } else {
        $time = strtotime($timestamp);
        if ($time === false) return '00:00';
        return date('H:i', $time);
    }
}

if (isset($_COOKIE['user_timezone'])) {
    $valid_timezones = timezone_identifiers_list();
    $user_timezone = $_COOKIE['user_timezone'];
    if (in_array($user_timezone, $valid_timezones)) date_default_timezone_set($user_timezone);
    else date_default_timezone_set('UTC');
} else {
    date_default_timezone_set('UTC');
    echo '
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                var timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;
                document.cookie = "user_timezone=" + timezone + "; path=/; max-age=" + (60*60*24*365);
                location.reload();
            });
        </script>
    ';
    exit();
}

$pagename = "TV Guide";
$logo_url = "assets/img/logo.png";
// Suporte a links antigos (?id=X&slug=live): o JS detecta isso e abre o
// player embutido automaticamente, sem navegar para outra página.
$deep_link_id = (isset($_GET["slug"]) && $_GET["slug"] == "live" && isset($_GET["id"])) ? $_GET["id"] : null;

// Requisição AJAX: devolve em JSON os dados para tocar um canal embutido na própria página
if ($is_ajax_play) {
    header('Content-Type: application/json; charset=utf-8');
    $play_id = $_GET['id'] ?? null;
    if (!$play_id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido.']);
        exit();
    }
    if (isset($_COOKIE["settings_array"])) {
        $SettingArray = json_decode($_COOKIE["settings_array"], true);
        $setting_ext = isset($SettingArray["stream_type"]) ? $SettingArray["stream_type"] : "m3u8";
    } else $setting_ext = "m3u8";
    $video_url = $get_dns . "/live/" . urlencode($username) . "/" . urlencode($password) . "/" . urlencode($play_id) . "." . $setting_ext;
    $mime_type = "application/x-mpegURL";
    $content_name = "Canal Desconhecido";
    $channel_info_list = getXtreamData('get_live_streams', ['stream_id' => $play_id]);
    if (!empty($channel_info_list)) {
        foreach ($channel_info_list as $channel_info) {
            if ($channel_info['stream_id'] == $play_id) {
                $content_name = $channel_info['name'];
                break;
            }
        }
    }
    $epg_info = getXtreamData('get_short_epg', ['stream_id' => $play_id, 'limit' => 10]);
    $epg_raw = $epg_info['epg_listings'] ?? [];
    usort($epg_raw, function($a, $b) { return strtotime($a['start']) - strtotime($b['start']); });
    $epg_out = [];
    foreach ($epg_raw as $p) {
        $epg_out[] = [
            'title' => decodeBase64IfNeeded($p['title']),
            'start' => formatEPGTime($p['start']),
            'end'   => formatEPGTime($p['end']),
        ];
    }
    echo json_encode([
        'success' => true,
        'content_name' => $content_name,
        'video_url' => $video_url,
        'mime_type' => $mime_type,
        'epg' => $epg_out,
    ]);
    exit();
}

// Requisição AJAX: devolve apenas o grid de canais de uma categoria
if ($is_ajax_category) {
    $category_id = $_GET['category_id'];
    $channels = getXtreamData('get_live_streams', ['category_id' => $category_id]);
    if (!empty($channels)) {
        foreach ($channels as $channel) {
            $stream_id = htmlspecialchars($channel["stream_id"], ENT_QUOTES, 'UTF-8');
            $title = htmlspecialchars($channel["name"], ENT_QUOTES, 'UTF-8');
            $desc_image = htmlspecialchars($channel["stream_icon"], ENT_QUOTES, 'UTF-8');
            echo "<div class='channel-card' onclick='playChannel({$stream_id})'>
                    <div class='channel-card-thumb'>
                        <img src='{$desc_image}' onerror=\"this.onerror=null;this.src='assets/img/logo.png';\" alt='{$title}'/>
                        <span class='live-tag'>AO VIVO</span>
                    </div>
                    <p>{$title}</p>
                  </div>";
        }
    } else {
        echo "<p class='empty-msg'>Nenhum canal encontrado nesta categoria.</p>";
    }
    exit();
}

// Dados usados na tela de navegação (destaques + guia + categorias)
$channel_api_categories = [];
$hero_channel = null;
$hero_category_name = '';
$hero_epg_title = '';
$hero_epg_time = '';
$featured_channels = [];
$guide_channels = [];

$channel_api_categories = getXtreamData('get_live_categories');

if (!empty($channel_api_categories)) {
    foreach ($channel_api_categories as $cat) {
        $chs = getXtreamData('get_live_streams', ['category_id' => $cat['category_id']]);
        if (!empty($chs)) {
            $featured_channels = $chs;
            $hero_category_name = $cat['category_name'];
            break;
        }
    }
}

$hero_channel = $featured_channels[0] ?? null;
if ($hero_channel) {
    $hero_epg = getXtreamData('get_short_epg', ['stream_id' => $hero_channel['stream_id'], 'limit' => 1]);
    $hero_listings = $hero_epg['epg_listings'] ?? [];
    if (!empty($hero_listings)) {
        $hero_epg_title = trim(decodeBase64IfNeeded($hero_listings[0]['title']));
        $hero_epg_time = formatEPGTime($hero_listings[0]['start']);
    }
}

// Até 4 canais em destaque ganham EPG detalhado para o Guia de Programas
$guide_source = array_slice($featured_channels, 0, 4);
foreach ($guide_source as $gc) {
    $epg = getXtreamData('get_short_epg', ['stream_id' => $gc['stream_id'], 'limit' => 4]);
    $listings = $epg['epg_listings'] ?? [];
    usort($listings, function($a, $b) { return strtotime($a['start']) - strtotime($b['start']); });
    $guide_channels[] = [
        'stream_id' => $gc['stream_id'],
        'name' => $gc['name'],
        'icon' => $gc['stream_icon'],
        'programs' => $listings,
    ];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8'); ?> - TV Guide</title>
    <link rel="icon" href="assets/img/favicon.ico" type="image/ico">
    <link href="https://vjs.zencdn.net/7.17.0/video-js.css" rel="stylesheet" />
    <script src="https://vjs.zencdn.net/7.17.0/video.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --tv-red: #e50914;
            --tv-red-2: #ff2a3a;
            --tv-black: #000000;
            --tv-border: rgba(255,255,255,0.08);
            --tv-text-dim: #9a9a9a;
        }
        html, body {
            box-sizing: border-box;
            min-height: 100%;
            margin: 0;
            padding: 0;
            background: var(--tv-black) !important;
            font-family: 'Montserrat', Arial, sans-serif;
            color: #f1f1f1;
            -webkit-user-select: none;
            -moz-user-select: none;
            -ms-user-select: none;
            user-select: none;
        }
        *, *::before, *::after { box-sizing: inherit; }
        a { text-decoration: none; }
        .tv-wrap {
            max-width: 1700px;
            margin: 0 auto;
            padding: 1.6rem 1.4rem 3rem;
        }
        /* ---------- HERO ---------- */
        .tv-hero {
            position: relative;
            border-radius: 18px;
            overflow: hidden;
            background: var(--tv-black);
            border: 1px solid var(--tv-border);
            min-height: 340px;
            display: flex;
            align-items: flex-end;
            margin-bottom: 2rem;
        }
        .tv-hero-bg {
            position: absolute;
            inset: 0;
            background-size: cover;
            background-position: center;
            filter: brightness(0.55) saturate(1.1);
        }
        .tv-hero::after {
            content: "";
            position: absolute;
            inset: 0;
            background: linear-gradient(90deg, rgba(0,0,0,0.92) 20%, rgba(0,0,0,0.35) 65%, rgba(0,0,0,0.05) 100%),
                        linear-gradient(0deg, rgba(0,0,0,0.95) 0%, rgba(0,0,0,0.1) 55%);
        }
        .tv-hero-content {
            position: relative;
            z-index: 2;
            padding: 2.2rem 2.2rem 2rem;
            max-width: 720px;
        }
        .tv-live-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--tv-red);
            color: #fff;
            font-size: 0.78rem;
            font-weight: 800;
            letter-spacing: 0.06em;
            padding: 5px 12px;
            border-radius: 5px;
            margin-bottom: 0.9rem;
        }
        .tv-live-badge i { font-size: 0.6rem; }
        .tv-hero-title {
            font-size: 2.1rem;
            font-weight: 800;
            margin: 0 0 0.8rem;
            line-height: 1.15;
            color: #fff;
        }
        .tv-hero-meta {
            display: flex;
            align-items: center;
            gap: 1.4rem;
            color: #d8d8d8;
            font-size: 0.95rem;
            font-weight: 600;
            margin-bottom: 0.9rem;
            flex-wrap: wrap;
        }
        .tv-hero-meta span { display: flex; align-items: center; gap: 6px; }
        .tv-hero-meta i { color: var(--tv-red); }
        .tv-hero-desc {
            color: #cfcfcf;
            font-size: 0.98rem;
            margin: 0 0 1.4rem;
            line-height: 1.5;
        }
        .tv-hero-actions { display: flex; gap: 0.8rem; flex-wrap: wrap; }
        .tv-btn {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            border: none;
            border-radius: 8px;
            padding: 0.75rem 1.5rem;
            font-size: 0.98rem;
            font-weight: 700;
            cursor: pointer;
            transition: transform 0.15s, background 0.2s, opacity 0.2s;
        }
        .tv-btn-primary { background: var(--tv-red); color: #fff; }
        .tv-btn-primary:hover { background: var(--tv-red-2); transform: translateY(-1px); }
        .tv-btn-secondary { background: rgba(255,255,255,0.08); color: #fff; border: 1px solid rgba(255,255,255,0.18); }
        .tv-btn-secondary:hover { background: rgba(255,255,255,0.14); }
        /* ---------- SECTION HEADERS ---------- */
        .tv-section { margin-bottom: 2.4rem; }
        .tv-section-title {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            margin: 0 0 1.1rem;
        }
        .tv-section-title .bar {
            width: 4px;
            height: 20px;
            background: var(--tv-red);
            border-radius: 2px;
            display: inline-block;
        }
        /* ---------- DESTAQUES (grid de canais) ---------- */
        .tv-channel-grid, .channel-list, #channel-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1.1rem;
        }
        .channel-card {
            background: var(--tv-black);
            border: 1px solid var(--tv-border);
            border-radius: 12px;
            padding: 0.9rem 0.8rem 0.8rem;
            cursor: pointer;
            transition: border 0.2s, transform 0.18s, background 0.2s;
            text-align: center;
        }
        .channel-card:hover {
            border-color: var(--tv-red);
            transform: translateY(-3px);
            background: rgba(229,9,20,0.06);
        }
        .channel-card-thumb {
            position: relative;
            width: 100%;
            aspect-ratio: 1 / 1;
            border-radius: 10px;
            overflow: hidden;
            background: #000;
            border: 1px solid var(--tv-border);
            margin-bottom: 0.6rem;
        }
        .channel-card-thumb img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            padding: 10px;
        }
        .channel-card .live-tag {
            position: absolute;
            top: 6px;
            right: 6px;
            background: var(--tv-red);
            color: #fff;
            font-size: 0.62rem;
            font-weight: 800;
            letter-spacing: 0.03em;
            padding: 2px 7px;
            border-radius: 4px;
        }
        .channel-card p {
            margin: 0;
            font-size: 0.92rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .empty-msg { color: var(--tv-text-dim); padding: 1rem 0; }
        /* ---------- GUIA DE PROGRAMAS ---------- */
        .tv-guide-table {
            background: var(--tv-black);
            border: 1px solid var(--tv-border);
            border-radius: 14px;
            overflow: hidden;
        }
        .tv-guide-row {
            display: flex;
            align-items: stretch;
            border-bottom: 1px solid var(--tv-border);
        }
        .tv-guide-row:last-child { border-bottom: none; }
        .tv-guide-channel {
            flex: 0 0 200px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 1rem;
            border-right: 1px solid var(--tv-border);
            background: rgba(255,255,255,0.02);
        }
        .tv-guide-channel img {
            width: 34px;
            height: 34px;
            object-fit: contain;
            border-radius: 6px;
            background: #000;
        }
        .tv-guide-channel span {
            font-weight: 700;
            font-size: 0.9rem;
            color: #fff;
        }
        .tv-guide-programs {
            flex: 1;
            display: flex;
            align-items: stretch;
            overflow-x: auto;
            gap: 0;
        }
        .tv-guide-programs::-webkit-scrollbar { height: 6px; }
        .tv-guide-programs::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.14); border-radius: 6px; }
        .tv-program-block {
            flex: 0 0 auto;
            min-width: 180px;
            max-width: 230px;
            padding: 0.85rem 1rem;
            border-right: 1px solid var(--tv-border);
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 4px;
        }
        .tv-program-block:last-child { border-right: none; }
        .tv-program-block.is-live { background: rgba(229,9,20,0.10); }
        .tv-program-time {
            font-size: 0.78rem;
            font-weight: 700;
            color: var(--tv-red);
        }
        .tv-program-block:not(.is-live) .tv-program-time { color: var(--tv-text-dim); }
        .tv-program-title {
            font-size: 0.87rem;
            font-weight: 600;
            color: #fff;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .tv-program-live-tag {
            font-size: 0.62rem;
            font-weight: 800;
            color: var(--tv-red);
            letter-spacing: 0.04em;
        }
        .tv-no-epg-row { padding: 1rem; color: var(--tv-text-dim); font-size: 0.9rem; }
        /* ---------- CATEGORIAS (pills) ---------- */
        .tv-category-pills {
            display: flex;
            gap: 0.7rem;
            overflow-x: auto;
            padding-bottom: 0.3rem;
            margin-bottom: 1.3rem;
        }
        .tv-category-pills::-webkit-scrollbar { height: 6px; }
        .tv-category-pills::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.14); border-radius: 6px; }
        .category-item {
            flex: 0 0 auto;
            background: var(--tv-black);
            border: 1.5px solid var(--tv-border);
            border-radius: 999px;
            padding: 0.55rem 1.2rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: #fff;
            cursor: pointer;
            white-space: nowrap;
            transition: border 0.2s, color 0.2s, background 0.2s;
        }
        .category-item:hover, .category-item.active {
            border-color: var(--tv-red);
            color: var(--tv-red);
            background: rgba(229,9,20,0.08);
        }
        /* ---------- PLAYER EMBUTIDO (toca no topo, sem sair da página) ---------- */
        .tv-live-player {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .tv-live-player-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .tv-live-close {
            flex: 0 0 auto;
            background: rgba(255,255,255,0.08);
            border: 1px solid var(--tv-border);
            color: #fff;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1rem;
            transition: border 0.2s, color 0.2s, background 0.2s;
        }
        .tv-live-close:hover { border-color: var(--tv-red); color: var(--tv-red); background: rgba(229,9,20,0.08); }
        .video-player-container {
            width: 100%;
            background: var(--tv-black);
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--tv-border);
            min-height: 240px;
        }
        .tv-player-title {
            font-size: 1.2rem;
            font-weight: 800;
            color: #fff;
            margin: 0;
        }
        .epg-section {
            background: var(--tv-black);
            border: 1px solid var(--tv-border);
            border-radius: 14px;
            padding: 1.1rem 1.3rem;
        }
        .epg-section h3 {
            margin: 0 0 0.9rem 0;
            font-size: 1rem;
            font-weight: 700;
            color: var(--tv-red);
        }
        .program-item {
            padding: 9px 0;
            border-bottom: 1px solid var(--tv-border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95rem;
        }
        .program-item:last-child { border-bottom: none; }
        .program-title { flex: 1; font-weight: 500; color: #eee; }
        .program-time { flex: 0 0 130px; text-align: right; color: var(--tv-red); font-weight: 600; }
        .no-epg { color: var(--tv-text-dim); text-align: center; padding: 12px 0 0 0; }
        @media (max-width: 720px) {
            .tv-hero { min-height: 300px; }
            .tv-hero-title { font-size: 1.5rem; }
            .tv-guide-channel { flex-basis: 140px; }
            .tv-wrap { padding: 1.1rem 0.8rem 2.4rem; }
        }
    </style>
</head>
<body oncontextmenu="return false;">
<script>
    document.addEventListener('selectstart', function(e) { e.preventDefault(); });
</script>

    <!-- ===================== TELA DE NAVEGAÇÃO ===================== -->
    <div class="tv-wrap">

        <!-- PLAYER EMBUTIDO: aparece aqui em cima ao escolher um canal,          -->
        <!-- sem sair desta página. Só some se o usuário clicar em fechar.        -->
        <div class="tv-live-player" id="live-player-section" style="display:none;">
            <div class="tv-live-player-head">
                <h2 class="tv-player-title"><i class="fa-solid fa-tower-broadcast" style="color:var(--tv-red);"></i> <span id="live-player-title-text">Carregando...</span></h2>
                <button class="tv-live-close" onclick="closePlayer()" title="Fechar"><i class="fa fa-xmark"></i></button>
            </div>
            <div class="video-player-container">
                <video id="videojs-player" class="video-js vjs-default-skin" controls preload="auto" playsinline></video>
            </div>
            <div class="epg-section" id="live-player-epg" style="display:none;">
                <h3>Guia de Programas</h3>
                <div id="epg-list"></div>
            </div>
        </div>

        <!-- HERO -->
        <?php if ($hero_channel): ?>
        <div class="tv-hero">
            <div class="tv-hero-bg" style="background-image:url('<?php echo htmlspecialchars($hero_channel['stream_icon'], ENT_QUOTES, 'UTF-8'); ?>');"></div>
            <div class="tv-hero-content">
                <span class="tv-live-badge"><i class="fa-solid fa-circle"></i> AO VIVO</span>
                <h1 class="tv-hero-title"><?php echo htmlspecialchars($hero_epg_title ?: $hero_channel['name'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <div class="tv-hero-meta">
                    <?php if ($hero_category_name): ?><span><i class="fa-solid fa-trophy"></i> <?php echo htmlspecialchars($hero_category_name, ENT_QUOTES, 'UTF-8'); ?></span><?php endif; ?>
                    <?php if ($hero_epg_time): ?><span><i class="fa-regular fa-clock"></i> Hoje | <?php echo $hero_epg_time; ?></span><?php endif; ?>
                </div>
                <p class="tv-hero-desc">Acompanhe <?php echo htmlspecialchars($hero_channel['name'], ENT_QUOTES, 'UTF-8'); ?> ao vivo agora em <?php echo htmlspecialchars($sitename, ENT_QUOTES, 'UTF-8'); ?>.</p>
                <div class="tv-hero-actions">
                    <button class="tv-btn tv-btn-primary" onclick="playChannel(<?php echo json_encode($hero_channel['stream_id']); ?>)"><i class="fa-solid fa-play"></i> Assistir agora</button>
                    <button class="tv-btn tv-btn-secondary"><i class="fa-solid fa-plus"></i> Minha Lista</button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- CANAIS EM DESTAQUE -->
        <?php if (!empty($featured_channels)): ?>
        <div class="tv-section">
            <h3 class="tv-section-title"><span class="bar"></span>Canais em destaque</h3>
            <div class="tv-channel-grid">
                <?php foreach (array_slice($featured_channels, 0, 7) as $channel): ?>
                    <?php
                        $stream_id = htmlspecialchars($channel["stream_id"], ENT_QUOTES, 'UTF-8');
                        $title = htmlspecialchars($channel["name"], ENT_QUOTES, 'UTF-8');
                        $desc_image = htmlspecialchars($channel["stream_icon"], ENT_QUOTES, 'UTF-8');
                    ?>
                    <div class="channel-card" onclick="playChannel(<?php echo $stream_id; ?>)">
                        <div class="channel-card-thumb">
                            <img src="<?php echo $desc_image; ?>" onerror="this.onerror=null;this.src='assets/img/logo.png';" alt="<?php echo $title; ?>"/>
                            <span class="live-tag">AO VIVO</span>
                        </div>
                        <p><?php echo $title; ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- GUIA DE PROGRAMAS -->
        <?php if (!empty($guide_channels)): ?>
        <div class="tv-section">
            <h3 class="tv-section-title"><span class="bar"></span>Guia de Programas</h3>
            <div class="tv-guide-table">
                <?php foreach ($guide_channels as $gc): ?>
                    <div class="tv-guide-row">
                        <div class="tv-guide-channel">
                            <img src="<?php echo htmlspecialchars($gc['icon'], ENT_QUOTES, 'UTF-8'); ?>" onerror="this.onerror=null;this.src='assets/img/logo.png';" alt="">
                            <span><?php echo htmlspecialchars($gc['name'], ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="tv-guide-programs">
                            <?php if (!empty($gc['programs'])): ?>
                                <?php foreach ($gc['programs'] as $i => $program): ?>
                                    <div class="tv-program-block<?php echo $i === 0 ? ' is-live' : ''; ?>">
                                        <span class="tv-program-time"><?php echo formatEPGTime($program['start']); ?> - <?php echo formatEPGTime($program['end']); ?></span>
                                        <span class="tv-program-title"><?php echo htmlspecialchars(decodeBase64IfNeeded($program['title']), ENT_QUOTES, 'UTF-8'); ?></span>
                                        <?php if ($i === 0): ?><span class="tv-program-live-tag">AO VIVO</span><?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="tv-no-epg-row">Guia de programação indisponível para este canal.</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- CATEGORIAS + TODOS OS CANAIS -->
        <div class="tv-section">
            <h3 class="tv-section-title"><span class="bar"></span>Canais</h3>
            <div class="tv-category-pills" id="category-list-desktop">
                <?php if (!empty($channel_api_categories)): ?>
                    <?php foreach ($channel_api_categories as $index => $category): ?>
                        <?php
                            $category_id = htmlspecialchars($category['category_id'], ENT_QUOTES, 'UTF-8');
                            $category_name = htmlspecialchars($category['category_name'], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="category-item<?php echo $index === 0 ? ' active' : ''; ?>" data-cat="<?php echo $category_id; ?>" onclick="loadChannels(<?php echo $category_id; ?>, this)"><?php echo $category_name; ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-msg">Não foram encontradas categorias.</p>
                <?php endif; ?>
            </div>
            <div id="channel-list" class="tv-channel-grid">
                <?php if (!empty($featured_channels)): ?>
                    <?php foreach ($featured_channels as $channel): ?>
                        <?php
                            $stream_id = htmlspecialchars($channel["stream_id"], ENT_QUOTES, 'UTF-8');
                            $title = htmlspecialchars($channel["name"], ENT_QUOTES, 'UTF-8');
                            $desc_image = htmlspecialchars($channel["stream_icon"], ENT_QUOTES, 'UTF-8');
                        ?>
                        <div class="channel-card" onclick="playChannel(<?php echo $stream_id; ?>)">
                            <div class="channel-card-thumb">
                                <img src="<?php echo $desc_image; ?>" onerror="this.onerror=null;this.src='assets/img/logo.png';" alt="<?php echo $title; ?>"/>
                                <span class="live-tag">AO VIVO</span>
                            </div>
                            <p><?php echo $title; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="empty-msg">Selecione uma categoria para ver os canais.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

<script>
    function loadChannels(categoryId, pillEl) {
        var channelList = document.getElementById('channel-list');
        if (pillEl) {
            document.querySelectorAll('.category-item').forEach(function(el){ el.classList.remove('active'); });
            pillEl.classList.add('active');
        }
        channelList.innerHTML = '<p class="empty-msg">Carregando canais...</p>';
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '?category_id=' + encodeURIComponent(categoryId), true);
        xhr.onreadystatechange = function () {
            if (xhr.readyState == 4 && xhr.status == 200) {
                channelList.innerHTML = xhr.responseText;
            }
        };
        xhr.send();
    }
    // ---------- PLAYER EMBUTIDO (não navega para outra página) ----------
    var vjsPlayer = null;
    var streamSrc = '';
    var streamType = '';
    var reconnectAttempts = 0;
    var maxReconnectAttempts = 5;
    var reconnectTimer = null;
    var lastProgressAt = Date.now();

    // Atraso do ao vivo (segundos) para ter folga de buffer e evitar travadas
    var liveDelaySeconds = 20;
    var delayApplied = false;

    function applyLiveDelay() {
        if (delayApplied || !vjsPlayer) return;
        var seekable = vjsPlayer.seekable();
        if (!seekable || !seekable.length) return;
        var start = seekable.start(0);
        var liveEdge = seekable.end(seekable.length - 1);
        var target = Math.max(start + 1, liveEdge - liveDelaySeconds);
        if (target < liveEdge - 1) {
            vjsPlayer.currentTime(target);
        }
        delayApplied = true;
    }

    // ---------- ANTI-TRAVAMENTO: guarda de buffer ----------
    // Se o buffer à frente ficar baixo, reduz levemente a velocidade para o
    // download alcançar antes de o vídeo parar; volta ao normal quando enche.
    var bufLow = 2.5;      // segundos: abaixo disso desacelera
    var bufOk = 6;         // segundos: acima disso volta a 1x
    var slowRate = 0.92;
    var stallCount = 0;

    function bufferAhead() {
        if (!vjsPlayer) return 0;
        var b = vjsPlayer.buffered();
        var t = vjsPlayer.currentTime();
        for (var i = 0; i < b.length; i++) {
            if (t >= b.start(i) - 0.1 && t <= b.end(i)) return b.end(i) - t;
        }
        return 0;
    }

    function bufferGuard() {
        if (!vjsPlayer || vjsPlayer.paused() || vjsPlayer.ended()) return;
        var ahead = bufferAhead();
        var rate = vjsPlayer.playbackRate();
        if (ahead < bufLow && rate === 1) {
            vjsPlayer.playbackRate(slowRate);
        } else if (ahead >= bufOk && rate !== 1) {
            vjsPlayer.playbackRate(1);
        }
    }

    // Painel de diagnóstico: abra a página com ?debug=1 (ex.: tvguide.php?debug=1)
    var DEBUG = /[?&]debug=1/.test(location.search);
    function initDebug() {
        var d = document.createElement('div');
        d.style.cssText = 'position:fixed;top:60px;left:8px;z-index:99999;background:rgba(0,0,0,.75);color:#0f0;font:12px/1.4 monospace;padding:6px 8px;border-radius:6px;pointer-events:none;';
        document.body.appendChild(d);
        setInterval(function() {
            if (!vjsPlayer) return;
            var s = vjsPlayer.seekable();
            var win = 0, behind = 0;
            if (s && s.length) {
                win = s.end(s.length - 1) - s.start(0);
                behind = s.end(s.length - 1) - vjsPlayer.currentTime();
            }
            var bw = '-';
            try {
                var vhs = vjsPlayer.tech({ IWillNotUseThisInPlugins: true }).vhs;
                if (vhs && vhs.bandwidth) bw = (vhs.bandwidth / 1e6).toFixed(1) + ' Mbps';
            } catch (e) {}
            d.textContent = 'buffer: ' + bufferAhead().toFixed(1) + 's | janela: ' + win.toFixed(0) +
                's | atraso do vivo: ' + behind.toFixed(0) + 's | veloc: ' + vjsPlayer.playbackRate() +
                'x | banda: ' + bw + ' | travadas: ' + stallCount;
        }, 1000);
    }

    function ensurePlayer() {
        if (vjsPlayer) return vjsPlayer;
        vjsPlayer = videojs('videojs-player', {
            controls: true,
            autoplay: true,
            preload: 'auto',
            fluid: true
        });

        // Recuperação automática: se o canal travar com erro de suporte/rede,
        // tenta recarregar a mesma fonte em vez de deixar o player parado.
        vjsPlayer.on('error', function() {
            if (reconnectTimer) return; // já tem uma tentativa agendada
            reconnectAttempts++;
            if (reconnectAttempts > maxReconnectAttempts) return; // desiste após várias tentativas
            var delay = Math.min(2000 * reconnectAttempts, 8000);
            reconnectTimer = setTimeout(function() {
                reconnectTimer = null;
                delayApplied = false;
                vjsPlayer.playbackRate(1);
                vjsPlayer.error(null);
                vjsPlayer.src({ src: streamSrc, type: streamType });
                vjsPlayer.load();
                vjsPlayer.play().catch(function() {});
            }, delay);
        });

        vjsPlayer.on('playing', function() {
            reconnectAttempts = 0;
            lastProgressAt = Date.now();
        });
        vjsPlayer.on('playing', applyLiveDelay);
        vjsPlayer.on('waiting', function() { stallCount++; });

        // Vigia proativo: acompanha se o tempo do vídeo está realmente avançando.
        // Se travar por alguns segundos, reconecta sozinho.
        vjsPlayer.on('timeupdate', function() {
            lastProgressAt = Date.now();
        });
        setInterval(function() {
            if (!vjsPlayer || vjsPlayer.paused() || vjsPlayer.ended()) return;
            if (Date.now() - lastProgressAt > 7000) {
                lastProgressAt = Date.now();
                vjsPlayer.trigger('error');
            }
        }, 2000);

        setInterval(bufferGuard, 500);
        if (DEBUG) initDebug();

        return vjsPlayer;
    }

    function playChannel(streamId) {
        var section = document.getElementById('live-player-section');
        var titleEl = document.getElementById('live-player-title-text');
        section.style.display = 'block';
        titleEl.textContent = 'Carregando...';
        section.scrollIntoView({ behavior: 'smooth', block: 'start' });

        fetch('tvguide.php?ajax_play=1&id=' + encodeURIComponent(streamId))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success) {
                    titleEl.textContent = 'Não foi possível carregar este canal.';
                    return;
                }
                titleEl.textContent = data.content_name;
                streamSrc = data.video_url;
                streamType = data.mime_type;
                reconnectAttempts = 0;
                delayApplied = false;
                if (vjsPlayer) vjsPlayer.playbackRate(1);

                var p = ensurePlayer();
                p.src({ src: streamSrc, type: streamType });
                p.load();
                p.play().catch(function() {});

                var epgWrap = document.getElementById('live-player-epg');
                var epgList = document.getElementById('epg-list');
                epgList.innerHTML = '';
                if (data.epg && data.epg.length) {
                    data.epg.forEach(function(prog) {
                        var row = document.createElement('div');
                        row.className = 'program-item';
                        var t = document.createElement('div');
                        t.className = 'program-title';
                        t.textContent = prog.title;
                        var tm = document.createElement('div');
                        tm.className = 'program-time';
                        tm.textContent = prog.start + ' - ' + prog.end;
                        row.appendChild(t);
                        row.appendChild(tm);
                        epgList.appendChild(row);
                    });
                    epgWrap.style.display = 'block';
                } else {
                    epgWrap.style.display = 'none';
                }
            })
            .catch(function() {
                titleEl.textContent = 'Erro ao carregar este canal. Tente novamente.';
            });
    }

    function closePlayer() {
        var section = document.getElementById('live-player-section');
        section.style.display = 'none';
        if (vjsPlayer) vjsPlayer.pause();
    }

    <?php if ($deep_link_id): ?>
    // Compatibilidade com links antigos (?id=X&slug=live): abre o player
    // embutido automaticamente, sem sair desta página.
    document.addEventListener('DOMContentLoaded', function() {
        playChannel(<?php echo json_encode($deep_link_id); ?>);
    });
    <?php endif; ?>
</script>
</body>
</html>