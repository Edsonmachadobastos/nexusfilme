<?php
include "session.php";
include "config.php";
include "header.php";

// Função para obter dados da API com cache em memória e CURL
function getApiDataWithCache($apiUrl, $cacheKey, $expirationTime = 300) {
    if (isset($_SESSION[$cacheKey]) && (time() - $_SESSION[$cacheKey]['time'] < $expirationTime)) {
        return $_SESSION[$cacheKey]['data'];
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        echo 'Error de CURL: ' . curl_error($ch);
        curl_close($ch);
        return [];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        echo "Error al conectarse a la API. Código HTTP: " . $httpCode;
        return [];
    }
    $data = json_decode($response, true);
    $_SESSION[$cacheKey] = [
        'time' => time(),
        'data' => $data
    ];
    return $data;
}

// Obter categorias de filmes
$apiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories";
$movie_categories = getApiDataWithCache($apiUrl, 'movie_categories_cache');

// Obter categorias de séries (pra também filtrar séries por plataforma)
$seriesCatApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_categories";
$series_categories = getApiDataWithCache($seriesCatApiUrl, 'series_categories_cache');

// Buscar links de categorias
function getCategoryLink($categories, $keywords) {
    foreach ($categories as $category) {
        $category_name = strtolower($category["category_name"]);
        foreach ($keywords as $keyword) {
            if (strpos($category_name, $keyword) !== false) {
                return "movies.php?id=" . $category["category_id"];
            }
        }
    }
    return "#";
}

// Igual à de cima, mas devolve o category_id cru (ou null), pra usar no
// filtro por plataforma direto na Home (filmes e séries).
function getCategoryId($categories, $keywords) {
    foreach ($categories as $category) {
        $category_name = strtolower($category["category_name"]);
        foreach ($keywords as $keyword) {
            if (strpos($category_name, $keyword) !== false) {
                return $category["category_id"];
            }
        }
    }
    return null;
}

$links = [
    'max' => getCategoryLink($movie_categories, ['hbo', 'max']),
    'disney' => getCategoryLink($movie_categories, ['disney']),
    'netflix' => getCategoryLink($movie_categories, ['netflix']),
    'amazon' => getCategoryLink($movie_categories, ['amazon', 'prime']),
    'marvel' => getCategoryLink($movie_categories, ['marvel']),
    'xmas' => getCategoryLink($movie_categories, ['navidad', 'xmas']),
    'halloween' => getCategoryLink($movie_categories, ['terror', 'horror'])
];

// Categoria de filme + categoria de série por plataforma, pra alimentar o
// filtro em JS dos ícones (Disney, Netflix, HBO Max, Prime, Marvel).
$platformKeywords = [
    'max' => ['hbo', 'max'],
    'disney' => ['disney'],
    'netflix' => ['netflix'],
    'amazon' => ['amazon', 'prime'],
    'marvel' => ['marvel'],
];
$platformCats = [];
foreach ($platformKeywords as $platform => $keywords) {
    $platformCats[$platform] = [
        'movie' => getCategoryId($movie_categories, $keywords),
        'series' => getCategoryId($series_categories, $keywords),
    ];
}

// Obter filmes
$apiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
$channel_api = getApiDataWithCache($apiUrl, 'vod_streams_cache');
shuffle($channel_api);
// Filmes em destaque (agora inclui poster/capa)
$random_movies_info = [];
$max_movies = 5;
$movie_count = 0;
foreach ($channel_api as $movie) {
    if ($movie_count >= $max_movies) break;
    $id = $movie["stream_id"];
    $infoApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_info&vod_id=" . $id;
    $channel_api_info = getApiDataWithCache($infoApiUrl, 'vod_info_cache_' . $id, 3600);
    $channel_api_info2 = $channel_api_info["info"];
    if (isset($channel_api_info2["backdrop_path"][0]) && !empty($channel_api_info2["backdrop_path"][0])) {
        $random_movies_info[] = [
            'title' => $channel_api_info2["name"],
            'description' => $channel_api_info2["plot"],
            'rating' => $channel_api_info2["rating"],
            'background_image' => $channel_api_info2["backdrop_path"][0],
            'poster' => $channel_api_info2["cover"] ?? $movie["stream_icon"] ?? "img/offvs.png",
            'play_link' => "Movie_description.php?id=" . $id
        ];
        $movie_count++;
    }
}
// Obter séries
$seriesApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
$channel_api1 = getApiDataWithCache($seriesApiUrl, 'series_cache');
shuffle($channel_api1);

// Obter canais
$channelsApiUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_live_streams";
$channel_api2 = getApiDataWithCache($channelsApiUrl, 'live_streams_cache');
shuffle($channel_api2);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Home - IPTV Estilo Netflix</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
<style>
:root {
    --primary: #e50914;
    --secondary: #141414;
    --glass-bg: rgba(20,20,20,0.72);
    --glass-blur: 18px;
    --radius: 1.5rem;
    --shadow: 0 8px 32px 0 rgba(0,0,0,0.35);
    --white-glass: rgba(255,255,255,0.08);
}
html, body {
    margin: 0;
    padding: 0;
    background: #000 !important;
    color: #fff;
    font-family: 'Montserrat', 'Roboto', Arial, sans-serif;
    min-height: 100vh;
    overflow-x: hidden;
}
#particles-js {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: 0;
    pointer-events: none;
}
.featured-movie-container {
    position: relative;
    min-height: 54vh;
    background-size: cover;
    background-position: center;
    display: flex;
    align-items: center;
    padding: 0 5vw;
    border-radius: var(--radius);
    margin: 2vw 2vw 0 2vw;
    overflow: hidden;
    box-shadow: none;
    transition: background-image 0.7s;
}
.featured-movie-overlay {
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0) 72%, #000 72%),
        linear-gradient(90deg,rgba(18,18,24,0.96) 0%,rgba(18,18,24,0.85) 40%,rgba(18,18,24,0.35) 100%);
    z-index: 1;
}
.featured-movie-content {
    position: relative;
    z-index: 2;
    display: flex;
    align-items: flex-end;
    gap: 2.5vw;
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
}
.featured-movie-poster {
    flex: 0 0 180px;
    width: 180px;
    height: 270px;
    box-shadow: 0 8px 32px #000a;
    border-radius: 1.1rem;
    overflow: hidden;
    background: #222;
    display: flex;
    align-items: center;
    justify-content: center;
}
.featured-movie-poster img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 1.1rem;
    display: block;
}
.featured-movie-info {
    background: var(--glass-bg);
    backdrop-filter: blur(var(--glass-blur));
    border-radius: 1.1rem;
    box-shadow: 0 8px 38px 0 rgba(0,0,0,0.45);
    padding: 1.5rem 2rem;
    max-width: 520px;
    min-width: 0;
    margin-bottom: 2vw;
    border: 1.5px solid rgba(255,255,255,0.13);
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}
.featured-movie-info .badge {
    display: inline-block;
    background: linear-gradient(90deg,#e50914 80%,#ffcc00 100%);
    color: #fff;
    font-size: 0.95rem;
    font-weight: bold;
    padding: 0.18em 0.9em;
    border-radius: 1em;
    letter-spacing: 1px;
    margin-bottom: 0.7em;
    box-shadow: 0 0 8px #e50914b0;
}
.featured-movie-info h2 {
    font-size: 1.45rem;
    margin-bottom: 0.3rem;
    font-weight: 800;
    letter-spacing: -1px;
    line-height: 1.1;
    color: #fff;
    text-shadow: 0 2px 8px #000a;
}
.featured-movie-info .movie-meta {
    margin-bottom: 0.5rem;
}
.featured-movie-info .rating {
    display: inline-block;
    background: #ffcc00;
    color: #222;
    font-size: 1.05rem;
    font-weight: 700;
    padding: 0.13em 0.7em;
    border-radius: 1em;
    margin-right: 0.7em;
    letter-spacing: 1px;
    box-shadow: 0 0 8px #ffcc0055;
}
.featured-movie-info p {
    font-size: 1rem;
    margin-bottom: 0.8rem;
    color: #e0e0e0;
    line-height: 1.4;
    text-shadow: 0 2px 8px #000a;
    max-height: 4.2em;
    overflow: hidden;
}
.play-button {
    display: inline-flex;
    align-items: center;
    background: linear-gradient(90deg, #e50914 60%, #ff2a68 100%);
    color: #fff;
    padding: 0.7rem 1.5rem;
    font-size: 1rem;
    font-weight: bold;
    border: none;
    border-radius: 1.5rem;
    text-decoration: none;
    box-shadow: 0 0 10px 2px #e50914b0;
    transition: transform 0.14s, box-shadow 0.2s, background 0.3s;
    letter-spacing: 1px;
    cursor: pointer;
    outline: none;
    filter: drop-shadow(0 0 6px #e50914);
    text-transform: uppercase;
    gap: 0.5em;
}
.play-button svg {
    margin-right: 8px;
}
.play-button:hover, .play-button:focus {
    transform: scale(1.06);
    box-shadow: 0 0 18px 4px #e50914e0;
    background: linear-gradient(90deg, #ff2a68 0%, #e50914 100%);
}
@media (max-width: 900px) {
    .featured-movie-content { flex-direction: column; align-items: flex-start; gap: 1.2rem;}
    .featured-movie-poster { width: 120px; height: 180px; flex-basis: 120px;}
    .featured-movie-info { padding: 1.1rem 1.2rem; max-width: 98vw;}
}
@media (max-width: 600px) {
    .featured-movie-container { min-height: 38vh; padding: 4vw 2vw;}
    .featured-movie-content { flex-direction: column; align-items: flex-start; gap: 1rem;}
    .featured-movie-poster { width: 90px; height: 135px; flex-basis: 90px;}
    .featured-movie-info h2 { font-size: 1.1rem;}
    .featured-movie-info p, .featured-movie-info .rating { font-size: 0.91rem;}
    .play-button { font-size: 0.91rem; padding: 0.6rem 1rem;}
}
.banner-buttons {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2vw;
    margin: 0;
    padding: 1.4vw 2vw;
    flex-wrap: wrap;
    background: #050912;
}
.banner-buttons a {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0c1526;
    backdrop-filter: blur(12px);
    border-radius: 1.2rem;
    box-shadow: none;
    padding: 0.3rem 1.1rem;
    border: 1.5px solid rgba(255,255,255,0.10);
    transition: box-shadow 0.22s, transform 0.18s, border 0.2s;
}
.banner-buttons a:has(img[alt="Disney+"]) {
    background: #f0f0f0;
}
.banner-buttons img {
    width: 100px;
    height: 38px;
    object-fit: contain;
    filter: drop-shadow(0 0 8px #0006);
    transition: transform 0.22s, filter 0.22s;
}
.banner-buttons a:hover, .banner-buttons a:focus {
    box-shadow: 0 0 24px 4px #e5091480;
    border: 1.5px solid #e50914cc;
    transform: scale(1.07);
}
.banner-buttons a:hover img, .banner-buttons a:focus img {
    filter: drop-shadow(0 0 18px #e50914cc);
    transform: scale(1.09) rotate(-3deg);
}
.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 3vw 2vw 1vw 2vw;
}
.section-title {
    margin: 0;
    font-size: 1.1rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.5px;
    text-shadow: 0 0 8px #e50914, 0 2px 12px #000;
    background: var(--glass-bg);
    backdrop-filter: blur(10px);
    border-radius: 0.7rem;
    padding: 0.3rem 1rem;
    display: inline-block;
    border: 1.2px solid rgba(255,255,255,0.09);
    box-shadow: var(--shadow);
}
.ver-mais-btn {
    background: linear-gradient(90deg, #e50914 60%, #ff2a68 100%);
    color: #fff;
    border: none;
    border-radius: 1.2rem;
    padding: 0.3rem 0.7rem; /* igual ao quick-btn */
    font-size: 0.92rem;    /* igual ao quick-btn */
    font-weight: 600;
    box-shadow: 0 0 8px #e5091499;
    cursor: pointer;
    margin-left: 1rem;
    transition: background 0.2s, box-shadow 0.2s, transform 0.18s;
    text-decoration: none;
    display: inline-block;
}
.ver-mais-btn:hover, .ver-mais-btn:focus {
    background: linear-gradient(90deg, #ff2a68 0%, #e50914 100%);
    box-shadow: 0 0 18px #e50914cc;
    transform: scale(1.05);
}
.horizontal-section {
    overflow-x: auto;
    white-space: nowrap;
    padding: 0 2vw 2vw 2vw;
    scroll-behavior: smooth;
}
.horizontal-section {
    scrollbar-color: #101a2e transparent;
}
.horizontal-section::-webkit-scrollbar {
    height: 10px;
    background: transparent;
}
.horizontal-section::-webkit-scrollbar-thumb {
    background: #101a2e;
    border-radius: 6px;
}
.grid-container {
    display: flex;
    gap: 2vw;
    overflow-x: auto;
    padding: 1vw 2vw 2vw 2vw;
    scroll-snap-type: x mandatory;
}
.grid-item {
    background: var(--white-glass);
    backdrop-filter: blur(10px);
    border-radius: 1.2rem;
    box-shadow: 0 4px 16px rgba(0,0,0,0.13);
    border: 1.5px solid rgba(255,255,255,0.11);
    min-width: 120px;
    max-width: 130px;
    flex: 0 0 130px;
    position: relative;
    overflow: hidden;
    transition: transform 0.21s cubic-bezier(.4,0,.2,1), box-shadow 0.22s;
    cursor: pointer;
    scroll-snap-align: start;
}
.grid-item img {
    width: 100%;
    height: 180px;
    object-fit: cover;
    border-radius: 1.2rem 1.2rem 0 0;
    transition: filter 0.18s;
    filter: brightness(0.96) saturate(1.1);
}
.grid-item p {
    text-align: left;
    margin: 0.7rem 1rem 1rem 1rem;
    font-size: 0.95rem;
    color: #fff;
    font-weight: 500;
    min-height: 2em;
    overflow: hidden;
}
.grid-item::after {
    content: '';
    display: block;
    position: absolute;
    inset: 0;
    border-radius: inherit;
    box-shadow: 0 0 0 0 #e50914;
    opacity: 0;
    transition: box-shadow 0.4s, opacity 0.4s;
    pointer-events: none;
}
.grid-item:hover, .grid-item:focus-within {
    transform: scale(1.07) translateY(-8px);
    box-shadow: 0 0 32px 8px #e5091444, 0 8px 32px 0 rgba(0,0,0,0.25);
    z-index: 2;
}
.grid-item:hover::after, .grid-item:focus-within::after {
    box-shadow: 0 0 32px 8px #e50914;
    opacity: 1;
}
.grid-item:hover img, .grid-item:focus-within img {
    filter: brightness(1.04) saturate(1.2) blur(1px);
}
.quick-actions {
    display: flex;
    gap: 0.7rem;
    position: absolute;
    bottom: 1.1rem;
    left: 50%;
    transform: translateX(-50%);
    opacity: 0;
    transition: opacity 0.18s;
    z-index: 3;
}
.grid-item:hover .quick-actions, .grid-item:focus-within .quick-actions {
    opacity: 1;
}
.quick-btn {
    background: linear-gradient(90deg, #e50914 60%, #ff2a68 100%);
    color: #fff;
    border: none;
    border-radius: 1.2rem;
    padding: 0.3rem 0.7rem;
    font-size: 0.92rem;
    font-weight: 600;
    box-shadow: 0 0 8px #e5091499;
    cursor: pointer;
    transition: background 0.2s, box-shadow 0.2s, transform 0.18s;
    outline: none;
}
.quick-btn:hover, .quick-btn:focus {
    background: linear-gradient(90deg, #ff2a68 0%, #e50914 100%);
    box-shadow: 0 0 18px #e50914cc;
    transform: scale(1.08);
}
</style>
</head>
<body>
<div class="featured-movie-container" id="featured-movie-container">
    <div id="particles-js"></div>
    <div class="featured-movie-overlay"></div>
    <div class="featured-movie-content">
        <div class="featured-movie-poster">
            <img id="movie-poster" src="img/offvs.png" alt="Poster do Filme" />
        </div>
        <div class="featured-movie-info">
            <span class="badge">DESTAQUE</span>
            <h2 id="movie-title">Título do Filme</h2>
            <div class="movie-meta">
                <span class="rating" id="movie-rating">9/10</span>
            </div>
            <p id="movie-description">Descrição do filme em destaque.</p>
            <a id="play-link" class="play-button" href="#">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" style="vertical-align: middle; margin-right:8px;">
                    <path d="M8 5v14l11-7z" fill="currentColor" />
                </svg>
                ASSISTA AGORA
            </a>
        </div>
    </div>
</div>

<div class="banner-buttons" id="platform-buttons">
    <button type="button" class="platform-btn" data-platform="max" data-label="HBO Max" data-movie-cat="<?= $platformCats['max']['movie'] ?? '' ?>" data-series-cat="<?= $platformCats['max']['series'] ?? '' ?>"><img src="https://upload.wikimedia.org/wikipedia/commons/1/17/HBO_Max_Logo.svg" alt="HBO Max" /></button>
    <button type="button" class="platform-btn" data-platform="disney" data-label="Disney+" data-movie-cat="<?= $platformCats['disney']['movie'] ?? '' ?>" data-series-cat="<?= $platformCats['disney']['series'] ?? '' ?>"><img src="https://upload.wikimedia.org/wikipedia/commons/3/3e/Disney%2B_logo.svg" alt="Disney+" /></button>
    <button type="button" class="platform-btn" data-platform="netflix" data-label="Netflix" data-movie-cat="<?= $platformCats['netflix']['movie'] ?? '' ?>" data-series-cat="<?= $platformCats['netflix']['series'] ?? '' ?>"><img src="https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg" alt="Netflix" /></button>
    <button type="button" class="platform-btn" data-platform="amazon" data-label="Prime Video" data-movie-cat="<?= $platformCats['amazon']['movie'] ?? '' ?>" data-series-cat="<?= $platformCats['amazon']['series'] ?? '' ?>"><img src="https://upload.wikimedia.org/wikipedia/commons/f/f1/Prime_Video.png" alt="Prime Video" /></button>
    <button type="button" class="platform-btn" data-platform="marvel" data-label="Marvel" data-movie-cat="<?= $platformCats['marvel']['movie'] ?? '' ?>" data-series-cat="<?= $platformCats['marvel']['series'] ?? '' ?>"><img src="https://i.postimg.cc/RF32pVTG/MARVEL-1.png" alt="Marvel" /></button>
    <button type="button" class="platform-btn platform-clear-btn" id="platform-clear" style="display:none;">✕ Limpar filtro</button>
</div>

<style>
.platform-btn {
    display: flex;
    align-items: center;
    justify-content: center;
    background: #0c1526;
    backdrop-filter: blur(12px);
    border-radius: 1.2rem;
    box-shadow: none;
    padding: 0.3rem 1.1rem;
    border: 1.5px solid rgba(255,255,255,0.10);
    transition: box-shadow 0.22s, transform 0.18s, border 0.2s;
    cursor: pointer;
}
.platform-btn:has(img[alt="Disney+"]) {
    background: #f0f0f0;
}
.platform-btn img {
    width: 100px;
    height: 38px;
    object-fit: contain;
    filter: drop-shadow(0 0 8px #0006);
    pointer-events: none;
}
.platform-btn.active {
    border-color: #e50914;
    box-shadow: 0 0 16px #e5091477;
    transform: scale(1.04);
}
.platform-clear-btn {
    color: #fff;
    font-size: 0.9rem;
    font-weight: 600;
    padding: 0.3rem 1rem;
    background: #1a0d10;
    border-color: rgba(229,9,20,0.4);
}
.platform-clear-btn:hover { background: #2a1216; }
.platform-empty-msg {
    color: #999;
    padding: 1.5vw 2vw;
    font-size: 0.95rem;
}
</style>

<div class="section-header">
    <h2 class="section-title" id="filmes-title">FILMES</h2>
    <a class="ver-mais-btn" href="moviesondemand.php">Ver Mais</a>
</div>
<div class="grid-container horizontal-section" id="filmes-grid">
    <?php
    $i = 0;
    foreach ($channel_api as $value) {
        if ($i < 16) {
            $title = $value["name"];
            $stream_id = $value["stream_id"];
            $medialink = "Movie_description.php?id=" . $stream_id;
            $poster = $value["stream_icon"] ?? "img/offvs.png";
            echo "<div class='grid-item' tabindex='0'>
                    <a href='$medialink'><img src='$poster' alt='$title' loading='lazy' /></a>
                    <p>$title</p>
                    <div class='quick-actions'>

                    </div>
                  </div>";
            $i++;
        }
    }
    ?>
</div>

<div class="section-header">
    <h2 class="section-title" id="series-title">SÉRIES</h2>
    <a class="ver-mais-btn" href="seriesondemand.php">Ver Mais</a>
</div>
<div class="grid-container horizontal-section" id="series-grid">
    <?php
    $i = 0;
    foreach ($channel_api1 as $value) {
        if ($i < 16) {
            $title = $value["name"];
            $series_id = $value["series_id"];
            $medialink = "seriesvideo.php?id=" . $series_id;
            $poster = $value["cover"] ?? "img/offvs.png";
            echo "<div class='grid-item' tabindex='0'>
                    <a href='$medialink'><img src='$poster' alt='$title' loading='lazy' /></a>
                    <p>$title</p>
                    <div class='quick-actions'>

                    </div>
                  </div>";
            $i++;
        }
    }
    ?>
</div>

<div class="section-header">
    <h2 class="section-title">CANAIS</h2>
    <a class="ver-mais-btn" href="tvguide.php">Ver Mais</a>
</div>
<div class="grid-container horizontal-section">
    <?php
    $i = 0;
    foreach ($channel_api2 as $value) {
        if ($i < 16) {
            $channel_title = $value["name"];
            $channel_id = $value["stream_id"];
            $channel_link = "tvguide.php?id=" . $channel_id . "&slug=live"; // Redireciona para a página do canal
            $channel_icon = $value["stream_icon"] ?? "img/offvs.png";
            echo "<div class='grid-item' tabindex='0'>
                    <a href='$channel_link'><img src='$channel_icon' alt='$channel_title' loading='lazy' /></a>
                    <p>$channel_title</p>
                    <div class='quick-actions'>

                    </div>
                  </div>";
            $i++;
        }
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/particles.js@2.0.0/particles.min.js"></script>
<script>
particlesJS("particles-js", {
    particles: {
        number: { value: 50, density: { enable: true, value_area: 800 } },
        color: { value: ["#e50914", "#ff2a68", "#fff"] },
        shape: { type: "circle" },
        opacity: { value: 0.15, random: true },
        size: { value: 6, random: true },
        move: { enable: true, speed: 1.5, direction: "none", random: true, straight: false, out_mode: "out" }
    },
    interactivity: {
        detect_on: "canvas",
        events: {
            onhover: { enable: true, mode: "repulse" },
            onclick: { enable: true, mode: "push" }
        },
        modes: {
            repulse: { distance: 110, duration: 0.4 },
            push: { particles_nb: 4 }
        }
    },
    retina_detect: true
});

document.addEventListener("DOMContentLoaded", function () {
    const movies = <?php echo json_encode($random_movies_info); ?>;
    let movieIndex = 0;
    function updateFeaturedMovie() {
        const movie = movies[movieIndex];
        document.getElementById("movie-title").innerText = movie.title;
        document.getElementById("movie-description").innerText = movie.description;
        document.getElementById("movie-rating").innerText = (movie.rating || "Sem avaliação") + "/10";
        document.getElementById("featured-movie-container").style.backgroundImage = `url('${movie.background_image}')`;
        document.getElementById("play-link").href = movie.play_link;
        document.getElementById("movie-poster").src = movie.poster || "img/offvs.png";
        movieIndex = (movieIndex + 1) % movies.length;
    }
    setInterval(updateFeaturedMovie, 5000);
    updateFeaturedMovie();
});

// ===== Filtro por plataforma (Disney, Netflix, HBO Max, Prime, Marvel) =====
document.addEventListener("DOMContentLoaded", function () {
    const filmesGrid = document.getElementById("filmes-grid");
    const seriesGrid = document.getElementById("series-grid");
    const filmesTitle = document.getElementById("filmes-title");
    const seriesTitle = document.getElementById("series-title");
    const clearBtn = document.getElementById("platform-clear");
    const buttons = document.querySelectorAll(".platform-btn[data-platform]");

    // Guarda o conteúdo original (os 16 aleatórios) pra restaurar no "Limpar filtro"
    const defaultFilmesHtml = filmesGrid.innerHTML;
    const defaultSeriesHtml = seriesGrid.innerHTML;
    const defaultFilmesTitle = filmesTitle.textContent;
    const defaultSeriesTitle = seriesTitle.textContent;

    function renderItems(container, items) {
        if (!items.length) {
            container.innerHTML = '<p class="platform-empty-msg">Nenhum título encontrado nesta plataforma.</p>';
            return;
        }
        container.innerHTML = items.map(function (item) {
            const title = (item.title || '').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            const poster = item.poster || 'img/offvs.png';
            return '<div class="grid-item" tabindex="0">' +
                     '<a href="' + item.link + '"><img src="' + poster + '" alt="' + title + '" loading="lazy" /></a>' +
                     '<p>' + title + '</p>' +
                     '<div class="quick-actions"></div>' +
                   '</div>';
        }).join('');
    }

    function resetFilter() {
        filmesGrid.innerHTML = defaultFilmesHtml;
        seriesGrid.innerHTML = defaultSeriesHtml;
        filmesTitle.textContent = defaultFilmesTitle;
        seriesTitle.textContent = defaultSeriesTitle;
        buttons.forEach(function (b) { b.classList.remove('active'); });
        clearBtn.style.display = 'none';
    }

    buttons.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const movieCat = btn.dataset.movieCat;
            const seriesCat = btn.dataset.seriesCat;
            const label = btn.dataset.label || '';

            if (!movieCat && !seriesCat) {
                // Essa plataforma não tem categoria correspondente nesse servidor.
                filmesGrid.innerHTML = '<p class="platform-empty-msg">Nenhuma categoria de "' + label + '" encontrada neste servidor.</p>';
                seriesGrid.innerHTML = '';
                filmesTitle.textContent = 'FILMES · ' + label;
                seriesTitle.textContent = 'SÉRIES · ' + label;
                buttons.forEach(function (b) { b.classList.remove('active'); });
                btn.classList.add('active');
                clearBtn.style.display = 'inline-flex';
                return;
            }

            buttons.forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            clearBtn.style.display = 'inline-flex';

            filmesTitle.textContent = 'FILMES · ' + label;
            seriesTitle.textContent = 'SÉRIES · ' + label;
            filmesGrid.innerHTML = '<p class="platform-empty-msg">Carregando...</p>';
            seriesGrid.innerHTML = '<p class="platform-empty-msg">Carregando...</p>';

            const fetchMovies = movieCat
                ? fetch('ajax_platform.php?type=movies&cat=' + encodeURIComponent(movieCat)).then(function (r) { return r.json(); }).catch(function () { return { items: [] }; })
                : Promise.resolve({ items: [] });
            const fetchSeries = seriesCat
                ? fetch('ajax_platform.php?type=series&cat=' + encodeURIComponent(seriesCat)).then(function (r) { return r.json(); }).catch(function () { return { items: [] }; })
                : Promise.resolve({ items: [] });

            Promise.all([fetchMovies, fetchSeries]).then(function (results) {
                renderItems(filmesGrid, results[0].items || []);
                renderItems(seriesGrid, results[1].items || []);
            });
        });
    });

    clearBtn.addEventListener('click', resetFilter);
});
</script>
</body>
</html>