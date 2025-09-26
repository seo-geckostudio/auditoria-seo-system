<?php
/**
 * SISTEMA DE AUDITORÍAS SEO - PUNTO DE ENTRADA PRINCIPAL
 * 
 * Este archivo actúa como router principal del sistema, manejando:
 * - Gestión de sesiones
 * - Verificación de instalación
 * - Enrutamiento de módulos
 * - Autenticación básica
 * - Procesamiento de formularios
 * 
 * @author Sistema de Auditorías SEO
 * @version 1.0
 */

// Configuración inicial
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Incluir archivos necesarios
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/forms.php';
require_once 'includes/notifications.php';

// Verificar si el sistema está instalado
if (!sistemaInstalado() && basename($_SERVER['PHP_SELF']) !== 'install.php') {
    header('Location: install.php');
    exit;
}

// Obtener parámetros de la URL
$modulo = $_GET['modulo'] ?? 'dashboard';
$accion = $_GET['accion'] ?? 'lista';
$id = $_GET['id'] ?? null;

// Autenticación simple para demo (en producción usar sistema completo)
if (!isset($_SESSION['usuario_id']) && $modulo !== 'login') {
    // Para demo, crear sesión automática
    $_SESSION['usuario_id'] = 1;
    $_SESSION['usuario_nombre'] = 'Demo User';
    $_SESSION['usuario_rol'] = 'admin';
}

// Incluir módulos según corresponda
$modulos_disponibles = ['auditorias', 'pasos', 'archivos', 'clientes'];
if (in_array($modulo, $modulos_disponibles)) {
    $archivo_modulo = "modules/{$modulo}.php";
    if (file_exists($archivo_modulo)) {
        require_once $archivo_modulo;
    }
}

// Procesar formularios POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $resultado = procesarFormulario($_POST, $modulo, $accion);
    if ($resultado) {
        // Redirigir para evitar reenvío de formulario
        $redirect_url = "?modulo={$modulo}&accion=lista";
        if (isset($resultado['redirect'])) {
            $redirect_url = $resultado['redirect'];
        }
        header("Location: {$redirect_url}");
        exit;
    }
}

// Función para procesar formularios
function procesarFormulario($datos, $modulo, $accion) {
    switch ($modulo) {
        case 'auditorias':
            if (function_exists('manejarAuditorias')) {
                return manejarAuditorias($accion, $datos);
            }
            break;
        case 'clientes':
            if (function_exists('manejarClientes')) {
                return manejarClientes($accion, $datos);
            }
            break;
        case 'pasos':
            if (function_exists('manejarPasos')) {
                return manejarPasos($accion, $datos);
            }
            break;
        case 'archivos':
            if (function_exists('manejarArchivos')) {
                return manejarArchivos($accion, $datos);
            }
            break;
    }
    return false;
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Auditorías SEO</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav {
            background: white;
            padding: 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }
        
        .nav li {
            margin: 0;
        }
        
        .nav a {
            display: block;
            padding: 1rem 1.5rem;
            text-decoration: none;
            color: #555;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .nav a:hover,
        .nav a.active {
            color: #667eea;
            border-bottom-color: #667eea;
            background-color: #f8f9ff;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .content {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .content-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #eee;
            background: #fafafa;
        }
        
        .content-body {
            padding: 2rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        
        .btn:hover {
            background: #5a67d8;
        }
        
        .btn-secondary {
            background: #718096;
        }
        
        .btn-secondary:hover {
            background: #4a5568;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .alert-success {
            background: #f0fff4;
            border-color: #38a169;
            color: #22543d;
        }
        
        .alert-error {
            background: #fed7d7;
            border-color: #e53e3e;
            color: #742a2a;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Sistema de Auditorías SEO</h1>
    </div>
    
    <nav class="nav">
        <ul>
            <li><a href="?modulo=dashboard" class="<?= $modulo === 'dashboard' ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li><a href="?modulo=auditorias" class="<?= $modulo === 'auditorias' ? 'active' : '' ?>">📋 Auditorías</a></li>
            <li><a href="?modulo=clientes" class="<?= $modulo === 'clientes' ? 'active' : '' ?>">👥 Clientes</a></li>
            <li><a href="?modulo=pasos" class="<?= $modulo === 'pasos' ? 'active' : '' ?>">📝 Pasos</a></li>
            <li><a href="?modulo=archivos" class="<?= $modulo === 'archivos' ? 'active' : '' ?>">📁 Archivos</a></li>
        </ul>
    </nav>
    
    <div class="container">
        <div class="content">
            <?php echo mostrarNotificaciones(); ?>
            
            <?php
            // Mostrar contenido según el módulo
            switch ($modulo) {
                case 'auditorias':
                    if (function_exists('manejarAuditorias')) {
                        manejarAuditorias($accion, $_GET);
                    } else {
                        echo '<div class="content-body"><p>Módulo de auditorías no disponible.</p></div>';
                    }
                    break;
                    
                case 'clientes':
                    if (function_exists('manejarClientes')) {
                        manejarClientes($accion, $_GET);
                    } else {
                        echo '<div class="content-body"><p>Módulo de clientes no disponible.</p></div>';
                    }
                    break;
                    
                case 'pasos':
                    if (function_exists('manejarPasos')) {
                        manejarPasos($accion, $_GET);
                    } else {
                        echo '<div class="content-body"><p>Módulo de pasos no disponible.</p></div>';
                    }
                    break;
                    
                case 'archivos':
                    if (function_exists('manejarArchivos')) {
                        manejarArchivos($accion, $_GET);
                    } else {
                        echo '<div class="content-body"><p>Módulo de archivos no disponible.</p></div>';
                    }
                    break;
                    
                case 'dashboard':
                default:
                    include 'views/dashboard.php';
                    break;
            }
            ?>
        </div>
    </div>
</body>
</html>