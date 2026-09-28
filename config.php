<?php
//DONT MODIFY BELOW//

// Definindo o caminho do arquivo JSON
$json_file = "./includes/dns/dns.json";

// Verificando se o diretório existe; caso contrário, criando-o
if (!is_dir('./includes/dns')) {
    mkdir('./includes/dns', 0755, true);
}

// Verificando a existência do arquivo JSON e criando-o, se necessário
if (!file_exists($json_file)) {
    $json = '{
        "default_color": "black",
        "dns_number": "no",
        "server1_name": "",
        "server1": "",
        "server2_name": "",
        "server2": "",
        "server3_name": "",
        "server3": "",
        "server4_name": "",
        "server4": "",
        "server5_name": "",
        "server5": "",
        "server6_name": "",
        "server6": ""
    }';
    file_put_contents($json_file, $json);
    $jsondata = $json;
} else {
    $jsondata = file_get_contents($json_file);
}

// Decodificando o JSON
$json = json_decode($jsondata, true);

// Verificando se o JSON foi decodificado corretamente
if ($json !== null) {
    $sitename = $json['sitename'] ?? '';
    $adminU = $json['username'] ?? '';
    $adminP = $json['password'] ?? '';
    $default_color = $json['default_color'] ?? 'black';
} else {
    // Definindo valores padrão caso o JSON seja inválido
    $sitename = '';
    $adminU = '';
    $adminP = '';
    $default_color = 'black';
}

// Verificação de HTTPS
if ((isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1)) ||
    (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) {
    $protocol = 'https://';
} else {
    $protocol = 'http://';
}

// Definindo o diretório base
$basedir = $protocol . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, strrpos($_SERVER['REQUEST_URI'], '/'));
$get_dir = $basedir;
