<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/helpers.php';

// Health state is persisted to reduce flapping between polls
// File stores per-endpoint counters: consecutive_successes, consecutive_failures, reported_ok
define('HEALTH_STATE_FILE', __DIR__ . '/../data/health_state.json');
function load_health_state(){
	$path = HEALTH_STATE_FILE;
	if(!is_readable($path)) return [];
	$raw = @file_get_contents($path);
	if(!$raw) return [];
	$j = json_decode($raw, true);
	return is_array($j) ? $j : [];
}
function save_health_state($state){
	$path = HEALTH_STATE_FILE;
	$dir = dirname($path);
	if(!is_dir($dir)) @mkdir($dir, 0755, true);
	$tmp = $path . '.tmp';
	@file_put_contents($tmp, json_encode($state));
	@rename($tmp, $path);
}

function probeEndpoint(string $url, int $attempts = 2, int $timeout = 3) : array {
	$attemptResults = [];
	$overallOk = false;
	$bestTime = null;
	for ($i = 0; $i < $attempts; $i++){
		$res = checkHttpEndpoint($url, $timeout);
		$attemptResults[] = $res;
		if ($res['ok']){
			$overallOk = true;
			if ($bestTime === null || ($res['time_ms'] ?? 0) < $bestTime) $bestTime = $res['time_ms'];
		}
		// short sleep between attempts if multiple are configured
		if ($i < $attempts - 1) usleep(150000);
	}
	return [
		'probe_ok' => $overallOk,
		'attempts' => $attemptResults,
		'probe_time_ms' => $bestTime !== null ? round($bestTime,1) : ($attemptResults[count($attemptResults)-1]['time_ms'] ?? 0),
		'probe_http_code' => $attemptResults[count($attemptResults)-1]['http_code'] ?? 0
	];
}

// thresholds: require this many consecutive failures to report Down, and this many consecutive successes to report Active
$FAIL_THRESHOLD = 2;
$SUCCESS_THRESHOLD = 2;

$state = load_health_state();
$result = ['timestamp' => date('c')];

$endpoints = [
	'gti_online' => 'http://gti-binan.uphsl.edu.ph:8339/',
	'gti_local' => 'http://192.168.89.251:8339/'
];

foreach($endpoints as $key => $url){
	$probe = probeEndpoint($url, 2, 3);
	$probeOk = (bool)$probe['probe_ok'];

	if(!isset($state[$key])){
		$state[$key] = [ 'consecutive_successes' => 0, 'consecutive_failures' => 0, 'reported_ok' => $probeOk ];
	}

	if($probeOk){
		$state[$key]['consecutive_successes'] = ($state[$key]['consecutive_successes'] ?? 0) + 1;
		$state[$key]['consecutive_failures'] = 0;
	} else {
		$state[$key]['consecutive_failures'] = ($state[$key]['consecutive_failures'] ?? 0) + 1;
		$state[$key]['consecutive_successes'] = 0;
	}

	// Decide reported_ok based on thresholds, otherwise keep previous reported_ok to avoid flapping
	$prevReported = isset($state[$key]['reported_ok']) ? (bool)$state[$key]['reported_ok'] : $probeOk;
	$reported = $prevReported;
	if($state[$key]['consecutive_failures'] >= $FAIL_THRESHOLD) $reported = false;
	if($state[$key]['consecutive_successes'] >= $SUCCESS_THRESHOLD) $reported = true;

	$state[$key]['reported_ok'] = $reported;

	// Build result entry: expose reported ok as 'ok' for compatibility, but include probe details
	$result[$key] = [
		'ok' => $reported,
		'probe_ok' => $probeOk,
		'http_code' => $probe['probe_http_code'] ?? 0,
		'time_ms' => $probe['probe_time_ms'] ?? 0,
		'consecutive_successes' => $state[$key]['consecutive_successes'],
		'consecutive_failures' => $state[$key]['consecutive_failures'],
		'attempts' => $probe['attempts']
	];
}

// persist state for next poll
save_health_state($state);

// append to history for trend charts
$historyPath = __DIR__ . '/../data/health_history.json';
$history = [];
if (is_readable($historyPath)){
	$raw = @file_get_contents($historyPath);
	$history = json_decode($raw, true) ?: [];
}
$entry = ['ts' => date('c'), 'result' => $result];
$history[] = $entry;
// Trim history to only keep the last 6 hours of samples to avoid unbounded growth
$cutoff = time() - (6 * 60 * 60); // 6 hours ago
$trimmed = [];
foreach ($history as $h) {
	$t = strtotime($h['ts'] ?? '');
	if ($t === false) continue;
	if ($t >= $cutoff) $trimmed[] = $h;
}
// Fallback: if trimmed is empty (first run), keep latest up to 720 entries
if (empty($trimmed)) {
	$trimmed = array_slice($history, -720);
}
// Also enforce an absolute cap to avoid huge files
if (count($trimmed) > 2000) $trimmed = array_slice($trimmed, -2000);
@file_put_contents($historyPath, json_encode($trimmed));

// simple alerting: if reported state changed for an endpoint, post to webhook if configured
$cfgPath = __DIR__ . '/../data/health_alerts.json';
$cfg = [];
if (is_readable($cfgPath)){
	$cfg = json_decode(@file_get_contents($cfgPath), true) ?: [];
}
if (!empty($cfg['webhook_url'])){
	foreach($endpoints as $key => $url){
		$prev = isset($state[$key]['_last_reported_ok']) ? $state[$key]['_last_reported_ok'] : null;
		// We store prior reported_ok each run in a transient field; compare with current reported_ok
		// Use the saved state file to detect changes: load then compare
		$saved = load_health_state();
		$prevReported = isset($saved[$key]['reported_ok']) ? (bool)$saved[$key]['reported_ok'] : null;
		$curr = $state[$key]['reported_ok'];
		if ($prevReported !== null && $prevReported !== $curr){
			// fire webhook (non-blocking best-effort)
			$payload = json_encode(['endpoint'=>$key,'url'=>$url,'reported_ok'=>$curr,'timestamp'=>date('c')]);
			// best-effort curl
			if (function_exists('curl_version')){
				$ch = curl_init($cfg['webhook_url']);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
				curl_setopt($ch, CURLOPT_TIMEOUT, 2);
				curl_exec($ch);
				curl_close($ch);
			} else {
				// fallback: @file_get_contents
				@file_get_contents($cfg['webhook_url'], false, stream_context_create(['http'=>['method'=>'POST','header'=>'Content-Type: application/json','content'=>$payload,'timeout'=>2]]));
			}
			// also append to incident log
			$logPath = __DIR__ . '/../data/incident_log.json';
			$log = [];
			if (is_readable($logPath)) $log = json_decode(@file_get_contents($logPath), true) ?: [];
			$log[] = ['ts'=>date('c'),'endpoint'=>$key,'url'=>$url,'reported_ok'=>$curr,'note'=>'auto alert via health probe'];
			@file_put_contents($logPath, json_encode($log));
		}
	}
}

echo json_encode($result);
exit;
