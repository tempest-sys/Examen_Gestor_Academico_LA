
<?php
// Cargar configuracion
$config = require __DIR__ . "/config.php";

// Construir DSN
$dsn = "mysql:host={$config['host']};"
     . "port={$config['port']};"
     . "dbname={$config['database']};"
     . "charset=utf8mb4";

// Configurar PDO
$opciones = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false
];

// Establecer conexion
try {
    $conexion = new PDO(
        $dsn,
        $config['username'],
        $config['password'],
        $opciones
    );
} catch (PDOException $error) {
    error_log("Error de conexion: " . $error->getMessage());
    throw new RuntimeException(
        "No fue posible conectar con la base de datos"
    );
}

// Devolver conexion
return $conexion;
