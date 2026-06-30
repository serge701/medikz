<?php
declare(strict_types=1);

namespace App\Services;

use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Output\QRGdImagePNG;
use chillerlan\QRCode\Output\QRMarkupSVG;
use Dompdf\Dompdf;
use Dompdf\Options;

final class RecetaPdf
{
    /**
     * Genera el PDF de una receta y devuelve los bytes crudos.
     *
     * @param array<string,mixed> $receta  Receta con campos pac_* y med_* (JOIN del modelo).
     * @param array<string,mixed> $clinica Datos de la clínica actual.
     */
    public function generar(array $receta, array $clinica): string
    {
        $qrDataUri = $this->generarQR($receta['codigo_verificacion']);
        $html      = $this->plantilla($receta, $clinica, $qrDataUri);

        $opts = new Options();
        $opts->set('isHtml5ParserEnabled', true);
        $opts->set('isPhpEnabled', false);

        $pdf = new Dompdf($opts);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('letter', 'portrait');
        $pdf->render();

        return $pdf->output();
    }

    private function generarQR(string $codigo): string
    {
        $url = url('recetas/verificar/' . $codigo);

        // Intenta PNG con GD (mejor calidad en PDF)
        if (extension_loaded('gd')) {
            try {
                $opts = new QROptions([
                    'outputInterface' => QRGdImagePNG::class,
                    'outputBase64'    => true,
                    'scale'           => 5,
                ]);
                return (new QRCode($opts))->render($url);
            } catch (\Throwable) {
                // cae al fallback SVG
            }
        }

        // Fallback: SVG embebido como data URI (no requiere GD)
        try {
            $opts = new QROptions([
                'outputInterface' => QRMarkupSVG::class,
                'outputBase64'    => false,
            ]);
            $svg = (new QRCode($opts))->render($url);
            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable) {
            return '';
        }
    }

    private function plantilla(array $r, array $cl, string $qr): string
    {
        $medicamentos = json_decode((string) ($r['medicamentos'] ?? '[]'), true) ?: [];
        $esc = static fn(?string $s): string => htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');

        // Datos básicos — todos pre-calculados como strings simples
        $nombrePac = $esc(trim($r['pac_nombre'] . ' ' . $r['pac_ap'] . ' ' . ($r['pac_am'] ?? '')));
        $edad      = edad_anios($r['pac_nacimiento'] ?? null);
        $edadHtml  = $edad !== null ? '<strong>Edad:</strong> ' . $edad . ' años' : '';
        $fecha     = $esc(fecha_legible($r['fecha_receta']));
        $codigo    = $esc($r['codigo_verificacion']);

        // Clínica
        $clNombre = $esc($cl['nombre'] ?? '');
        $clSub    = ($cl['direccion'] ? $esc($cl['direccion']) : '')
                  . ($cl['telefono']  ? ' · Tel: ' . $esc($cl['telefono']) : '')
                  . ($cl['email']     ? ' · ' . $esc($cl['email']) : '');

        // Médico (columna derecha del encabezado)
        $medNombre = $esc($r['med_nombre'] ?? '');
        $medCedula = $esc($r['med_cedula'] ?? '');
        $medEsp    = $esc($r['med_especialidad'] ?? '');
        $medUniv   = $esc($r['med_universidad'] ?? '');

        $docCol = '';
        if ($medNombre) $docCol .= "<div class='doc-name'>{$medNombre}</div>";
        if ($medCedula) $docCol .= "<div>C&eacute;d. Prof.: {$medCedula}</div>";
        if ($medEsp)    $docCol .= "<div>{$medEsp}</div>";
        if ($medUniv)   $docCol .= "<div style='font-size:8.5pt;'>{$medUniv}</div>";

        // Pie de firma
        $firmaTexto = $medNombre . ($medCedula ? '<br>C&eacute;d. Prof. ' . $medCedula : '');

        // QR
        $qrImg = $qr ? "<img src='{$qr}' width='90' height='90'>" : '';

        // Secciones opcionales
        $diagSeccion = !empty($r['diagnostico'])
            ? "<div class='section-hdr'>Diagn&oacute;stico</div>"
              . "<div class='section-body'><p style='font-size:10pt;margin:0;'>" . $esc($r['diagnostico']) . "</p></div>"
            : '';

        $indGenerales = !empty($r['indicaciones_generales'])
            ? nl2br($esc($r['indicaciones_generales']))
            : '';
        $indSeccion = $indGenerales
            ? "<div class='section-hdr'>Indicaciones generales</div>"
              . "<div class='section-body'><p style='font-size:9.5pt;margin:0;'>{$indGenerales}</p></div>"
            : '';

        // Filas de medicamentos
        $medRows = '';
        foreach ($medicamentos as $i => $med) {
            $nombre = $esc($med['nombre'] ?? '');
            $dosis  = $esc($med['dosis'] ?? '');
            $freq   = $esc($med['frecuencia'] ?? '');
            $dur    = $esc($med['duracion'] ?? '');
            $ind    = $esc($med['indicaciones'] ?? '');
            $num    = $i + 1;

            $dosisTxt = $dosis ? " <span class='med-dosis'>&nbsp;&ndash;&nbsp;{$dosis}</span>" : '';
            $freqTxt  = $freq
                ? "<div class='med-freq'>Tomar: {$freq}" . ($dur ? " &middot; Duraci&oacute;n: {$dur}" : '') . "</div>"
                : '';
            $indTxt   = $ind ? "<div class='med-ind'>{$ind}</div>" : '';

            $medRows .= "<tr><td>"
                . "<span class='med-num'>{$num}</span>"
                . "<span class='med-nombre'>{$nombre}</span>{$dosisTxt}"
                . $freqTxt . $indTxt
                . "</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family: Arial, Helvetica, sans-serif;
    font-size: 10.5pt;
    color: #1a1a2e;
    background: #fff;
}
/* Barra de acento superior */
.accent-bar {
    background: #1a56a0;
    height: 5px;
    width: 100%;
    margin-bottom: 0;
}
/* Contenedor principal con márgenes */
.wrap { padding: 18px 32px 24px; }

/* Cabecera */
.header-tbl { width:100%; border-collapse:collapse; margin-bottom:0; }
.header-tbl td { vertical-align:top; }
.clinic-name { font-size:15pt; font-weight:bold; color:#1a56a0; line-height:1.2; }
.clinic-sub  { font-size:8.5pt; color:#666; margin-top:3px; }
.doc-col     { text-align:right; font-size:9pt; color:#333; }
.doc-name    { font-size:11pt; font-weight:bold; color:#1a1a2e; }
.header-divider { border:none; border-top:1.5px solid #1a56a0; margin:10px 0 12px; }

/* Barra del paciente */
.pac-bar {
    width: 100%;
    border-collapse: collapse;
    background: #f4f7fc;
    border-left: 4px solid #1a56a0;
    margin-bottom: 14px;
}
.pac-bar td {
    padding: 7px 12px;
    font-size: 9.5pt;
    color: #222;
}
.pac-bar .label { color:#555; font-size:8.5pt; display:block; margin-bottom:1px; }
.pac-bar .valor { font-weight:bold; }

/* Secciones */
.section-hdr {
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #fff;
    background: #1a56a0;
    padding: 3px 10px;
    margin: 14px 0 0;
}
.section-body { padding: 8px 4px 4px; }

/* Rx símbolo + título */
.rx-hdr {
    font-size: 8pt;
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #fff;
    background: #1a56a0;
    padding: 3px 10px;
    margin: 14px 0 0;
}
.rx-label {
    display: inline-block;
    background: #fff;
    color: #1a56a0;
    font-weight: bold;
    font-size: 9pt;
    padding: 0 5px 0 0;
    margin-right: 4px;
    font-style: italic;
}

/* Tabla de medicamentos */
.meds { width:100%; border-collapse:collapse; margin-top:4px; }
.meds tr td { padding:7px 4px 7px; border-bottom:1px dotted #d0d8e8; vertical-align:top; }
.meds tr:last-child td { border-bottom:none; }
.med-num {
    display:inline-block;
    width:18px; height:18px;
    background:#1a56a0;
    color:#fff;
    font-size:8pt;
    font-weight:bold;
    text-align:center;
    line-height:18px;
    border-radius:50%;
    margin-right:6px;
    vertical-align:middle;
}
.med-nombre { font-size:11pt; font-weight:bold; color:#1a1a2e; }
.med-dosis  { font-size:9.5pt; color:#444; }
.med-freq   { font-size:9pt; color:#555; font-style:italic; margin-top:2px; }
.med-ind    { font-size:8.5pt; color:#777; margin-top:1px; }

/* Pie de página */
.footer-tbl { width:100%; border-collapse:collapse; margin-top:22px; border-top:1px solid #ccc; padding-top:4px; }
.firma-area { vertical-align:bottom; padding-top:8px; }
.firma-line { border-top:1px solid #555; width:190px; margin-top:38px; padding-top:4px; font-size:9pt; color:#333; line-height:1.5; }
.qr-area    { text-align:center; vertical-align:bottom; padding-top:8px; width:120px; }
.qr-texto   { font-size:7.5pt; color:#666; margin-top:4px; text-align:center; }
.nom-texto  { font-size:7pt; color:#aaa; text-align:center; margin-top:2px; }
</style>
</head>
<body>

<div class="accent-bar"></div>
<div class="wrap">

<!-- Cabecera -->
<table class="header-tbl" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:60%">
            <div class="clinic-name">{$clNombre}</div>
            <div class="clinic-sub">{$clSub}</div>
        </td>
        <td class="doc-col">
            {$docCol}
        </td>
    </tr>
</table>
<hr class="header-divider">

<!-- Paciente -->
<table class="pac-bar" cellpadding="0" cellspacing="0">
    <tr>
        <td style="width:42%">
            <span class="label">Paciente</span>
            <span class="valor">{$nombrePac}</span>
        </td>
        <td style="width:16%">
            <span class="label">Edad</span>
            <span class="valor">{$edad} a&ntilde;os</span>
        </td>
        <td style="width:20%">
            <span class="label">Fecha</span>
            <span class="valor">{$fecha}</span>
        </td>
        <td style="width:22%; text-align:right">
            <span class="label">Folio</span>
            <span class="valor" style="font-family:monospace;font-size:8.5pt;">{$codigo}</span>
        </td>
    </tr>
</table>

<!-- Diagnóstico (opcional) -->
{$diagSeccion}

<!-- Medicamentos -->
<div class="rx-hdr">Rx &nbsp; Medicamentos</div>
<div class="section-body">
<table class="meds" cellpadding="0" cellspacing="0">
    <tbody>{$medRows}</tbody>
</table>
</div>

<!-- Indicaciones generales (opcional) -->
{$indSeccion}

<!-- Footer: firma + QR -->
<table class="footer-tbl" cellpadding="0" cellspacing="0">
    <tr>
        <td class="firma-area">
            <div class="firma-line">{$firmaTexto}</div>
        </td>
        <td class="qr-area">
            {$qrImg}
            <div class="qr-texto">Verificar autenticidad<br><span style="font-family:monospace;">{$codigo}</span></div>
            <div class="nom-texto">V&aacute;lida 30 d&iacute;as &nbsp;&middot;&nbsp; NOM-004-SSA3-2012</div>
            <div style="font-size:6.5pt;color:#bbb;margin-top:4px;">Generado con Medikz &nbsp;&middot;&nbsp; medikz.com</div>
        </td>
    </tr>
</table>

</div><!-- wrap -->
</body>
</html>
HTML;
    }
}
