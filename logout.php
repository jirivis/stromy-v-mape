<?php

/*
---------------------------------------------------
 Stromy v mapě
 Soubor: logout.php
---------------------------------------------------
*/

session_start();

// Vymaže všechny proměnné relace
$_SESSION = [];

// Smaže session cookie
if (ini_get("session.use_cookies")) {

    $params = session_get_cookie_params();

    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Zruší session
session_destroy();

// Zpět na přihlášení
header("Location: /login.php");
exit;