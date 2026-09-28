<?php

include "session.php";
include "header.php"; // Incluir el menú
include "config.php";

$id = $_GET["id"];
$slug = $_GET["slug"];
$ext = isset($_GET["ext"]) ? $_GET["ext"] : "mp4";
$logo_url = "assets/img/logo.png"; // URL del logo de tu sitio

$audio_tracks = []; // Inicializamos un array para las pistas de audio

// Lógica para películas y series
if ($slug == "movie") {
    $video_url = $get_dns . "/movie/" . $username . "/" . $password . "/" . $id . "." . $ext;
    $mime_type = "video/mp4";  // Tipo para películas (MP4)
    
    // Obtener el nombre real de la película y las pistas de audio
    $movie_info = getXtreamData('get_vod_info', ['vod_id' => $id]);
    $content_name = $movie_info['info']['name'];  // Nombre de la película
    $audio_tracks = $movie_info['info']['audio'];  // Obtener las pistas de audio
} elseif ($slug == "series") {
    $video_url = $get_dns . "/series/" . $username . "/" . $password . "/" . $id . "." . $ext;
    $mime_type = "video/mp4";  // Tipo para series (MP4)

    // Obtener el nombre de la serie y episodio, y las pistas de audio
    $series_info = getXtreamData('get_series_info', ['series_id' => $_GET['series_id']]);
    $content_name = $series_info['info']['name'];  // Nombre de la serie
    $audio_tracks = $series_info['info']['audio'];  // Obtener las pistas de audio
}

// Función para obtener datos desde Xtream Codes API
function getXtreamData($endpoint, $params = []) {
    global $get_dns, $username, $password;
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=" . $endpoint;
    if (!empty($params)) {
        $url .= '&' . http_build_query($params);
    }
    // cURL em vez de file_get_contents: não depende de allow_url_fopen (que
    // em muitas hospedagens compartilhadas vem desligado por padrão).
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
        CURLOPT_TIMEOUT => 10,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clappr Player - Multiple Audio Tracks</title>
    <script src="https://cdn.jsdelivr.net/npm/clappr@latest/dist/clappr.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <style>
        /* Reset e base */
        html, body {
            margin: 0;
            padding: 0;
            width: 100vw;
            height: 100vh;
            min-height: 100vh;
            box-sizing: border-box;
            background: linear-gradient(135deg, #181c23 0%, #232a34 100%);
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            color: #fff;
            overflow: hidden;
        }

        /* Glassmorphism container */
        .video-player-container {
            width: 90vw;
            max-width: 1100px;
            height: 70vh;
            min-height: 400px;
            margin: auto;
            margin-top: 4vh;
            background: rgba(30, 34, 45, 0.6);
            box-shadow: 0 8px 40px 0 rgba(0,0,0,0.45), 0 1.5px 10px 0 #1a1a1a;
            border-radius: 36px;
            backdrop-filter: blur(18px) saturate(120%);
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: stretch;
            overflow: hidden;
            border: 2px solid rgba(255,255,255,0.07);
        }

        /* Player ocupa o centro */
        #player {
            width: 100%;
            height: 100%;
            border-radius: 24px;
            overflow: hidden;
            box-shadow: 0 4px 32px 0 rgba(0,0,0,0.25);
            z-index: 1;
        }

        /* Glass info */
        .video-info {
            position: absolute;
            top: 32px;
            left: 36px;
            background: rgba(28,32,40,0.65);
            padding: 18px 32px 14px 32px;
            border-radius: 24px;
            box-shadow: 0 4px 24px 0 rgba(0,0,0,0.18);
            font-size: 1.45rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            color: #fff;
            text-shadow: 0 2px 8px #000a;
            z-index: 3;
            border: 1.5px solid rgba(255,255,255,0.08);
            backdrop-filter: blur(6px);
            transition: background 0.3s;
        }
        .video-info h2 {
            margin: 0;
            font-size: 1.4em;
            font-family: 'Inter', 'Segoe UI', Arial, sans-serif;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: #fff;
        }

        /* Botão de voltar com animação e glassmorphism */
        .back_button {
            position: absolute;
            top: 28px;
            left: 28px;
            width: 48px;
            height: 48px;
            background: rgba(24, 34, 44, 0.72);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            box-shadow: 0 2px 12px 0 #0005;
            text-decoration: none;
            color: #fff;
            font-size: 1.65rem;
            z-index: 10;
            border: 1.5px solid rgba(255,255,255,0.10);
            transition: background 0.25s, box-shadow 0.25s, color 0.25s;
            backdrop-filter: blur(4px);
        }
        .back_button:hover {
            background: linear-gradient(135deg, #ff0055 0%, #ff6a00 100%);
            color: #fff;
            box-shadow: 0 4px 24px 0 #ff005544;
        }

        /* Responsividade */
        @media (max-width: 900px) {
            .video-player-container {
                width: 98vw;
                height: 60vh;
                min-height: 320px;
                border-radius: 26px;
            }
            .video-info {
                top: 14px;
                left: 14px;
                right: 14px;
                padding: 10px 18px;
                border-radius: 14px;
            }
            .back_button { top: 10px; left: 10px; width: 38px; height: 38px; font-size: 1.1rem; }
        }
        @media (max-width: 600px) {
            .video-player-container {
                width: 100vw;
                height: 54vh;
                min-height: 180px;
                border-radius: 0;
                margin-top: 0;
            }
            .video-info {
                top: 6px;
                left: 6px;
                right: 6px;
                padding: 7px 12px;
                border-radius: 8px;
                font-size: 1.1rem;
            }
            .video-info h2 { font-size: 1.1em; }
            .back_button { width: 32px; height: 32px; font-size: 1rem; }
        }

        /* Scrollbar dark */
        ::-webkit-scrollbar {
            width: 8px;
            background: #232a34;
        }
        ::-webkit-scrollbar-thumb {
            background: #2c3440;
            border-radius: 8px;
        }
    </style>
</head>
<body>

    <div class="video-player-container">
        <a class="back_button" href="
            <?php if ($slug == 'movie') {
                echo 'Movie_description.php?id=' . $id;
            } elseif ($slug == 'series') {
                echo 'seriesvideo.php?id=' . $_GET['series_id'];
            } ?>">
            <i class="fa fa-arrow-left" aria-hidden="true"></i>
        </a>

        <div class="video-info">
            <h2><?php echo $content_name; ?></h2>
        </div>

        <!-- Clappr player -->
        <div id="player"></div>
    </div>

    <script>
        // Inicializar Clappr Player
        var player = new Clappr.Player({
            source: "<?php echo $video_url; ?>",
            mimeType: "<?php echo $mime_type; ?>",
            parentId: "#player",
            autoPlay: true,
            mute: false,
            height: '100%',
            width: '100%',
        });

        player.on(Clappr.Events.PLAYER_READY, function() {
            // Tracks de áudio (console)
            var audioTracks = player.core.getCurrentContainer().playback.el.audioTracks;
            if (audioTracks && audioTracks.length > 1) {
                for (let i = 0; i < audioTracks.length; i++) {
                    console.log('Pista de audio:', audioTracks[i].label || 'Pista ' + i, 'Idioma:', audioTracks[i].language);
                }
                audioTracks[1].enabled = true;
            } else {
                console.log('No hay múltiples pistas de audio');
            }
        });

        // Recuperação automática (igual ao canal ao vivo): se travar ou der
        // erro, tenta reconectar sozinho em vez de deixar parado na tela.
        var streamSrc = "<?php echo $video_url; ?>";
        var streamMime = "<?php echo $mime_type; ?>";
        var reconnectAttempts = 0;
        var maxReconnectAttempts = 5;
        var reconnectTimer = null;
        var lastProgressAt = Date.now();

        function attemptReconnect() {
            if (reconnectTimer) return; // já tem uma tentativa agendada
            reconnectAttempts++;
            if (reconnectAttempts > maxReconnectAttempts) {
                return; // desiste após várias tentativas, mostra o erro normalmente
            }
            var delay = Math.min(2000 * reconnectAttempts, 8000);
            reconnectTimer = setTimeout(function() {
                reconnectTimer = null;
                lastProgressAt = Date.now();
                try {
                    player.load(streamSrc, streamMime, true);
                } catch (e) {
                    console.log('Falha ao tentar reconectar:', e);
                }
            }, delay);
        }

        player.on(Clappr.Events.PLAYER_ERROR, attemptReconnect);

        player.on(Clappr.Events.PLAYER_TIMEUPDATE, function() {
            lastProgressAt = Date.now();
        });

        player.on(Clappr.Events.PLAYER_PLAY, function() {
            reconnectAttempts = 0;
            lastProgressAt = Date.now();
        });

        // Vigia proativo: se o vídeo travar (parar de avançar) por alguns
        // segundos, reconecta antes mesmo do player acusar erro de verdade.
        var STALL_TIMEOUT_MS = 7000;
        setInterval(function() {
            if (Date.now() - lastProgressAt > STALL_TIMEOUT_MS) {
                lastProgressAt = Date.now(); // evita repetir a cada tick
                attemptReconnect();
            }
        }, 2000);
    </script>

</body>
</html>
