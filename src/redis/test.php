<!-- src/redis/test.php -->
<?php
// Conexión a Redis
$redis = new Redis();
try {
    $redis->connect('redis', 6379);  // 'redis' = nombre del servicio en docker-compose
    
    echo "<h1>Redis Test</h1>";
    
    // Probar SET/GET
    $redis->set('test_key', '¡Hola desde Docker! ' . date('H:i:s'));
    $value = $redis->get('test_key');
    
    echo "<p>Valor obtenido de Redis: <strong>$value</strong></p>";
    
    // Contador de visitas
    $visits = $redis->incr('page_visits');
    echo "<p>Visitas a esta página: <strong>$visits</strong></p>";
    
    // Información del servidor Redis
    echo "<h2>Info de Redis:</h2>";
    echo "<pre>";
    print_r($redis->info());
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error conectando a Redis: " . $e->getMessage() . "</p>";
    echo "<p>Asegúrate de que el servicio 'redis' esté corriendo en docker-compose</p>";
}
?>