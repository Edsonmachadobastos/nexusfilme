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
        curl_close($ch);
        return [];
    }
    curl_close($ch);
    if ($httpCode !== 200) {
        return [];
    }
    return json_decode($response, true);
}

// Parâmetros
$order = isset($_GET['order']) ? $_GET['order'] : 'default';
$category_id = isset($_GET['id']) ? $_GET['id'] : null;

// Busca categorias da API
$categories_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series_categories";
$categories = getApiData($categories_url);

// Busca nome da categoria selecionada
$category_name = "Todas as Séries";
if ($category_id && $category_id !== 'recently_added') {
    foreach ($categories as $cat) {
        if ($cat['category_id'] == $category_id) {
            $category_name = $cat['category_name'];
            break;
        }
    }
} elseif ($category_id === 'recently_added') {
    $category_name = "Adicionados Recentemente";
}

// Busca séries da categoria selecionada
if ($category_id === 'recently_added') {
    $series_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series&limit=30";
    $series_api = getApiData($series_url);
} elseif ($category_id) {
    $series_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series&category_id=" . $category_id;
    $series_api = getApiData($series_url);
} else {
    $series_url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series";
    $series_api = getApiData($series_url);
}

// Ordenação extra (A-Z, recentes)
if ($order == 'recent') {
    usort($series_api, function($a, $b) {
        return strtotime($b['added']) - strtotime($a['added']);
    });
} elseif ($order == 'az') {
    usort($series_api, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title><?php echo htmlspecialchars($category_name); ?> - Séries</title>
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet" />
<style>
/* Coloque aqui o seu CSS premium e responsivo (igual aos exemplos anteriores) */
body {
    margin: 0;
    padding: 0;
    background: #000 !important;
    color: #fff;
    font-family: 'Montserrat', 'Roboto', Arial, sans-serif;
    min-height: 100vh;
}
.content-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 2vw 2vw 4vw 2vw;
}
.header-section {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    padding: 24px 32px;
    border-radius: 28px;
    background: linear-gradient(90deg, #1a2232 80%, #232c43 100%);
    position: relative;
    overflow: hidden;
}
.heading {
    font-size: 2.1rem;
    font-weight: 700;
    color: #fff;
    letter-spacing: 0.03em;
    margin: 0;
    text-shadow: 0 2px 16px #00eaff33, 0 1px 0 #0007;
}
.header-image {
    width: 160px !important;
    height: 80px !important;
    object-fit: contain;
    border-radius: 18px;
    margin-left: 32px;
    background: rgba(0,0,0,0.12);
}
.sort-container {
    display: flex;
    align-items: center;
    margin-bottom: 28px;
    padding: 18px 24px;
    border-radius: 20px;
    gap: 16px;
    background: linear-gradient(90deg, #232c43 60%, #1a2232 100%);
}
.sort-label {
    font-size: 1.05rem;
    font-weight: 600;
    color: #00eaff;
    letter-spacing: 0.01em;
}
.custom-select {
    position: relative;
    width: 210px;
}
.custom-select select {
    width: 100%;
    padding: 12px 38px 12px 14px;
    font-size: 1rem;
    color: #fff;
    background: rgba(30,40,60,0.85);
    border: 1.5px solid #00eaff55;
    border-radius: 12px;
    appearance: none;
    outline: none;
    transition: border 0.2s, box-shadow 0.2s;
    font-family: inherit;
    font-weight: 500;
    box-shadow: 0 1px 6px 0 #00eaff11;
}
.list {
    display: flex;
    flex-wrap: wrap;
    gap: 32px 24px;
    justify-content: center;
    list-style: none;
    padding: 0;
    margin: 0;
}
.list_series {
    width: 180px;
    min-width: 160px;
    max-width: 200px;
    text-align: center;
    background: rgba(30,40,60,0.82);
    border-radius: 24px;
    padding: 18px 10px 12px 10px;
    margin: 0;
    box-shadow: 0 6px 18px 0 #00eaff22, 0 1.5px 8px 0 #0005;
    transition: transform 0.28s cubic-bezier(.4,2.2,.2,1), box-shadow 0.3s, background 0.3s;
    position: relative;
    overflow: visible;
    cursor: pointer;
    border: 1.5px solid transparent;
}
.list_series:hover {
    transform: scale(1.08) translateY(-8px);
    background: linear-gradient(135deg, #1a2232 70%, #00eaff22 100%);
    box-shadow: 0 12px 32px 0 #00eaff44, 0 0 16px #fff3;
    border: 1.5px solid #00eaff88;
    z-index: 2;
}
.card-image-container {
    position: relative;
    width: 140px;
    height: 204px;
    margin: 0 auto;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 2px 16px #00eaff22, 0 1px 4px #0004;
    background: #10151e;
    transition: box-shadow 0.3s;
}
.lazy {
    width: 140px !important;
    height: 204px !important;
    object-fit: cover;
    border-radius: 16px;
    filter: blur(8px);
    transition: filter 0.4s, transform 0.33s cubic-bezier(.4,2.2,.2,1);
    display: block;
    margin: 0 auto;
    background: #222;
}
.lazy-loaded {
    filter: blur(0);
}
.list_series:hover .lazy {
    transform: scale(1.09) rotate(-1deg);
    filter: blur(0) brightness(1.06) saturate(1.25) drop-shadow(0 0 10px #00eaff66);
}
.card-hover-desc {
    position: absolute;
    left: 0; right: 0; bottom: -100%;
    background: linear-gradient(0deg, #0F171Eee 80%, #0F171E00 100%);
    color: #fff;
    font-size: 1.04rem;
    font-weight: 500;
    letter-spacing: 0.01em;
    padding: 18px 10px 8px 10px;
    opacity: 0;
    transition: bottom 0.26s cubic-bezier(.4,2.2,.2,1), opacity 0.24s;
    border-radius: 0 0 16px 16px;
    pointer-events: none;
    z-index: 2;
    text-shadow: 0 2px 8px #000c;
}
.list_series:hover .card-hover-desc {
    bottom: 0;
    opacity: 1;
}
.list_series p {
    font-size: 1.06rem;
    font-weight: 600;
    color: #E1E9F1;
    margin-top: 14px;
    margin-bottom: 0;
    text-overflow: ellipsis;
    white-space: nowrap;
    overflow: hidden;
    max-width: 140px;
    margin-left: auto;
    margin-right: auto;
    letter-spacing: 0.01em;
    text-shadow: 0 1px 4px #0007, 0 0 8px #00eaff22;
}
@media (max-width: 900px) {
    .content-container { padding: 10px 2vw; }
    .header-section, .sort-container { flex-direction: column; align-items: flex-start; padding: 14px 6px; gap: 10px; }
    .header-image { margin-left: 0; margin-top: 10px; width: 110px !important; height: 50px !important; }
    .heading { font-size: 1.15rem; }
    .list { gap: 16px 6px; }
    .list_series { width: 44vw; min-width: 110px; max-width: 150px; padding: 8px 2px 8px 2px; border-radius: 16px; }
    .card-image-container, .lazy { width: 90vw !important; max-width: 120px !important; height: 160px !important; border-radius: 10px; }
    .list_series p { font-size: 0.93rem; max-width: 120px; }
}
@media (max-width: 600px) {
    .content-container { padding: 0 0 10px 0; }
    .header-section, .sort-container { padding: 8px 4px; border-radius: 12px; }
    .header-section { flex-direction: column; align-items: center; gap: 8px; margin-bottom: 18px; }
    .header-image { width: 90px !important; height: 38px !important; margin-top: 4px; }
    .heading { font-size: 1.01rem; text-align: center; margin-bottom: 2px; }
    .sort-container { margin-bottom: 10px; gap: 6px; }
    .custom-select { width: 100%; min-width: 120px; }
    .custom-select select { font-size: 0.98rem; padding: 10px 32px 10px 10px; }
    .list { gap: 10px 2vw; justify-content: center; }
    .list_series { width: 90vw; min-width: 0; max-width: 98vw; padding: 8px 0 8px 0; border-radius: 14px; margin: 0 auto; }
    .card-image-container, .lazy { width: 86vw !important; max-width: 320px !important; height: 124vw !important; max-height: 440px !important; border-radius: 9px; }
    .list_series p { font-size: 1.08rem; max-width: 90vw; text-align: center; margin-top: 8px; }
    .card-hover-desc { font-size: 0.95rem; padding: 10px 6px 6px 6px; border-radius: 0 0 10px 10px; }
}
</style>
</head>
<body>
<div class="content-container">

    <div class="header-section">
        <div>
            <h4 class="heading">Séries sob demanda - <?php echo htmlspecialchars($category_name); ?></h4>
        </div>
        <img class="header-image" src="assets/img/logo.png" alt="Logo" width="160" height="80">
    </div>

    <!-- SELECT DINÂMICO -->
    <div class="sort-container">
        <label for="order-select" class="sort-label">Plataforma: </label>
        <div class="custom-select">
            <select id="order-select" onchange="sortSeries()">
                <option value="recently_added"<?php if ($category_id === 'recently_added') echo ' selected'; ?>>Adicionados Recentemente</option>
                <?php
                foreach ($categories as $cat) {
                    $selected = ($category_id == $cat['category_id']) ? ' selected' : '';
                    echo '<option value="' . htmlspecialchars($cat['category_id']) . '"' . $selected . '>' . htmlspecialchars($cat['category_name']) . '</option>';
                }
                ?>
            </select>
        </div>
        <label for="order-type" class="sort-label" style="margin-left:20px;">Ordenar: </label>
        <div class="custom-select">
            <select id="order-type" onchange="orderType()">
                <option value="default"<?php if ($order == 'default') echo ' selected'; ?>>Padrão</option>
                <option value="recent"<?php if ($order == 'recent') echo ' selected'; ?>>Adicionados recentemente</option>
                <option value="az"<?php if ($order == 'az') echo ' selected'; ?>>A-Z</option>
            </select>
        </div>
    </div>

    <div class="list">
        <?php
        if (!empty($series_api)) {
            foreach ($series_api as $value2) {
                $title2 = $value2["name"];
                $stream_id2 = $value2["series_id"];
                $medialink2 = "seriesvideo.php?id=" . $stream_id2;
                $img2 = $value2["cover"];
                $poster = (!empty($img2) && $img2 != "https://image.tmdb.org/t/p/w600_and_h900_bestv2") ? $img2 : "https://i.imgur.com/Mn7aXQD.jpg";
                echo '<li class="list_series" medianame="' . htmlspecialchars($title2) . '" medialink="' . htmlspecialchars($medialink2) . '">
                    <a href="' . htmlspecialchars($medialink2) . '">
                        <div class="card-image-container">
                            <img class="lazy" src="' . htmlspecialchars($poster) . '" alt="' . htmlspecialchars($title2) . '" width="140" height="204" loading="lazy">
                            <div class="card-hover-desc">
                                <span>' . htmlspecialchars($title2) . '</span>
                            </div>
                        </div>
                    </a>
                    <p>' . htmlspecialchars($title2) . '</p>
                </li>';
            }
        } else {
            echo '<p style="color:#fff;text-align:center;width:100%;">Nenhuma série encontrada nesta plataforma.</p>';
        }
        ?>
    </div>
</div>

<script>
function sortSeries() {
    var id = document.getElementById('order-select').value;
    var order = document.getElementById('order-type').value;
    window.location.href = "?id=" + id + "&order=" + order;
}
function orderType() {
    sortSeries();
}
// Lazy blur effect
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('img.lazy').forEach(function(img) {
        if (img.complete) {
            img.classList.add('lazy-loaded');
        } else {
            img.addEventListener('load', function() {
                img.classList.add('lazy-loaded');
            });
        }
    });
});
</script>
</body>
</html>
