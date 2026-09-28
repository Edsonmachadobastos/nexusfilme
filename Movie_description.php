<?php

include "session.php";
include "header.php"; // Incluir el menú
require_once __DIR__ . '/includes/planos_helpers.php';

$id = $_GET["id"];
$tmdb_api_key = pwb_load_config()['tmdb_api_key']; // Configurável em admin.php

// Definir el User-Agent
$userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

// Función para realizar una solicitud API usando CURL
function getApiData($url) {
    global $userAgent;
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

// Obtener la información desde Xtream Codes
$xtream_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_info&vod_id=" . $id;
$channel_api = getApiData($xtream_url);
$channel_api2 = $channel_api["info"];
$channel_api3 = $channel_api["movie_data"];

$title = $channel_api2["name"];
$description = $channel_api2["plot"];
$youtube_trailer = $channel_api2["youtube_trailer"];
$movie_genre = $channel_api2["genre"];
$releasedate = $channel_api2["releasedate"];
$movie_rating = $channel_api2["rating"];
$container_extension = $channel_api3["container_extension"];
$backdrop = $channel_api2["backdrop_path"][0] ?? '';

// Verificar si el poster es válido, de lo contrario usar uno por defecto
$img = isset($channel_api2["movie_image"]) && !empty($channel_api2["movie_image"]) 
    ? $channel_api2["movie_image"] 
    : "https://i.imgur.com/Mn7aXQD.jpg"; // Poster por defecto si no hay imagen disponible

// Obtener el ID de la película en TMDb
$tmdb_url = "https://api.themoviedb.org/3/search/movie?api_key={$tmdb_api_key}&query=" . urlencode($title);
$tmdb_movie_data = getApiData($tmdb_url);
$tmdb_movie_id = $tmdb_movie_data['results'][0]['id'] ?? null;

// Obtener el reparto desde TMDb
$cast_list = '';
if ($tmdb_movie_id) {
    $tmdb_cast_url = "https://api.themoviedb.org/3/movie/{$tmdb_movie_id}/credits?api_key={$tmdb_api_key}";
    $cast_data = getApiData($tmdb_cast_url);
    $cast = $cast_data['cast'] ?? [];
    $max_cast = 5; // Limitar a los 5 actores principales

    foreach (array_slice($cast, 0, $max_cast) as $actor) {
        $actor_img = "https://image.tmdb.org/t/p/w200" . ($actor["profile_path"] ?? '');
        $actor_name = $actor["name"];
        $character = $actor["character"];
        
        // Si no hay foto del actor, usamos una imagen por defecto
        if (empty($actor["profile_path"])) {
            $actor_img = "https://i.imgur.com/placeholder.png"; // Reemplaza con tu propia imagen de placeholder
        }
        
        $cast_list .= "
            <div class='actor'>
                <img src='{$actor_img}' alt='{$actor_name}'>
                <div class='actor-name'>{$actor_name}</div>
                <div class='actor-character'>as {$character}</div>
            </div>
        ";
    }
}

echo "
<html lang=\"en\">
<head>
    <meta charset=\"utf-8\">
    <meta http-equiv=\"X-UA-Compatible\" content=\"IE=edge\">
    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">
    <link rel=\"icon\" href=\"assets/img/favicon.ico\" type=\"image/ico\">
    <title>{$sitename} - Movies On Demand</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700&display=swap');
        body {
            font-family: 'Montserrat', Arial, sans-serif;
            background: #000 !important;
            min-height: 100vh;
            overflow-x: hidden;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: url('{$backdrop}') no-repeat center center fixed;
            background-size: cover;
            filter: blur(18px) brightness(0.6) saturate(1.2);
            z-index: -2;
        }
        body::after {
            content: '';
            position: fixed;
            top: 0; left: 0;
            width: 100vw; height: 100vh;
            background: linear-gradient(120deg,rgba(16,19,26,0.96) 60%,rgba(30,33,44,0.7) 100%);
            z-index: -1;
        }
        .content-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 32px 10px;
            box-sizing: border-box;
        }
        .movie-container {
            display: flex;
            flex-wrap: wrap;
            gap: 40px;
            background: rgba(30,33,44,0.67);
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.45);
            border-radius: 32px;
            backdrop-filter: blur(18px) saturate(120%);
            border: 1.5px solid rgba(255,255,255,0.09);
            padding: 40px 32px;
            max-width: 1080px;
            width: 100%;
            animation: fadeIn 1.2s cubic-bezier(.4,0,.2,1);
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(40px);}
            to { opacity: 1; transform: none;}
        }
        .poster {
            flex: 1 1 280px;
            max-width: 320px;
            min-width: 220px;
            display: flex;
            align-items: flex-start;
            justify-content: center;
        }
        .poster img {
            width: 100%;
            max-width: 320px;
            border-radius: 24px;
            box-shadow: 0 8px 32px 0 rgba(0,0,0,0.75), 0 0 0 6px rgba(229,9,20,0.07);
            transition: transform .35s cubic-bezier(.4,0,.2,1), box-shadow .35s;
            background: #181b23;
        }
        .poster img:hover {
            transform: scale(1.04) rotate(-1deg);
            box-shadow: 0 12px 48px 0 rgba(229,9,20,0.18), 0 0 0 10px rgba(255,255,255,0.12);
        }
        .movie-details {
            flex: 2 1 400px;
            min-width: 270px;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
        }
        .movie-details h1 {
            font-size: 2.7rem;
            font-weight: 700;
            margin-bottom: 16px;
            letter-spacing: 0.01em;
            color: #fff;
            text-shadow: 0 4px 24px rgba(229,9,20,0.08);
        }
        .genre-release {
            margin-bottom: 10px;
            font-size: 1.07rem;
            color: #b7b9c6;
            letter-spacing: .02em;
        }
        .rating {
            margin-bottom: 18px;
            font-size: 1rem;
            color: #FFD700;
            font-weight: 600;
            text-shadow: 0 2px 8px rgba(0,0,0,0.18);
        }
        .movie-details p {
            font-size: 1.13rem;
            color: #d6d7e0;
            margin-bottom: 24px;
            line-height: 1.6;
            max-width: 600px;
            text-shadow: 0 2px 8px rgba(0,0,0,0.11);
        }
        .buttons {
            display: flex;
            gap: 16px;
            margin-bottom: 28px;
        }
        .button-primary {
            background: linear-gradient(90deg, #e50914 60%, #ff2a68 100%);
            color: #fff;
            padding: 14px 32px;
            border: none;
            border-radius: 18px;
            cursor: pointer;
            font-size: 1.18rem;
            font-weight: 700;
            letter-spacing: .02em;
            box-shadow: 0 2px 12px 0 rgba(229,9,20,0.18);
            transition: background 0.25s, box-shadow 0.25s, transform 0.25s;
            text-decoration: none;
            outline: none;
            position: relative;
        }
        .button-primary:after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 18px;
            box-shadow: 0 0 18px 2px #e50914, 0 0 4px 0 #fff;
            opacity: 0;
            transition: opacity .22s;
            pointer-events: none;
        }
        .button-primary:hover, .button-primary:focus {
            background: linear-gradient(90deg, #ff2a68 40%, #e50914 100%);
            box-shadow: 0 6px 24px 0 rgba(229,9,20,0.32), 0 0 0 2px #fff3;
            transform: translateY(-2px) scale(1.03);
        }
        .button-primary:hover:after, .button-primary:focus:after {
            opacity: 0.18;
        }
        .trailer-button {
            background: linear-gradient(90deg, #23243a 60%, #3c3f5c 100%);
            color: #fff;
            margin-left: 0;
            border: 1.5px solid #e50914;
            box-shadow: 0 2px 12px 0 rgba(229,9,20,0.09);
        }
        .trailer-button:hover, .trailer-button:focus {
            background: linear-gradient(90deg, #3c3f5c 40%, #23243a 100%);
            border-color: #ff2a68;
        }
        .cast {
            margin-top: 18px;
            display: flex;
            gap: 18px;
            flex-wrap: wrap;
        }
        .cast .actor {
            background: rgba(34,37,49,0.58);
            border-radius: 18px;
            padding: 12px 10px 10px 10px;
            min-width: 110px;
            max-width: 120px;
            box-shadow: 0 2px 12px 0 rgba(229,9,20,0.04);
            transition: transform .22s, box-shadow .22s;
            text-align: center;
            position: relative;
        }
        .cast .actor:hover {
            transform: translateY(-6px) scale(1.07);
            box-shadow: 0 4px 24px 0 rgba(229,9,20,0.16), 0 0 0 2px #fff3;
            background: rgba(229,9,20,0.12);
        }
        .cast .actor img {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 8px;
            border: 2px solid #e50914;
            box-shadow: 0 2px 8px 0 rgba(229,9,20,0.08);
            background: #23243a;
        }
        .actor-name {
            font-size: 1rem;
            font-weight: 600;
            color: #fff;
            margin-bottom: 2px;
        }
        .actor-character {
            font-size: .95rem;
            color: #b7b9c6;
        }
        /* Modal estilo glassmorphism */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0; top: 0;
            width: 100vw; height: 100vh;
            background: rgba(10,12,20,0.82);
            backdrop-filter: blur(12px) saturate(120%);
            justify-content: center;
            align-items: center;
            animation: fadeIn .6s;
        }
        .modal-content {
            background: rgba(34,37,49,0.85);
            border-radius: 28px;
            padding: 0;
            border: 1.5px solid rgba(255,255,255,0.11);
            box-shadow: 0 8px 32px 0 rgba(229,9,20,0.18);
            width: 92vw; max-width: 720px;
            overflow: hidden;
            position: relative;
        }
        .modal-content iframe {
            display: block;
            width: 100%; height: 360px;
            border: none;
            border-radius: 0 0 28px 28px;
            background: #181b23;
        }
        .close {
            color: #fff;
            font-size: 2.2rem;
            font-weight: bold;
            position: absolute;
            top: 12px; right: 24px;
            cursor: pointer;
            z-index: 2;
            transition: color .2s;
        }
        .close:hover, .close:focus {
            color: #e50914;
            text-shadow: 0 0 8px #ff2a68;
        }
        /* Responsivo */
        @media (max-width: 900px) {
            .movie-container {
                flex-direction: column;
                align-items: center;
                padding: 32px 8px;
            }
            .poster { margin: 0 0 24px 0; }
            .movie-details { min-width: 0; }
        }
        @media (max-width: 600px) {
            .movie-container {
                padding: 18px 2px;
                border-radius: 18px;
            }
            .poster img { border-radius: 14px; }
            .movie-details h1 { font-size: 1.5rem; }
            .modal-content iframe { height: 200px; }
            .cast .actor { min-width: 84px; max-width: 90px; padding: 8px 4px 6px 4px;}
            .cast .actor img { width: 52px; height: 52px; }
        }
    </style>
</head>
<body>
<div class=\"content-wrapper\">
    <div class=\"movie-container\">
        <div class=\"poster\">
            <img src=\"{$img}\" alt=\"{$title}\">
        </div>
        <div class=\"movie-details\">
            <h1>{$title}</h1>
            <div class=\"genre-release\">
                {$movie_genre} | {$releasedate}
            </div>
            <div class=\"rating\">Avaliação: {$movie_rating}/10</div>
            <p>{$description}</p>
            <div class=\"buttons\">
                <a href=\"video_player.php?id={$id}&slug=movie&ext={$container_extension}\" class=\"button-primary\">Assista agora</a>
                <button class=\"button-primary trailer-button\" id=\"openModal\">Assista ao trailer</button>
            </div>
            <div class=\"cast\">
                {$cast_list}
            </div>
        </div>
    </div>
</div>
<!-- Modal para o trailer -->
<div id=\"trailerModal\" class=\"modal\">
    <div class=\"modal-content\">
        <span class=\"close\" id=\"closeModal\">&times;</span>
        <iframe width='100%' height='400px' src='https://www.youtube.com/embed/{$youtube_trailer}' allow='autoplay; encrypted-media' allowfullscreen></iframe>
    </div>
</div>
<script>
    var modal = document.getElementById('trailerModal');
    var btn = document.getElementById('openModal');
    var span = document.getElementById('closeModal');
    btn.onclick = function() { modal.style.display = 'flex'; }
    span.onclick = function() { modal.style.display = 'none'; }
    window.onclick = function(event) { if (event.target == modal) modal.style.display = 'none'; }
</script>
</body>
</html>
";
?>
