<?php
// Endpoint usado pelo clique nos ícones de plataforma (Disney, Netflix, HBO
// Max, Prime, Marvel) na Home. Busca a lista COMPLETA de filmes ou séries
// de uma categoria específica direto na API Xtream — diferente da Home, que
// só mostra 16 itens aleatórios, aqui vem tudo da categoria escolhida.
include "session.php";
include "config.php";

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$catId = preg_replace('/[^0-9]/', '', (string) ($_GET['cat'] ?? ''));

if ($catId === '' || !in_array($type, ['movies', 'series'], true)) {
    echo json_encode(['items' => [], 'error' => 'Parâmetros inválidos']);
    exit;
}

function ajax_platform_api_get($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    if (curl_errno($ch)) {
        curl_close($ch);
        return [];
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($httpCode !== 200) {
        return [];
    }
    $data = json_decode($response, true);
    return is_array($data) ? $data : [];
}

$items = [];

if ($type === 'movies') {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_vod_streams&category_id=" . $catId;
    $data = ajax_platform_api_get($url);
    foreach ($data as $value) {
        $items[] = [
            'title' => $value['name'] ?? '',
            'link' => "Movie_description.php?id=" . ($value['stream_id'] ?? ''),
            'poster' => $value['stream_icon'] ?? "img/offvs.png",
        ];
    }
} else {
    $url = $get_dns . "/player_api.php?username=" . $username . "&password=" . $password . "&action=get_series&category_id=" . $catId;
    $data = ajax_platform_api_get($url);
    foreach ($data as $value) {
        $items[] = [
            'title' => $value['name'] ?? '',
            'link' => "seriesvideo.php?id=" . ($value['series_id'] ?? ''),
            'poster' => $value['cover'] ?? "img/offvs.png",
        ];
    }
}

echo json_encode(['items' => $items]);