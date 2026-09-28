<?php

session_start();
if (isset($_SESSION["username"])) {
    session_destroy();
    header("Location: index.php?logout=true");
    exit;
}
session_destroy();
header("Location: index.php?login=error");
exit;

?>

