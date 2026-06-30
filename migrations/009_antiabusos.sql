-- Migración 009: anti-abuso de trial
-- Guarda la IP de registro para limitar nuevas cuentas por IP

ALTER TABLE clinicas
    ADD COLUMN registro_ip VARCHAR(45) NULL AFTER estado_saas;
