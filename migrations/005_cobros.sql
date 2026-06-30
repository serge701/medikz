-- =====================================================================
--  MedApp · Migración 005 · Cobros / Ventas
--  Ejecutar después de 004_recetas.sql.
-- =====================================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS cobros (
    id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinica_id      INT UNSIGNED    NOT NULL,
    paciente_id     INT UNSIGNED    NOT NULL,
    cita_id         INT UNSIGNED    DEFAULT NULL,
    consulta_id     INT UNSIGNED    DEFAULT NULL,
    fecha_cobro     DATE            NOT NULL,
    concepto        VARCHAR(255)    NOT NULL,
    monto           DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    metodo_pago     ENUM('efectivo','tarjeta','transferencia','cheque')
                                    NOT NULL DEFAULT 'efectivo',
    estado          ENUM('pagado','pendiente','cancelado')
                                    NOT NULL DEFAULT 'pagado',
    notas           TEXT            DEFAULT NULL,
    creado_por      INT UNSIGNED    DEFAULT NULL,
    created_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      TIMESTAMP       NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_cobros_clinica   (clinica_id),
    KEY idx_cobros_paciente  (paciente_id),
    KEY idx_cobros_fecha     (fecha_cobro),
    KEY idx_cobros_estado    (estado),
    KEY idx_cobros_cita      (cita_id),
    KEY idx_cobros_consulta  (consulta_id),
    CONSTRAINT fk_cobros_clinica   FOREIGN KEY (clinica_id)   REFERENCES clinicas (id)   ON DELETE CASCADE,
    CONSTRAINT fk_cobros_paciente  FOREIGN KEY (paciente_id)  REFERENCES pacientes (id)  ON DELETE CASCADE,
    CONSTRAINT fk_cobros_cita      FOREIGN KEY (cita_id)      REFERENCES citas (id)      ON DELETE SET NULL,
    CONSTRAINT fk_cobros_consulta  FOREIGN KEY (consulta_id)  REFERENCES consultas (id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
