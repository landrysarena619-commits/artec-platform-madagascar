<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$filterAction = trim($_GET['action'] ?? '');
$filterUser = (int) ($_GET['user'] ?? 0);

$where = [];
$params = [];
if ($filterAction !== '') { $where[] = 'l.action LIKE ?'; $params[] = '%' . $filterAction . '%'; }
if ($filterUser > 0) { $where[] = 'l.user_id = ?'; $params[] = $filterUser; }

$sql = "
    SELECT l.*, u.nom AS user_nom, u.role AS user_role
    FROM logs l
    LEFT JOIN users u ON u.id = l.user_id
    " . ($where ? 'WHERE ' . implode(' AND ', $where) : '') . "
    ORDER BY l.created_at DESC
    LIMIT 200
";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

$users = db()->query("SELECT id, nom FROM users ORDER BY nom")->fetchAll();

$pageTitle = 'Logs d\'activité';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-list-check text-artec"></i> Logs d'activité</h1>

<form method="get" class="card card-artec mb-4">
    <div class="card-body">
        <div class="row g-2">
            <div class="col-md-4">
                <input type="text" name="action" class="form-control" placeholder="Filtrer par action…"
                       value="<?= e($filterAction) ?>">
            </div>
            <div class="col-md-4">
                <select name="user" class="form-select">
                    <option value="">Tous les utilisateurs</option>
                    <?php foreach ($users as $u): ?>
                        <option value="<?= (int) $u['id'] ?>" <?= $filterUser === (int) $u['id'] ? 'selected' : '' ?>>
                            <?= e($u['nom']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-artec w-100"><i class="bi bi-funnel"></i> Filtrer</button></div>
            <div class="col-md-2"><a href="<?= e(BASE_URL) ?>admin/logs.php" class="btn btn-outline-secondary w-100">Reset</a></div>
        </div>
    </div>
</form>

<div class="card card-artec">
    <div class="card-header"><i class="bi bi-clock-history"></i> <?= count($logs) ?> entrée(s)</div>
    <div class="card-body p-0">
        <?php if (empty($logs)): ?>
            <p class="text-muted p-3 mb-0">Aucun log correspondant.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-sm table-artec mb-0">
                    <thead><tr><th>Date</th><th>Utilisateur</th><th>Rôle</th><th>Action</th><th>Entité</th><th>Détails</th></tr></thead>
                    <tbody>
                        <?php foreach ($logs as $l): ?>
                            <tr>
                                <td><small><?= date('d/m/Y H:i:s', strtotime($l['created_at'])) ?></small></td>
                                <td><?= e($l['user_nom'] ?? '—') ?></td>
                                <td><?= $l['user_role'] ? '<span class="badge bg-secondary">' . e($l['user_role']) . '</span>' : '—' ?></td>
                                <td><span class="badge bg-info"><?= e($l['action']) ?></span></td>
                                <td><small><?= e($l['entite'] ?? '') ?> <?= $l['entite_id'] ? '#' . (int) $l['entite_id'] : '' ?></small></td>
                                <td><small><?= e(mb_substr($l['details'] ?? '', 0, 80)) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>