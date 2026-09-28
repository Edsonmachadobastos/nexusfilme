<?php
session_start();

// Verificação de sessão ativa
if (!isset($_SESSION["username"]) || !isset($_SESSION["password"]) || !isset($_SESSION["server_url"])) {
    header("Location: ./login.php");
    exit;
}

// Carrega dados da sessão
$get_dns    = $_SESSION["server_url"];
$username   = $_SESSION["username"];
$password   = $_SESSION["password"];

// Revalida com a API apenas a cada 5 minutos (evita erro em servidores lentos)
$now = time();
$lastCheck = $_SESSION['last_api_check'] ?? 0;

if (($now - $lastCheck) > 300) {
    $authUrl   = "{$get_dns}/player_api.php?username={$username}&password={$password}";
    $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36';

    // cURL em vez de file_get_contents: não depende de allow_url_fopen
    // (que em muitas hospedagens compartilhadas vem desligado por padrão).
    $ch = curl_init($authUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ["User-Agent: $userAgent"],
        CURLOPT_TIMEOUT => 8,
        CURLOPT_CONNECTTIMEOUT => 8,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $api = $response ? json_decode($response, true) : null;

    // Se a API responder e recusar → derruba sessão
    if ($api !== null && (!isset($api['user_info']['auth']) || $api['user_info']['auth'] != 1 || ($api['user_info']['status'] ?? '') !== "Active")) {
        session_destroy();
        header("Location: ./login.php?err=auth");
        exit;
    }

    // Se a API não responder (timeout/erro de rede) → mantém sessão, não derruba
    $_SESSION['last_api_check'] = $now;
    if ($api !== null) {
        $_SESSION['user_info'] = $api['user_info'] ?? [];
    }
}

echo "\n";
?>
