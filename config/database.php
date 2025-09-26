<?php
/**
 * Configuración de la base de datos SQLite
 * Sistema de Auditorías SEO
 */

// Configuración de la base de datos
require_once 'database_config.php';

// Configuración de PDO para SQLite
$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

/**
 * Obtiene la conexión a la base de datos
 * @return PDO|null
 */
function obtenerConexion() {
    global $pdo_options;
    
    try {
        if (USE_SQLITE) {
            // Crear directorio si no existe
            $db_dir = dirname(SQLITE_DB_PATH);
            if (!is_dir($db_dir)) {
                mkdir($db_dir, 0755, true);
            }
            
            $dsn = "sqlite:" . SQLITE_DB_PATH;
            $pdo = new PDO($dsn, null, null, $pdo_options);
            
            // Habilitar claves foráneas en SQLite
            $pdo->exec('PRAGMA foreign_keys = ON');
            
            return $pdo;
        } else {
            // Configuración MySQL (comentada por defecto)
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            return new PDO($dsn, DB_USER, DB_PASS, $pdo_options);
        }
    } catch (PDOException $e) {
        error_log("Error de conexión a la base de datos: " . $e->getMessage());
        return null;
    }
}

// =====================================================
// CONSTANTES DEL SISTEMA
// =====================================================

// Rutas de archivos
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('TEMP_PATH', __DIR__ . '/../temp/');
define('LOGS_PATH', __DIR__ . '/../logs/');

// Configuración de archivos
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'png', 'jpg', 'jpeg', 'gif', 'txt', 'csv']);

// Estados de auditoría
define('ESTADOS_AUDITORIA', [
    'borrador' => 'Borrador',
    'en_progreso' => 'En Progreso',
    'revision' => 'En Revisión',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada'
]);

// Estados de pasos
define('ESTADOS_PASO', [
    'pendiente' => 'Pendiente',
    'en_progreso' => 'En Progreso',
    'completado' => 'Completado',
    'omitido' => 'Omitido'
]);

// Prioridades
define('PRIORIDADES', [
    'baja' => 'Baja',
    'media' => 'Media',
    'alta' => 'Alta',
    'critica' => 'Crítica'
]);

// =====================================================
// FUNCIONES AUXILIARES
// =====================================================

/**
 * Verifica si la base de datos existe y está configurada
 * @return bool
 */
function verificarBaseDatos() {
    if (USE_SQLITE) {
        return file_exists(SQLITE_DB_PATH);
    } else {
        try {
            $pdo = obtenerConexion();
            return $pdo !== null;
        } catch (Exception $e) {
            return false;
        }
    }
}

/**
 * Obtiene información de la base de datos
 * @return array
 */
function obtenerInfoBaseDatos() {
    $pdo = obtenerConexion();
    if (!$pdo) return null;
    
    try {
        if (USE_SQLITE) {
            $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
            $tablas = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'tipo' => 'SQLite',
                'archivo' => SQLITE_DB_PATH,
                'tablas' => count($tablas),
                'tamaño' => file_exists(SQLITE_DB_PATH) ? filesize(SQLITE_DB_PATH) : 0
            ];
        } else {
            $stmt = $pdo->query("SELECT COUNT(*) as total FROM information_schema.tables WHERE table_schema = '" . DB_NAME . "'");
            $result = $stmt->fetch();
            
            return [
                'tipo' => 'MySQL',
                'host' => DB_HOST,
                'base_datos' => DB_NAME,
                'tablas' => $result['total']
            ];
        }
    } catch (Exception $e) {
        return null;
    }
}

/**
 * Verifica si el sistema está instalado
 * @return bool
 */
function sistemaInstalado() {
    if (!verificarBaseDatos()) {
        return false;
    }
    
    $pdo = obtenerConexion();
    if (!$pdo) return false;
    
    try {
        // Verificar si existe la tabla usuarios y tiene al menos un registro
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $result = $stmt->fetch();
        return $result['total'] > 0;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Crea los directorios necesarios del sistema
 */
function crearDirectoriosIniciales() {
    $directorios = [
        UPLOAD_PATH,
        TEMP_PATH,
        LOGS_PATH,
        __DIR__ . '/../data/'
    ];
    
    foreach ($directorios as $dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }
}

// Crear directorios al cargar el archivo
crearDirectoriosIniciales();

?>