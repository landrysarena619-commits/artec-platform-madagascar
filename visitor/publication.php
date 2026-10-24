<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    redirect(BASE_URL . 'visitor/index.php');
}

$stmt = db()->prepare("
    SELECT p.*, o.nom AS operateur_nom, o.slug AS operateur_slug, o.description AS operateur_desc
    FROM publications p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    WHERE p.id = ? AND p.publie = 1
");
$stmt->execute([$id]);
$pub = $stmt->fetch();

if (!$pub) {
    redirect(BASE_URL . 'visitor/index.php');
}

$stmtAvis = db()->prepare("
    SELECT note, commentaire, region, service, created_at
    FROM avis WHERE operateur_id = ? AND publie = 1
    ORDER BY created_at DESC LIMIT 30
");
$stmtAvis->execute([$pub['operateur_id']]);
$avis = $stmtAvis->fetchAll();

$stmtNote = db()->prepare("SELECT AVG(note) AS moy, COUNT(*) AS total FROM avis WHERE operateur_id = ? AND publie = 1");
$stmtNote->execute([$pub['operateur_id']]);
$noteInfo = $stmtNote->fetch();
$noteMoy = $noteInfo['moy'] ? (float) $noteInfo['moy'] : 0;
$noteTotal = (int) $noteInfo['total'];

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

<div class="container py-4">
    <!-- Fil d'Ariane discret -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>visitor/index.php" class="text-decoration-none">Accueil</a></li>
            <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>visitor/index.php?op=<?= e($pub['operateur_slug']) ?>" class="text-decoration-none"><?= e($pub['operateur_nom']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e(mb_substr($pub['titre'], 0, 30)) ?>...</li>
        </ol>
    </nav>

    <div class="row g-4">
        
        <!-- COLONNE GAUCHE (Contenu Principal) -->
        <div class="col-lg-8">
            
            <!-- CARTE PUBLICATION -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
                <div class="card-body p-4 p-md-5">
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge-operateur badge-<?= e($pub['operateur_slug']) ?> px-3 py-2" style="font-size: 0.8rem;">
                            <?= e($pub['operateur_nom']) ?>
                        </span>
                        <span class="text-muted small d-flex align-items-center gap-1">
                            <i class="bi bi-eye"></i> <?= (int) $pub['vues'] ?> vues
                        </span>
                    </div>

                    <h1 class="fw-bold mb-4" style="color: #1e293b; letter-spacing: -0.03em; font-size: 2.2rem;"><?= e($pub['titre']) ?></h1>

                    <div class="d-flex flex-wrap gap-2 mb-5">
                        <span class="badge bg-light text-dark border px-3 py-2 fw-normal text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                            <?= e(ucfirst($pub['type'])) ?>
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 fw-normal text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                            <i class="bi bi-wifi me-1"></i> <?= e(SERVICES[$pub['service']] ?? $pub['service']) ?>
                        </span>
                        <?php if ($pub['date_fin']): ?>
                            <span class="badge bg-warning text-dark px-3 py-2 fw-normal text-uppercase" style="letter-spacing: 0.5px; font-size: 0.75rem;">
                                <i class="bi bi-clock me-1"></i> Jusqu'au <?= date('d/m/Y', strtotime($pub['date_fin'])) ?>
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($pub['prix'] > 0): ?>
                        <div class="p-4 mb-5 rounded-3 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 5px solid #0a3d91;">
                            <div>
                                <div class="text-uppercase fw-bold text-primary mb-1" style="letter-spacing: 1px; font-size: 0.75rem;">Prix mensuel indicatif</div>
                                <div class="fs-2 fw-bold text-dark" style="letter-spacing: -1px;"><?= number_format((float) $pub['prix'], 0, ',', ' ') ?> <span class="fs-5 text-muted fw-normal">Ar</span></div>
                            </div>
                            <i class="bi bi-tag-fill fs-1 text-primary" style="opacity: 0.2;"></i>
                        </div>
                    <?php endif; ?>

                    <div class="mb-5 text-secondary" style="line-height: 1.8; font-size: 1.05rem;">
                        <?= nl2br(e($pub['contenu'])) ?>
                    </div>

                    <hr class="my-4" style="border-color: #e2e8f0;">

                    <div class="d-flex flex-wrap gap-3 mt-4">
                        <button type="button" class="btn btn-artec px-4 py-2" data-bs-toggle="modal" data-bs-target="#avisModal">
                            <i class="bi bi-star me-2"></i> Donner mon avis
                        </button>
                        <a href="<?= e(BASE_URL) ?>visitor/chat.php?pub=<?= (int) $pub['id'] ?>&op=<?= (int) $pub['operateur_id'] ?>" class="btn btn-outline-primary px-4 py-2">
                            <i class="bi bi-chat-dots me-2"></i> Contacter l'opérateur
                        </a>
                    </div>
                </div>
            </div>

            <!-- LISTE DES AVIS -->
            <div class="card shadow-sm border-0" style="border-radius: 12px;">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold" style="color: #1e293b;"><i class="bi bi-chat-quote text-primary me-2"></i> Avis des citoyens</h5>
                    <span class="badge bg-primary rounded-pill"><?= $noteTotal ?></span>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($avis)): ?>
                        <div class="p-5 text-center text-muted">
                            <i class="bi bi-chat-square-text display-4 d-block mb-3 opacity-25"></i>
                            <p class="mb-0">Aucun avis pour le moment. Soyez le premier à partager votre expérience.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($avis as $a): ?>
                                <div class="list-group-item p-4 border-bottom" style="border-color: #f1f5f9 !important;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="bi bi-person text-secondary"></i>
                                            </div>
                                            <div>
                                                <div class="text-warning" style="font-size: 0.85rem;">
                                                    <?php
                                                    $s = round((int) $a['note'] / 2);
                                                    for ($i = 1; $i <= 5; $i++) {
                                                        echo $i <= $s ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                                    }
                                                    ?>
                                                    <span class="text-dark fw-bold ms-2"><?= (int) $a['note'] ?>/10</span>
                                                </div>
                                                <div class="text-muted small mt-1">
                                                    <i class="bi bi-geo-alt me-1"></i> <?= e($a['region']) ?> 
                                                    <span class="mx-2">|</span> 
                                                    <i class="bi bi-wifi me-1"></i> <?= e(SERVICES[$a['service']] ?? $a['service']) ?>
                                                </div>
                                            </div>
                                        </div>
                                        <small class="text-muted"><?= date('d/m/Y', strtotime($a['created_at'])) ?></small>
                                    </div>
                                    <?php if ($a['commentaire']): ?>
                                        <p class="mb-0 mt-2 text-secondary" style="font-size: 0.95rem;"><?= nl2br(e($a['commentaire'])) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- COLONNE DROITE (Sidebar Pro) -->
        <div class="col-lg-4">
            
            <!-- CARTE SATISFACTION -->
            <div class="card shadow-sm border-0 mb-4 text-center" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 1px; font-size: 0.75rem;">Satisfaction globale</h6>
                    <div class="d-inline-flex align-items-baseline mb-2">
                        <span class="display-3 fw-bold" style="color: #0a3d91; letter-spacing: -2px;"><?= number_format($noteMoy, 1) ?></span>
                        <span class="fs-4 text-muted ms-1">/10</span>
                    </div>
                    <div class="text-warning mb-3 fs-5">
                        <?php
                        $fullStars = round($noteMoy / 2);
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $fullStars ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                        }
                        ?>
                    </div>
                    <p class="text-muted small mb-0">Basé sur <strong><?= $noteTotal ?></strong> avis</p>
                </div>
            </div>

            <!-- CARTE REPARTITION -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px;">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 1px; font-size: 0.75rem;">Répartition des notes</h6>
                    <?php
                    $total = max(1, (int) $rep['n0_2'] + (int) $rep['n3_5'] + (int) $rep['n6_8'] + (int) $rep['n9_10']);
                    $bars = [
                        ['label' => '9-10 Excellent', 'value' => (int) $rep['n9_10'], 'color' => '#10b981'],
                        ['label' => '6-8 Bien',       'value' => (int) $rep['n6_8'],  'color' => '#06b6d4'],
                        ['label' => '3-5 Moyen',      'value' => (int) $rep['n3_5'],  'color' => '#f59e0b'],
                        ['label' => '0-2 Mauvais',    'value' => (int) $rep['n0_2'],  'color' => '#dc3545'],
                    ];
                    foreach ($bars as $b):
                        $pct = (int) round($b['value'] / $total * 100);
                    ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1 text-secondary">
                                <span class="fw-medium"><?= e($b['label']) ?></span>
                                <span class="text-muted"><?= $b['value'] ?></span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px; background-color: #f1f5f9;">
                                <div class="progress-bar" role="progressbar" style="width: <?= $pct ?>%; background-color: <?= $b['color'] ?>; border-radius: 4px;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- CARTE A PROPOS -->
            <div class="card shadow-sm border-0" style="border-radius: 12px; background: #f8fafc;">
                <div class="card-body p-4">
                    <h6 class="text-uppercase text-muted fw-bold mb-3" style="letter-spacing: 1px; font-size: 0.75rem;">À propos de l'opérateur</h6>
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" style="width: 45px; height: 45px; background-color: <?= e($pub['operateur_couleur'] ?? '#64748b') ?>;">
                            <?= mb_substr($pub['operateur_nom'], 0, 1) ?>
                        </div>
                        <div>
                            <div class="fw-bold text-dark"><?= e($pub['operateur_nom']) ?></div>
                            <div class="small text-muted">Opérateur télécom</div>
                        </div>
                    </div>
                    <p class="small text-secondary mb-0 lh-base"><?= e($pub['operateur_desc'] ?? 'Aucune description disponible.') ?></p>
                </div>
            </div>

        </div>
    </div>
</div>

<!-- MODAL AVIS (Design Pro) -->
<div class="modal fade" id="avisModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 16px;">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-star-fill text-warning me-2"></i> Laisser un avis</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="avis-form">
                <div class="modal-body p-4">
                    <p class="text-muted small mb-4">Votre avis aide l'ARTEC à réguler la qualité des services télécoms à Madagascar.</p>
                    
                    <input type="hidden" name="publication_id" value="<?= (int) $pub['id'] ?>">
                    <input type="hidden" name="operateur_id" value="<?= (int) $pub['operateur_id'] ?>">
                    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Note globale</label>
                        <div class="d-flex align-items-center gap-3">
                            <input type="range" class="form-range" name="note" min="0" max="10" step="1" value="8" oninput="document.getElementById('noteVal').textContent = this.value">
                            <span id="noteVal" class="badge bg-primary fs-5 px-3 py-2">8</span>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Région</label>
                            <select name="region" class="form-select bg-light" required>
                                <?php foreach (REGIONS_MADAGASCAR as $r): ?>
                                    <option value="<?= e($r) ?>"><?= e($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Service</label>
                            <select name="service" class="form-select bg-light" required>
                                <?php foreach (SERVICES as $k => $v): ?>
                                    <option value="<?= e($k) ?>" <?= $k === $pub['service'] ? 'selected' : '' ?>><?= e($v) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-uppercase text-muted" style="letter-spacing: 0.5px;">Votre commentaire</label>
                        <textarea name="commentaire" class="form-control bg-light" rows="3" placeholder="Décrivez votre expérience..."></textarea>
                    </div>

                    <div id="avis-alert"></div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-artec px-4">Envoyer l'avis</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('avis-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const form = e.target;
    const box = document.getElementById('avis-alert');
    const btn = form.querySelector('button[type=submit]');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Envoi...';
    try {
        const res = await fetch('<?= e(BASE_URL) ?>api/submit_avis.php', { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        box.innerHTML = '<div class="alert alert-' + (data.success ? 'success' : 'danger') + ' border-0 mt-3">' + data.message + '</div>';
        if (data.success) { setTimeout(() => location.reload(), 1500); }
    } catch (err) {
        box.innerHTML = '<div class="alert alert-danger border-0 mt-3">Erreur réseau.</div>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Envoyer l\'avis';
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>