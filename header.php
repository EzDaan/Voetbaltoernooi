<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'functions.php';

// Try to locate the project's `css/style.css` by walking up the URL path
function find_css_href() {
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
    $scriptName = $_SERVER['SCRIPT_NAME'];
    $parts = explode('/', $scriptName);
    for ($i = count($parts); $i >= 0; $i--) {
        $candidate = implode('/', array_slice($parts, 0, $i));
        if ($candidate === '') $candidate = '/';
        $fullPath = $docRoot . $candidate . '/css/style.css';
        if (file_exists($fullPath)) {
            return $candidate . '/css/style.css';
        }
    }
    return '/css/style.css';
}

$cssHref = find_css_href();
$projectBase = substr($cssHref, 0, -strlen('/css/style.css'));
if ($projectBase === '/') $projectBase = '';

$defaultNav = [
    ['href' => 'Team_inschrijven.php', 'label' => 'Team inschrijven'],
    ['href' => 'Teams_bekijken.php', 'label' => 'Teams'],
    ['href' => 'Wedstrijd_Planning.php', 'label' => 'Wedstrijd planning'],
    ['href' => 'Klassement.php', 'label' => 'Klassement'],
];

$navLinks = $navLinks ?? $defaultNav;

function buildHref($href) {
    global $projectBase;
    $base = rtrim($projectBase, '/');
    if ($base === '') return $href;
    return $base . '/' . ltrim($href, '/');
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voetbaltoernooi</title>
    <link rel="stylesheet" href="<?php echo htmlspecialchars($cssHref); ?>">
</head>
<body>
    <div class="topbar">
        <div class="nav">
            <?php foreach ($navLinks as $link): ?>
                <a href="<?php echo htmlspecialchars(buildHref($link['href'])); ?>"><?php echo htmlspecialchars($link['label']); ?></a>
            <?php endforeach; ?>
        </div>
        <div>
            <?php if (function_exists('isLoggedIn') && isLoggedIn()): ?>
                <a href="<?php echo htmlspecialchars(buildHref('logout.php')); ?>" class="logout-button">Uitloggen</a>
            <?php else: ?>
                <a href="<?php echo htmlspecialchars(buildHref('inloggen.php')); ?>">Inloggen</a>
            <?php endif; ?>
        </div>
    </div>
