<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = hp_get_db();

// --- Filters ---
$filterType = $_GET['type'] ?? '';
$filterIp = trim($_GET['ip'] ?? '');
$search = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filterType !== '') {
    $where[] = 'event_type = :type';
    $params[':type'] = $filterType;
}
if ($filterIp !== '') {
    $where[] = 'ip = :ip';
    $params[':ip'] = $filterIp;
}
if ($search !== '') {
    $where[] = '(username_attempt LIKE :q OR password_attempt LIKE :q OR path LIKE :q OR user_agent LIKE :q)';
    $params[':q'] = '%' . $search . '%';
}
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

// --- Stats ---
$totalEvents = (int)$pdo->query('SELECT COUNT(*) FROM events')->fetchColumn();
$uniqueIps = (int)$pdo->query('SELECT COUNT(DISTINCT ip) FROM events')->fetchColumn();
$loginAttempts = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_type = 'login_attempt'")->fetchColumn();
$trapHits = (int)$pdo->query("SELECT COUNT(*) FROM events WHERE event_type = 'trap_hit'")->fetchColumn();

$topIps = $pdo->query('
    SELECT ip, COUNT(*) as cnt FROM events GROUP BY ip ORDER BY cnt DESC LIMIT 5
')->fetchAll(PDO::FETCH_ASSOC);

$topUsernames = $pdo->query("
    SELECT username_attempt, COUNT(*) as cnt FROM events
    WHERE username_attempt IS NOT NULL AND username_attempt != ''
    GROUP BY username_attempt ORDER BY cnt DESC LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// --- Event list (most recent first, capped) ---
$stmt = $pdo->prepare("SELECT * FROM events $whereSql ORDER BY id DESC LIMIT 200");
$stmt->execute($params);
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Honeypot Dashboard</title>
<style>
    * { box-sizing: border-box; }
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif; background: #f3f4f6; margin: 0; color: #1f2933; }
    header { background: #111827; color: #fff; padding: 14px 24px; display: flex; justify-content: space-between; align-items: center; }
    header h1 { font-size: 16px; font-weight: 600; }
    header a { color: #d1d5db; font-size: 13px; text-decoration: none; }
    .wrap { padding: 24px; max-width: 1200px; margin: 0 auto; }
    .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; margin-bottom: 20px; }
    .stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; }
    .stat-card .num { font-size: 24px; font-weight: 700; }
    .stat-card .label { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .panels { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 20px; }
    .panel { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; }
    .panel h2 { font-size: 13px; margin-bottom: 10px; color: #374151; }
    .panel table { width: 100%; font-size: 13px; }
    .panel td { padding: 4px 0; border-bottom: 1px solid #f3f4f6; }
    .filters { background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; padding: 14px; margin-bottom: 14px; display: flex; gap: 10px; align-items: end; flex-wrap: wrap; }
    .filters label { font-size: 11px; color: #6b7280; display: block; margin-bottom: 3px; }
    .filters input, .filters select { padding: 6px 8px; border: 1px solid #d1d5db; border-radius: 4px; font-size: 13px; }
    .filters button { padding: 7px 14px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
    table.log { width: 100%; border-collapse: collapse; background: #fff; border: 1px solid #e5e7eb; border-radius: 6px; overflow: hidden; font-size: 12px; }
    table.log th { background: #f9fafb; text-align: left; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; color: #374151; }
    table.log td { padding: 7px 10px; border-bottom: 1px solid #f3f4f6; vertical-align: top; }
    .badge { display: inline-block; padding: 2px 7px; border-radius: 10px; font-size: 11px; font-weight: 600; }
    .badge.login_attempt { background: #fee2e2; color: #b91c1c; }
    .badge.trap_hit { background: #fef3c7; color: #92400e; }
    .badge.page_view { background: #e0e7ff; color: #3730a3; }
    .mono { font-family: ui-monospace, monospace; }
    .muted { color: #9ca3af; }
</style>
</head>
<body>
<header>
    <h1>🍯 Honeypot Dashboard</h1>
    <a href="logout.php">Log out</a>
</header>
<div class="wrap">

    <div class="stats">
        <div class="stat-card"><div class="num"><?= $totalEvents ?></div><div class="label">Total events</div></div>
        <div class="stat-card"><div class="num"><?= $uniqueIps ?></div><div class="label">Unique IPs</div></div>
        <div class="stat-card"><div class="num"><?= $loginAttempts ?></div><div class="label">Login attempts</div></div>
        <div class="stat-card"><div class="num"><?= $trapHits ?></div><div class="label">Trap hits</div></div>
    </div>

    <div class="panels">
        <div class="panel">
            <h2>Top attacking IPs</h2>
            <table>
                <?php foreach ($topIps as $row): ?>
                <tr>
                    <td class="mono"><?= htmlspecialchars($row['ip']) ?></td>
                    <td style="text-align:right"><?= (int)$row['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$topIps): ?><tr><td class="muted">No data yet</td></tr><?php endif; ?>
            </table>
        </div>
        <div class="panel">
            <h2>Most tried usernames</h2>
            <table>
                <?php foreach ($topUsernames as $row): ?>
                <tr>
                    <td class="mono"><?= htmlspecialchars($row['username_attempt']) ?></td>
                    <td style="text-align:right"><?= (int)$row['cnt'] ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$topUsernames): ?><tr><td class="muted">No data yet</td></tr><?php endif; ?>
            </table>
        </div>
    </div>

    <form class="filters" method="GET">
        <div>
            <label>Event type</label>
            <select name="type">
                <option value="">All</option>
                <option value="login_attempt" <?= $filterType === 'login_attempt' ? 'selected' : '' ?>>login_attempt</option>
                <option value="trap_hit" <?= $filterType === 'trap_hit' ? 'selected' : '' ?>>trap_hit</option>
                <option value="page_view" <?= $filterType === 'page_view' ? 'selected' : '' ?>>page_view</option>
            </select>
        </div>
        <div>
            <label>IP</label>
            <input type="text" name="ip" value="<?= htmlspecialchars($filterIp) ?>" placeholder="1.2.3.4">
        </div>
        <div>
            <label>Search (user/pass/path/UA)</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="admin, sqlmap...">
        </div>
        <button type="submit">Filter</button>
        <?php if ($filterType || $filterIp || $search): ?><a href="index.php" style="font-size:13px; color:#6b7280;">Clear</a><?php endif; ?>
    </form>

    <table class="log">
        <thead>
        <tr>
            <th>Time</th>
            <th>Type</th>
            <th>IP</th>
            <th>Method</th>
            <th>Path</th>
            <th>Username</th>
            <th>Password</th>
            <th>User agent</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($events as $e): ?>
            <tr>
                <td class="mono"><?= htmlspecialchars($e['created_at']) ?></td>
                <td><span class="badge <?= htmlspecialchars($e['event_type']) ?>"><?= htmlspecialchars($e['event_type']) ?></span></td>
                <td class="mono"><?= htmlspecialchars($e['ip']) ?></td>
                <td><?= htmlspecialchars($e['method']) ?></td>
                <td class="mono"><?= htmlspecialchars($e['path']) ?></td>
                <td class="mono"><?= htmlspecialchars($e['username_attempt'] ?? '') ?></td>
                <td class="mono"><?= htmlspecialchars($e['password_attempt'] ?? '') ?></td>
                <td class="muted" title="<?= htmlspecialchars($e['user_agent'] ?? '') ?>">
                    <?= htmlspecialchars(mb_strimwidth($e['user_agent'] ?? '', 0, 40, '...')) ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$events): ?>
            <tr><td colspan="8" class="muted">No events recorded yet. Once someone hits the decoy, activity shows up here.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <p class="muted" style="margin-top:10px; font-size:12px;">Showing up to 200 most recent events matching your filters.</p>
</div>
</body>
</html>
