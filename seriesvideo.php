<?php
// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include "header.php";
require_once __DIR__ . '/includes/planos_helpers.php';

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una solicitud API usando CURL
function getApiData($url, $userAgent) {
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
        echo "Error al conectarse a la API. Código HTTP: " . $httpCode;
        return [];
    }

    return json_decode($response, true);
}

// Obtener el ID de la serie y la temporada seleccionada (si existe)
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$selected_season = isset($_GET['season']) ? (int)$_GET['season'] : null;

$tmdb_api_key = pwb_load_config()['tmdb_api_key']; // Configurável em admin.php

// Validar que el ID de la serie esté disponible
if (!$id) {
    die("No se proporcionó ID de la serie.");
}

// URL de la API para obtener información de la serie
$api_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_info&series_id=" . $id;
$series_data = getApiData($api_url, $userAgent);

// Verificar si la API devolvió datos válidos
if (!isset($series_data['info'])) {
    die("No se pudo obtener información de la serie.");
}

// Obtener la información de la serie
$series_info = $series_data['info'];
$title = $series_info['name'];
$description = $series_info['plot'];
$backdrop = isset($series_info['backdrop_path'][0]) ? $series_info['backdrop_path'][0] : "https://i.postimg.cc/YCDngMfZ/Design-sem-nome-2.png"; // Imagen por defecto
$poster = isset($series_info['cover']) ? $series_info['cover'] : "https://i.imgur.com/Mn7aXQD.jpg";

// Buscar la serie en TMDb por título para obtener el ID de TMDb
$tmdb_url = "https://api.themoviedb.org/3/search/tv?api_key={$tmdb_api_key}&query=" . urlencode($title);
$tmdb_data = getApiData($tmdb_url, $userAgent);
$tmdb_id = $tmdb_data['results'][0]['id'] ?? null;

// Obtener el trailer desde TMDb
$youtube_trailer = '';
if ($tmdb_id) {
    $tmdb_trailer_url = "https://api.themoviedb.org/3/tv/{$tmdb_id}/videos?api_key={$tmdb_api_key}";
    $tmdb_trailer_data = getApiData($tmdb_trailer_url, $userAgent);
    
    // Filtrar solo los trailers de YouTube
    foreach ($tmdb_trailer_data['results'] as $video) {
        if ($video['site'] === 'YouTube' && $video['type'] === 'Trailer') {
            $youtube_trailer = $video['key'];
            break;
        }
    }
}

// Obtener el cast de la serie desde TMDb
$cast_list = '';
if ($tmdb_id) {
    $tmdb_cast_url = "https://api.themoviedb.org/3/tv/{$tmdb_id}/credits?api_key={$tmdb_api_key}";
    $tmdb_cast_data = getApiData($tmdb_cast_url, $userAgent);
    $cast = $tmdb_cast_data['cast'] ?? [];
    $max_cast = 5; // Limitar a los 5 actores principales

    foreach (array_slice($cast, 0, $max_cast) as $actor) {
        $actor_img = "https://image.tmdb.org/t/p/w200" . ($actor['profile_path'] ?? '');
        $actor_name = $actor['name'];
        $character = $actor['character'];
        
        if (empty($actor['profile_path'])) {
            $actor_img = "https://i.postimg.cc/YCDngMfZ/Design-sem-nome-2.png"; // Imagen por defecto si no hay perfil
        }
        
        $cast_list .= "
            <div class='actor'>
                <img src='{$actor_img}' alt='{$actor_name}' class='actor-img'>
                <div class='actor-name'>{$actor_name}</div>
                <div class='actor-character'>as {$character}</div>
            </div>
        ";
    }
}

// Obtener las temporadas y episodios
$seasons = isset($series_data['episodes']) ? $series_data['episodes'] : [];

// Si no se ha seleccionado temporada, seleccionar la primera
if ($selected_season === null && !empty($seasons)) {
    reset($seasons); // Mover el puntero al primer elemento
    $selected_season = key($seasons); // Obtener la clave del primer elemento
}

// Obtener episodios de la temporada seleccionada
$episodes = isset($seasons[$selected_season]) ? $seasons[$selected_season] : [];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $sitename . ' - ' . $title; ?></title>
    <style>
@import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;900&display=swap');

body {
    background: #000 !important;
    color: #f5f5f7;
    font-family: 'Montserrat', Arial, sans-serif;
    min-height: 100vh;
    margin: 0;
    padding: 0;
}

/* HERO - Banner principal com glassmorphism e overlay */
.hero {
    background-image: url('<?php echo $backdrop; ?>');
    background-size: cover;
    background-position: center;
    position: relative;
    color: white;
    min-height: 60vh;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0 0 48px 48px;
    overflow: hidden;
}
.hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background: rgba(20,20,32,0.82);
    backdrop-filter: blur(8px);
    z-index: 1;
}
.hero-content {
    position: relative;
    z-index: 2;
    text-align: center;
    background: rgba(36,37,54,0.38);
    border-radius: 32px;
    padding: 36px 28px 28px 28px;
    box-shadow: 0 8px 32px 0 rgba(0,0,0,0.32);
    backdrop-filter: blur(8px);
    max-width: 480px;
    margin: 32px auto;
}
.hero img {
    max-width: 220px;
    border-radius: 32px;
    box-shadow: 0 4px 32px 0 rgba(0,0,0,0.7);
    margin-bottom: 18px;
    border: 2px solid rgba(255,255,255,0.08);
}
.hero h1 {
    font-size: 2.6em;
    font-weight: 900;
    letter-spacing: 1px;
    margin-bottom: 12px;
    color: #fff;
    text-shadow: 0 2px 24px #e50914a0;
}
.hero p {
    font-size: 1.15em;
    color: #e0e0e0;
    margin: 0 auto;
    margin-bottom: 12px;
    line-height: 1.6;
}

/* Botões principais */
.buttons {
    margin-top: 22px;
    text-align: center;
    display: flex;
    gap: 18px;
    justify-content: center;
    flex-wrap: wrap;
}
.button {
    background: linear-gradient(90deg, #e50914 60%, #ff2e63 100%);
    color: #fff;
    padding: 13px 32px;
    border: none;
    border-radius: 32px;
    cursor: pointer;
    font-size: 1.13em;
    font-weight: 600;
    margin: 0 5px 12px 0;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 16px #e5091430;
    transition: transform 0.13s, box-shadow 0.13s, filter 0.13s;
    text-decoration: none;
    filter: brightness(1.08);
    outline: none;
}
.button:hover, .season-button.active {
    background: linear-gradient(90deg, #ff2e63 0%, #e50914 100%);
    box-shadow: 0 4px 32px #e50914a0, 0 0 8px #fff1;
    transform: scale(1.07);
    filter: brightness(1.16);
}

/* Temporadas */
.season-buttons {
    margin: 32px 0 12px 0;
    text-align: center;
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
}
.season-button {
    background: rgba(40,40,60,0.82);
    color: #fff;
    padding: 10px 22px;
    border: none;
    cursor: pointer;
    border-radius: 22px;
    font-size: 1em;
    font-weight: 500;
    margin: 0 4px 8px 0;
    transition: background 0.18s, box-shadow 0.13s, color 0.13s;
    box-shadow: 0 2px 12px #0002;
    text-decoration: none;
}
.season-button:hover, .season-button.active {
    background: linear-gradient(90deg, #e50914 60%, #ff2e63 100%);
    color: #fff;
    box-shadow: 0 4px 24px #e5091460;
}

/* Modal - Glassmorphism */
.modal {
    display: none;
    position: fixed;
    z-index: 10000;
    left: 0; top: 0; width: 100%; height: 100%;
    background: rgba(20,20,32,0.95);
    justify-content: center;
    align-items: center;
    animation: modalFadeIn 0.3s;
}
@keyframes modalFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
.modal-content {
    background: rgba(36,37,54,0.82);
    padding: 32px 24px;
    border-radius: 32px;
    box-shadow: 0 10px 40px 0 #000a, 0 0 24px #e5091420;
    width: 90vw;
    max-width: 900px;
    overflow-y: auto;
    max-height: 92vh;
    position: relative;
    backdrop-filter: blur(12px);
    border: 1.5px solid rgba(255,255,255,0.08);
}
.close {
    color: #fff;
    font-size: 2.2em;
    font-weight: 900;
    position: absolute;
    right: 28px;
    top: 18px;
    cursor: pointer;
    text-shadow: 0 2px 18px #e50914b0;
    transition: color 0.15s;
    z-index: 2;
}
.close:hover {
    color: #ff2e63;
}

/* Carrossel de episódios */
.episodes-carousel {
    display: flex;
    overflow-x: auto;
    gap: 22px;
    padding: 18px 0 8px 0;
    scroll-behavior: smooth;
}
.episode {
    flex: 0 0 210px;
    background: rgba(28,29,44,0.94);
    border-radius: 24px;
    overflow: hidden;
    cursor: pointer;
    transition: transform 0.23s cubic-bezier(.17,.67,.83,.67), box-shadow 0.18s;
    box-shadow: 0 2px 18px #0008;
    position: relative;
    border: 1.5px solid rgba(255,255,255,0.06);
}
.episode img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    border-radius: 24px 24px 0 0;
    filter: brightness(0.92) saturate(1.2);
    transition: filter 0.18s;
}
.episode-title {
    padding: 13px 10px 15px 10px;
    color: #fff;
    font-size: 1.08em;
    font-weight: 600;
    text-align: center;
    min-height: 48px;
    letter-spacing: 0.3px;
    text-shadow: 0 2px 8px #0004;
}
.episode:hover {
    transform: scale(1.08) translateY(-7px);
    box-shadow: 0 8px 32px #e5091440, 0 0 24px #fff2;
    border-color: #e50914;
}
.episode:hover img {
    filter: brightness(1.08) saturate(1.35) blur(0.5px);
}

/* Elenco (Cast) */
.cast {
    margin-top: 38px;
    text-align: center;
    display: flex;
    gap: 22px;
    justify-content: center;
    flex-wrap: wrap;
}
.cast .actor {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(36,37,54,0.56);
    border-radius: 24px;
    padding: 16px 18px 12px 18px;
    margin-bottom: 12px;
    box-shadow: 0 2px 16px #0003;
    border: 1px solid rgba(255,255,255,0.06);
    min-width: 120px;
    transition: background 0.15s, box-shadow 0.13s;
}
.cast .actor:hover {
    background: linear-gradient(90deg, #18181f 60%, #23243a 100%);
    box-shadow: 0 4px 32px #e5091430;
}
.cast .actor-img {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    object-fit: cover;
    box-shadow: 0 4px 18px #e5091440;
    margin-bottom: 10px;
    border: 2.5px solid #fff2;
}
.cast .actor-name {
    font-size: 1.08em;
    font-weight: bold;
    color: #fff;
    margin-bottom: 2px;
}
.cast .actor-character {
    font-size: 0.96em;
    color: #ff2e63;
    opacity: 0.85;
}

/* Responsividade */
@media (max-width: 900px) {
    .hero-content { max-width: 90vw; padding: 24px 8vw; }
    .episode { flex: 0 0 170px; }
}
@media (max-width: 600px) {
    .hero { min-height: 42vh; border-radius: 0 0 24px 24px; }
    .hero-content { padding: 18px 6vw; border-radius: 18px; }
    .hero img { max-width: 120px; border-radius: 18px; }
    .hero h1 { font-size: 1.5em; }
    .buttons { flex-direction: column; gap: 10px; }
    .season-buttons { flex-direction: column; gap: 8px; }
    .modal-content { padding: 12px 2vw; border-radius: 18px; }
    .episode { flex: 0 0 120px; border-radius: 14px; }
    .cast .actor { min-width: 80px; padding: 10px 6px; border-radius: 12px; }
    .cast .actor-img { width: 55px; height: 55px; border-radius: 50%; }
}

/* Scrollbar custom */
::-webkit-scrollbar {
    height: 10px;
    background: #23243a;
    border-radius: 8px;
}
::-webkit-scrollbar-thumb {
    background: linear-gradient(90deg, #e50914 40%, #ff2e63 100%);
    border-radius: 8px;
}
    </style>
</head>
<body>
<div class="hero">
    <div class="hero-content">
        <img src="<?php echo $poster; ?>" alt="<?php echo $title; ?>">
        <h1><?php echo $title; ?></h1>
        <p><?php echo $description; ?></p>
    </div>
</div>

<div class="container">
    <div class="season-buttons">
        <!-- Botones de temporada -->
        <?php foreach ($seasons as $season_number => $season_episodes): ?>
            <a href="?id=<?php echo $id; ?>&season=<?php echo $season_number; ?>" class="season-button <?php echo ($season_number == $selected_season) ? 'active' : ''; ?>">
                Temporada <?php echo $season_number; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="buttons">
        <button class="button" id="openModal">Ver episodios</button>
        <?php if ($youtube_trailer): ?>
            <a href="#" class="button" id="openTrailerModal">Ver trailer</a>
        <?php endif; ?>
    </div>

    <div class="cast">
        <?php echo $cast_list; ?>
    </div>
</div>

<!-- Modal para episodios -->
<div id="episodesModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeModal">&times;</span>
        <div class="episodes-carousel" id="modal-episodes-carousel">
            <!-- Los episodios del modal se mostrarán aquí -->
        </div>
    </div>
</div>

<!-- Modal para el trailer -->
<div id="trailerModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeTrailerModal">&times;</span>
        <iframe width='100%' height='400px' src='https://www.youtube.com/embed/<?php echo $youtube_trailer; ?>' frameborder='0' allow='autoplay; encrypted-media' allowfullscreen></iframe>
    </div>
</div>

<script>
    // Función para llenar episodios en el modal
    function loadSeason(seasonNumber) {
        const episodes = <?php echo json_encode($seasons); ?>[seasonNumber];
        const carousel = document.getElementById('modal-episodes-carousel');
        carousel.innerHTML = ''; // Limpiar el contenido del carrusel

        episodes.forEach(function(episode) {
            const playUrl = `video_player.php?id=${episode.id}&slug=series&ext=${episode.container_extension}&series_id=<?php echo $id; ?>`;
            const episodeHtml = `
                <div class="episode">
                    <a href="${playUrl}">
                        <img src="${episode.info.movie_image}" alt="${episode.title}">
                        <div class="episode-title">${episode.title || 'Sin título'}</div>
                    </a>
                </div>
            `;
            carousel.insertAdjacentHTML('beforeend', episodeHtml);
        });
    }

    // Obtener el modal
    var episodesModal = document.getElementById('episodesModal');
    var trailerModal = document.getElementById('trailerModal');
    var btn = document.getElementById('openModal');
    var closeBtn = document.getElementById('closeModal');
    var trailerBtn = document.getElementById('openTrailerModal');
    var closeTrailerBtn = document.getElementById('closeTrailerModal');

    // Abrir el modal al hacer clic en el botón
    btn.onclick = function() {
        episodesModal.style.display = 'flex';
        loadSeason(<?php echo $selected_season; ?>);
    }

    // Abrir el modal del trailer
    trailerBtn.onclick = function() {
        trailerModal.style.display = 'flex';
    }

    // Cerrar el modal al hacer clic en la 'X'
    closeBtn.onclick = function() {
        episodesModal.style.display = 'none';
    }

    closeTrailerBtn.onclick = function() {
        trailerModal.style.display = 'none';
    }

    // Cerrar el modal al hacer clic fuera del modal
    window.onclick = function(event) {
        if (event.target == episodesModal) {
            episodesModal.style.display = 'none';
        }
        if (event.target == trailerModal) {
            trailerModal.style.display = 'none';
        }
    }
</script>

</body>
</html>
