<?php
require_once 'functions.php';

// Roep de logout-functie aan (redirect en exit binnen functie)
if (function_exists('logout')) {
    logout();
} else {
    // Fallback: probeer sessie handmatig te vernietigen
    if (session_status() === PHP_SESSION_NONE) { session_start(); }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'], $params['secure'], $params['httponly']
        );
    }
    session_destroy();
    setcookie('user_email', '', time() - 3600, '/');
    header('Location: index.php');
    exit();
}

// logout.php: sluit sessie af en verwijst naar de inlogpagina
session_start();

// Verwijder alle sessievariabelen
$_SESSION = [];

// Verwijder eventuele sessiecookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'], $params['secure'], $params['httponly']
    );
}

// Extra: verwijder eventueel opgeslagen 'remember me' cookie met e-mail
if (isset($_COOKIE['user_email'])) {
    setcookie('user_email', '', time() - 3600, '/');
}

// Vernietig de sessie op de server
session_destroy();

// Redirect terug naar de loginpagina
header('Location: index.php');
exit();

?>
