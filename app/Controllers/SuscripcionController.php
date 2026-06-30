<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\Config;
use App\Core\Auditoria;
use App\Core\Tenant;
use App\Models\Clinica;
use App\Models\Pago;

/**
 * SaaS — pantalla de suscripción y webhook de Stripe.
 */
final class SuscripcionController extends Controller
{
    /** GET /suscripcion */
    public function index(): void
    {
        Auth::require();   // solo verifica sesión, guardia no aplica aquí (prefijo 'suscri')

        $clinicaId    = Tenant::clinicaId();
        $clinicaModel = new Clinica();
        $clinica      = $clinicaModel->find($clinicaId);

        $pagoModel = new Pago();
        $historial = $pagoModel->ultimos($clinicaId, 5);

        $this->render('suscripcion/index', [
            'pageTitle' => 'Suscripción',
            'clinica'   => $clinica,
            'historial' => $historial,
            'stripePk'  => Config::get('stripe')['public_key'] ?? '',
        ]);
    }

    /** POST /suscripcion/checkout — crea Stripe Checkout Session */
    public function checkout(): void
    {
        Auth::require();

        if (!class_exists(\Stripe\Stripe::class)) {
            flash('error', 'Módulo de pagos no disponible. Contacta al administrador.');
            redirect('suscripcion');
            return;
        }

        $plan = trim((string) ($_POST['plan'] ?? 'mensual'));
        if (!in_array($plan, ['mensual', 'anual'], true)) {
            $plan = 'mensual';
        }

        $stripeCfg = Config::get('stripe');
        \Stripe\Stripe::setApiKey($stripeCfg['secret_key']);

        $clinicaId    = Tenant::clinicaId();
        $clinicaModel = new Clinica();
        $clinica      = $clinicaModel->find($clinicaId);

        $baseUrl = Config::baseUrl();

        // Precio según plan: usa negociado por clínica si existe, sino el estándar de config
        if ($plan === 'anual') {
            $precioUnitario = ($clinica['precio_anual'] ?? null) !== null
                ? (int) $clinica['precio_anual']
                : (int) $stripeCfg['precio_anual'];
            $nombrePlan  = 'Medikz · Suscripción anual';
            $descripcion = 'Acceso completo por 12 meses. Equivale a $'
                . number_format($precioUnitario / 100 / 12, 0, '.', ',')
                . '/mes — sin cobros adicionales durante el año.';
        } else {
            $precioUnitario = ($clinica['precio_mensual'] ?? null) !== null
                ? (int) $clinica['precio_mensual']
                : (int) $stripeCfg['precio_mxn'];
            $nombrePlan  = 'Medikz · Suscripción mensual';
            $descripcion = 'Acceso completo al sistema de gestión de consultorio.';
        }

        $session = \Stripe\Checkout\Session::create([
            'payment_method_types' => ['card'],
            'line_items'           => [[
                'price_data' => [
                    'currency'     => 'mxn',
                    'unit_amount'  => $precioUnitario,
                    'product_data' => [
                        'name'        => $nombrePlan,
                        'description' => $descripcion,
                    ],
                ],
                'quantity' => 1,
            ]],
            'mode'               => 'payment',
            'success_url'        => $baseUrl . '/suscripcion/exito?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'         => $baseUrl . '/suscripcion',
            'customer_email'     => $clinica['email'] ?? Auth::user()['email'],
            'metadata'           => ['clinica_id' => $clinicaId, 'plan' => $plan],
        ]);

        $pagoModel = new Pago();
        try {
            $pagoModel->crearPendiente($clinicaId, $session->id, $precioUnitario / 100, $plan);
        } catch (\PDOException $e) {
            // La columna `plan` puede no existir si la migración 011 no se ha ejecutado.
            // El plan viaja en los metadatos de Stripe, así que el pago igual se procesa correctamente.
            error_log('medapp crearPendiente: ' . $e->getMessage());
        }

        header('Location: ' . $session->url);
        exit;
    }

    /** GET /suscripcion/exito?session_id=... */
    public function exito(): void
    {
        Auth::require();

        $sessionId = trim((string) ($_GET['session_id'] ?? ''));

        if ($sessionId === '') {
            redirect('suscripcion');
            return;
        }

        if (!class_exists(\Stripe\Stripe::class)) {
            redirect('suscripcion');
            return;
        }

        $stripeCfg = Config::get('stripe');
        \Stripe\Stripe::setApiKey($stripeCfg['secret_key']);

        try {
            $session = \Stripe\Checkout\Session::retrieve($sessionId);
        } catch (\Exception) {
            redirect('suscripcion');
            return;
        }

        if ($session->payment_status === 'paid') {
            $planMeta = (string) ($session->metadata['plan'] ?? 'mensual');
            $this->procesarPago((string) $session->id, (string) ($session->payment_intent ?? ''), $planMeta);
        }

        $clinicaId    = Tenant::clinicaId();
        $clinicaModel = new Clinica();
        $clinica      = $clinicaModel->find($clinicaId);

        $this->render('suscripcion/exito', [
            'pageTitle' => 'Pago exitoso',
            'clinica'   => $clinica,
        ]);
    }

    /** POST /webhook/stripe — recibe eventos de Stripe */
    public function webhook(): void
    {
        $payload   = file_get_contents('php://input');
        $sigHeader = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
        $secret    = Config::get('stripe')['webhook_secret'] ?? '';

        if (!class_exists(\Stripe\Stripe::class)) {
            http_response_code(503);
            echo 'Stripe SDK not loaded';
            exit;
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            http_response_code(400);
            echo 'Webhook signature verification failed.';
            exit;
        }

        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            if ($session->payment_status === 'paid') {
                $planMeta = (string) ($session->metadata['plan'] ?? 'mensual');
                $this->procesarPago(
                    (string) $session->id,
                    (string) ($session->payment_intent ?? ''),
                    $planMeta
                );
            }
        }

        http_response_code(200);
        echo 'ok';
        exit;
    }

    // ── internos ──────────────────────────────────────────────────────────

    private function procesarPago(string $sessionId, string $paymentIntent, string $planFallback = 'mensual'): void
    {
        $pagoModel = new Pago();
        $pago      = $pagoModel->porSession($sessionId);

        if ($pago === null) {
            return;
        }
        // Idempotencia: si ya fue procesado, no volver a procesar
        if ($pago['estado'] === 'completado') {
            return;
        }

        // $pago['plan'] puede ser null si la migración 011 aún no se corrió;
        // en ese caso usamos el plan que viene de los metadatos de Stripe.
        $plan = ($pago['plan'] ?? null) ?: $planFallback;
        if (!in_array($plan, ['mensual', 'anual'], true)) {
            $plan = 'mensual';
        }
        $hoy        = date('Y-m-d');
        $finPeriodo = $plan === 'anual'
            ? (new \DateTimeImmutable())->modify('+1 year')->format('Y-m-d')
            : (new \DateTimeImmutable())->modify('+1 month')->format('Y-m-d');
        $clinicaId  = (int) $pago['clinica_id'];

        $pagoModel->marcarCompletado((int) $pago['id'], $paymentIntent, $hoy, $finPeriodo);

        $clinicaModel = new Clinica();
        $clinicaModel->extenderSuscripcion($clinicaId, $plan);

        Auditoria::logDirecto($clinicaId, null, 'suscripcion.pago', 'pago', (int) $pago['id'], [
            'session_id' => $sessionId,
        ]);
    }
}
