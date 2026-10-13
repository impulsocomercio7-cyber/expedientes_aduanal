CREATE DATABASE IF NOT EXISTS expedientes_aduanal CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE expedientes_aduanal;

CREATE TABLE IF NOT EXISTS usuarios (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(120) NOT NULL,
 usuario VARCHAR(60) NOT NULL UNIQUE,
 correo VARCHAR(190) NULL,
 password_hash VARCHAR(255) NOT NULL,
 rol ENUM('Administrador','Usuario común') NOT NULL DEFAULT 'Usuario común',
 activo TINYINT(1) NOT NULL DEFAULT 1,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en DATETIME NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS clientes (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 codigo VARCHAR(40) NOT NULL UNIQUE,
 tipo_persona ENUM('Fisica','Moral') NOT NULL,
 rfc VARCHAR(13) NOT NULL,
 nombre VARCHAR(220) NOT NULL,
 direccion TEXT NOT NULL,
 telefono VARCHAR(40) NULL,
 correo VARCHAR(190) NULL,
 estatus ENUM('Acreditado','En espera','Rechazado') NOT NULL DEFAULT 'En espera',
 estatus_manual ENUM('Automatico','Rechazado') NOT NULL DEFAULT 'Automatico',
 creado_por INT UNSIGNED NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 actualizado_en DATETIME NULL,
 deleted_at DATETIME NULL,
 eliminado_por INT UNSIGNED NULL,
 CONSTRAINT fk_clientes_creador FOREIGN KEY (creado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
 CONSTRAINT fk_clientes_eliminado FOREIGN KEY (eliminado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
 INDEX idx_clientes_rfc (rfc), INDEX idx_clientes_estatus (estatus), INDEX idx_clientes_deleted (deleted_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tipos_documento (
 id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 nombre VARCHAR(255) NOT NULL,
 descripcion TEXT NULL,
 aplica_fisica TINYINT(1) NOT NULL DEFAULT 1,
 aplica_moral TINYINT(1) NOT NULL DEFAULT 1,
 obligatorio TINYINT(1) NOT NULL DEFAULT 1,
 bloqueante TINYINT(1) NOT NULL DEFAULT 1,
 requiere_emision TINYINT(1) NOT NULL DEFAULT 0,
 requiere_vencimiento TINYINT(1) NOT NULL DEFAULT 0,
 activo TINYINT(1) NOT NULL DEFAULT 1,
 orden INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS documentos_cliente (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 cliente_id INT UNSIGNED NOT NULL,
 tipo_documento_id INT UNSIGNED NOT NULL,
 nombre_original VARCHAR(255) NOT NULL,
 nombre_archivo VARCHAR(255) NOT NULL,
 ruta_relativa VARCHAR(500) NOT NULL,
 mime_type VARCHAR(100) NOT NULL,
 extension VARCHAR(10) NOT NULL,
 tamano_bytes BIGINT UNSIGNED NOT NULL,
 fecha_emision DATE NULL,
 fecha_vencimiento DATE NULL,
 observaciones TEXT NULL,
 version INT UNSIGNED NOT NULL DEFAULT 1,
 es_version_actual TINYINT(1) NOT NULL DEFAULT 1,
 cargado_por INT UNSIGNED NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_doc_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
 CONSTRAINT fk_doc_tipo FOREIGN KEY (tipo_documento_id) REFERENCES tipos_documento(id),
 CONSTRAINT fk_doc_usuario FOREIGN KEY (cargado_por) REFERENCES usuarios(id) ON DELETE SET NULL,
 INDEX idx_doc_cliente_tipo (cliente_id,tipo_documento_id,es_version_actual),
 INDEX idx_doc_vencimiento (fecha_vencimiento)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS historial_estatus (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 cliente_id INT UNSIGNED NOT NULL,
 estatus_anterior VARCHAR(30) NULL,
 estatus_nuevo VARCHAR(30) NOT NULL,
 motivo VARCHAR(500) NULL,
 usuario_id INT UNSIGNED NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_hist_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id),
 CONSTRAINT fk_hist_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS bitacora (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 usuario_id INT UNSIGNED NULL,
 cliente_id INT UNSIGNED NULL,
 accion VARCHAR(100) NOT NULL,
 detalle JSON NULL,
 ip VARCHAR(45) NULL,
 creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_bitacora_fecha (creado_en),
 CONSTRAINT fk_bitacora_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
 CONSTRAINT fk_bitacora_cliente FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Cuenta inicial. Cambiar la contraseña inmediatamente después de entrar.
-- Hash generado para CambiarEstaClave!2026
INSERT IGNORE INTO usuarios (nombre, usuario, correo, password_hash, rol, activo)
VALUES ('Administrador del sistema', 'admin', NULL, '$2y$12$8ycZpFu9UrHMNnNyZDXqY.2iCRetxUD4CDibs/.TLZ51sJN1vGEBe', 'Administrador', 1);

INSERT INTO tipos_documento
 (nombre, aplica_fisica, aplica_moral, obligatorio, bloqueante, requiere_emision, requiere_vencimiento, orden)
SELECT x.nombre, x.aplica_fisica, x.aplica_moral, x.obligatorio, x.bloqueante,
       x.requiere_emision, x.requiere_vencimiento, x.orden
FROM (
 SELECT 'Carta de encomienda' AS nombre,1 AS aplica_fisica,1 AS aplica_moral,1 AS obligatorio,1 AS bloqueante,0 AS requiere_emision,0 AS requiere_vencimiento,10 AS orden UNION ALL
 SELECT 'Contrato de prestación de servicios (imprimir a doble cara)',1,1,1,1,0,0,20 UNION ALL
 SELECT 'Carta bajo protesta de decir verdad — Regla 1.4.14',1,1,1,1,0,0,30 UNION ALL
 SELECT 'Carta bajo protesta de datos del importador',1,1,1,1,0,0,40 UNION ALL
 SELECT 'Constancia de Situación Fiscal (reciente)',1,1,1,1,1,0,50 UNION ALL
 SELECT 'Opinión de Cumplimiento SAT (reciente)',1,1,1,1,1,1,60 UNION ALL
 SELECT 'Opinión de cumplimiento IMSS o carta responsiva / opinión sin obligaciones patronales',1,1,1,1,1,1,70 UNION ALL
 SELECT 'Identificación oficial del representante legal',1,1,1,1,0,1,80 UNION ALL
 SELECT 'Comprobante de domicilio donde realiza sus actividades',1,1,1,1,1,1,90 UNION ALL
 SELECT 'Estado de cuenta bancario de la cuenta para transacciones',1,1,1,1,1,0,100 UNION ALL
 SELECT 'Fotografía de la fachada del establecimiento',1,1,1,0,0,0,110 UNION ALL
 SELECT 'Aviso de apertura del establecimiento en el SAT',1,1,1,1,0,0,120 UNION ALL
 SELECT 'Verificación de domicilio SAT o solicitud de verificación',1,1,1,1,0,0,130 UNION ALL
 SELECT 'Contrato de arrendamiento u otro documento de posesión del inmueble',1,1,1,0,1,1,140 UNION ALL
 SELECT 'Escrituras o predial (inmueble propio)',1,1,1,0,1,1,150 UNION ALL
 SELECT 'Fotografía y factura de vehículo(s) de uso empresarial',1,1,1,0,1,0,160 UNION ALL
 SELECT 'Acta constitutiva',0,1,1,1,0,0,170 UNION ALL
 SELECT 'Poder notarial del representante legal (si no está incluido en el acta)',0,1,0,0,0,0,180
) AS x
WHERE NOT EXISTS (SELECT 1 FROM tipos_documento LIMIT 1);
