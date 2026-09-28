<?php
include "session.php";
include "config.php";
include "header.php";

// Função para obter dados da API via CURL
function getApiData($url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
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

// Categorias de filmes
$categoriesUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_categories";
$movie_categories = getApiData($categoriesUrl);
$initial_sections = array_slice($movie_categories, 0, 5);
$later_sections = array_slice($movie_categories, 5);

// Filmes em destaque (com poster/capa)
$moviesUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams";
$channel_api_movies = getApiData($moviesUrl);
shuffle($channel_api_movies);
$random_movies_info = [];
$max_movies = 5;
$movie_count = 0;
foreach ($channel_api_movies as $movie) {
    if ($movie_count >= $max_movies) break;
    $id = $movie["stream_id"];
    $movieInfoUrl = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_info&vod_id=" . $id;
    $channel_api_info = getApiData($movieInfoUrl);
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
    min-height: 0;
    display: block;
    transition: max-height 0.3s;
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
    padding: 0.3rem 0.7rem;
    font-size: 0.92rem;
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

/* --- CARDS PEQUENOS E BONITOS --- */
.grid-container {
    display: flex;
    gap: 14px;
    overflow-x: auto;
    padding: 1vw 2vw 2vw 2vw;
    scroll-snap-type: x mandatory;
    scrollbar-width: none; /* Firefox */
    -ms-overflow-style: none; /* IE e Edge */
}
.grid-container::-webkit-scrollbar {
    display: none; /* Chrome, Safari, Opera */
}
.grid-item {
    background: #181818;
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.13);
    border: 1px solid rgba(255,255,255,0.10);
    width: 110px;
    min-width: 110px;
    max-width: 110px;
    display: flex;
    flex-direction: column;
    align-items: center;
    transition: transform 0.21s cubic-bezier(.4,0,.2,1), box-shadow 0.22s;
    cursor: pointer;
    scroll-snap-align: start;
    padding: 0 0 10px 0;
    position: relative;
    overflow: hidden;
}
.grid-item img {
    width: 100%;
    height: 160px;
    object-fit: cover;
    border-radius: 12px 12px 0 0;
    margin-bottom: 8px;
    box-shadow: 0 1px 5px rgba(0,0,0,0.18);
    transition: filter 0.18s;
    filter: brightness(0.96) saturate(1.1);
}
.grid-item p {
    text-align: center;
    margin: 0 4px 6px 4px;
    font-size: 0.82rem;
    color: #fff;
    font-weight: 500;
    min-height: 1.7em;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
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
    transform: scale(1.05) translateY(-4px);
    box-shadow: 0 0 14px 3px #e5091444, 0 4px 14px 0 rgba(0,0,0,0.18);
    z-index: 2;
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

<!-- Seções iniciais de categorias -->
<?php
foreach ($initial_sections as $value) {
    $category_id = $value["category_id"];
    $category_name = $value["category_name"];
    echo "<div class='section-header'>
            <h2 class='section-title'>$category_name</h2>
            <a class='ver-mais-btn' href='movies.php?id=$category_id'>Ver Mais</a>
          </div>";
    $movies_api = getApiData($get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams&category_id=" . $category_id . "&limit=16");
    echo "<div class='grid-container horizontal-section'>";
    foreach ($movies_api as $movie) {
        $title = $movie["name"];
        $stream_id = $movie["stream_id"];
        $poster = $movie["stream_icon"] ?: "https://i.imgur.com/Mn7aXQD.jpg";
        $medialink = "Movie_description.php?id=" . $stream_id;
        echo "<div class='grid-item' tabindex='0'>
                <a href='$medialink'><img src='$poster' alt='$title' loading='lazy' /></a>
                <p>$title</p>
                <div class='quick-actions'></div>
              </div>";
    }
    echo "</div>";
}
?>

<!-- Seções que carregam sob demanda -->
<div id="lazy-sections"></div>
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
document.addEventListener("DOMContentLoaded", function () {
    updateFeaturedMovie();
});

// Lazy load das seções adicionais ao rolar
document.addEventListener("DOMContentLoaded", function() {
    let laterSections = <?php echo json_encode($later_sections); ?>;
    let lazySectionsContainer = document.getElementById('lazy-sections');
    let currentIndex = 0;
    function loadMoreSections() {
        for (let i = 0; i < 2; i++) {
            if (currentIndex >= laterSections.length) return;
            let section = laterSections[currentIndex];
            let category_id = section.category_id;
            let category_name = section.category_name;
            let sectionHTML = `
                <div class="section-header">
                    <h2 class="section-title">${category_name}</h2>
                    <a class="ver-mais-btn" href="movies.php?id=${category_id}">Ver Mais</a>
                </div>
                <div class="grid-container horizontal-section" id="carousel_${category_id}"></div>
            `;
            lazySectionsContainer.insertAdjacentHTML('beforeend', sectionHTML);
            fetch('<?php echo $get_dns; ?>/player_api.php?username=<?php echo $username; ?>&password=<?php echo $password; ?>&action=get_vod_streams&category_id=' + category_id + '&limit=16')
                .then(response => response.json())
                .then(movies_api => {
                    let carousel = document.getElementById('carousel_' + category_id);
                    movies_api.forEach(movie => {
                        let title = movie.name;
                        let stream_id = movie.stream_id;
                        let poster = movie.stream_icon || "img/offvs.png";
                        let medialink = "Movie_description.php?id=" + stream_id;
                        let movieHTML = `
                            <div class="grid-item" tabindex="0">
                                <a href="${medialink}"><img src="${poster}" alt="${title}" loading="lazy"></a>
                                <p>${title}</p>
                                <div class="quick-actions"></div>
                            </div>
                        `;
                        carousel.insertAdjacentHTML('beforeend', movieHTML);
                    });
                });
            currentIndex++;
        }
    }
    loadMoreSections();
    window.addEventListener('scroll', function() {
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 100) {
            loadMoreSections();
        }
    });
});
</script>
</body>
</html>
