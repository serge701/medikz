-- =====================================================================
--  MedApp · Migración 003 · Historial clínico / Consultas
--  Ejecutar después de 002_citas.sql.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS consultas (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id          INT UNSIGNED NOT NULL,
    paciente_id         INT UNSIGNED NOT NULL,
    medico_id           INT UNSIGNED DEFAULT NULL,
    cita_id             INT UNSIGNED DEFAULT NULL,
    fecha_consulta      DATE         NOT NULL,
    motivo_consulta     TEXT         DEFAULT NULL,
    exploracion_fisica  TEXT         DEFAULT NULL,
    diagnostico         TEXT         DEFAULT NULL,
    tratamiento         TEXT         DEFAULT NULL,
    observaciones       TEXT         DEFAULT NULL,
    proximo_control     DATE         DEFAULT NULL,
    creado_por          INT UNSIGNED DEFAULT NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_consultas_clinica  (clinica_id),
    KEY idx_consultas_paciente (paciente_id),
    KEY idx_consultas_medico   (medico_id),
    KEY idx_consultas_cita     (cita_id),
    KEY idx_consultas_fecha    (fecha_consulta),
    CONSTRAINT fk_consultas_clinica  FOREIGN KEY (clinica_id)  REFERENCES clinicas (id)  ON DELETE CASCADE,
    CONSTRAINT fk_consultas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes (id) ON DELETE CASCADE,
    CONSTRAINT fk_consultas_medico   FOREIGN KEY (medico_id)   REFERENCES medicos (id)   ON DELETE SET NULL,
    CONSTRAINT fk_consultas_cita     FOREIGN KEY (cita_id)     REFERENCES citas (id)     ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
