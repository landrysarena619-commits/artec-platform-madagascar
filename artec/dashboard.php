<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

$stmt = db()->query("
    SELECT
        o.id, o.nom, o.slug, o.couleur,
        (SELECT COUNT(*) FROM publications WHERE operateur_id = o.id AND publie = 1) AS nb_pub,
        (SELECT COUNT(*) FROM avis WHERE operateur_id = o.id AND publie = 1) AS nb_avis,
        (SELECT COALESCE(ROUND(AVG(note),2),0) FROM avis WHERE operateur_id = o.id AND publie = 1) AS note_moy,
        (SELECT COUNT(*) FROM reclamations WHERE operateur_id = o.id AND statut IN ('nouveau','en_cours')) AS nb_reclam,
        (SELECT COUNT(*) FROM decisions WHERE operateur_id = o.id AND statut IN ('ouverte','en_cours')) AS nb_decision
    FROM operateurs o
    WHERE o.actif = 1
    ORDER BY o.id
");
$operateurs = $stmt->fetchAll();

$avisSignales = (int) db()->query("SELECT COUNT(*) FROM avis WHERE signale = 1 AND modere = 0")->fetchColumn();

$reclamations = db()->query("
    SELECT r.*, o.nom AS op_nom, o.slug AS op_slug
    FROM reclamations r
    INNER JOIN operateurs o ON o.id = r.operateur_id
    ORDER BY r.created_at DESC
    LIMIT 5
")->fetchAll();

$decisions = db()->query("
    SELECT d.*, o.nom AS op_nom, o.slug AS op_slug
    FROM decisions d
    INNER JOIN operateurs o ON o.id = d.operateur_id
    ORDER BY d.created_at DESC
    LIMIT 5
")->fetchAll();

$predictions = db()->query("
    SELECT p.*, o.nom AS op_nom
    FROM predictions p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    ORDER BY p.date_prediction DESC
    LIMIT 6
")->fetchAll();

$pageTitle = 'Dashboard ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-bold mb-1"><i class="bi bi-shield-check text-artec"></i> Dashboard ARTEC</h1>
        <p class="text-muted mb-0">Vue d'ensemble de la regulation des telecoms</p>
    </div>
    <?php if ($avisSignales > 0): ?>
        <a href="<?= e(BASE_URL) ?>artec/avis_moderation.php" class="btn btn-warning">
            <i class="bi bi-flag-fill"></i> <?= $avisSignales ?> avis signale(s)
        </a>
    <?php endif; ?>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($operateurs as $op): ?>
        <div class="col-md-4">
            <div class="card card-artec h-100" style="border-top: 4px solid <?= e($op['couleur']) ?>;">
                <div class="card-body">
                    <h5 class="fw-bold mb-1"><?= e($op['nom']) ?></h5>
                    <div class="text-muted small mb-3">Note globale</div>
                    <div class="display-5 fw-bold text-artec mb-1">
                        <?= number_format((float) $op['note_moy'], 1) ?><span class="fs-5">/10</span>
                    </div>
                    <div class="rating-stars mb-3">
                        <?php
                        $s = round((float) $op['note_moy'] / 2);
                        for ($i = 1; $i <= 5; $i++) echo $i <= $s ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                        ?>
                    </div>
                    <div class="row g-2 small">
                        <div class="col-6"><i class="bi bi-megaphone text-primary"></i> <strong><?= (int) $op['nb_pub'] ?></strong> publications</div>
                        <div class="col-6"><i class="bi bi-star text-warning"></i> <strong><?= (int) $op['nb_avis'] ?></strong> avis</div>
                        <div class="col-6"><i class="bi bi-exclamation-circle text-danger"></i> <strong><?= (int) $op['nb_reclam'] ?></strong> reclam.</div>
                        <div class="col-6"><i class="bi bi-shield-exclamation text-warning"></i> <strong><?= (int) $op['nb_decision'] ?></strong> decisions</div>
                    </div>
                    <a href="<?= e(BASE_URL) ?>artec/operateurs.php?id=<?= (int) $op['id'] ?>"
                       class="btn btn-sm btn-outline-primary w-100 mt-3">
                        Voir le detail <i class="bi bi-arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-bar-chart"></i> Comparaison des notes moyennes</div>
            <div class="card-body"><canvas id="compChart" height="100"></canvas></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-artec h-100">
            <div class="card-header"><i class="bi bi-cpu"></i> Predictions ML recentes</div>
            <div class="card-body p-0">
                <?php if (empty($predictions)): ?>
                    <div class="p-3 text-muted small">
                        Aucune prediction disponible.<br>
                        Lance <code>python predict.py</code> pour generer.
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($predictions as $p): ?>
                            <li class="list-group-item small">
                                <strong><?= e($p['op_nom']) ?></strong>
                                <span class="badge bg-info"><?= e($p['type_prediction']) ?></span><br>
                                Valeur : <strong><?= number_format((float) $p['valeur'], 2) ?></strong>
                                <?php if ($p['label']): ?> - <?= e($p['label']) ?><?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-4 mt-3">
    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header"><i class="bi bi-exclamation-circle"></i> Reclamations recentes</div>
            <div class="card-body p-0">
                <?php if (empty($reclamations)): ?>
                    <p class="text-muted p-3 mb-0">Aucune reclamation.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($reclamations as $r): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="badge-operateur badge-<?= e($r['op_slug']) ?>"><?= e($r['op_nom']) ?></span>
                                    <span class="badge bg-<?= $r['statut'] === 'nouveau' ? 'danger' : ($r['statut'] === 'en_cours' ? 'warning text-dark' : ($r['statut'] === 'resolu' ? 'success' : 'secondary')) ?>">
                                        <?= e($r['statut']) ?>
                                    </span>
                                </div>
                                <div class="mt-1"><strong><?= e($r['sujet']) ?></strong></div>
                                <small class="text-muted"><?= date('d/m/Y', strtotime($r['created_at'])) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card card-artec h-100">
            <div class="card-header d-flex justify-content-between">
                <span><i class="bi bi-shield-exclamation"></i> Decisions recentes</span>
                <a href="<?= e(BASE_URL) ?>artec/decisions.php" class="btn btn-sm btn-light">Gerer</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($decisions)): ?>
                    <p class="text-muted p-3 mb-0">Aucune decision.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($decisions as $d): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="badge-operateur badge-<?= e($d['op_slug']) ?>"><?= e($d['op_nom']) ?></span>
                                    <span class="badge bg-<?= $d['gravite'] === 'critique' ? 'danger' : ($d['gravite'] === 'haute' ? 'warning text-dark' : 'secondary') ?>">
                                        <?= e($d['gravite']) ?>
                                    </span>
                                </div>
                                <div class="mt-1"><strong><?= e($d['titre']) ?></strong></div>
                                <small class="text-muted"><?= e(ucfirst(str_replace('_', ' ', $d['type']))) ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
const compCtx = document.getElementById('compChart');
if (compCtx) {
    new Chart(compCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($operateurs, 'nom')) ?>,
            datasets: [{
                label: 'Note moyenne /10',
                data: <?= json_encode(array_map(fn($o) => (float) $o['note_moy'], $operateurs)) ?>,
                backgroundColor: <?= json_encode(array_column($operateurs, 'couleur')) ?>,
                borderRadius: 8,
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { min: 0, max: 10 } }
        }
    });
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>