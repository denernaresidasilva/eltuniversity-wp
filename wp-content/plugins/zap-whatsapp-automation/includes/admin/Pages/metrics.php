<?php
use ZapWA\Metrics;

$summary = Metrics::summary();
$events  = Metrics::events();
$logs    = Metrics::last_logs();
?>

<div class="wrap">
    <h1>📊 Métricas WhatsApp</h1>

    <ul style="display:flex;gap:20px;font-size:16px;">
        <li>✅ Enviadas: <strong><?php echo $summary['sent']; ?></strong></li>
        <li>❌ Erros: <strong><?php echo $summary['error']; ?></strong></li>
        <li>📦 Total: <strong><?php echo $summary['total']; ?></strong></li>
    </ul>

    <h2>Eventos mais usados</h2>
    <table class="widefat">
        <thead>
            <tr><th>Evento</th><th>Total</th></tr>
        </thead>
        <tbody>
            <?php foreach ($events as $e): ?>
                <tr>
                    <td><?php echo esc_html($e->event); ?></td>
                    <td><?php echo esc_html($e->total); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Últimos disparos</h2>
    <table class="widefat">
        <thead>
            <tr>
                <th>Data</th>
                <th>Evento</th>
                <th>Telefone</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?php echo esc_html($log->created_at); ?></td>
                    <td><?php echo esc_html($log->event); ?></td>
                    <td><?php echo esc_html($log->phone); ?></td>
                    <td><?php echo esc_html($log->status); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
