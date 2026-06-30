-- =====================================================================
--  MedApp · Migración 002 · Módulo de Agenda y Citas
--  Ejecutar después de 001_init.sql.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS citas (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    clinica_id          INT UNSIGNED NOT NULL,
    paciente_id         INT UNSIGNED NOT NULL,
    medico_id           INT UNSIGNED DEFAULT NULL,
    fecha               DATE         NOT NULL,
    hora_inicio         TIME         NOT NULL,
    hora_fin            TIME         NOT NULL,
    motivo              VARCHAR(255) DEFAULT NULL,
    estado              ENUM('programada','confirmada','atendida','cancelada','no_asistio')
                            NOT NULL DEFAULT 'programada',
    notas               TEXT         DEFAULT NULL,
    motivo_cancelacion  TEXT         DEFAULT NULL,
    creado_por          INT UNSIGNED DEFAULT NULL,
    created_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at          TIMESTAMP    NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_citas_clinica  (clinica_id),
    KEY idx_citas_fecha    (fecha),
    KEY idx_citas_paciente (paciente_id),
    KEY idx_citas_medico   (medico_id),
    KEY idx_citas_estado   (estado),
    CONSTRAINT fk_citas_clinica  FOREIGN KEY (clinica_id)  REFERENCES clinicas (id)  ON DELETE CASCADE,
    CONSTRAINT fk_citas_paciente FOREIGN KEY (paciente_id) REFERENCES pacientes (id) ON DELETE CASCADE,
    CONSTRAINT fk_citas_medico   FOREIGN KEY (medico_id)   REFERENCES medicos (id)   ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
