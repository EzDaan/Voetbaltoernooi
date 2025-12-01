<?php
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
