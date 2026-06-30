<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Config;
use App\Core\Database;

final class WhatsappService
{
    private array $cfg;

    public function __construct()
    {
        $this->cfg = Config::get('twilio') ?? [];
    }

    /**
     * Envía un mensaje de WhatsApp y registra el intento en la tabla recordatorios.
     * Usa INSERT IGNORE para que el UNIQUE KEY (cita_id, tipo) evite duplicados.
     *
     * @param int    $clinicaId
     * @param int    $citaId
     * @param string $tipo       'confirmacion' | 'recordatorio_24h'
     * @param string $telefono   Teléfono del paciente (10 dígitos MX o E.164)
     * @param string $mensaje    Texto del mensaje
     */
    public function enviar(int $clinicaId, int $citaId, string $tipo, string $telefono, string $mensaje): bool
    {
        $to = $this->normalizarTelefono($telefono);
        if ($to === '') {
            $this->registrar($clinicaId, $citaId, $tipo, $telefono, 'error', null, 'Teléfono inválido');
            return false;
        }

        $enabled = (bool) ($this->cfg['enabled'] ?? false);

        if (!$enabled) {
            $this->registrar($clinicaId, $citaId, $tipo, $to, 'simulado', 'SIM-' . uniqid(), null);
            error_log("[WhatsApp SIMULADO] To: {$to} | {$mensaje}");
            return true;
        }

        try {
            $sid   = $this->cfg['account_sid'] ?? '';
            $token = $this->cfg['auth_token']  ?? '';
            $from  = $this->cfg['from_whatsapp'] ?? 'whatsapp:+14155238886';

            $client   = new \Twilio\Rest\Client($sid, $token);
            $enviado  = $client->messages->create('whatsapp:' . $to, [
                'from' => $from,
                'body' => $mensaje,
            ]);

            $this->registrar($clinicaId, $citaId, $tipo, $to, 'enviado', $enviado->sid, null);
            return true;
        } catch (\Throwable $e) {
            $this->registrar($clinicaId, $citaId, $tipo, $to, 'error', null, substr($e->getMessage(), 0, 255));
            return false;
        }
    }

    /**
     * Normaliza un teléfono al formato E.164 para México.
     * Acepta: 10 dígitos locales, +521XXXXXXXXXX, 521XXXXXXXXXX, 52XXXXXXXXXX
     */
    public function normalizarTelefono(string $tel): string
    {
        $tel = preg_replace('/\D/', '', $tel) ?? '';

        if (strlen($tel) === 10) {
            return '+52' . $tel;
        }
        if (strlen($tel) === 12 && str_starts_with($tel, '52')) {
            return '+' . $tel;
        }
        if (strlen($tel) === 13 && str_starts_with($tel, '521')) {
            // +521XXXXXXXXXX → quitar el 1 extra de portabilidad
            return '+52' . substr($tel, 3);
        }
        if (strlen($tel) === 11 && str_starts_with($tel, '1')) {
            // Algunos operadores agregan el 1 delante: 152XXXXXXXXXX
            return '+52' . substr($tel, 1);
        }

        return '';
    }

    private function registrar(
        int     $clinicaId,
        int     $citaId,
        string  $tipo,
        string  $telefono,
        string  $estado,
        ?string $twilioSid,
        ?string $errorMsg
    ): void {
        try {
            $pdo = Database::conn();
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO recordatorios
                    (clinica_id, cita_id, tipo, telefono, estado, twilio_sid, error_msg, enviado_at)
                 VALUES
                    (:clinica_id, :cita_id, :tipo, :telefono, :estado, :twilio_sid, :error_msg, NOW())'
            );
            $stmt->execute([
                ':clinica_id' => $clinicaId,
                ':cita_id'    => $citaId,
                ':tipo'       => $tipo,
                ':telefono'   => $telefono,
                ':estado'     => $estado,
                ':twilio_sid' => $twilioSid,
                ':error_msg'  => $errorMsg,
            ]);
        } catch (\Throwable) {
            // No romper el flujo principal si el registro falla
        }
    }
}
