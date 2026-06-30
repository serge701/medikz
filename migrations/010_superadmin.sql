-- Migración 010: precio negociado por clínica + flag superadmin

-- Precio mensual negociado en centavos MXN. NULL = usa el precio estándar de config.
ALTER TABLE clinicas
    ADD COLUMN precio_mensual INT NULL DEFAULT NULL
        COMMENT 'Precio negociado en centavos MXN. NULL = precio estándar.'
        AFTER registro_ip;

-- Flag de super-admin (solo el dueño del SaaS)
-- Para activarlo: UPDATE usuarios SET superadmin = 1 WHERE email = 'tu@email.com';
ALTER TABLE usuarios
    ADD COLUMN superadmin TINYINT(1) NOT NULL DEFAULT 0 AFTER es_propietario;
