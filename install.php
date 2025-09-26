<?php
/**
 * INSTALADOR DEL SISTEMA DE AUDITORÍAS SEO
 * 
 * Este script se encarga de:
 * - Verificar requisitos del sistema
 * - Configurar la base de datos
 * - Crear el esquema de la base de datos
 * - Configurar el usuario administrador
 * - Importar pasos desde archivos Markdown
 * 
 * @author Sistema de Auditorías SEO
 * @version 1.0
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

require_once 'config/database.php';

$paso_actual = $_GET['paso'] ?? 1;
$errores = [];
$mensajes = [];

// Procesar instalación
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($_POST['accion']) {
        case 'verificar_requisitos':
            $requisitos = verificarRequisitos();
            if ($requisitos['cumple_todos']) {
                header('Location: install.php?paso=2');
                exit;
            } else {
                $errores[] = 'No se cumplen todos los requisitos del sistema.';
            }
            break;
            
        case 'configurar_bd':
            if (configurarBaseDatos()) {
                header('Location: install.php?paso=3');
                exit;
            } else {
                $errores[] = 'Error al configurar la base de datos.';
            }
            break;
            
        case 'crear_admin':
            if (crearUsuarioAdmin($_POST)) {
                header('Location: install.php?paso=4');
                exit;
            } else {
                $errores[] = 'Error al crear el usuario administrador.';
            }
            break;
            
        case 'importar_pasos':
            if (importarPasosIniciales()) {
                header('Location: install.php?paso=5');
                exit;
            } else {
                $errores[] = 'Error al importar los pasos iniciales.';
            }
            break;
    }
}

/**
 * Verifica los requisitos del sistema
 */
function verificarRequisitos() {
    $requisitos = [
        'php_version' => [
            'nombre' => 'PHP 7.4 o superior',
            'cumple' => version_compare(PHP_VERSION, '7.4.0', '>=')
        ],
        'pdo_sqlite' => [
            'nombre' => 'Extensión PDO SQLite',
            'cumple' => extension_loaded('pdo_sqlite')
        ],
        'directorio_data' => [
            'nombre' => 'Directorio data/ escribible',
            'cumple' => is_writable(__DIR__ . '/data') || mkdir(__DIR__ . '/data', 0755, true)
        ],
        'directorio_uploads' => [
            'nombre' => 'Directorio uploads/ escribible',
            'cumple' => is_writable(__DIR__ . '/uploads') || mkdir(__DIR__ . '/uploads', 0755, true)
        ],
        'directorio_temp' => [
            'nombre' => 'Directorio temp/ escribible',
            'cumple' => is_writable(__DIR__ . '/temp') || mkdir(__DIR__ . '/temp', 0755, true)
        ],
        'directorio_logs' => [
            'nombre' => 'Directorio logs/ escribible',
            'cumple' => is_writable(__DIR__ . '/logs') || mkdir(__DIR__ . '/logs', 0755, true)
        ]
    ];
    
    $cumple_todos = true;
    foreach ($requisitos as $req) {
        if (!$req['cumple']) {
            $cumple_todos = false;
            break;
        }
    }
    
    $requisitos['cumple_todos'] = $cumple_todos;
    return $requisitos;
}

/**
 * Configura la base de datos SQLite
 */
function configurarBaseDatos() {
    try {
        $pdo = obtenerConexion();
        if (!$pdo) {
            return false;
        }
        
        // Leer y ejecutar el esquema de la base de datos
        $schema = file_get_contents(__DIR__ . '/database_schema.sql');
        if (!$schema) {
            return false;
        }
        
        // Dividir el esquema en declaraciones individuales
        $statements = explode(';', $schema);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                $pdo->exec($statement);
            }
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error configurando base de datos: " . $e->getMessage());
        return false;
    }
}

/**
 * Crea el usuario administrador
 */
function crearUsuarioAdmin($datos) {
    try {
        $pdo = obtenerConexion();
        if (!$pdo) {
            return false;
        }
        
        $nombre = $datos['nombre'] ?? 'Administrador';
        $email = $datos['email'] ?? 'admin@auditoria-seo.com';
        $password = password_hash($datos['password'] ?? 'admin123', PASSWORD_DEFAULT);
        
        // Eliminar usuario admin existente si existe
        $pdo->exec("DELETE FROM usuarios WHERE email = 'admin@auditoria-seo.com'");
        
        $stmt = $pdo->prepare("
            INSERT INTO usuarios (nombre, email, password, rol, activo) 
            VALUES (?, ?, ?, 'admin', 1)
        ");
        
        return $stmt->execute([$nombre, $email, $password]);
    } catch (Exception $e) {
        error_log("Error creando usuario admin: " . $e->getMessage());
        return false;
    }
}

/**
 * Importa los pasos iniciales desde archivos Markdown
 */
function importarPasosIniciales() {
    try {
        // Verificar si ya existen pasos
        $pdo = obtenerConexion();
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM pasos_plantilla");
        $result = $stmt->fetch();
        
        if ($result['total'] > 0) {
            return true; // Ya hay pasos importados
        }
        
        // Importar pasos de ejemplo
        $pasos_ejemplo = [
            ['fase_id' => 1, 'codigo_paso' => 'PREP_001', 'nombre' => 'Configuración inicial del proyecto', 'es_critico' => 1, 'tiempo_estimado_horas' => 2, 'orden_en_fase' => 1],
            ['fase_id' => 1, 'codigo_paso' => 'PREP_002', 'nombre' => 'Análisis preliminar del sitio web', 'es_critico' => 1, 'tiempo_estimado_horas' => 3, 'orden_en_fase' => 2],
            ['fase_id' => 2, 'codigo_paso' => 'TEC_001', 'nombre' => 'Análisis de velocidad de carga', 'es_critico' => 1, 'tiempo_estimado_horas' => 4, 'orden_en_fase' => 1],
            ['fase_id' => 2, 'codigo_paso' => 'TEC_002', 'nombre' => 'Revisión de estructura HTML', 'es_critico' => 0, 'tiempo_estimado_horas' => 2, 'orden_en_fase' => 2],
            ['fase_id' => 3, 'codigo_paso' => 'CONT_001', 'nombre' => 'Análisis de contenido duplicado', 'es_critico' => 1, 'tiempo_estimado_horas' => 3, 'orden_en_fase' => 1],
            ['fase_id' => 3, 'codigo_paso' => 'CONT_002', 'nombre' => 'Optimización de meta tags', 'es_critico' => 1, 'tiempo_estimado_horas' => 2, 'orden_en_fase' => 2],
            ['fase_id' => 4, 'codigo_paso' => 'OFF_001', 'nombre' => 'Análisis de backlinks', 'es_critico' => 0, 'tiempo_estimado_horas' => 5, 'orden_en_fase' => 1],
            ['fase_id' => 4, 'codigo_paso' => 'OFF_002', 'nombre' => 'Evaluación de autoridad de dominio', 'es_critico' => 0, 'tiempo_estimado_horas' => 2, 'orden_en_fase' => 2],
            ['fase_id' => 5, 'codigo_paso' => 'INF_001', 'nombre' => 'Generación de informe ejecutivo', 'es_critico' => 1, 'tiempo_estimado_horas' => 4, 'orden_en_fase' => 1],
            ['fase_id' => 5, 'codigo_paso' => 'INF_002', 'nombre' => 'Presentación de recomendaciones', 'es_critico' => 1, 'tiempo_estimado_horas' => 2, 'orden_en_fase' => 2]
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO pasos_plantilla (fase_id, codigo_paso, nombre, es_critico, tiempo_estimado_horas, orden_en_fase) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($pasos_ejemplo as $paso) {
            $stmt->execute([
                $paso['fase_id'],
                $paso['codigo_paso'],
                $paso['nombre'],
                $paso['es_critico'],
                $paso['tiempo_estimado_horas'],
                $paso['orden_en_fase']
            ]);
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Error importando pasos: " . $e->getMessage());
        return false;
    }
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación - Sistema de Auditorías SEO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .installer {
            background: white;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 600px;
            overflow: hidden;
        }
        
        .installer-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .installer-header h1 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }
        
        .installer-body {
            padding: 2rem;
        }
        
        .step-indicator {
            display: flex;
            justify-content: center;
            margin-bottom: 2rem;
        }
        
        .step {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 0.5rem;
            font-weight: bold;
            color: #64748b;
        }
        
        .step.active {
            background: #667eea;
            color: white;
        }
        
        .step.completed {
            background: #10b981;
            color: white;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .btn {
            background: #667eea;
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-success {
            background: #10b981;
        }
        
        .btn-success:hover {
            background: #059669;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 6px;
            border-left: 4px solid;
        }
        
        .alert-error {
            background: #fef2f2;
            border-color: #ef4444;
            color: #991b1b;
        }
        
        .alert-success {
            background: #f0fdf4;
            border-color: #22c55e;
            color: #166534;
        }
        
        .requirements-list {
            list-style: none;
        }
        
        .requirements-list li {
            padding: 0.5rem 0;
            display: flex;
            align-items: center;
        }
        
        .requirements-list .icon {
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
        
        .success { color: #10b981; }
        .error { color: #ef4444; }
    </style>
</head>
<body>
    <div class="installer">
        <div class="installer-header">
            <h1>🔍 Sistema de Auditorías SEO</h1>
            <p>Instalación del Sistema</p>
        </div>
        
        <div class="installer-body">
            <div class="step-indicator">
                <div class="step <?= $paso_actual >= 1 ? ($paso_actual == 1 ? 'active' : 'completed') : '' ?>">1</div>
                <div class="step <?= $paso_actual >= 2 ? ($paso_actual == 2 ? 'active' : 'completed') : '' ?>">2</div>
                <div class="step <?= $paso_actual >= 3 ? ($paso_actual == 3 ? 'active' : 'completed') : '' ?>">3</div>
                <div class="step <?= $paso_actual >= 4 ? ($paso_actual == 4 ? 'active' : 'completed') : '' ?>">4</div>
                <div class="step <?= $paso_actual >= 5 ? ($paso_actual == 5 ? 'active' : 'completed') : '' ?>">5</div>
            </div>
            
            <?php if (!empty($errores)): ?>
                <div class="alert alert-error">
                    <?php foreach ($errores as $error): ?>
                        <p><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <?php switch ($paso_actual): case 1: ?>
                <h2>Paso 1: Verificación de Requisitos</h2>
                <p>Verificando que el sistema cumple con todos los requisitos necesarios...</p>
                
                <?php $requisitos = verificarRequisitos(); ?>
                <ul class="requirements-list">
                    <?php foreach ($requisitos as $key => $req): ?>
                        <?php if ($key === 'cumple_todos') continue; ?>
                        <li>
                            <span class="icon <?= $req['cumple'] ? 'success' : 'error' ?>">
                                <?= $req['cumple'] ? '✓' : '✗' ?>
                            </span>
                            <?= $req['nombre'] ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                
                <form method="post" style="margin-top: 2rem;">
                    <input type="hidden" name="accion" value="verificar_requisitos">
                    <button type="submit" class="btn" <?= !$requisitos['cumple_todos'] ? 'disabled' : '' ?>>
                        <?= $requisitos['cumple_todos'] ? 'Continuar' : 'Requisitos no cumplidos' ?>
                    </button>
                </form>
                
            <?php break; case 2: ?>
                <h2>Paso 2: Configuración de Base de Datos</h2>
                <p>Configurando la base de datos SQLite y creando las tablas necesarias...</p>
                
                <form method="post" style="margin-top: 2rem;">
                    <input type="hidden" name="accion" value="configurar_bd">
                    <button type="submit" class="btn">Configurar Base de Datos</button>
                </form>
                
            <?php break; case 3: ?>
                <h2>Paso 3: Usuario Administrador</h2>
                <p>Crear el usuario administrador del sistema:</p>
                
                <form method="post" style="margin-top: 1rem;">
                    <input type="hidden" name="accion" value="crear_admin">
                    
                    <div class="form-group">
                        <label for="nombre">Nombre completo:</label>
                        <input type="text" id="nombre" name="nombre" value="Administrador" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="admin@auditoria-seo.com" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Contraseña:</label>
                        <input type="password" id="password" name="password" value="admin123" required>
                    </div>
                    
                    <button type="submit" class="btn">Crear Usuario</button>
                </form>
                
            <?php break; case 4: ?>
                <h2>Paso 4: Importar Pasos Iniciales</h2>
                <p>Importando los pasos iniciales del proceso de auditoría...</p>
                
                <form method="post" style="margin-top: 2rem;">
                    <input type="hidden" name="accion" value="importar_pasos">
                    <button type="submit" class="btn">Importar Pasos</button>
                </form>
                
            <?php break; case 5: ?>
                <div class="alert alert-success">
                    <h2>🎉 ¡Instalación Completada!</h2>
                    <p>El sistema ha sido instalado correctamente. Ya puedes comenzar a usar el Sistema de Auditorías SEO.</p>
                </div>
                
                <div style="text-align: center; margin-top: 2rem;">
                    <a href="index.php" class="btn btn-success">Acceder al Sistema</a>
                </div>
                
            <?php break; endswitch; ?>
        </div>
    </div>
</body>
</html>