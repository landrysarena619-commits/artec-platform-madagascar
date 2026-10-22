<?php
/**
 * Page détail d'une publication + avis + formulaire d'avis.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Publication introuvable.', 'danger');
    redirect(BASE_URL . 'visitor/index.php');
}

// --- Récupérer la publication ---
$stmt = db()->prepare("
    SELECT p.*, o.nom AS operateur_nom, o.slug AS operateur_slug,
           o.couleur AS operateur_couleur, o.description AS operateur_desc
    FROM publications p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    WHERE p.id = ? AND p.publie = 1
");
$stmt->execute([$id]);
$pub = $stmt->fetch();

if (!$pub) {
    flash('error', 'Publication introuvable ou non publiée.', 'danger');
    redirect(BASE_URL . 'visitor/index.php');
}

// --- Incrémenter les vues (une seule fois par session) ---
if (empty($_SESSION['vues'][$id])) {
    db()->prepare("UPDATE publications SET vues = vues + 1 WHERE id = ?")->execute([$id]);
    $_SESSION['vues'][$id] = true;
}

// --- Récupérer les avis publiés ---
$stmtAvis = db()->prepare("
    SELECT note, commentaire, region, service, created_at
    FROM avis
    WHERE operateur_id = ? AND publie = 1
    ORDER BY created_at DESC
    LIMIT 30
");
$stmtAvis->execute([$pub['operateur_id']]);
$avis = $stmtAvis->fetchAll();

// --- Calcul note moyenne ---
$stmtNote = db()->prepare("SELECT AVG(note) AS moy, COUNT(*) AS total FROM avis WHERE operateur_id = ? AND publie = 1");
$stmtNote->execute([$pub['operateur_id']]);
$noteInfo = $stmtNote->fetch();

// --- Répartition des notes pour mini-graphique ---
$stmtRep = db()->prepare("
    SELECT
        SUM(note BETWEEN 0 AND 2) AS n0_2,
        SUM(note BETWEEN 3 AND 5) AS n3_5,
        SUM(note BETWEEN 6 AND 8) AS n6_8,
        SUM(note BETWEEN 9 AND 10) AS n9_10
    FROM avis WHERE operateur_id = ? AND publie = 1
");
$stmtRep->execute([$pub['operateur_id']]);
$rep = $stmtRep->fetch();

$pageTitle = $pub['titre'];
require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= FIL D'ARIANE ================= -->
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>visitor/index.php">Accueil</a></li>
        <li class="breadcrumb-item active"><?= e(mb_substr($pub['titre'], 0, 50)) ?></li>
    </ol>
</nav>

<div class="row g-4">
    <!-- ============ DÉTAIL PUBLICATION ============ -->
    <div class="col-lg-8">
        <article class="card card-artec">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <span class="badge-operateur badge-<?= e($pub['operateur_slug']) ?> fs-6">
                        <?= e($pub['operateur_nom']) ?>
                    </span>
                    <span class="text-muted small">
                        <i class="bi bi-eye"></i> <?= (int) $pub['vues'] ?> vues
                    </span>
                </div>

                <h1 class="fw-bold mb-3"><?= e($pub['titre']) ?></h1>

                <div class="d-flex flex-wrap gap-2 mb-4">
                    <span class="badge bg-primary">
                        <i class="bi bi-tag"></i> <?= e(ucfirst($pub['type'])) ?>
                    </span>
                    <span class="badge bg-secondary">
                        <i class="bi bi-wifi"></i> <?= e(SERVICES[$pub['service']] ?? $pub['service']) ?>
                    </span>
                    <?php if ($pub['date_fin']): ?>
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-calendar-event"></i>
                            Jusqu'au <?= date('d/m/Y', strtotime($pub['date_fin'])) ?>
                        </span>
                    <?php endif; ?>
                </div>

                <?php if ($pub['prix'] > 0): ?>
                    <div class="alert alert-info d-flex align-items-center">
                        <i class="bi bi-cash-coin fs-3 me-3"></i>
                        <div>
                            <div class="fw-bold fs-4 text-artec"><?= number_format((float) $pub['prix'], 0, ',', ' ') ?> Ar</div>
                            <div class="small text-muted">Prix mensuel indicatif</div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="content-article mb-4" style="white-space: pre-line; line-height: 1.7;">
                    <?= e($pub['contenu']) ?>
                </div>

                <hr>

                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-artec" data-bs-toggle="modal" data-bs-target="#avisModal">
                        <i class="bi bi-star"></i> Laisser un avis
                    </button>
                    <a href="<?= e(BASE_URL) ?>visitor/chat.php?pub=<?= (int) $pub['id'] ?>&op=<?= (int) $pub['operateur_id'] ?>"
                       class="btn btn-outline-primary">
                        <i class="bi bi-chat-dots"></i> Discuter avec l'opérateur
                    </a>
                    <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#reclamModal">
                        <i class="bi bi-exclamation-triangle"></i> Signaler un problème
                    </button>
                </div>
            </div>
        </article>
    </div>

    <!-- ============ SIDEBAR : NOTES + RÉPARTITION ============ -->
    <div class="col-lg-4">
        <div class="card card-artec mb-3">
            <div class="card-header"><i class="bi bi-star-fill"></i> Satisfaction globale</div>
            <div class="card-body text-center">
                <div class="display-4 fw-bold text-artec"><?= number_format((float) $noteInfo['moy'], 1) ?><span class="fs-5">/10</span></div>
                <div class="rating-stars mb-2">
                    <?php
                    $fullStars = round((float) $noteInfo['moy'] / 2);
                    for ($i = 1; $i <= 5; $i++) {
                        echo $i <= $fullStars ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                    }
                    ?>
                </div>
                <p class="text-muted small mb-0"><?= (int) $noteInfo['total'] ?> avis pour <?= e($pub['operateur_nom']) ?></p>
            </div>
        </div>

        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-bar-chart"></i> Répartition des notes</div>
            <div class="card-body">
                <?php
                $total = max(1, array_sum(array_map('intval', $rep)));
                $bars = [
                    ['label' => '9-10 (Excellent)', 'value' => (int) $rep['n9_10'], 'class' => 'bg-success'],
                    ['label' => '6-8 (Bien)',       'value' => (int) $rep['n6_8'],  'class' => 'bg-info'],
                    ['label' => '3-5 (Moyen)',      'value' => (int) $rep['n3_5'],  'class' => 'bg-warning'],
                    ['label' => '0-2 (Mauvais)',    'value' => (int) $rep['n0_2'],  'class' => 'bg-danger'],
                ];
                foreach ($bars as $b):
                    $pct = (int) round($b['value'] / $total * 100);
                ?>
                    <div class="mb-2">
                        <div class="d-flex justify-content-between small mb-1">
                            <span><?= e($b['label']) ?></span>
                            <span class="text-muted"><?= $b['value'] ?> (<?= $pct ?>%)</span>
                        </div>
                        <div class="progress" style="height: 8px;">
                            <div class="progress-bar <?= e($b['class']) ?>" style="width: <?= $pct ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- ================= LISTE DES AVIS ================= -->
<section class="mt-5">
    <h3 class="fw-bold mb-3"><i class="bi bi-chat-quote text-artec"></i> Avis des citoyens (<?= (int) $noteInfo['total'] ?>)</h3>
    <div id="avis-list">
        <?php if (empty($avis)): ?>
            <div class="alert alert-info">Soyez le premier à donner votre avis sur cet opérateur.</div>
        <?php else: ?>
            <?php foreach ($avis as $a): ?>
                <div class="card card-artec mb-2">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <div>
                                <span class="rating-stars">
                                    <?php
                                    $stars = round((int) $a['note'] / 2);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $stars ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                    }
                                    ?>
                                </span>
                                <span class="fw-bold ms-2"><?= (int) $a['note'] ?>/10</span>
                            </div>
                            <small class="text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></small>
                        </div>
                        <?php if ($a['commentaire']): ?>
                            <p class="mb-2"><?= nl2br(e($a['commentaire'])) ?></p>
                        <?php endif; ?>
                        <div class="small text-muted">
                            <i class="bi bi-geo-alt"></i> <?= e($a['region']) ?>
                            · <i class="bi bi-wifi"></i> <?= e(SERVICES[$a['service']] ?? $a['service']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- ================= MODAL AVIS ================= -->
<div class="modal fade" id="avisModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-artec">
            <div class="modal-header bg-artec text-white">
                <h5 class="modal-title"><i class="bi bi-star"></i> Laisser un avis</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="avis-form">
                <div class="modal-body">
                    <input type="hidden" name="publication_id" value="<?= (int) $pub['id'] ?>">
                    <input type="hidden" name="operateur_id" value="<?= (int) $pub['operateur_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Note globale (0 à 10)</label>
                        <input type="range" class="form-range" name="note" min="0" max="10" step="1" value="8"
                               oninput="document.getElementById('noteVal').textContent = this.value">
                        <div class="text-center"><span id="noteVal" class="badge bg-artec fs-6">8</span> / 10</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Région</label>
                        <select name="region" class="form-select" required>
                            <?php foreach (REGIONS_MADAGASCAR as $r): ?>
                                <option value="<?= e($r) ?>"><?= e($r) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Service concerné</label>
                        <select name="service" class="form-select" required>
                            <?php foreach (SERVICES as $k => $v): ?>
                                <option value="<?= e($k) ?>" <?= $k === $pub['service'] ? 'selected' : '' ?>><?= e($v) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Commentaire (optionnel)</label>
                        <textarea name="commentaire" class="form-control" rows="3"
                                  placeholder="Décrivez votre expérience..." maxlength="1000"></textarea>
                    </div>

                    <div id="avis-alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-artec">
                        <i class="bi bi-send"></i> Envoyer mon avis
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL RÉCLAMATION ================= -->
<div class="modal fade" id="reclamModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-artec">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bi bi-exclamation-triangle"></i> Signaler un problème</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form id="reclam-form">
                <div class="modal-body">
                    <input type="hidden" name="publication_id" value="<?= (int) $pub['id'] ?>">
                    <input type="hidden" name="operateur_id" value="<?= (int) $pub['operateur_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="mb-3">
                        <label class="form-label fw-bold">Sujet</label>
                        <input type="text" name="sujet" class="form-control" maxlength="200" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Catégorie</label>
                        <select name="categorie" class="form-select" required>
                            <option value="reseau">Réseau</option>
                            <option value="facturation">Facturation</option>
                            <option value="service_client">Service client</option>
                            <option value="offre_non_respectee">Offre non respectée</option>
                            <option value="autre">Autre</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control" rows="4" required></textarea>
                    </div>
                    <div id="reclam-alert"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-danger">Signaler</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============ SOUMISSION AVIS EN AJAX ============
document.getElementById('avis-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const alertBox = document.getElementById('avis-alert');
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;

    try {
        const res = await fetch('<?= e(BASE_URL) ?>api/submit_avis.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();

        if (data.success) {
            alertBox.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            form.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    } catch (err) {
        alertBox.innerHTML = '<div class="alert alert-danger">Erreur réseau. Réessayez.</div>';
    } finally {
        btn.disabled = false;
    }
});

// ============ SOUMISSION RÉCLAMATION EN AJAX ============
document.getElementById('reclam-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const alertBox = document.getElementById('reclam-alert');
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;

    try {
        const res = await fetch('<?= e(BASE_URL) ?>api/signaler_probleme.php', {
            method: 'POST',
            body: new FormData(form)
        });
        const data = await res.json();

        if (data.success) {
            alertBox.innerHTML = '<div class="alert alert-success">' + data.message + '</div>';
            form.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            alertBox.innerHTML = '<div class="alert alert-danger">' + data.message + '</div>';
        }
    } catch (err) {
        alertBox.innerHTML = '<div class="alert alert-danger">Erreur réseau.</div>';
    } finally {
        btn.disabled = false;
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>