-- =====================================================================
--  MedApp · Migración 006 · SaaS: Trial + Suscripciones + Pagos
-- =====================================================================
SET NAMES utf8mb4;

ALTER TABLE clinicas
    ADD COLUMN IF NOT EXISTS trial_ends_at     DATE     DEFAULT NULL          AFTER activo,
    ADD COLUMN IF NOT EXISTS suscripcion_hasta DATE     DEFAULT NULL          AFTER trial_ends_at,
    ADD COLUMN IF NOT EXISTS estado_saas       ENUM('trial','activo','suspendido')
                                               NOT NULL DEFAULT 'trial'       AFTER suscripcion_hasta;

-- Índice para consultas de estado
ALTER TABLE clinicas
    ADD INDEX IF NOT EXISTS idx_clinicas_estado_saas (estado_saas);

-- Actualizar la clínica demo a "activo" sin vencimiento (cuenta eterna de demo)
UPDATE clinicas SET estado_saas = 'activo', suscripcion_hasta = '2099-12-31' WHERE id = 1;

CREATE TABLE IF NOT EXISTS pagos (
    id                    INT UNSIGNED    NOT NULL AUTO_INCREMENT,
    clinica_id            INT UNSIGNED    NOT NULL,
    stripe_session_id     VARCHAR(200)    DEFAULT NULL,
    stripe_payment_intent VARCHAR(200)    DEFAULT NULL,
    monto                 DECIMAL(10,2)   NOT NULL,
    moneda                VARCHAR(3)      NOT NULL DEFAULT 'MXN',
    concepto              VARCHAR(255)    NOT NULL DEFAULT 'Suscripción mensual MedApp',
    estado                ENUM('pendiente','completado','fallido') NOT NULL DEFAULT 'pendiente',
    periodo_inicio        DATE            DEFAULT NULL,
    periodo_fin           DATE            DEFAULT NULL,
    created_at            TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagos_clinica (clinica_id),
    KEY idx_pagos_session (stripe_session_id),
    CONSTRAINT fk_pagos_clinica FOREIGN KEY (clinica_id) REFERENCES clinicas (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
