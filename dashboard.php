<?php
require_once 'includes/require_login.php';
require_once 'includes/helpers.php';
$config = include __DIR__ . '/includes/config.php';

// Endpoint URLs (display only)
$gti_online_url = 'http://gti-binan.uphsl.edu.ph:8339/';
$gti_local_url = 'http://192.168.89.251:8339/';

    // Prefer cached health state/history for fast page renders. If those files are missing,
    // fall back to live probes (which may be slower).
    $statePath = __DIR__ . '/data/health_state.json';
    $historyPath = __DIR__ . '/data/health_history.json';
    $last_checked_display = '--';
    $last_checked_display_iso = '';

    $gti_online = ['ok' => null, 'time_ms' => 0, 'http_code' => 0];
    $gti_local = ['ok' => null, 'time_ms' => 0, 'http_code' => 0];

    if (is_readable($statePath)) {
        $raw = @file_get_contents($statePath);
        $state = json_decode($raw, true) ?: [];
        $gti_online['ok'] = isset($state['gti_online']['reported_ok']) ? (bool)$state['gti_online']['reported_ok'] : null;
        $gti_local['ok'] = isset($state['gti_local']['reported_ok']) ? (bool)$state['gti_local']['reported_ok'] : null;
    }

    if (is_readable($historyPath)) {
        $raw = @file_get_contents($historyPath);
        $history = json_decode($raw, true) ?: [];
        if (!empty($history)) {
            $last = end($history);
            $lastTs = $last['ts'] ?? ($last['result']['timestamp'] ?? null);
            if ($lastTs) {
                $last_checked_display = date('Y-m-d H:i:s', strtotime($lastTs));
                $last_checked_display_iso = $lastTs;
            }
            // populate time/http_code from last known probe if available
            if (isset($last['result']['gti_online'])) {
                $gti_online['time_ms'] = $last['result']['gti_online']['time_ms'] ?? $gti_online['time_ms'];
                $gti_online['http_code'] = $last['result']['gti_online']['http_code'] ?? $gti_online['http_code'];
            }
            if (isset($last['result']['gti_local'])) {
                $gti_local['time_ms'] = $last['result']['gti_local']['time_ms'] ?? $gti_local['time_ms'];
                $gti_local['http_code'] = $last['result']['gti_local']['http_code'] ?? $gti_local['http_code'];
            }
        }
    }

// If cached state reports a component as down, do a quick live probe to avoid showing stale failures
// (keeps fast cached path for normal operation but verifies 'Down' at page load).
if ($gti_online['ok'] === false) {
    $verify = checkHttpEndpoint($gti_online_url, 2);
    if ($verify['ok'] === true) {
        $gti_online = $verify;
    } else {
        $gti_online['http_code'] = $verify['http_code'] ?? $gti_online['http_code'];
        $gti_online['time_ms'] = $verify['time_ms'] ?? $gti_online['time_ms'];
    }
}
if ($gti_local['ok'] === false) {
    $verify = checkHttpEndpoint($gti_local_url, 2);
    if ($verify['ok'] === true) {
        $gti_local = $verify;
    } else {
        $gti_local['http_code'] = $verify['http_code'] ?? $gti_local['http_code'];
        $gti_local['time_ms'] = $verify['time_ms'] ?? $gti_local['time_ms'];
    }
}
// If no cached data, fall back to live probes (may be slow)
if ($gti_online['ok'] === null || $gti_local['ok'] === null) {
    $gti_online = checkHttpEndpoint($gti_online_url, 3);
    $gti_local = checkHttpEndpoint($gti_local_url, 3);
    $last_checked_display = date('Y-m-d H:i:s');
}

function _statusBadge(array $res) {
    if ($res['ok']) return '<span class="badge bg-success">Active</span>';
    if (isset($res['http_code']) && $res['http_code'] >= 400) return '<span class="badge bg-warning">Error</span>';
    return '<span class="badge bg-danger">Down</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ITS System - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="icon" href="assets/img/favicon.ico" type="image/x-icon">
    <link rel="icon" type="image/png" href="assets/img/its_logo.png">
    <link rel="apple-touch-icon" href="assets/img/its_logo.png">
    <style>
        /* Reuse modern table styles from accounts page (muted palette) */
        :root{--acct-text:#cfd8e3;--muted:#9aa4b2;--row-bg:rgba(255,255,255,0.02);--row-hover:rgba(255,255,255,0.03);--primary-50:rgba(59,130,246,0.07);--primary-200:rgba(59,130,246,0.18)}
        .table-modern{width:100%;border-collapse:separate;border-spacing:0 10px}
        .table-modern thead th{background:transparent;color:var(--muted);font-weight:600;border-bottom:0;padding:12px 14px;text-align:left}
        .table-modern tbody tr{background:var(--row-bg);border-radius:10px;box-shadow:0 1px 0 rgba(0,0,0,0.12);transition:transform .12s ease,box-shadow .12s ease}
        .table-modern tbody tr td{border:0;padding:14px;vertical-align:middle;color:var(--acct-text)}
        .table-modern tbody tr:hover{transform:translateY(-3px);box-shadow:0 10px 24px rgba(0,0,0,0.25);background:var(--row-hover)}
        @media (max-width:700px){ .table-modern thead th{display:none} .table-modern tbody tr td{display:block;padding:10px} }
    </style>
    <!-- Chart.js removed: using a lightweight live-check status display instead -->
</head>
<body>
    <?php include 'sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid dashboard-compact">
            <header class="page-header">
                <div>
                    <h1 class="page-title">RTO Monitoring Dashboard</h1>
                    <p class="text-muted small">Overview of system metrics and status</p>
                    <div class="mt-1 small text-muted">Last checked: <span id="last-checked" data-ts="<?php echo htmlspecialchars($last_checked_display_iso); ?>"><?php echo htmlspecialchars($last_checked_display); ?></span></div>
                </div>
                <div class="page-actions">
                    <button class="btn btn-primary" data-refresh>Refresh</button>
                </div>
            </header>
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card metric-card">
                        <div class="card-body">
                            <h5 class="card-title">GTI Status</h5>
                            <div class="table-responsive">
                                <table class="table-modern">
                                <thead>
                                    <tr>
                                        <th>Component</th>
                                        <th>Status</th>
                                        <th>Last Updated</th>
                                        <th class="hide-mobile">Links</th>
                                    </tr>
                                </thead>
                                <tbody>
                                                <tr>
                                                    <td>GTI Online</td>
                                                    <td id="gti-online-status"><?php echo _statusBadge($gti_online); ?></td>
                                                    <td id="gti-online-time"><?php echo $gti_online['ok'] ? ($gti_online['time_ms'] . ' ms (HTTP ' . $gti_online['http_code'] . ')') : ($gti_online['http_code'] ? 'HTTP ' . $gti_online['http_code'] : 'No response'); ?></td>
                                                    <td class="hide-mobile"><code><?php echo htmlspecialchars($gti_online_url); ?></code></td>
                                                </tr>
                                                <tr>
                                                    <td>GTI Local</td>
                                                    <td id="gti-local-status"><?php echo _statusBadge($gti_local); ?></td>
                                                    <td id="gti-local-time"><?php echo $gti_local['ok'] ? ($gti_local['time_ms'] . ' ms (HTTP ' . $gti_local['http_code'] . ')') : ($gti_local['http_code'] ? 'HTTP ' . $gti_local['http_code'] : 'No response'); ?></td>
                                                    <td class="hide-mobile"><code><?php echo htmlspecialchars($gti_local_url); ?></code></td>
                                                </tr>
                                </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6">
                    <div class="card metric-card">
                        <div class="card-body">
                            <h5 class="card-title">GTI Online — recent latency (ms)</h5>
                            <canvas id="chartGtiOnline" style="height:160px;"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card metric-card">
                        <div class="card-body">
                            <h5 class="card-title">GTI Local — recent latency (ms)</h5>
                            <canvas id="chartGtiLocal" style="height:160px;"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GTI Live Check card removed; last-checked moved to header -->
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Simplified live-check polling: update GTI status rows and a last-checked time
        const spinner = document.getElementById('live-check-spinner');
        const lastChecked = document.getElementById('last-checked');
        if (lastChecked && lastChecked.dataset && lastChecked.dataset.ts) {
            try { lastChecked.textContent = new Date(lastChecked.dataset.ts).toLocaleString(); } catch(e) { }
        }

        function statusBadgeFrom(res) {
            if (!res) return '<span class="badge bg-secondary">Unknown</span>';
            if (res.ok) return '<span class="badge bg-success">Active</span>';
            if (res.http_code >= 400) return '<span class="badge bg-warning">Error</span>';
            return '<span class="badge bg-danger">Down</span>';
        }

        function timeTextFrom(res) {
            if (!res) return 'No data';
            if (res.ok) return `${res.time_ms} ms (HTTP ${res.http_code})`;
            if (res.http_code) return `HTTP ${res.http_code}`;
            return res.error || 'No response';
        }

        function setChecking(on) {
            if (!spinner) return;
            spinner.style.display = on ? 'inline-block' : 'none';
        }

        // chart globals for streaming
        const maxPoints = 120;
        let chartGtiOnline = null;
        let chartGtiLocal = null;
        function buildEmptyChart(ctx){
            return new Chart(ctx, { type: 'line', data: { labels: [], datasets: [{ label: 'ms', data: [], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.14)', pointRadius:0 }] }, options: { animation: { duration: 200 }, responsive: true, maintainAspectRatio: false, scales:{ x:{ display:false }, y:{ display:true } }, plugins:{ legend:{ display:false } } } });
        }

        function updateHealthDOM(data) {
            if (data.gti_online) {
                const elStatus = document.getElementById('gti-online-status');
                const elTime = document.getElementById('gti-online-time');
                if (elStatus) elStatus.innerHTML = statusBadgeFrom(data.gti_online);
                if (elTime) elTime.textContent = timeTextFrom(data.gti_online);
            }
            if (data.gti_local) {
                const elStatus = document.getElementById('gti-local-status');
                const elTime = document.getElementById('gti-local-time');
                if (elStatus) elStatus.innerHTML = statusBadgeFrom(data.gti_local);
                if (elTime) elTime.textContent = timeTextFrom(data.gti_local);
            }
        }

        async function fetchHealth() {
            try {
                setChecking(true);
                const res = await fetch('<?php echo $config->base; ?>api/health.php', { cache: 'no-store' });
                if (!res.ok) throw new Error('HTTP ' + res.status);
                const data = await res.json();
                updateHealthDOM(data);
                if (lastChecked) lastChecked.textContent = new Date().toLocaleString();
                // ensure charts initialized
                const cOnline = document.getElementById('chartGtiOnline');
                const cLocal = document.getElementById('chartGtiLocal');
                if(cOnline && !chartGtiOnline) chartGtiOnline = buildEmptyChart(cOnline.getContext('2d'));
                if(cLocal && !chartGtiLocal) chartGtiLocal = buildEmptyChart(cLocal.getContext('2d'));
                // push latest sample into charts so they move live
                const tsLabel = new Date().toLocaleTimeString();
                if(chartGtiOnline && data.gti_online){
                    const v = (typeof data.gti_online.time_ms !== 'undefined') ? data.gti_online.time_ms : (data.gti_online.probe_time_ms || null);
                    chartGtiOnline.data.labels.push(tsLabel);
                    chartGtiOnline.data.datasets[0].data.push(v === null ? null : parseFloat(v));
                    if(chartGtiOnline.data.labels.length > maxPoints){ chartGtiOnline.data.labels.shift(); chartGtiOnline.data.datasets[0].data.shift(); }
                    chartGtiOnline.update();
                }
                if(chartGtiLocal && data.gti_local){
                    const v = (typeof data.gti_local.time_ms !== 'undefined') ? data.gti_local.time_ms : (data.gti_local.probe_time_ms || null);
                    chartGtiLocal.data.labels.push(tsLabel);
                    chartGtiLocal.data.datasets[0].data.push(v === null ? null : parseFloat(v));
                    if(chartGtiLocal.data.labels.length > maxPoints){ chartGtiLocal.data.labels.shift(); chartGtiLocal.data.datasets[0].data.shift(); }
                    chartGtiLocal.update();
                }
            } catch (e) {
                console.warn('Health fetch failed', e);
                // show failed timestamp so UI doesn't stay at "--"
                if (lastChecked) lastChecked.textContent = new Date().toLocaleString() + ' (failed)';
                // show endpoints as down in the UI
                updateHealthDOM({ gti_online: { ok: false, http_code: 0, time_ms: 0 }, gti_local: { ok: false, http_code: 0, time_ms: 0 } });
            } finally {
                setChecking(false);
            }
        }

        // Poll every 30s
        fetchHealth();
        const healthInterval = setInterval(fetchHealth, 30000);

        // Fetch history and draw small charts
        async function fetchAndDrawHistory(){
            try{
                const r = await fetch('<?php echo $config->base; ?>api/health_history.php', { cache: 'no-store' });
                if(!r.ok) return;
                const j = await r.json();
                const hist = (j && j.history) ? j.history : [];
                if(!hist.length) return;
                // Build arrays for last N points
                // limit to recent points for charting (e.g. last 120 samples)
                const recent = hist.slice(-120);
                const labels = recent.map(h=> new Date(h.ts).toLocaleTimeString());
                const gtiOnlineSeries = recent.map(h => h.result && h.result.gti_online ? (h.result.gti_online.time_ms || 0) : null);
                const gtiLocalSeries = recent.map(h => h.result && h.result.gti_local ? (h.result.gti_local.time_ms || 0) : null);
                // use static canvases in the HTML to avoid creating/appending elements repeatedly
                const chartOnlineEl = document.getElementById('chartGtiOnline');
                const chartLocalEl = document.getElementById('chartGtiLocal');
                if(!chartOnlineEl || !chartLocalEl) return;
                // seed global charts (create if missing) and set their data
                if(chartGtiOnline === null && chartOnlineEl) chartGtiOnline = buildEmptyChart(chartOnlineEl.getContext('2d'));
                if(chartGtiLocal === null && chartLocalEl) chartGtiLocal = buildEmptyChart(chartLocalEl.getContext('2d'));
                if(chartGtiOnline){ chartGtiOnline.data.labels = labels; chartGtiOnline.data.datasets[0].data = gtiOnlineSeries.map(v=>v===null?null:parseFloat(v)); chartGtiOnline.update(); }
                if(chartGtiLocal){ chartGtiLocal.data.labels = labels; chartGtiLocal.data.datasets[0].data = gtiLocalSeries.map(v=>v===null?null:parseFloat(v)); chartGtiLocal.update(); }
            }catch(e){ console.warn('history fetch error', e); }
        }
        fetchAndDrawHistory();
        setInterval(fetchAndDrawHistory, 30000);

        // Manual refresh: bind all buttons with data-refresh
        const refreshBtns = document.querySelectorAll('[data-refresh]');
        refreshBtns.forEach(b => b.addEventListener('click', fetchHealth));
    </script>
</body>
</html>