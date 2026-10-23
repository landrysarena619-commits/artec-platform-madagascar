<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];
$errors = [];

// --- Répondre à une réclamation ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $id = (int) ($_POST['id'] ?? 0);
    $reponse = trim($_POST['reponse'] ?? '');
    $statut = $_POST['statut'] ?? 'en_cours';

    if ($id > 0 && $reponse !== '') {
        if (!in_array($statut, ['en_cours', 'resolu', 'rejete'], true)) $statut = 'en_cours';
        $stmt = db()->prepare("
            UPDATE reclamations
            SET reponse = ?, date_reponse = NOW(), statut = ?
            WHERE id = ? AND operateur_id = ?
        ");
        $stmt->execute([$reponse, $statut, $id, $opId]);
        log_action('repondre_reclamation', 'reclamations', $id, "Statut : $statut");
        flash('success', 'Réponse enregistrée.', 'success');
    } else {
        flash('error', 'Réponse vide.', 'danger');
    }
    redirect(BASE_URL . 'operator/reclamations.php');
}

$stmt = db()->prepare("
    SELECT r.*, p.titre AS pub_titre
    FROM reclamations r
    LEFT JOIN publications p ON p.id = r.publication_id
    WHERE r.operateur_id = ?
    ORDER BY r.created_at DESC
");
$stmt->execute([$opId]);
$reclamations = $stmt->fetchAll();

$pageTitle = 'Réclamations';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-exclamation-circle text-artec"></i> Réclamations reçues</h1>

<?php if (empty($reclamations)): ?>
    <div class="alert alert-info">Aucune réclamation pour le moment. 🎉</div>
<?php else: ?>
    <?php foreach ($reclamations as $r): ?>
        <div class="card card-artec mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span>
                    <strong><?= e($r['sujet']) ?></strong>
                    <span class="badge bg-secondary ms-2"><?= e($r['categorie']) ?></span>
                </span>
                <span class="badge bg-<?= $r['statut'] === 'nouveau' ? 'danger' : ($r['statut'] === 'en_cours' ? 'warning text-dark' : ($r['statut'] === 'resolu' ? 'success' : 'secondary')) ?>">
                    <?= e($r['statut']) ?>
                </span>
            </div>
            <div class="card-body">
                <p class="mb-2"><?= nl2br(e($r['description'])) ?></p>
                <small class="text-muted">
                    Publié le <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?>
                    <?php if ($r['pub_titre']): ?> — sur « <?= e($r['pub_titre']) ?> »<?php endif; ?>
                </small>

                <?php if ($r['reponse']): ?>
                    <div class="alert alert-info mt-3 mb-0">
                        <strong>Votre réponse :</strong>
                        <?= nl2br(e($r['reponse'])) ?>
                        <br><small>Le <?= date('d/m/Y', strtotime($r['date_reponse'])) ?></small>
                    </div>
                <?php else: ?>
                    <form method="post" class="mt-3">
                        <?= csrf_field() ?>
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <div class="mb-2">
                            <label class="form-label fw-bold">Votre réponse</label>
                            <textarea name="reponse" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="d-flex gap-2">
                            <select name="statut" class="form-select" style="max-width: 200px;">
                                <option value="en_cours">En cours</option>
                                <option value="resolu">Résolu</option>
                                <option value="rejete">Rejeté</option>
                            </select>
                            <button class="btn btn-artec"><i class="bi bi-send"></i> Répondre</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>