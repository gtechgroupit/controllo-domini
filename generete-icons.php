<?php
/**
 * Script per generare icone placeholder
 * Controllo Domini - G Tech Group
 * 
 * Esegui questo script una volta per creare tutte le icone necessarie
 */

// Definisci path base
define('ABSPATH', dirname(__FILE__) . '/');

// Crea directory se non esiste
$assets_dir = ABSPATH . 'assets/images/';
if (!is_dir($assets_dir)) {
    mkdir($assets_dir, 0755, true);
}

/**
 * Genera un'icona placeholder con il logo del sito
 * 
 * @param int $width Larghezza
 * @param int $height Altezza
 * @param string $filename Nome file di output
 * @param bool $rounded Angoli arrotondati
 */
function generateIcon($width, $height, $filename, $rounded = false) {
    global $assets_dir;
    
    // Crea immagine
    $image = imagecreatetruecolor($width, $height);
    
    // Abilita trasparenza
    imagesavealpha($image, true);
    
    // Colori
    $bg_color = imagecolorallocate($image, 93, 142, 207); // Blu primary
    $text_color = imagecolorallocate($image, 255, 255, 255); // Bianco
    $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
    
    // Riempi con trasparenza
    imagefill($image, 0, 0, $transparent);
    
    if ($rounded) {
        // Crea forma arrotondata
        $radius = min($width, $height) / 2;
        imagefilledellipse($image, $width/2, $height/2, $width, $height, $bg_color);
    } else {
        // Rettangolo con angoli arrotondati
        $radius = 20;
        imagefilledrectangle($image, $radius, 0, $width - $radius, $height, $bg_color);
        imagefilledrectangle($image, 0, $radius, $width, $height - $radius, $bg_color);
        
        // Angoli arrotondati
        imagefilledellipse($image, $radius, $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $width - $radius, $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $radius, $height - $radius, $radius * 2, $radius * 2, $bg_color);
        imagefilledellipse($image, $width - $radius, $height - $radius, $radius * 2, $radius * 2, $bg_color);
    }
    
    // Aggiungi testo "CD" (Controllo Domini)
    $font_size = min($width, $height) / 3;
    $text = 'CD';
    
    // Calcola posizione del testo
    $bbox = imagettfbbox($font_size, 0, __DIR__ . '/assets/fonts/Poppins-Bold.ttf', $text);
    
    // Se il font non esiste, usa font built-in
    if (!$bbox || !file_exists(__DIR__ . '/assets/fonts/Poppins-Bold.ttf')) {
        // Usa font built-in
        $font_size = 5; // Font size per imagestring
        $text_width = imagefontwidth($font_size) * strlen($text);
        $text_height = imagefontheight($font_size);
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2;
        imagestring($image, $font_size, $x, $y, $text, $text_color);
    } else {
        // Usa TrueType font
        $text_width = $bbox[2] - $bbox[0];
        $text_height = $bbox[1] - $bbox[7];
        $x = ($width - $text_width) / 2;
        $y = ($height - $text_height) / 2 + $text_height;
        imagettftext($image, $font_size, 0, $x, $y, $text_color, __DIR__ . '/assets/fonts/Poppins-Bold.ttf', $text);
    }
    
    // Salva immagine
    imagepng($image, $assets_dir . $filename);
    imagedestroy($image);
    
    echo "✅ Creato: {$filename} ({$width}x{$height})\n";
}

/**
 * Genera favicon ICO multi-risoluzione
 * 
 * @param string $filename Nome file di output
 */
function generateFavicon($filename) {
    global $assets_dir;
    
    // Per semplicità, crea un PNG e rinominalo in ICO
    // In produzione, usa una libreria per creare veri file ICO
    generateIcon(32, 32, 'favicon-temp.png', false);
    
    // Copia come ICO (non è un vero ICO ma funzionerà per i browser moderni)
    copy($assets_dir . 'favicon-temp.png', $assets_dir . $filename);
    unlink($assets_dir . 'favicon-temp.png');
    
    // Copia anche nella root
    copy($assets_dir . $filename, ABSPATH . $filename);
    
    echo "✅ Creato: {$filename} (favicon)\n";
}

// Inizia generazione
echo "🚀 Generazione icone placeholder per Controllo Domini...\n\n";

// Favicon
generateFavicon('favicon.ico');
generateIcon(32, 32, 'favicon-32x32.png', false);
generateIcon(16, 16, 'favicon-16x16.png', false);

// Apple Touch Icon
generateIcon(180, 180, 'apple-touch-icon.png', true);

// Icone per PWA/Manifest
generateIcon(192, 192, 'icon-192.png', true);
generateIcon(512, 512, 'icon-512.png', true);

// Icone aggiuntive
generateIcon(144, 144, 'icon-144.png', true);
generateIcon(96, 96, 'icon-96.png', true);
generateIcon(72, 72, 'icon-72.png', true);
generateIcon(48, 48, 'icon-48.png', true);

// Logo generico
generateIcon(300, 300, 'logo.png', false);

// Placeholder generico per immagini mancanti
generateIcon(400, 300, 'placeholder.png', false);

// Open Graph Image (dimensioni social media)
$og_image = imagecreatetruecolor(1200, 630);
imagesavealpha($og_image, true);

// Sfondo gradiente
for ($i = 0; $i < 630; $i++) {
    $color = imagecolorallocate($og_image, 
        93 + ($i / 630 * 30), 
        142 + ($i / 630 * 20), 
        207 - ($i / 630 * 30)
    );
    imageline($og_image, 0, $i, 1200, $i, $color);
}

// Testo centrale
$white = imagecolorallocate($og_image, 255, 255, 255);
$text = "Controllo Domini";
$font_size = 60;

// Usa font built-in per semplicità
imagestring($og_image, 5, 450, 300, $text, $white);

imagepng($og_image, $assets_dir . 'og-image.jpg', 9);
imagedestroy($og_image);

echo "✅ Creato: og-image.jpg (1200x630)\n";

// Crea anche una versione per Twitter
copy($assets_dir . 'og-image.jpg', $assets_dir . 'twitter-card.jpg');
echo "✅ Creato: twitter-card.jpg (1200x630)\n";

echo "\n✨ Tutte le icone sono state generate con successo!\n";
echo "📁 Le icone si trovano in: " . $assets_dir . "\n";

// Crea un semplice CSS per lo splash screen del PWA
$splash_css = '/* PWA Splash Screen Styles */
.splash-screen {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: linear-gradient(135deg, #5d8ecf 0%, #264573 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.splash-logo {
    width: 200px;
    height: 200px;
    background: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 72px;
    font-weight: bold;
    color: #5d8ecf;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0% { transform: scale(0.95); opacity: 0.8; }
    50% { transform: scale(1.05); opacity: 1; }
    100% { transform: scale(0.95); opacity: 0.8; }
}';

file_put_contents(ABSPATH . 'assets/css/splash.css', $splash_css);
echo "\n✅ Creato: splash.css per PWA\n";

// Crea browserconfig.xml per Windows tiles
$browserconfig = '<?xml version="1.0" encoding="utf-8"?>
<browserconfig>
    <msapplication>
        <tile>
            <square70x70logo src="/assets/images/icon-72.png"/>
            <square150x150logo src="/assets/images/icon-144.png"/>
            <square310x310logo src="/assets/images/icon-512.png"/>
            <TileColor>#5d8ecf</TileColor>
        </tile>
    </msapplication>
</browserconfig>';

file_put_contents(ABSPATH . 'browserconfig.xml', $browserconfig);
echo "✅ Creato: browserconfig.xml\n";

echo "\n🎉 Setup completato! Il sito ora ha tutte le icone necessarie.\n";
?>
