<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

// --- Actions ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if ($id > 0) {
        if ($action === 'publier') {
            db()->prepare("UPDATE avis SET modere = 1, publie = 1, signale = 0 WHERE id = ?")->execute([$id]);
            log_action('moderate_avis', 'avis', $id, 'Avis publié');
            flash('success', 'Avis publié.', 'success');
        } elseif ($action === 'masquer') {
            db()->prepare("UPDATE avis SET modere = 1, publie = 0 WHERE id = ?")->execute([$id]);
            log_action('moderate_avis', 'avis', $id, 'Avis masqué');
            flash('success', 'Avis masqué.', 'success');
        }
    }
    redirect(BASE_URL . 'artec/avis_moderation.php');
}

$stmt = db()->query("
    SELECT a.*, o.nom AS op_nom, o.slug AS op_slug, p.titre AS pub_titre
    FROM avis a
    INNER JOIN operateurs o ON o.id = a.operateur_id
    LEFT JOIN publications p ON p.id = a.publication_id
    WHERE a.signale = 1 AND a.modere = 0
    ORDER BY a.created_at DESC
");
$avisSignales = $stmt->fetchAll();

$stmt = db()->query("
    SELECT a.*, o.nom AS op_nom, o.slug AS op_slug
    FROM avis a
    INNER JOIN operateurs o ON o.id = a.operateur_id
    WHERE a.modere = 1
    ORDER BY a.created_at DESC
    LIMIT 20
");
$avisModeres = $stmt->fetchAll();

$pageTitle = 'Modération avis';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-flag text-artec"></i> Modération des avis</h1>

<div class="card card-artec mb-4">
    <div class="card-header bg-warning text-dark">
        <i class="bi bi-exclamation-triangle"></i> Avis signalés (<?= count($avisSignales) ?>)
    </div>
    <div class="card-body">
        <?php if (empty($avisSignales)): ?>
            <p class="text-muted mb-0">Aucun avis signalé en attente. 🎉</p>
        <?php else: ?>
            <?php foreach ($avisSignales as $a): ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between">
                        <div>
                            <span class="badge-operateur badge-<?= e($a['op_slug']) ?>"><?= e($a['op_nom']) ?></span>
                            <strong class="ms-2"><?= (int) $a['note'] ?>/10</strong>
                            <small class="text-muted">— <?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></small>
                        </div>
                    </div>
                    <?php if ($a['commentaire']): ?><p class="mt-2 mb-2"><?= nl2br(e($a['commentaire'])) ?></p><?php endif; ?>
                    <div class="d-flex gap-2">
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <input type="hidden" name="action" value="publier">
                            <button class="btn btn-sm btn-success"><i class="bi bi-check-circle"></i> Publier</button>
                        </form>
                        <form method="post" class="d-inline">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= (int) $a['id'] ?>">
                            <input type="hidden" name="action" value="masquer">
                            <button class="btn btn-sm btn-danger"><i class="bi bi-eye-slash"></i> Masquer</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="card card-artec">
    <div class="card-header"><i class="bi bi-check2-circle"></i> Avis récemment modérés</div>
    <div class="card-body p-0">
        <?php if (empty($avisModeres)): ?>
            <p class="text-muted p-3 mb-0">Aucun avis modéré pour l'instant.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-artec mb-0">
                    <thead><tr><th>Opérateur</th><th>Note</th><th>Commentaire</th><th>Statut</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($avisModeres as $a): ?>
                            <tr>
                                <td><span class="badge-operateur badge-<?= e($a['op_slug']) ?>"><?= e($a['op_nom']) ?></span></td>
                                <td><?= (int) $a['note'] ?>/10</td>
                                <td><small><?= e(mb_substr($a['commentaire'] ?? '', 0, 80)) ?>…</small></td>
                                <td><?= $a['publie'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-secondary">Masqué</span>' ?></td>
                                <td><small><?= date('d/m/Y', strtotime($a['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>