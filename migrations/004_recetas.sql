-- =====================================================================
--  MedApp · Migración 004 · Recetas digitales
--  Ejecutar después de 003_consultas.sql.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS recetas (
    id                      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id              INT UNSIGNED NOT NULL,
    paciente_id             INT UNSIGNED NOT NULL,
    medico_id               INT UNSIGNED DEFAULT NULL,
    consulta_id             INT UNSIGNED DEFAULT NULL,
    fecha_receta            DATE         NOT NULL,
    diagnostico             TEXT         DEFAULT NULL,
    medicamentos            JSON         NOT NULL,
    indicaciones_generales  TEXT         DEFAULT NULL,
    codigo_verificacion     VARCHAR(12)  NOT NULL,
    creado_por              INT UNSIGNED DEFAULT NULL,
    created_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at              TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at              TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_recetas_codigo (codigo_verificacion),
    KEY idx_recetas_clinica   (clinica_id),
    KEY idx_recetas_paciente  (paciente_id),
    KEY idx_recetas_medico    (medico_id),
    KEY idx_recetas_consulta  (consulta_id),
    KEY idx_recetas_fecha     (fecha_receta),
    CONSTRAINT fk_recetas_clinica   FOREIGN KEY (clinica_id)   REFERENCES clinicas (id)   ON DELETE CASCADE,
    CONSTRAINT fk_recetas_paciente  FOREIGN KEY (paciente_id)  REFERENCES pacientes (id)  ON DELETE CASCADE,
    CONSTRAINT fk_recetas_medico    FOREIGN KEY (medico_id)    REFERENCES medicos (id)    ON DELETE SET NULL,
    CONSTRAINT fk_recetas_consulta  FOREIGN KEY (consulta_id)  REFERENCES consultas (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
