<?php
/**
 * generate_placeholders.php
 * Run once to create placeholder images in assets/img/sample/
 * Usage: C:\xampp\php\php.exe generate_placeholders.php
 */

$sampleDir = __DIR__ . '/assets/img/sample/';
$imgDir    = __DIR__ . '/assets/img/';

if (!function_exists('imagecreatetruecolor')) {
    die("GD extension is required. Enable it in php.ini.\n");
}

function makePlaceholder(string $path, string $label, string $bgHex, int $w = 400, int $h = 400): void {
    $img = imagecreatetruecolor($w, $h);
    [$r, $g, $b] = sscanf($bgHex, '#%02x%02x%02x');
    $bg   = imagecolorallocate($img, $r, $g, $b);
    $text = imagecolorallocate($img, 255, 255, 255);
    $gray = imagecolorallocate($img, 255, 255, 255);
    imagefill($img, 0, 0, $bg);

    // White translucent box in center
    $cx = (int)($w / 2);
    $cy = (int)($h / 2);
    $ow = (int)($w * 0.35);
    $oh = (int)($h * 0.30);
    imagefilledrectangle($img, $cx - $ow, $cy - $oh, $cx + $ow, $cy + $oh,
        imagecolorallocate($img, (int)min(255, $r + 50), (int)min(255, $g + 50), (int)min(255, $b + 50)));

    // Centered text
    $fontSize = 4;
    $tw = imagefontwidth($fontSize) * strlen($label);
    imagestring($img, $fontSize, (int)(($w - $tw) / 2), $cy - 10, $label, $text);

    // "ThriftZone" watermark
    $wm   = 'ThriftZone';
    $wmW  = imagefontwidth(2) * strlen($wm);
    $wmC  = imagecolorallocate($img, (int)min(255, $r + 80), (int)min(255, $g + 80), (int)min(255, $b + 80));
    imagestring($img, 2, (int)(($w - $wmW) / 2), $h - 24, $wm, $wmC);

    // Save as JPEG (or PNG for placeholder.svg)
    if (str_ends_with($path, '.png')) {
        imagepng($img, $path);
    } else {
        imagejpeg($img, $path, 85);
    }
    imagedestroy($img);
    echo "✓ $path\n";
}

// Make sure directories exist
@mkdir($sampleDir, 0755, true);

$colors = ['#0D9488', '#0F2A43', '#64748B', '#059669', '#7C3AED', '#DC2626', '#D97706'];

// Generic placeholder
makePlaceholder($sampleDir . 'placeholder.svg', 'No Image', '#94A3B8');

// Fashion
makePlaceholder($sampleDir . 'fashion-01.jpg', 'Kemeja Flannel',  $colors[0]);
makePlaceholder($sampleDir . 'fashion-02.jpg', 'Dress Floral',    $colors[2]);
makePlaceholder($sampleDir . 'fashion-03.jpg', 'Sepatu Nike',     $colors[1]);
makePlaceholder($sampleDir . 'fashion-04.jpg', 'Tas Kulit',       $colors[3]);
makePlaceholder($sampleDir . 'fashion-05.jpg', 'Topi Baseball',   $colors[4]);

// Entertainment
makePlaceholder($sampleDir . 'ent-01.jpg', 'Novel Laskar',   $colors[1]);
makePlaceholder($sampleDir . 'ent-02.jpg', 'Vinyl Beatles',  $colors[0]);
makePlaceholder($sampleDir . 'ent-03.jpg', 'PS4 Spiderman',  $colors[2]);
makePlaceholder($sampleDir . 'ent-04.jpg', 'DVD Movie',      $colors[3]);

// Utilities
makePlaceholder($sampleDir . 'util-01.jpg', 'Blender Miyako',  $colors[0]);
makePlaceholder($sampleDir . 'util-02.jpg', 'Fairy Light LED', $colors[2]);
makePlaceholder($sampleDir . 'util-03.jpg', 'Sony Earphone',   $colors[1]);
makePlaceholder($sampleDir . 'util-04.jpg', 'Set Obeng',       $colors[3]);

// Collectibles
makePlaceholder($sampleDir . 'col-01.jpg', 'LPS Figurine',   $colors[4]);
makePlaceholder($sampleDir . 'col-02.jpg', 'Hot Wheels',      $colors[0]);
makePlaceholder($sampleDir . 'col-03.jpg', 'Figma Levi',      $colors[2]);
makePlaceholder($sampleDir . 'col-04.jpg', 'Pokemon Cards',   $colors[1]);
makePlaceholder($sampleDir . 'col-05.jpg', 'Koin Kuno 1941',  $colors[3]);

// Other
makePlaceholder($sampleDir . 'other-01.jpg', 'Kaktus Mini',  $colors[3]);
makePlaceholder($sampleDir . 'other-02.jpg', 'Puzzle 1000',  $colors[0]);
makePlaceholder($sampleDir . 'other-03.jpg', 'Baju Bayi',    $colors[2]);

// QRIS placeholder (simulated QR code look)
function makeQRIS(string $path): void {
    $w = 300; $h = 300;
    $img = imagecreatetruecolor($w, $h);
    $white = imagecolorallocate($img, 255, 255, 255);
    $navy  = imagecolorallocate($img, 15, 42, 67);
    $teal  = imagecolorallocate($img, 13, 148, 136);
    imagefill($img, 0, 0, $white);

    // Simulate QR module grid
    srand(42); // deterministic seed
    $mod = 10; // pixel size per module
    $margin = 20;
    $cols = (int)(($w - 2 * $margin) / $mod);
    $rows = (int)(($h - 50 - $margin) / $mod);

    for ($r = 0; $r < $rows; $r++) {
        for ($c = 0; $c < $cols; $c++) {
            if (rand(0, 1)) {
                $x1 = $margin + $c * $mod;
                $y1 = $margin + $r * $mod;
                imagefilledrectangle($img, $x1, $y1, $x1 + $mod - 2, $y1 + $mod - 2, $navy);
            }
        }
    }

    // Finder patterns (top-left, top-right, bottom-left)
    $fp = [[0,0],[($cols-7)*$mod,0],[0,($rows-7)*$mod]];
    foreach ($fp as [$fx, $fy]) {
        $fx += $margin; $fy += $margin;
        // Outer square
        imagefilledrectangle($img, $fx, $fy, $fx+70, $fy+70, $navy);
        imagefilledrectangle($img, $fx+10, $fy+10, $fx+60, $fy+60, $white);
        imagefilledrectangle($img, $fx+20, $fy+20, $fx+50, $fy+50, $navy);
    }

    // Label at bottom
    $label  = 'QRIS ThriftZone';
    $lw     = imagefontwidth(3) * strlen($label);
    imagestring($img, 3, (int)(($w - $lw) / 2), $h - 28, $label, $teal);

    imagepng($img, $path);
    echo "✓ $path\n";
}

makeQRIS($imgDir . 'qris.png');

echo "\nDone! All placeholder images generated.\n";
