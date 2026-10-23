<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

$stmt = db()->prepare("
    SELECT d.*, u.nom AS artec_nom
    FROM decisions d
    INNER JOIN users u ON u.id = d.artec_user_id
    WHERE d.operateur_id = ?
    ORDER BY d.created_at DESC
");
$stmt->execute([$opId]);
$decisions = $stmt->fetchAll();

$pageTitle = 'Décisions ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-shield-exclamation text-artec"></i> Décisions ARTEC reçues</h1>

<?php if (empty($decisions)): ?>
    <div class="alert alert-info">Aucune décision reçue. 🎉</div>
<?php else: ?>
    <?php foreach ($decisions as $d): ?>
        <div class="card card-artec mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <strong><?= e($d['titre']) ?></strong>
                    <span class="badge bg-<?= $d['gravite'] === 'critique' ? 'danger' : ($d['gravite'] === 'haute' ? 'warning text-dark' : 'secondary') ?> ms-2">
                        <?= e($d['gravite']) ?>
                    </span>
                </span>
                <span class="badge bg-<?= $d['statut'] === 'resolue' ? 'success' : 'danger' ?>">
                    <?= e($d['statut']) ?>
                </span>
            </div>
            <div class="card-body">
                <p class="mb-2 small text-muted">
                    Type : <?= e(ucfirst(str_replace('_', ' ', $d['type']))) ?>
                    · Émise par <?= e($d['artec_nom']) ?>
                    · <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                    <?php if ($d['date_limite']): ?>
                        · <strong>Limite : <?= date('d/m/Y', strtotime($d['date_limite'])) ?></strong>
                    <?php endif; ?>
                </p>
                <p><?= nl2br(e($d['contenu'])) ?></p>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>