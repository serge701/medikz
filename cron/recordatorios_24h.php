<?php
declare(strict_types=1);

/**
 * Cron: envía recordatorios de WhatsApp 24 h antes de cada cita.
 *
 * Ejecutar diariamente, p.ej. a las 8:00 AM:
 *   php C:\xampp\htdocs\medapp\cron\recordatorios_24h.php
 *
 * Windows Task Scheduler:
 *   Programa: C:\xampp\php\php.exe
 *   Argumentos: C:\xampp\htdocs\medapp\cron\recordatorios_24h.php
 *   Horario: diario 08:00
 *
 * Linux crontab:
 *   0 8 * * * php /var/www/medapp/cron/recordatorios_24h.php >> /var/log/medapp_cron.log 2>&1
 */

// Solo CLI
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Solo CLI');
}

define('MEDAPP_ROOT', dirname(__DIR__));
require MEDAPP_ROOT . '/vendor/autoload.php';

// Arrancar config (sin sesión ni HTTP)
\App\Core\Config::boot();

$pdo = \App\Core\Database::conn();

$dias  = ['domingo','lunes','martes','miércoles','jueves','viernes','sábado'];
$meses = ['','enero','febrero','marzo','abril','mayo','junio','julio',
          'agosto','septiembre','octubre','noviembre','diciembre'];

$manana = (new DateTime('tomorrow'))->format('Y-m-d');

// Buscar citas de mañana que estén programadas/confirmadas y sin recordatorio_24h
$sql = "SELECT c.id, c.clinica_id, c.fecha, c.hora_inicio,
               p.nombre AS pac_nombre, p.apellido_paterno AS pac_ap, p.telefono,
               cl.nombre AS clinica_nombre
        FROM citas c
        INNER JOIN pacientes p  ON p.id  = c.paciente_id AND p.deleted_at IS NULL
        INNER JOIN clinicas  cl ON cl.id = c.clinica_id  AND cl.deleted_at IS NULL
        WHERE c.fecha      = :manana
          AND c.deleted_at IS NULL
          AND c.estado     IN ('programada', 'confirmada')
          AND NOT EXISTS (
              SELECT 1 FROM recordatorios r
              WHERE r.cita_id = c.id AND r.tipo = 'recordatorio_24h'
          )
        ORDER BY c.clinica_id, c.hora_inicio";

$stmt = $pdo->prepare($sql);
$stmt->execute([':manana' => $manana]);
$citas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($citas)) {
    echo "[" . date('Y-m-d H:i:s') . "] Sin recordatorios pendientes para {$manana}.\n";
    exit(0);
}

$wa  = new \App\Services\WhatsappService();
$ok  = 0;
$err = 0;

foreach ($citas as $cita) {
    $telefono = trim((string) ($cita['telefono'] ?? ''));
    if ($telefono === '') {
        echo "  [SKIP] Cita #{$cita['id']} — paciente sin teléfono\n";
        continue;
    }

    $ts       = strtotime($cita['fecha']);
    $fechaStr = $dias[date('w', $ts)] . ' '
              . (int)date('j', $ts) . ' de '
              . $meses[(int)date('n', $ts)];
    $hora     = !empty($cita['hora_inicio'])
        ? substr((string)$cita['hora_inicio'], 0, 5)
        : '';

    $nombrePac = trim($cita['pac_nombre'] . ' ' . $cita['pac_ap']);
    $clinica   = $cita['clinica_nombre'] ?? 'la clínica';

    $mensaje = "Recordatorio: {$nombrePac}, tienes cita mañana en {$clinica} "
             . "({$fechaStr}" . ($hora ? " a las {$hora}" : '') . "). "
             . "Si necesitas cancelar, comunícate con nosotros con anticipación.";

    $enviado = $wa->enviar(
        (int) $cita['clinica_id'],
        (int) $cita['id'],
        'recordatorio_24h',
        $telefono,
        $mensaje
    );

    if ($enviado) {
        echo "  [OK]  Cita #{$cita['id']} → {$telefono}\n";
        $ok++;
    } else {
        echo "  [ERR] Cita #{$cita['id']} → {$telefono}\n";
        $err++;
    }
}

echo "[" . date('Y-m-d H:i:s') . "] Listo. Enviados: {$ok}, Errores: {$err}\n";
exit(0);
