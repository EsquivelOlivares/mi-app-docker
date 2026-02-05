<?php
// debug.php - Colócalo en la raíz de tu proyecto
echo "<h1>🔧 Debug de rutas PHP</h1>";

// Ruta actual de cart.php
$cartPath = __DIR__ . '/app/cart.php';
echo "<h3>1. Verificando cart.php</h3>";
echo "Ruta: $cartPath<br>";
echo "Existe: " . (file_exists($cartPath) ? '✅' : '❌') . "<br>";

// Ruta de connection.php desde diferentes perspectivas
echo "<h3>2. Rutas a connection.php</h3>";

$paths = [
    'Desde raíz' => __DIR__ . '/includes/connection.php',
    'Desde app/' => __DIR__ . '/app/../includes/connection.php',
    'Desde app/cart/' => __DIR__ . '/app/cart/../../includes/connection.php',
];

foreach ($paths as $desc => $path) {
    echo "<strong>$desc:</strong> $path<br>";
    echo "Existe: " . (file_exists($path) ? '✅' : '❌') . "<br><br>";
}

// Mostrar contenido de includes/
echo "<h3>3. Contenido de includes/</h3>";
$includesDir = __DIR__ . '/includes';
if (is_dir($includesDir)) {
    $files = scandir($includesDir);
    echo "<pre>";
    print_r($files);
    echo "</pre>";
} else {
    echo "❌ El directorio includes/ no existe<br>";
}

// Mostrar __DIR__
echo "<h3>4. __DIR__ actual</h3>";
echo "__DIR__ = " . __DIR__ . "<br>";

// Verificar montaje de Docker
echo "<h3>5. Verificando montaje Docker</h3>";
echo "<pre>";
echo "Directorio actual: " . getcwd() . "\n";
echo "Archivos en raíz:\n";
system("ls -la " . escapeshellarg(__DIR__));
echo "</pre>";
?>