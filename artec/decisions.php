<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

$user = current_user();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? 'create';

    if ($action === 'create') {
        $opId    = (int) ($_POST['operateur_id'] ?? 0);
        $type    = $_POST['type'] ?? 'avertissement';
        $titre   = trim($_POST['titre'] ?? '');
        $contenu = trim($_POST['contenu'] ?? '');
        $gravite = $_POST['gravite'] ?? 'moyenne';
        $limite  = $_POST['date_limite'] ?: null;

        if ($opId <= 0) $errors[] = 'Opérateur requis.';
        if ($titre === '') $errors[] = 'Titre requis.';
        if ($contenu === '') $errors[] = 'Contenu requis.';

        if (empty($errors)) {
            $stmt = db()->prepare("
                INSERT INTO decisions (operateur_id, artec_user_id, type, titre, contenu, gravite, statut, date_limite)
                VALUES (?, ?, ?, ?, ?, ?, 'ouverte', ?)
            ");
            $stmt->execute([$opId, $user['id'], $type, $titre, $contenu, $gravite, $limite]);
            log_action('create_decision', 'decisions', (int) db()->lastInsertId(), "Créée : $titre");
            flash('success', 'Décision créée.', 'success');
            redirect(BASE_URL . 'artec/decisions.php');
        }
    } elseif ($action === 'cloturer') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("UPDATE decisions SET statut = 'resolue' WHERE id = ?")->execute([$id]);
        flash('success', 'Décision clôturée.', 'success');
        redirect(BASE_URL . 'artec/decisions.php');
    }
}

$stmt = db()->query("SELECT id, nom FROM operateurs WHERE actif = 1 ORDER BY nom");
$operateurs = $stmt->fetchAll();

$stmt = db()->query("
    SELECT d.*, o.nom AS op_nom, o.slug AS op_slug, u.nom AS artec_nom
    FROM decisions d
    INNER JOIN operateurs o ON o.id = d.operateur_id
    INNER JOIN users u ON u.id = d.artec_user_id
    ORDER BY d.created_at DESC
");
$decisions = $stmt->fetchAll();

$pageTitle = 'Décisions ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-shield-exclamation text-artec"></i> Décisions de régulation</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $e) echo '<li>' . e($e) . '</li>'; ?></ul></div>
<?php endif; ?>

<div class="row g-4">
    <!-- ===== FORMULAIRE ===== -->
    <div class="col-lg-5">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-plus-circle"></i> Nouvelle décision</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Opérateur *</label>
                        <select name="operateur_id" class="form-select" required>
                            <option value="">— Choisir —</option>
                            <?php foreach ($operateurs as $op): ?>
                                <option value="<?= (int) $op['id'] ?>"><?= e($op['nom']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Type *</label>
                        <select name="type" class="form-select" required>
                            <option value="avertissement">Avertissement</option>
                            <option value="mise_en_conformite">Mise en conformité</option>
                            <option value="sanction">Sanction</option>
                            <option value="information">Information</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Gravité *</label>
                        <select name="gravite" class="form-select" required>
                            <option value="faible">Faible</option>
                            <option value="moyenne" selected>Moyenne</option>
                            <option value="haute">Haute</option>
                            <option value="critique">Critique</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Titre *</label>
                        <input type="text" name="titre" class="form-control" required maxlength="200">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Contenu *</label>
                        <textarea name="contenu" class="form-control" rows="5" required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Date limite (optionnel)</label>
                        <input type="date" name="date_limite" class="form-control">
                    </div>

                    <button type="submit" class="btn btn-artec w-100">
                        <i class="bi bi-check-circle"></i> Créer la décision
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- ===== LISTE ===== -->
    <div class="col-lg-7">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-list-check"></i> Toutes les décisions (<?= count($decisions) ?>)</div>
            <div class="card-body p-0">
                <?php if (empty($decisions)): ?>
                    <p class="text-muted p-3 mb-0">Aucune décision émise pour l'instant.</p>
                <?php else: ?>
                    <?php foreach ($decisions as $d): ?>
                        <div class="border-bottom p-3">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="badge-operateur badge-<?= e($d['op_slug']) ?>"><?= e($d['op_nom']) ?></span>
                                <span class="badge bg-<?= $d['statut'] === 'ouverte' ? 'danger' : ($d['statut'] === 'en_cours' ? 'warning text-dark' : ($d['statut'] === 'resolue' ? 'success' : 'secondary')) ?>">
                                    <?= e($d['statut']) ?>
                                </span>
                            </div>
                            <strong><?= e($d['titre']) ?></strong>
                            <div class="small text-muted">
                                <?= e(ucfirst(str_replace('_', ' ', $d['type']))) ?> · Gravité : <?= e($d['gravite']) ?>
                                · <?= date('d/m/Y', strtotime($d['created_at'])) ?>
                            </div>
                            <p class="mb-2 mt-1 small"><?= nl2br(e(mb_substr($d['contenu'], 0, 200))) ?>…</p>
                            <?php if ($d['statut'] !== 'resolue'): ?>
                                <form method="post" class="d-inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="cloturer">
                                    <input type="hidden" name="id" value="<?= (int) $d['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success">
                                        <i class="bi bi-check2"></i> Clôturer
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>