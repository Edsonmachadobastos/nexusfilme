<?php
session_start();
session_destroy(); // Destrói a sessão
header('Location: painel.php'); // Redireciona para o painel de login
exit;
?>
