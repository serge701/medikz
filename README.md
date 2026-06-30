# MedApp · Cimiento (v0.1)

Sistema de gestión de consultorio médico. PHP puro + MariaDB + AdminLTE 4.
Arquitectura **multi-clínica** (multi-tenant): un médico aislado es una clínica
con `tipo_plan = 'individual'`; una clínica grande es la misma estructura con
varios usuarios bajo el mismo `clinica_id`.

---

## Instalación en XAMPP (local)

1. **Copia la carpeta** `medapp` dentro de `xampp/htdocs/`.
   La app quedará en `http://localhost/medapp/`.

2. **Crea la base de datos e importa el esquema:**
   - Abre phpMyAdmin → pestaña *SQL*, o desde consola.
   - Crea la base: `CREATE DATABASE medapp CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;`
   - Selecciona `medapp` e **importa** el archivo `migrations/001_init.sql`.

3. **Revisa credenciales** en `config/config.php`
   (por defecto XAMPP: usuario `root`, contraseña vacía).

4. **Verifica que `mod_rewrite` esté activo** en Apache (en XAMPP viene activo por
   defecto). El `.htaccess` de la raíz enruta todo a `index.php`.

5. **Entra** a `http://localhost/medapp/`

### Acceso demo

| Rol        | Correo                | Contraseña    |
|------------|-----------------------|---------------|
| Médico     | `admin@demo.com`      | `password123` |
| Recepción  | `recepcion@demo.com`  | `password123` |

> Cambia estas contraseñas en cuanto verifiques el acceso.
> Inicia sesión con **recepción** para comprobar que NO ve el menú
> *Historial clínico* ni *Recetas*.

---

## Sobre el problema del `/` en los enlaces

Resuelto de raíz: `BASE_URL` se **detecta automáticamente** y todos los enlaces
pasan por el helper `url()`. Nunca escribas rutas como `/login`; usa
`url('login')`. Así funciona igual en `localhost/medapp/` y en producción.

---

## Estructura

```
medapp/
├── index.php              ← front controller (único punto de entrada)
├── .htaccess              ← rewrite a index.php
├── routes.php             ← tabla de rutas
├── composer.json          ← librerías para módulos futuros (PDF, QR, mail)
├── config/
│   └── config.php         ← credenciales y ajustes
├── migrations/
│   └── 001_init.sql       ← esquema + datos semilla
├── storage/logs/          ← logs (protegido por .htaccess)
└── app/
    ├── Core/              ← Router, Database, Auth, Csrf, Tenant, Auditoria, BaseModel...
    ├── Controllers/
    ├── Models/
    └── Views/
```

## Seguridad ya incluida en el cimiento

- **Aislamiento multi-clínica** automático en `BaseModel` (todo `find/all/update/delete`
  filtra por `clinica_id` de la sesión; imposible filtrar datos entre clínicas).
- **Sesión endurecida** (httponly, samesite, regeneración de ID al login).
- **Contraseñas** con `password_hash()` / `password_verify()` (bcrypt).
- **CSRF** en todos los formularios POST.
- **Consultas preparadas** (PDO, sin concatenar SQL).
- **Borrado lógico** (`deleted_at`) — los datos clínicos nunca se borran físicamente.
- **Bitácora de auditoría** (tabla `auditoria`).
- **Separación de acceso clínico**: la recepción no puede ver historial ni recetas
  (bloqueado en menú y en controlador).

## Orden de construcción

1. ✅ Cimiento (este entregable)
2. ⬜ Pacientes
3. ⬜ Agenda y citas
4. ⬜ Historial clínico / consultas
5. ⬜ Recetas digitales (PDF + QR + cédula)
6. ⬜ Cobros / ventas
7. ⬜ Métricas

## Notas para producción (más adelante)

- AdminLTE 4 se carga por CDN para arrancar rápido. Para producción,
  autohospedar los assets en `/assets`.
- Poner `'debug' => false` en `config/config.php`.
- Idealmente, apuntar el *DocumentRoot* a una carpeta `public/` en vez de la raíz.
- Mover credenciales a variables de entorno.
