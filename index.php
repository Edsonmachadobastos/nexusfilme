<?php

session_start();

if (isset($_SESSION['username'])) {
	header('Location: homex.php');
}
else {
	header('Location: login.php');
}

?>