-- Tabla para rastrear recordatorios enviados por WhatsApp
CREATE TABLE IF NOT EXISTS recordatorios (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    clinica_id   INT UNSIGNED NOT NULL,
    cita_id      INT UNSIGNED NOT NULL,
    tipo         ENUM('confirmacion', 'recordatorio_24h') NOT NULL,
    telefono     VARCHAR(20)  NOT NULL,
    estado       ENUM('enviado', 'error', 'simulado') NOT NULL DEFAULT 'enviado',
    twilio_sid   VARCHAR(64)  NULL,
    error_msg    TEXT         NULL,
    enviado_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_cita_tipo (cita_id, tipo),
    INDEX idx_clinica  (clinica_id),
    INDEX idx_enviado  (enviado_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
