-- =====================================================
-- ESQUEMA DE BASE DE DATOS - SISTEMA DE AUDITORÍAS SEO
-- =====================================================
-- Versión: 1.0
-- Base de datos: SQLite
-- Descripción: Esquema completo para el sistema de gestión de auditorías SEO

-- =====================================================
-- TABLA: USUARIOS
-- =====================================================
CREATE TABLE usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    rol ENUM('admin', 'consultor') DEFAULT 'consultor',
    activo BOOLEAN DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: CLIENTES
-- =====================================================
CREATE TABLE clientes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre_empresa VARCHAR(200) NOT NULL,
    nombre_contacto VARCHAR(100),
    email_contacto VARCHAR(150),
    telefono VARCHAR(20),
    sitio_web VARCHAR(255),
    sector VARCHAR(100),
    descripcion TEXT,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: FASES
-- =====================================================
CREATE TABLE fases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    numero_fase INTEGER NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- TABLA: PASOS_PLANTILLA
-- =====================================================
CREATE TABLE pasos_plantilla (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    fase_id INTEGER NOT NULL,
    codigo_paso VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(300) NOT NULL,
    descripcion TEXT,
    es_critico BOOLEAN DEFAULT 0,
    tiempo_estimado_horas DECIMAL(5,2) DEFAULT 0,
    orden_en_fase INTEGER DEFAULT 0,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (fase_id) REFERENCES fases(id)
);

-- =====================================================
-- TABLA: AUDITORIAS
-- =====================================================
CREATE TABLE auditorias (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cliente_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    nombre_auditoria VARCHAR(300) NOT NULL,
    descripcion TEXT,
    sitio_web VARCHAR(255),
    estado ENUM('borrador', 'en_progreso', 'revision', 'completada', 'cancelada') DEFAULT 'borrador',
    prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    porcentaje_completado DECIMAL(5,2) DEFAULT 0,
    fecha_inicio DATE,
    fecha_fin_estimada DATE,
    fecha_fin_real DATE,
    notas_generales TEXT,
    activo BOOLEAN DEFAULT 1,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- =====================================================
-- TABLA: AUDITORIA_PASOS
-- =====================================================
CREATE TABLE auditoria_pasos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auditoria_id INTEGER NOT NULL,
    paso_plantilla_id INTEGER NOT NULL,
    nombre_personalizado VARCHAR(300),
    descripcion_personalizada TEXT,
    estado ENUM('pendiente', 'en_progreso', 'completado', 'omitido') DEFAULT 'pendiente',
    prioridad ENUM('baja', 'media', 'alta', 'critica') DEFAULT 'media',
    tiempo_estimado_horas DECIMAL(5,2) DEFAULT 0,
    tiempo_real_horas DECIMAL(5,2) DEFAULT 0,
    fecha_inicio DATETIME,
    fecha_completado DATETIME,
    notas TEXT,
    resultado TEXT,
    orden_personalizado INTEGER DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
    FOREIGN KEY (paso_plantilla_id) REFERENCES pasos_plantilla(id)
);

-- =====================================================
-- TABLA: ARCHIVOS
-- =====================================================
CREATE TABLE archivos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auditoria_paso_id INTEGER NOT NULL,
    nombre_original VARCHAR(255) NOT NULL,
    nombre_archivo VARCHAR(255) NOT NULL,
    ruta_archivo VARCHAR(500) NOT NULL,
    tipo_mime VARCHAR(100),
    tamaño_bytes INTEGER DEFAULT 0,
    descripcion TEXT,
    es_publico BOOLEAN DEFAULT 0,
    eliminado BOOLEAN DEFAULT 0,
    fecha_subida DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_paso_id) REFERENCES auditoria_pasos(id) ON DELETE CASCADE
);

-- =====================================================
-- TABLA: COMENTARIOS
-- =====================================================
CREATE TABLE comentarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auditoria_paso_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    comentario TEXT NOT NULL,
    es_interno BOOLEAN DEFAULT 0,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_paso_id) REFERENCES auditoria_pasos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- =====================================================
-- TABLA: HISTORIAL_CAMBIOS
-- =====================================================
CREATE TABLE historial_cambios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    auditoria_id INTEGER NOT NULL,
    usuario_id INTEGER NOT NULL,
    tipo_cambio VARCHAR(50) NOT NULL,
    descripcion_cambio TEXT NOT NULL,
    datos_anteriores TEXT,
    datos_nuevos TEXT,
    fecha_cambio DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (auditoria_id) REFERENCES auditorias(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
);

-- =====================================================
-- DATOS INICIALES
-- =====================================================

-- Insertar fases iniciales
INSERT INTO fases (numero_fase, nombre, descripcion) VALUES
(1, 'Análisis Inicial y Configuración', 'Configuración inicial del proyecto y análisis preliminar'),
(2, 'Auditoría Técnica', 'Análisis técnico completo del sitio web'),
(3, 'Análisis de Contenido y SEO On-Page', 'Evaluación del contenido y optimización on-page'),
(4, 'Análisis de SEO Off-Page y Autoridad', 'Análisis de enlaces y autoridad del dominio'),
(5, 'Informes y Recomendaciones', 'Generación de informes finales y recomendaciones');

-- Insertar usuario administrador por defecto
INSERT INTO usuarios (nombre, email, password, rol) VALUES
('Administrador', 'admin@auditoria-seo.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
-- Nota: La contraseña por defecto es 'password' (debe cambiarse en producción)

-- =====================================================
-- ÍNDICES PARA OPTIMIZACIÓN
-- =====================================================

-- Índices para usuarios
CREATE INDEX idx_usuarios_email ON usuarios(email);
CREATE INDEX idx_usuarios_activo ON usuarios(activo);

-- Índices para clientes
CREATE INDEX idx_clientes_nombre_empresa ON clientes(nombre_empresa);
CREATE INDEX idx_clientes_activo ON clientes(activo);

-- Índices para pasos_plantilla
CREATE INDEX idx_pasos_plantilla_fase ON pasos_plantilla(fase_id);
CREATE INDEX idx_pasos_plantilla_codigo ON pasos_plantilla(codigo_paso);
CREATE INDEX idx_pasos_plantilla_activo ON pasos_plantilla(activo);

-- Índices para auditorías
CREATE INDEX idx_auditorias_cliente ON auditorias(cliente_id);
CREATE INDEX idx_auditorias_usuario ON auditorias(usuario_id);
CREATE INDEX idx_auditorias_estado ON auditorias(estado);
CREATE INDEX idx_auditorias_fecha_inicio ON auditorias(fecha_inicio);
CREATE INDEX idx_auditorias_activo ON auditorias(activo);

-- Índices para auditoria_pasos
CREATE INDEX idx_auditoria_pasos_estado ON auditoria_pasos(estado);
CREATE INDEX idx_auditoria_pasos_auditoria ON auditoria_pasos(auditoria_id);
CREATE INDEX idx_auditoria_pasos_fecha_completado ON auditoria_pasos(fecha_completado);

CREATE INDEX idx_archivos_paso ON archivos(auditoria_paso_id);
CREATE INDEX idx_archivos_fecha ON archivos(fecha_subida);

CREATE INDEX idx_comentarios_paso ON comentarios(auditoria_paso_id);
CREATE INDEX idx_comentarios_fecha ON comentarios(fecha_creacion);

CREATE INDEX idx_historial_auditoria ON historial_cambios(auditoria_id);
CREATE INDEX idx_historial_fecha ON historial_cambios(fecha_cambio);

-- =====================================================
-- VISTAS ÚTILES PARA CONSULTAS FRECUENTES
-- =====================================================

-- Vista: Resumen de auditorías con información del cliente
CREATE VIEW vista_auditorias_resumen AS
SELECT 
    a.id,
    a.nombre_auditoria,
    c.nombre_empresa as cliente,
    u.nombre as consultor,
    a.estado,
    a.porcentaje_completado,
    a.fecha_inicio,
    a.fecha_fin_estimada,
    COUNT(ap.id) as total_pasos,
    SUM(CASE WHEN ap.estado = 'completado' THEN 1 ELSE 0 END) as pasos_completados,
    SUM(CASE WHEN ap.estado = 'pendiente' THEN 1 ELSE 0 END) as pasos_pendientes
FROM auditorias a
LEFT JOIN clientes c ON a.cliente_id = c.id
LEFT JOIN usuarios u ON a.usuario_id = u.id
LEFT JOIN auditoria_pasos ap ON a.id = ap.auditoria_id
GROUP BY a.id;

-- Vista: Pasos por fase con información detallada
CREATE VIEW vista_pasos_por_fase AS
SELECT 
    f.numero_fase,
    f.nombre as fase_nombre,
    pt.codigo_paso,
    pt.nombre as paso_nombre,
    pt.es_critico,
    pt.tiempo_estimado_horas,
    pt.orden_en_fase
FROM fases f
LEFT JOIN pasos_plantilla pt ON f.id = pt.fase_id
WHERE pt.activo = 1
ORDER BY f.numero_fase, pt.orden_en_fase;

-- =====================================================
-- COMENTARIOS FINALES
-- =====================================================
/*
ESTRUCTURA DE LA BASE DE DATOS:

1. USUARIOS: Gestión de consultores y administradores
2. CLIENTES: Información de empresas que solicitan auditorías
3. FASES: Las 5 fases principales del proceso de auditoría
4. PASOS_PLANTILLA: Plantilla de pasos importados desde documentos .md
5. AUDITORIAS: Auditorías principales con información general
6. AUDITORIA_PASOS: Pasos específicos de cada auditoría con estados
7. ARCHIVOS: Archivos subidos asociados a cada paso
8. COMENTARIOS: Notas y comentarios en pasos específicos
9. HISTORIAL_CAMBIOS: Log completo de cambios para auditoría

CARACTERÍSTICAS PRINCIPALES:
- Estructura normalizada y escalable
- Soporte para múltiples usuarios y clientes
- Tracking completo de progreso y cambios
- Flexibilidad para personalizar pasos por auditoría
- Sistema de archivos organizado
- Índices optimizados para consultas frecuentes
- Vistas para facilitar consultas
- Compatibilidad con SQLite
*/