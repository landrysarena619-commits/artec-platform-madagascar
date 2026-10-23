<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

// --- KPI globaux ---
$kpi = db()->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS nb_users,
        (SELECT COUNT(*) FROM operateurs WHERE actif = 1) AS nb_op,
        (SELECT COUNT(*) FROM publications) AS nb_pub,
        (SELECT COUNT(*) FROM avis) AS nb_avis,
        (SELECT COUNT(*) FROM messages) AS nb_msg,
        (SELECT COUNT(*) FROM reclamations) AS nb_reclam,
        (SELECT COUNT(*) FROM decisions) AS nb_dec
")->fetch();

// --- Utilisateurs récents ---
$users = db()->query("
    SELECT u.*, o.nom AS op_nom
    FROM users u
    LEFT JOIN operateurs o ON o.id = u.operateur_id
    ORDER BY u.created_at DESC
    LIMIT 8
")->fetchAll();

// --- Activité récente (logs) ---
$logs = db()->query("
    SELECT l.*, u.nom AS user_nom
    FROM logs l
    LEFT JOIN users u ON u.id = l.user_id
    ORDER BY l.created_at DESC
    LIMIT 10
")->fetchAll();

$pageTitle = 'Dashboard Admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-bold mb-1"><i class="bi bi-gear-fill text-artec"></i> Administration</h1>
        <p class="text-muted mb-0">Vue technique de la plateforme</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(BASE_URL) ?>admin/users.php" class="btn btn-artec"><i class="bi bi-people"></i> Utilisateurs</a>
        <a href="<?= e(BASE_URL) ?>admin/logs.php" class="btn btn-outline-primary"><i class="bi bi-list-check"></i> Logs</a>
    </div>
</div>

<!-- ===== KPI ===== -->
<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-value"><?= (int) $kpi['nb_users'] ?></div><div class="kpi-label"><i class="bi bi-people"></i> Utilisateurs</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-orange"><div class="kpi-value"><?= (int) $kpi['nb_pub'] ?></div><div class="kpi-label"><i class="bi bi-megaphone"></i> Publications</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-yas"><div class="kpi-value"><?= (int) $kpi['nb_avis'] ?></div><div class="kpi-label"><i class="bi bi-star"></i> Avis</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-airtel"><div class="kpi-value"><?= (int) $kpi['nb_msg'] ?></div><div class="kpi-label"><i class="bi bi-chat-dots"></i> Messages</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-value"><?= (int) $kpi['nb_op'] ?></div><div class="kpi-label">Opérateurs actifs</div></div></div>
    <div class="col-md-4"><div class="kpi-card kpi-orange"><div class="kpi-value"><?= (int) $kpi['nb_reclam'] ?></div><div class="kpi-label">Réclamations</div></div></div>
    <div class="col-md-4"><div class="kpi-card kpi-airtel"><div class="kpi-value"><?= (int) $kpi['nb_dec'] ?></div><div class="kpi-label">Décisions</div></div></div>
</div>

<div class="row g-4">
    <!-- ===== UTILISATEURS RÉCENTS ===== -->
    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-people"></i> Utilisateurs récents</span>
                <a href="<?= e(BASE_URL) ?>admin/users.php" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php foreach ($users as $u): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= e($u['nom']) ?></strong>
                                <div class="small text-muted"><?= e($u['email']) ?></div>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : ($u['role'] === 'artec' ? 'primary' : 'success') ?>">
                                    <?= e($u['role']) ?>
                                </span>
                                <?php if (!$u['actif']): ?><br><small class="text-danger">inactif</small><?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <!-- ===== LOGS RÉCENTS ===== -->
    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-check"></i> Activité récente</span>
                <a href="<?= e(BASE_URL) ?>admin/logs.php" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                    <p class="text-muted p-3 mb-0">Aucune activité.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($logs as $l): ?>
                            <li class="list-group-item small">
                                <span class="badge bg-secondary"><?= e($l['action']) ?></span>
                                <strong><?= e($l['user_nom'] ?? 'Anonyme') ?></strong>
                                <?= e($l['details'] ? '— ' . mb_substr($l['details'], 0, 60) : '') ?>
                                <div class="text-muted"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>