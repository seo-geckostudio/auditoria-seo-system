<?php
/**
 * Configuración de conexión a la base de datos
 * Sistema de Auditorías SEO
 */

// =====================================================
// CONFIGURACIÓN MYSQL (Comentada por defecto)
// =====================================================
/*
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'auditoria_seo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');
define('USE_SQLITE', false);
*/

// =====================================================
// CONFIGURACIÓN SQLITE (Por defecto)
// =====================================================
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'auditoria_seo');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuración SQLite
define('USE_SQLITE', true);
define('SQLITE_DB_PATH', __DIR__ . '/../data/auditoria_seo.sqlite');

?>