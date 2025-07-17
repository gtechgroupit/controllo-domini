<?php
/**
 * Bootstrap file - Inizializzazione comune per tutte le pagine
 * 
 * @author G Tech Group
 * @version 4.0
 */

// Definisci la root del progetto
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Carica configurazione principale
$config_file = ABSPATH . 'config/config.php';
if (!file_exists($config_file)) {
    die('Errore: File di configurazione mancante. Contatta l\'amministratore.');
}
require_once $config_file;

// Carica funzioni principali
$functions_file = ABSPATH . 'includes/functions.php';
if (!file_exists($functions_file)) {
    die('Errore: File delle funzioni mancante. Contatta l\'amministratore.');
}
require_once $functions_file;

// Carica altre funzioni necessarie se esistono
$additional_includes = [
    'dns-functions.php',
    'whois-functions.php',
    'blacklist-functions.php',
    'cloud-functions.php',
    'ssl-functions.php',
    'security-functions.php'
];

foreach ($additional_includes as $include) {
    $file_path = ABSPATH . 'includes/' . $include;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

// Imposta variabili globali se non esistono
if (!isset($current_page)) {
    $current_page = '';
}

if (!isset($page_title)) {
    $page_title = APP_NAME;
}

if (!isset($page_description)) {
    $page_description = SEO_DESCRIPTION;
}

if (!isset($canonical_url)) {
    $canonical_url = APP_URL;
}

// Verifica che le costanti essenziali siano definite
if (!defined('APP_URL')) {
    define('APP_URL', 'https://controllodomini.it');
}

if (!defined('APP_NAME')) {
    define('APP_NAME', 'Controllo Domini');
}

if (!defined('APP_VERSION')) {
    define('APP_VERSION', '4.0');
}
?>
