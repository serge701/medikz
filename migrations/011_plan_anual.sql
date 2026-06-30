-- Migración 011: Plan anual de suscripción
-- Ejecutar en phpMyAdmin o línea de comandos MySQL

-- Precio anual negociado por clínica (centavos, NULL = precio estándar de config)
ALTER TABLE clinicas
    ADD COLUMN precio_anual INT NULL DEFAULT NULL
    AFTER precio_mensual;

-- Plan elegido al momento del pago (mensual | anual)
ALTER TABLE pagos
    ADD COLUMN plan VARCHAR(10) NOT NULL DEFAULT 'mensual'
    AFTER concepto;
