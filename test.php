<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conexión Xtream Codes</title>
    <style>
        /* Estilos generales con fondo oscuro */
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background: #1e1e1e;
            color: #e0e0e0;
            margin: 0;
        }

        /* Contenedor principal */
        .container {
            background-color: #333;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.6);
            width: 100%;
            max-width: 500px;
            text-align: center;
        }

        /* Estilos del formulario */
        form, .logout-form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        input[type="text"],
        input[type="password"],
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
        }

        input[type="text"],
        input[type="password"] {
            background: #444;
            color: #e0e0e0;
        }

        input[type="submit"] {
            background-color: #007acc;
            color: #fff;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: bold;
        }

        input[type="submit"]:hover {
            background-color: #005a99;
        }

        .logout-button {
            background-color: #ff6347;
            color: #fff;
            cursor: pointer;
            padding: 10px;
            border-radius: 5px;
            border: none;
            font-size: 16px;
            font-weight: bold;
            transition: background-color 0.3s ease;
        }

        .logout-button:hover {
            background-color: #ff4500;
        }

        h2 {
            margin-bottom: 20px;
            color: #ffffff;
        }

        .result {
            margin-top: 20px;
            text-align: left;
        }

        .result h3 {
            color: #ffaa00;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .category {
            padding: 10px;
            background-color: #2a2a2a;
            border-radius: 5px;
            margin-bottom: 10px;
            color: #e0e0e0;
        }
    </style>
</head>
<body>
<div class="container">
    <?php
    session_start();

    // Verificar si se ha enviado el formulario de desconexión
    if (isset($_POST['logout'])) {
        session_destroy();
        header("Location: " . $_SERVER['PHP_SELF']);
        exit;
    }

    // Verificar si se ha enviado el formulario de conexión
    if (isset($_POST['submit'])) {
        $_SESSION['xtream_url'] = $_POST['xtream_url'];
        $_SESSION['username'] = $_POST['username'];
        $_SESSION['password'] = $_POST['password'];
    }

    if (isset($_SESSION['xtream_url']) && isset($_SESSION['username']) && isset($_SESSION['password'])) {
        $xtream_url = $_SESSION['xtream_url'];
        $username = $_SESSION['username'];
        $password = $_SESSION['password'];

        function verificar_login_curl($xtream_url, $username, $password) {
            $url = $xtream_url . "/player_api.php?username=" . $username . "&password=" . $password . "&action=user_info";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response, true);
            return isset($data['user_info']['auth']) && $data['user_info']['auth'] == 1;
        }

        function fetch_from_xtream($xtream_url, $username, $password, $type) {
            $url = $xtream_url . "/player_api.php?username=" . $username . "&password=" . $password . "&action=" . $type;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            return json_decode($response, true);
        }

        function fetch_categories($xtream_url, $username, $password, $type) {
            $url = $xtream_url . "/player_api.php?username=" . $username . "&password=" . $password . "&action=" . $type;
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($ch);
            curl_close($ch);
            return json_decode($response, true);
        }

        if (verificar_login_curl($xtream_url, $username, $password)) {
            echo "<div class='result'><h3>Login exitoso!</h3>";

            $canal_prueba = fetch_from_xtream($xtream_url, $username, $password, "get_live_streams");
            $pelicula_prueba = fetch_from_xtream($xtream_url, $username, $password, "get_vod_streams");
            $serie_prueba = fetch_from_xtream($xtream_url, $username, $password, "get_series");

            echo "<div class='category'><h3>Canal de Prueba</h3>";
            echo !empty($canal_prueba) ? "Nombre del canal: " . $canal_prueba[0]['name'] . "<br>ID del canal: " . $canal_prueba[0]['stream_id'] . "<br><img src='" . $canal_prueba[0]['stream_icon'] . "' style='width:100px;'><br>" : "No se encontró ningún canal.<br>";
            echo "</div>";

            echo "<div class='category'><h3>Película de Prueba</h3>";
            echo !empty($pelicula_prueba) ? "Nombre de la película: " . $pelicula_prueba[0]['name'] . "<br>ID de la película: " . $pelicula_prueba[0]['stream_id'] . "<br><img src='" . $pelicula_prueba[0]['stream_icon'] . "' style='width:100px;'><br>" : "No se encontró ninguna película.<br>";
            echo "</div>";

            echo "<div class='category'><h3>Serie de Prueba</h3>";
            echo !empty($serie_prueba) ? "Nombre de la serie: " . $serie_prueba[0]['name'] . "<br>ID de la serie: " . $serie_prueba[0]['series_id'] . "<br><img src='" . $serie_prueba[0]['cover'] . "' style='width:100px;'><br>" : "No se encontró ninguna serie.<br>";
            echo "</div>";

            $categorias_peliculas = fetch_categories($xtream_url, $username, $password, "get_vod_categories");
            echo "<h3>Categorías de Películas</h3>";
            foreach ($categorias_peliculas as $categoria) {
                echo "<div class='category'>Nombre: " . $categoria['category_name'] . "<br>ID: " . $categoria['category_id'] . "</div>";
            }

            $categorias_series = fetch_categories($xtream_url, $username, $password, "get_series_categories");
            echo "<h3>Categorías de Series</h3>";
            foreach ($categorias_series as $categoria) {
                echo "<div class='category'>Nombre: " . $categoria['category_name'] . "<br>ID: " . $categoria['category_id'] . "</div>";
            }
            echo "</div>";
            ?>

            <!-- Formulario de desconexión -->
            <form method="POST" class="logout-form">
                <input type="hidden" name="logout" value="1">
                <button type="submit" class="logout-button">Desconectar</button>
            </form>

            <?php
        } else {
            echo "<h3>Error de autenticación. Verifica tus credenciales.</h3>";
        }
    } else {
    ?>
        <h2>Conectar a Xtream Codes</h2>
        <form method="POST">
            <input type="text" name="xtream_url" placeholder="URL del servidor Xtream" required>
            <input type="text" name="username" placeholder="Usuario" required>
            <input type="password" name="password" placeholder="Contraseña" required>
            <input type="submit" name="submit" value="Conectar">
        </form>
    <?php } ?>
</div>
</body>
</html>
