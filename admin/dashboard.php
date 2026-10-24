<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('admin');

$kpi = db()->query("
    SELECT
        (SELECT COUNT(*) FROM users) AS nb_users,
        (SELECT COUNT(*) FROM users WHERE actif = 0) AS nb_users_attente,
        (SELECT COUNT(*) FROM operateurs WHERE actif = 1) AS nb_op,
        (SELECT COUNT(*) FROM publications) AS nb_pub,
        (SELECT COUNT(*) FROM avis) AS nb_avis,
        (SELECT COUNT(*) FROM messages) AS nb_msg,
        (SELECT COUNT(*) FROM reclamations) AS nb_reclam,
        (SELECT COUNT(*) FROM decisions) AS nb_dec
")->fetch();

$usersEnAttente = db()->query("
    SELECT u.*, o.nom AS op_nom
    FROM users u
    LEFT JOIN operateurs o ON o.id = u.operateur_id
    WHERE u.actif = 0
    ORDER BY u.created_at DESC
")->fetchAll();

$users = db()->query("
    SELECT u.*, o.nom AS op_nom
    FROM users u
    LEFT JOIN operateurs o ON o.id = u.operateur_id
    WHERE u.actif = 1
    ORDER BY u.created_at DESC
    LIMIT 8
")->fetchAll();

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
        <h1 class="fw-bold mb-1"><i class="bi bi-gear text-artec"></i> Administration</h1>
        <p class="text-muted mb-0">Vue technique de la plateforme</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= e(BASE_URL) ?>admin/users.php" class="btn btn-artec"><i class="bi bi-people"></i> Utilisateurs</a>
        <a href="<?= e(BASE_URL) ?>admin/logs.php" class="btn btn-outline-primary"><i class="bi bi-list-check"></i> Logs</a>
    </div>
</div>

<?php if ($kpi['nb_users_attente'] > 0): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong><?= (int) $kpi['nb_users_attente'] ?> compte(s)</strong> en attente de validation.
        <a href="<?= e(BASE_URL) ?>admin/users.php#attente" class="alert-link">Voir la liste</a>
    </div>
<?php endif; ?>

<div class="row g-3 mb-4">
    <div class="col-md-3"><div class="kpi-card"><div class="kpi-value"><?= (int) $kpi['nb_users'] ?></div><div class="kpi-label"><i class="bi bi-people"></i> Utilisateurs</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-orange"><div class="kpi-value"><?= (int) $kpi['nb_pub'] ?></div><div class="kpi-label"><i class="bi bi-megaphone"></i> Publications</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-yas"><div class="kpi-value"><?= (int) $kpi['nb_avis'] ?></div><div class="kpi-label"><i class="bi bi-star"></i> Avis</div></div></div>
    <div class="col-md-3"><div class="kpi-card kpi-airtel"><div class="kpi-value"><?= (int) $kpi['nb_msg'] ?></div><div class="kpi-label"><i class="bi bi-chat-dots"></i> Messages</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="kpi-card"><div class="kpi-value"><?= (int) $kpi['nb_op'] ?></div><div class="kpi-label">Operateurs actifs</div></div></div>
    <div class="col-md-4"><div class="kpi-card kpi-orange"><div class="kpi-value"><?= (int) $kpi['nb_reclam'] ?></div><div class="kpi-label">Reclamations</div></div></div>
    <div class="col-md-4"><div class="kpi-card kpi-airtel"><div class="kpi-value"><?= (int) $kpi['nb_dec'] ?></div><div class="kpi-label">Decisions</div></div></div>
</div>

<?php if (!empty($usersEnAttente)): ?>
    <div class="card card-artec mb-4" id="attente">
        <div class="card-header bg-warning text-dark"><i class="bi bi-clock-history"></i> Comptes en attente de validation (<?= count($usersEnAttente) ?>)</div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-artec mb-0">
                    <thead><tr><th>Nom</th><th>Email</th><th>Role</th><th>Date</th><th class="text-end">Actions</th></tr></thead>
                    <tbody>
                        <?php foreach ($usersEnAttente as $u): ?>
                            <tr>
                                <td><strong><?= e($u['nom']) ?></strong></td>
                                <td><small><?= e($u['email']) ?></small></td>
                                <td><span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : ($u['role'] === 'artec' ? 'primary' : 'success') ?>"><?= e($u['role']) ?></span></td>
                                <td><small><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></small></td>
                                <td class="text-end">
                                    <form method="post" action="<?= e(BASE_URL) ?>admin/users.php" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="valider">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Valider</button>
                                    </form>
                                    <form method="post" action="<?= e(BASE_URL) ?>admin/users.php" class="d-inline" onsubmit="return confirm('Rejeter ce compte ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="rejeter">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="btn btn-sm btn-danger"><i class="bi bi-x-circle"></i> Rejeter</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-people"></i> Utilisateurs actifs</span>
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
                            <span class="badge bg-<?= $u['role'] === 'admin' ? 'dark' : ($u['role'] === 'artec' ? 'primary' : 'success') ?>">
                                <?= e($u['role']) ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-list-check"></i> Activite recente</span>
                <a href="<?= e(BASE_URL) ?>admin/logs.php" class="btn btn-sm btn-light">Tout voir</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($logs)): ?>
                    <p class="text-muted p-3 mb-0">Aucune activite.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($logs as $l): ?>
                            <li class="list-group-item small">
                                <span class="badge bg-secondary"><?= e($l['action']) ?></span>
                                <strong><?= e($l['user_nom'] ?? 'Anonyme') ?></strong>
                                <?= e($l['details'] ? '- ' . mb_substr($l['details'], 0, 60) : '') ?>
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