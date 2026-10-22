<?php
/**
 * Créer / modifier une publication.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];
$id = (int) ($_GET['id'] ?? 0);

$pub = [
    'id' => 0,
    'titre' => '',
    'contenu' => '',
    'type' => 'offre',
    'service' => 'mobile',
    'prix' => '',
    'date_debut' => date('Y-m-d'),
    'date_fin' => date('Y-m-d', strtotime('+30 days')),
    'publie' => 1,
];

// --- Mode édition ---
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM publications WHERE id = ? AND operateur_id = ?");
    $stmt->execute([$id, $opId]);
    $found = $stmt->fetch();
    if (!$found) {
        flash('error', 'Publication introuvable ou non autorisée.', 'danger');
        redirect(BASE_URL . 'operator/publications.php');
    }
    $pub = $found;
}

$errors = [];

// --- Traitement POST ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Session expirée. Rechargez la page.';
    } else {
        $titre    = trim($_POST['titre'] ?? '');
        $contenu  = trim($_POST['contenu'] ?? '');
        $type     = $_POST['type'] ?? 'offre';
        $service  = $_POST['service'] ?? 'mobile';
        $prix     = $_POST['prix'] !== '' ? (float) $_POST['prix'] : null;
        $dateDeb  = $_POST['date_debut'] ?: null;
        $dateFin  = $_POST['date_fin'] ?: null;
        $publie   = isset($_POST['publie']) ? 1 : 0;

        if ($titre === '') $errors[] = 'Le titre est obligatoire.';
        if ($contenu === '') $errors[] = 'Le contenu est obligatoire.';
        if (!in_array($type, ['offre', 'promotion', 'information', 'nouveaute'], true)) $errors[] = 'Type invalide.';
        if (!array_key_exists($service, SERVICES)) $errors[] = 'Service invalide.';
        if ($prix !== null && $prix < 0) $errors[] = 'Prix invalide.';

        if (empty($errors)) {
            // Slug unique
            $slug = slugify($titre);
            $base = $slug;
            $i = 1;
            while (true) {
                $check = db()->prepare("SELECT id FROM publications WHERE slug = ? AND id != ?");
                $check->execute([$slug, $id]);
                if (!$check->fetch()) break;
                $slug = $base . '-' . (++$i);
            }

            if ($id > 0) {
                $stmt = db()->prepare("
                    UPDATE publications
                    SET titre=?, slug=?, contenu=?, type=?, service=?, prix=?, date_debut=?, date_fin=?, publie=?
                    WHERE id=? AND operateur_id=?
                ");
                $stmt->execute([$titre, $slug, $contenu, $type, $service, $prix, $dateDeb, $dateFin, $publie, $id, $opId]);
                log_action('update_publication', 'publications', $id, "Modifiée : $titre");
                flash('success', 'Publication modifiée avec succès.', 'success');
            } else {
                $stmt = db()->prepare("
                    INSERT INTO publications (operateur_id, titre, slug, contenu, type, service, prix, date_debut, date_fin, publie)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$opId, $titre, $slug, $contenu, $type, $service, $prix, $dateDeb, $dateFin, $publie]);
                log_action('create_publication', 'publications', (int) db()->lastInsertId(), "Créée : $titre");
                flash('success', 'Publication créée avec succès.', 'success');
            }
            redirect(BASE_URL . 'operator/publications.php');
        }
    }
}

$pageTitle = $id > 0 ? 'Modifier la publication' : 'Nouvelle publication';
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>operator/dashboard.php">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>operator/publications.php">Publications</a></li>
        <li class="breadcrumb-item active"><?= $id > 0 ? 'Modifier' : 'Nouvelle' ?></li>
    </ol>
</nav>

<h1 class="fw-bold mb-4">
    <i class="bi bi-<?= $id > 0 ? 'pencil-square' : 'plus-circle' ?> text-artec"></i>
    <?= $id > 0 ? 'Modifier la publication' : 'Nouvelle publication' ?>
</h1>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" class="card card-artec">
    <div class="card-body p-4">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label fw-bold">Titre *</label>
            <input type="text" name="titre" class="form-control form-control-lg" required maxlength="200"
                   value="<?= e($pub['titre']) ?>" placeholder="Ex: Offre Fibre 100 Mbps">
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Type *</label>
                <select name="type" class="form-select" required>
                    <?php foreach (['offre', 'promotion', 'information', 'nouveaute'] as $t): ?>
                        <option value="<?= e($t) ?>" <?= $pub['type'] === $t ? 'selected' : '' ?>>
                            <?= e(ucfirst($t)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6 mb-3">
                <label class="form-label fw-bold">Service concerné *</label>
                <select name="service" class="form-select" required>
                    <?php foreach (SERVICES as $k => $v): ?>
                        <option value="<?= e($k) ?>" <?= $pub['service'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label fw-bold">Contenu *</label>
            <textarea name="contenu" class="form-control" rows="6" required
                      placeholder="Décrivez votre offre en détail..."><?= e($pub['contenu']) ?></textarea>
        </div>

        <div class="row">
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Prix (Ar/mois)</label>
                <input type="number" name="prix" class="form-control" step="0.01" min="0"
                       value="<?= e((string) $pub['prix']) ?>" placeholder="0 = gratuit">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Date de début</label>
                <input type="date" name="date_debut" class="form-control" value="<?= e($pub['date_debut'] ?? '') ?>">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label fw-bold">Date de fin</label>
                <input type="date" name="date_fin" class="form-control" value="<?= e($pub['date_fin'] ?? '') ?>">
            </div>
        </div>

        <div class="form-check form-switch mb-3">
            <input class="form-check-input" type="checkbox" name="publie" id="publie"
                   <?= $pub['publie'] ? 'checked' : '' ?>>
            <label class="form-check-label fw-bold" for="publie">Publier immédiatement</label>
        </div>
    </div>
    <div class="card-footer bg-white d-flex justify-content-between">
        <a href="<?= e(BASE_URL) ?>operator/publications.php" class="btn btn-secondary">
            <i class="bi bi-x-circle"></i> Annuler
        </a>
        <button type="submit" class="btn btn-artec">
            <i class="bi bi-check-circle"></i> <?= $id > 0 ? 'Enregistrer' : 'Créer' ?>
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>