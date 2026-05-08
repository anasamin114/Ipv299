<?php
// ================================================================
// admin.php — Painel administrativo protegido por senha
// Usuário: Admin   |   Senha: 12345678
// ================================================================

session_start();

// Credenciais
$adminUser = 'Admin';
$adminPass = '12345678';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if ($_POST['username'] === $adminUser && $_POST['password'] === $adminPass) {
        $_SESSION['admin_logged_in'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $erro = 'Credenciais inválidas.';
    }
}

// Verificar se já está logado
$logado = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Carregar logs
$logs = [];
$logFile = __DIR__ . '/visits_log.json';
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $logs = json_decode($content, true) ?? [];
}

// Ações: limpar logs
if ($logado && isset($_GET['action']) && $_GET['action'] === 'clear') {
    file_put_contents($logFile, json_encode([], JSON_PRETTY_PRINT));
    header('Location: admin.php');
    exit;
}

// Ação: baixar logs como CSV
if ($logado && isset($_GET['action']) && $_GET['action'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="visitas_export.csv');
    $output = fopen('php://output', 'w');
    // BOM UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    // Cabeçalho
    fputcsv($output, ['IP', 'Data/Hora', 'País', 'Código', 'Região', 'Cidade', 'ISP', 'Latitude', 'Longitude', 'User-Agent', 'Referer', 'Página']);
    foreach ($logs as $v) {
        fputcsv($output, [
            $v['ip'],
            $v['timestamp'],
            $v['pais'],
            $v['pais_code'],
            $v['regiao'],
            $v['cidade'],
            $v['isp'],
            $v['lat'] ?? '',
            $v['lon'] ?? '',
            $v['user_agent'],
            $v['referer'],
            $v['pagina']
        ]);
    }
    fclose($output);
    exit;
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin — Painel de Visitas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #0d1117;
            color: #c9d1d9;
            min-height: 100vh;
        }

        /* --- Tela de Login --- */
        .login-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-box {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 16px;
            padding: 40px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }
        .login-box h1 {
            font-size: 1.6rem;
            margin-bottom: 6px;
            color: #58a6ff;
        }
        .login-box p {
            font-size: 0.85rem;
            color: #8b949e;
            margin-bottom: 24px;
        }
        .login-box input {
            width: 100%;
            padding: 12px 16px;
            margin-bottom: 12px;
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 8px;
            color: #c9d1d9;
            font-size: 0.95rem;
            outline: none;
            transition: border 0.2s;
        }
        .login-box input:focus {
            border-color: #58a6ff;
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            background: #238636;
            border: none;
            border-radius: 8px;
            color: #fff;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }
        .login-box button:hover {
            background: #2ea043;
        }
        .login-box .erro {
            color: #f85149;
            font-size: 0.85rem;
            margin-top: 12px;
        }

        /* --- Dashboard --- */
        .topbar {
            background: #161b22;
            border-bottom: 1px solid #30363d;
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .topbar h1 {
            font-size: 1.4rem;
            color: #58a6ff;
        }
        .topbar .stats {
            color: #8b949e;
            font-size: 0.9rem;
        }
        .topbar .stats strong {
            color: #c9d1d9;
        }
        .topbar .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .topbar .actions a, .topbar .actions button {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 0.85rem;
            font-weight: 500;
            text-decoration: none;
            color: #c9d1d9;
            border: 1px solid #30363d;
            background: #21262d;
            cursor: pointer;
            transition: all 0.2s;
        }
        .topbar .actions a:hover, .topbar .actions button:hover {
            background: #30363d;
        }
        .topbar .actions .btn-danger {
            color: #f85149;
            border-color: #f85149;
        }
        .topbar .actions .btn-danger:hover {
            background: #da3633;
            color: #fff;
        }
        .topbar .actions .btn-csv {
            color: #3fb950;
            border-color: #3fb950;
        }
        .topbar .actions .btn-csv:hover {
            background: #238636;
            color: #fff;
        }

        .container {
            padding: 24px;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Cards de resumo */
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 12px;
            margin-bottom: 24px;
        }
        .summary-card {
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 10px;
            padding: 16px;
            text-align: center;
        }
        .summary-card .num {
            font-size: 1.6rem;
            font-weight: 700;
            color: #58a6ff;
        }
        .summary-card .label {
            font-size: 0.75rem;
            color: #8b949e;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        /* Tabela */
        .table-wrap {
            overflow-x: auto;
            background: #161b22;
            border: 1px solid #30363d;
            border-radius: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        thead {
            background: #21262d;
        }
        th {
            padding: 12px 14px;
            text-align: left;
            font-weight: 600;
            color: #8b949e;
            text-transform: uppercase;
            font-size: 0.7rem;
            letter-spacing: 0.5px;
            white-space: nowrap;
            border-bottom: 1px solid #30363d;
        }
        td {
            padding: 10px 14px;
            border-bottom: 1px solid #21262d;
            white-space: nowrap;
        }
        tr:hover td {
            background: rgba(88,166,255,0.05);
        }
        td .ip-link {
            color: #58a6ff;
            text-decoration: none;
        }
        td .ip-link:hover {
            text-decoration: underline;
        }
        .truncate {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }
        .empty {
            text-align: center;
            padding: 40px;
            color: #484f58;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .topbar { flex-direction: column; align-items: stretch; }
            .container { padding: 12px; }
            td, th { padding: 8px 10px; font-size: 0.75rem; }
        }
    </style>
</head>
<body>

<?php if (!$logado): ?>

    <!-- ===================== TELA DE LOGIN ===================== -->
    <div class="login-container">
        <div class="login-box">
            <h1>🔒 Painel Admin</h1>
            <p>Autenticação necessária para acessar os logs</p>
            <form method="POST">
                <input type="text" name="username" placeholder="Usuário" required autofocus>
                <input type="password" name="password" placeholder="Senha" required>
                <button type="submit" name="login">Entrar</button>
                <?php if (isset($erro)): ?>
                    <div class="erro"><?= htmlspecialchars($erro) ?></div>
                <?php endif; ?>
            </form>
        </div>
    </div>

<?php else: ?>

    <!-- ===================== DASHBOARD ===================== -->
    <div class="topbar">
        <div>
            <h1>📊 Painel de Visitas</h1>
            <div class="stats">
                <strong><?= count($logs) ?></strong> registros capturados
            </div>
        </div>
        <div class="actions">
            <a href="?action=csv" class="btn-csv">📥 Exportar CSV</a>
            <a href="?action=clear" class="btn-danger" onclick="return confirm('Tem certeza que deseja limpar TODOS os logs?')">🗑️ Limpar Logs</a>
            <a href="?logout=1">🚪 Sair</a>
        </div>
    </div>

    <div class="container">
        <!-- Cards -->
        <?php
        $total = count($logs);
        $ipsUnicos = count(array_unique(array_column($logs, 'ip')));
        $paises = count(array_unique(array_filter(array_column($logs, 'pais'), function($v) { return $v !== '—'; })));
        $ultimo = $total > 0 ? $logs[$total - 1]['timestamp'] : '—';
        ?>
        <div class="summary">
            <div class="summary-card">
                <div class="num"><?= $total ?></div>
                <div class="label">Total de Visitas</div>
            </div>
            <div class="summary-card">
                <div class="num"><?= $ipsUnicos ?></div>
                <div class="label">IPs Únicos</div>
            </div>
            <div class="summary-card">
                <div class="num"><?= $paises ?></div>
                <div class="label">Países</div>
            </div>
            <div class="summary-card">
                <div class="num" style="font-size:1rem;"><?= htmlspecialchars($ultimo) ?></div>
                <div class="label">Última Visita</div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="table-wrap">
            <?php if (empty($logs)): ?>
                <div class="empty">Nenhuma visita registrada ainda.</div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>IP</th>
                            <th>Data/Hora</th>
                            <th>País</th>
                            <th>Região</th>
                            <th>Cidade</th>
                            <th>ISP</th>
                            <th>Coordenadas</th>
                            <th>Referer</th>
                            <th>User-Agent</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_reverse($logs) as $i => $v): ?>
                            <tr>
                                <td><?= $total - $i ?></td>
                                <td>
                                    <a href="https://whatismyipaddress.com/ip/<?= urlencode($v['ip']) ?>" target="_blank" class="ip-link">
                                        <?= htmlspecialchars($v['ip']) ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars($v['timestamp']) ?></td>
                                <td>
                                    <?php if ($v['pais_code'] !== '—'): ?>
                                        <span class="badge"><?= htmlspecialchars($v['pais_code']) ?></span>
                                    <?php endif; ?>
                                    <?= htmlspecialchars($v['pais']) ?>
                                </td>
                                <td><?= htmlspecialchars($v['regiao']) ?></td>
                                <td><?= htmlspecialchars($v['cidade']) ?></td>
                                <td><?= htmlspecialchars($v['isp']) ?></td>
                                <td>
                                    <?php if ($v['lat'] && $v['lon']): ?>
                                        <a href="https://www.google.com/maps?q=<?= $v['lat'] ?>,<?= $v['lon'] ?>" target="_blank" class="ip-link">
                                            <?= htmlspecialchars(number_format($v['lat'], 4)) ?>, <?= htmlspecialchars(number_format($v['lon'], 4)) ?>
                                        </a>
                                    <?php else: ?>
                                        —
                                    <?php endif; ?>
                                </td>
                                <td><span class="truncate" title="<?= htmlspecialchars($v['referer']) ?>"><?= htmlspecialchars($v['referer']) ?></span></td>
                                <td><span class="truncate" title="<?= htmlspecialchars($v['user_agent']) ?>"><?= htmlspecialchars($v['user_agent']) ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>
</body>
</html>
