-- =====================================================================
--  MedApp · Migración 001 · Esquema inicial
--  Compatible con MariaDB 10.4 (XAMPP). Motor InnoDB, utf8mb4.
--
--  Modelo multi-clínica (multi-tenant por columna clinica_id):
--    - Un médico aislado = una clínica con tipo_plan = 'individual'.
--    - Una clínica = varios usuarios bajo el mismo clinica_id.
--  Todas las tablas de datos llevan clinica_id + borrado lógico (deleted_at).
-- =====================================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ---------------------------------------------------------------------
-- Clínicas (el "tenant")
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS clinicas (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre      VARCHAR(150) NOT NULL,
    tipo_plan   ENUM('individual','clinica') NOT NULL DEFAULT 'individual',
    rfc         VARCHAR(20)  DEFAULT NULL,
    telefono    VARCHAR(30)  DEFAULT NULL,
    email       VARCHAR(150) DEFAULT NULL,
    direccion   VARCHAR(255) DEFAULT NULL,
    activo      TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at  TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_clinicas_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Usuarios (cuentas de acceso: médico, recepción, admin de clínica)
--   email único GLOBAL: al iniciar sesión, el email determina la clínica.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS usuarios (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id      INT UNSIGNED NOT NULL,
    nombre          VARCHAR(150) NOT NULL,
    email           VARCHAR(150) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    rol             ENUM('admin_clinica','medico','recepcion') NOT NULL DEFAULT 'medico',
    es_propietario  TINYINT(1)   NOT NULL DEFAULT 0,
    activo          TINYINT(1)   NOT NULL DEFAULT 1,
    last_login      TIMESTAMP    NULL DEFAULT NULL,
    created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email),
    KEY idx_usuarios_clinica (clinica_id),
    KEY idx_usuarios_rol (rol),
    CONSTRAINT fk_usuarios_clinica FOREIGN KEY (clinica_id)
        REFERENCES clinicas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Médicos (datos profesionales ligados a un usuario con rol 'medico')
--   Separamos la identidad profesional (cédula, especialidad, firma) de la
--   cuenta de login. La cédula es obligatoria para recetas (NOM-004 / receta).
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS medicos (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id          INT UNSIGNED NOT NULL,
    usuario_id          INT UNSIGNED DEFAULT NULL,
    nombre              VARCHAR(150) NOT NULL,
    cedula_profesional  VARCHAR(30)  DEFAULT NULL,
    especialidad        VARCHAR(120) DEFAULT NULL,
    cedula_especialidad VARCHAR(30)  DEFAULT NULL,
    universidad         VARCHAR(150) DEFAULT NULL,
    telefono            VARCHAR(30)  DEFAULT NULL,
    activo              TINYINT(1)   NOT NULL DEFAULT 1,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_medicos_clinica (clinica_id),
    KEY idx_medicos_usuario (usuario_id),
    CONSTRAINT fk_medicos_clinica FOREIGN KEY (clinica_id)
        REFERENCES clinicas (id) ON DELETE CASCADE,
    CONSTRAINT fk_medicos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pacientes (campos base alineados a NOM-004-SSA3-2012)
--   El expediente clínico detallado vivirá en tablas aparte (consultas,
--   notas, recetas) en módulos posteriores. Aquí van los datos de filiación.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS pacientes (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id          INT UNSIGNED NOT NULL,
    nombre              VARCHAR(100) NOT NULL,
    apellido_paterno    VARCHAR(100) NOT NULL,
    apellido_materno    VARCHAR(100) DEFAULT NULL,
    sexo                ENUM('M','F','O') DEFAULT NULL,
    fecha_nacimiento    DATE         DEFAULT NULL,
    curp                VARCHAR(18)  DEFAULT NULL,
    telefono            VARCHAR(30)  DEFAULT NULL,
    email               VARCHAR(150) DEFAULT NULL,
    direccion           VARCHAR(255) DEFAULT NULL,
    ciudad              VARCHAR(100) DEFAULT NULL,
    estado              VARCHAR(100) DEFAULT NULL,
    cp                  VARCHAR(10)  DEFAULT NULL,
    tipo_sangre         VARCHAR(5)   DEFAULT NULL,
    alergias            TEXT         DEFAULT NULL,
    antecedentes        TEXT         DEFAULT NULL,
    contacto_emergencia VARCHAR(150) DEFAULT NULL,
    tel_emergencia      VARCHAR(30)  DEFAULT NULL,
    creado_por          INT UNSIGNED DEFAULT NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pacientes_clinica (clinica_id),
    KEY idx_pacientes_nombre (apellido_paterno, apellido_materno, nombre),
    KEY idx_pacientes_curp (curp),
    KEY idx_pacientes_telefono (telefono),
    CONSTRAINT fk_pacientes_clinica FOREIGN KEY (clinica_id)
        REFERENCES clinicas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Auditoría (bitácora de acceso/acciones; exigida por LFPDPPP)
--   No se edita ni se borra. Sin FK estricta a usuario para conservar el
--   registro aunque el usuario se elimine.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS auditoria (
    id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id  INT UNSIGNED DEFAULT NULL,
    usuario_id  INT UNSIGNED DEFAULT NULL,
    accion      VARCHAR(80)  NOT NULL,
    entidad     VARCHAR(50)  DEFAULT NULL,
    entidad_id  INT UNSIGNED DEFAULT NULL,
    detalle     TEXT         DEFAULT NULL,
    ip          VARCHAR(45)  DEFAULT NULL,
    user_agent  VARCHAR(255) DEFAULT NULL,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_auditoria_clinica (clinica_id),
    KEY idx_auditoria_usuario (usuario_id),
    KEY idx_auditoria_accion (accion),
    KEY idx_auditoria_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
--  DATOS SEMILLA (demo)
--  Clínica individual + usuario propietario (médico) + ficha de médico.
--  Login:  admin@demo.com   /   password123
--  CAMBIA esta contraseña tras el primer acceso.
-- =====================================================================

INSERT INTO clinicas (id, nombre, tipo_plan, telefono, email, activo)
VALUES (1, 'Consultorio Demo', 'individual', '8990000000', 'contacto@demo.com', 1);

INSERT INTO usuarios (id, clinica_id, nombre, email, password_hash, rol, es_propietario, activo)
VALUES (
    1, 1, 'Dr. Demo',
    'admin@demo.com',
    '$2y$10$L8IGZCevfnSEt2YOdop52ek6UPbp2BOMPBU4U4nnVAG4SNeFVLVNq', -- password123
    'medico', 1, 1
);

INSERT INTO medicos (id, clinica_id, usuario_id, nombre, cedula_profesional, especialidad)
VALUES (1, 1, 1, 'Dr. Demo', '0000000', 'Medicina General');

-- Usuario de recepción de ejemplo (misma clínica). Contraseña: password123
INSERT INTO usuarios (id, clinica_id, nombre, email, password_hash, rol, es_propietario, activo)
VALUES (
    2, 1, 'Recepción Demo',
    'recepcion@demo.com',
    '$2y$10$L8IGZCevfnSEt2YOdop52ek6UPbp2BOMPBU4U4nnVAG4SNeFVLVNq', -- password123
    'recepcion', 0, 1
);
