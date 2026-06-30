<?php
// $fechaInicial
$csrf    = \App\Core\Csrf::token();
$urlBase = url('');
?>

<!-- FullCalendar v6 -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/locales/es.global.min.js"></script>

<style>
    /* Contenedor */
    #calendar-wrap {
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,.08);
        padding: 1.25rem 1.5rem 1.5rem;
    }

    /* Toolbar */
    .fc .fc-toolbar-title { font-size: 1.1rem; font-weight: 700; color: #0f1724; }
    .fc .fc-button {
        background: #f1f5f9 !important;
        border: 1px solid #e2e8f0 !important;
        color: #334155 !important;
        border-radius: 7px !important;
        font-size: .8rem !important;
        font-weight: 600 !important;
        padding: .35rem .75rem !important;
        box-shadow: none !important;
    }
    .fc .fc-button:hover { background: #e2e8f0 !important; }
    .fc .fc-button-primary:not(:disabled).fc-button-active,
    .fc .fc-button-primary:not(:disabled):active {
        background: #0f1724 !important;
        border-color: #0f1724 !important;
        color: #fff !important;
    }
    .fc .fc-today-button { text-transform: capitalize; }

    /* Botón Nueva cita */
    .fc .fc-nuevaCita-button {
        background: #4e9af1 !important;
        border-color: #4e9af1 !important;
        color: #fff !important;
        padding: .35rem 1rem !important;
    }
    .fc .fc-nuevaCita-button:hover { background: #2563eb !important; border-color: #2563eb !important; }

    /* Cabecera de días */
    .fc .fc-col-header-cell {
        background: #f8faff;
        border-bottom: 2px solid #e2e8f0;
        padding: .4rem 0;
    }
    .fc .fc-col-header-cell-cushion {
        font-size: .8rem;
        font-weight: 700;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: .05em;
        text-decoration: none;
    }
    /* Hoy destacado */
    .fc .fc-day-today { background: #f0f6ff !important; }
    .fc .fc-day-today .fc-col-header-cell-cushion { color: #2563eb; }

    /* Línea de hora actual */
    .fc .fc-timegrid-now-indicator-line { border-color: #ef4444; }
    .fc .fc-timegrid-now-indicator-arrow { border-top-color: #ef4444; border-bottom-color: #ef4444; }

    /* Etiquetas de hora */
    .fc .fc-timegrid-slot-label-cushion { font-size: .75rem; color: #94a3b8; font-weight: 500; }

    /* Celdas de tiempo — hover */
    .fc .fc-timegrid-slot:hover { background: rgba(78,154,241,.06); cursor: pointer; }

    /* Eventos */
    .fc-event {
        border: none !important;
        border-radius: 6px !important;
        padding: 2px 5px !important;
        cursor: pointer;
    }
    .fc-event-inner { line-height: 1.3; }
    .fc-event-name  { font-weight: 600; font-size: .78rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .fc-event-meta  { font-size: .7rem; opacity: .88; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Toast de error drag */
    #fc-toast {
        position: fixed; bottom: 1.5rem; left: 50%; transform: translateX(-50%);
        background: #1e293b; color: #fff; padding: .6rem 1.2rem;
        border-radius: 8px; font-size: .875rem; z-index: 9999;
        display: none; box-shadow: 0 4px 12px rgba(0,0,0,.25);
    }
</style>

<div class="d-flex flex-wrap gap-2 mb-2">
    <?php foreach ([
        ['#3b82f6', 'Programada'],
        ['#16a34a', 'Confirmada'],
        ['#dc2626', 'Cancelada'],
        ['#6b7280', 'Atendida'],
        ['#d97706', 'No asistió'],
    ] as [$color, $label]): ?>
    <span class="d-flex align-items-center gap-1" style="font-size:.78rem;color:#475569">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= $color ?>;flex-shrink:0;display:inline-block"></span>
        <?= $label ?>
    </span>
    <?php endforeach ?>
</div>

<div id="calendar-wrap">
    <div id="calendar"
         data-fecha="<?= e($fechaInicial) ?>"
         data-csrf="<?= e($csrf) ?>"
         data-url-eventos="<?= url('agenda/eventos') ?>"
         data-url-nueva="<?= url('agenda/nueva') ?>"
         data-url-mover="<?= url('agenda') ?>">
    </div>
</div>

<div id="fc-toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const el         = document.getElementById('calendar');
    const csrf       = el.dataset.csrf;
    const urlEventos = el.dataset.urlEventos;
    const urlNueva   = el.dataset.urlNueva;
    const urlMover   = el.dataset.urlMover;

    function mostrarToast(msg) {
        const t = document.getElementById('fc-toast');
        t.textContent = msg;
        t.style.display = 'block';
        setTimeout(() => { t.style.display = 'none'; }, 3500);
    }

    function fmtDate(d)  { return d.toISOString().slice(0, 10); }
    function fmtTime(d)  { return d.toTimeString().slice(0, 5); }

    const calendar = new FullCalendar.Calendar(el, {
        locale:        'es',
        initialView:   'timeGridWeek',
        initialDate:   el.dataset.fecha || undefined,
        firstDay:      1,           // lunes
        slotMinTime:   '09:00:00',
        slotMaxTime:   '19:00:00',
        slotDuration:  '00:30:00',
        slotLabelInterval: '01:00',
        allDaySlot:    false,
        nowIndicator:  true,
        editable:      true,
        selectable:    true,
        selectMirror:  true,
        dayMaxEvents:  false,
        height:        'auto',
        expandRows:    true,

        headerToolbar: {
            left:   'prev,next today',
            center: 'title',
            right:  'nuevaCita timeGridWeek,timeGridDay',
        },

        customButtons: {
            nuevaCita: {
                text: '+ Nueva cita',
                click: () => { window.location.href = urlNueva; },
            },
        },

        buttonText: {
            today: 'Hoy',
            week:  'Semana',
            day:   'Día',
        },

        // Carga eventos vía AJAX
        events: function (info, successCb, failureCb) {
            fetch(urlEventos + '?start=' + info.startStr.slice(0,10) + '&end=' + info.endStr.slice(0,10))
                .then(r => r.json())
                .then(successCb)
                .catch(failureCb);
        },

        // Contenido personalizado del evento
        eventContent: function (arg) {
            const p      = arg.event.extendedProps;
            const nombre = arg.event.title || '';
            return {
                html: `<div class="fc-event-inner">
                           <div class="fc-event-name">${escHtml(nombre)}</div>
                           ${p.motivo ? `<div class="fc-event-meta">${escHtml(p.motivo)}</div>` : ''}
                       </div>`,
            };
        },

        // Click en evento → detalle
        eventClick: function (info) {
            info.jsEvent.preventDefault();
            window.location.href = info.event.extendedProps.url;
        },

        // Click / selección en celda vacía → nueva cita
        select: function (info) {
            const fecha  = fmtDate(info.start);
            const hIni   = fmtTime(info.start);
            const hFin   = fmtTime(info.end);
            window.location.href = urlNueva
                + '?fecha=' + fecha
                + '&hora_inicio=' + hIni
                + '&hora_fin=' + hFin;
        },

        // Drag & drop → mover cita
        eventDrop: function (info) {
            moverCita(info.event, info.revert);
        },

        // Resize → cambiar duración
        eventResize: function (info) {
            moverCita(info.event, info.revert);
        },
    });

    calendar.render();

    function moverCita(event, revert) {
        if (!event.end) {
            revert();
            return;
        }
        const body = new URLSearchParams({
            _csrf:       csrf,
            fecha:       fmtDate(event.start),
            hora_inicio: fmtTime(event.start),
            hora_fin:    fmtTime(event.end),
        });
        fetch(urlMover + '/' + event.id + '/mover', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString(),
        })
        .then(r => r.json())
        .then(data => {
            if (!data.ok) {
                revert();
                mostrarToast('⚠ ' + (data.error || 'No se pudo mover la cita.'));
            }
        })
        .catch(() => {
            revert();
            mostrarToast('Error de conexión al guardar el cambio.');
        });
    }

    function escHtml(s) {
        return String(s ?? '').replace(/[&<>"']/g,
            c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    }
});
</script>
