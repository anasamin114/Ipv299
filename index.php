<?php
// ================================================================
// index.php — Página principal que captura IP + geolocalização
// ================================================================

// Função para obter dados de geolocalização via API
function getGeoData($ip) {
    $url = "https://free.freeipapi.com/api/json/{$ip}";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    return null;
}

// Capturar dados do visitante
$ip = $_SERVER['REMOTE_ADDR'];
$userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconhecido';
$referer = $_SERVER['HTTP_REFERER'] ?? 'Direto/Acesso direto';
$page = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
$timestamp = date('Y-m-d H:i:s');

// Buscar geolocalização desse IP
$geo = getGeoData($ip);

$country  = $geo['countryName'] ?? '—';
$countryCode = $geo['countryCode'] ?? '—';
$region   = $geo['regionName'] ?? '—';
$city     = $geo['cityName'] ?? '—';
$isp      = $geo['asnOrganization'] ?? '—';
$lat      = $geo['latitude'] ?? null;
$lon      = $geo['longitude'] ?? null;

// Montar registro
$registro = [
    'ip'          => $ip,
    'timestamp'   => $timestamp,
    'pais'        => $country,
    'pais_code'   => $countryCode,
    'regiao'      => $region,
    'cidade'      => $city,
    'isp'         => $isp,
    'lat'         => $lat,
    'lon'         => $lon,
    'user_agent'  => $userAgent,
    'referer'     => $referer,
    'pagina'      => $page
];

// Salvar em arquivo JSON (log de visitantes)
$logFile = __DIR__ . '/visits_log.json';
$logs = [];
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $logs = json_decode($content, true) ?? [];
}
$logs[] = $registro;
file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu IP</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            color: #e0e0e0;
            padding: 20px;
        }
        .card {
            background: rgba(20, 20, 40, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(100, 100, 255, 0.2);
            border-radius: 24px;
            padding: 40px;
            width: 100%;
            max-width: 520px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.6);
            text-align: center;
        }
        h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 8px;
            background: linear-gradient(90deg, #00d2ff, #3a7bd5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .subtitle {
            font-size: 0.9rem;
            color: #8888aa;
            margin-bottom: 28px;
        }
        .ip-display {
            background: rgba(0,0,0,0.4);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 20px;
            border: 1px solid rgba(100,100,255,0.1);
        }
        .ip-label {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 3px;
            color: #6a6a9a;
            margin-bottom: 8px;
        }
        .ip-address {
            font-size: 2.2rem;
            font-weight: 700;
            font-family: 'Courier New', monospace;
            color: #ffffff;
            word-break: break-all;
        }
        .geo-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
            margin-top: 20px;
            text-align: left;
        }
        .geo-item {
            background: rgba(255,255,255,0.04);
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .geo-item.full { grid-column: 1 / -1; }
        .geo-item .label {
            font-size: 0.65rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #6666aa;
        }
        .geo-item .value {
            font-size: 1rem;
            font-weight: 500;
            margin-top: 2px;
            color: #ccccee;
        }
        .footer {
            margin-top: 24px;
            font-size: 0.75rem;
            color: #555577;
        }
        @media (max-width: 480px) {
            .card { padding: 24px; }
            .ip-address { font-size: 1.6rem; }
            .geo-details { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🌐 Meu IP</h1>
        <p class="subtitle">Informações do seu endereço público</p>
        <div class="ip-display">
            <div class="ip-label">Seu Endereço IP</div>
            <div class="ip-address"><?= htmlspecialchars($ip) ?></div>
        </div>
        <div class="geo-details">
            <div class="geo-item">
                <div class="label">País</div>
                <div class="value"><?= htmlspecialchars($country) ?></div>
            </div>
            <div class="geo-item">
                <div class="label">Região</div>
                <div class="value"><?= htmlspecialchars($region) ?></div>
            </div>
            <div class="geo-item">
                <div class="label">Cidade</div>
                <div class="value"><?= htmlspecialchars($city) ?></div>
            </div>
            <div class="geo-item">
                <div class="label">Provedor (ISP)</div>
                <div class="value"><?= htmlspecialchars($isp) ?></div>
            </div>
            <div class="geo-item full">
                <div class="label">Coordenadas</div>
                <div class="value"><?= ($lat && $lon) ? htmlspecialchars("{$lat}, {$lon}") : '—' ?></div>
            </div>
        </div>
        <div class="footer">Pentest Autorizado — Apenas para fins de segurança ofensiva</div>
    </div>
</body>
</html>
