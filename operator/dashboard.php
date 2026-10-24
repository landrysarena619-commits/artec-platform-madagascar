<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

$kpi = db()->prepare("
    SELECT
        (SELECT COUNT(*) FROM publications WHERE operateur_id = ?) AS nb_pub,
        (SELECT COUNT(*) FROM avis WHERE operateur_id = ? AND publie = 1) AS nb_avis,
        (SELECT COALESCE(ROUND(AVG(note),1), 0) FROM avis WHERE operateur_id = ? AND publie = 1) AS note_moy,
        (SELECT COUNT(*) FROM reclamations WHERE operateur_id = ? AND statut IN ('nouveau','en_cours')) AS nb_reclam
");
$kpi->execute([$opId, $opId, $opId, $opId]);
$k = $kpi->fetch();

$stmt = db()->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM avis WHERE publication_id = p.id) AS nb_avis
    FROM publications p
    WHERE p.operateur_id = ?
    ORDER BY p.created_at DESC
    LIMIT 5
");
$stmt->execute([$opId]);
$recentPubs = $stmt->fetchAll();

$stmt = db()->prepare("
    SELECT a.*, p.titre AS pub_titre
    FROM avis a
    LEFT JOIN publications p ON p.id = a.publication_id
    WHERE a.operateur_id = ? AND a.publie = 1
    ORDER BY a.created_at DESC
    LIMIT 5
");
$stmt->execute([$opId]);
$recentAvis = $stmt->fetchAll();

$stmt = db()->prepare("
    SELECT DATE(created_at) AS jour, ROUND(AVG(note),1) AS moyenne, COUNT(*) AS nb
    FROM avis
    WHERE operateur_id = ? AND created_at >= (CURDATE() - INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY jour
");
$stmt->execute([$opId]);
$evolution = $stmt->fetchAll();

$pageTitle = 'Dashboard Operateur';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-bold mb-1">
            <span class="badge-operateur badge-<?= e($user['operateur_slug']) ?>"><?= e($user['operateur_nom']) ?></span>
        </h1>
        <p class="text-muted mb-0">Bienvenue, <?= e($user['nom']) ?></p>
    </div>
    <a href="<?= e(BASE_URL) ?>operator/publication_edit.php" class="btn btn-artec">
        <i class="bi bi-plus-circle"></i> Nouvelle publication
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="kpi-card">
            <div class="kpi-value"><?= (int) $k['nb_pub'] ?></div>
            <div class="kpi-label"><i class="bi bi-megaphone"></i> Publications</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-orange">
            <div class="kpi-value"><?= (int) $k['nb_avis'] ?></div>
            <div class="kpi-label"><i class="bi bi-star"></i> Avis recus</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-yas">
            <div class="kpi-value"><?= number_format((float) $k['note_moy'], 1) ?><span class="small">/10</span></div>
            <div class="kpi-label"><i class="bi bi-graph-up"></i> Note moyenne</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="kpi-card kpi-airtel">
            <div class="kpi-value"><?= (int) $k['nb_reclam'] ?></div>
            <div class="kpi-label"><i class="bi bi-exclamation-circle"></i> Reclamations</div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-graph-up-arrow"></i> Evolution des notes (7 derniers jours)</div>
            <div class="card-body">
                <canvas id="evoChart" height="100"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-artec h-100">
            <div class="card-header"><i class="bi bi-chat-quote"></i> Derniers avis</div>
            <div class="card-body p-0">
                <?php if (empty($recentAvis)): ?>
                    <p class="text-muted p-3 mb-0">Aucun avis pour le moment.</p>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($recentAvis as $a): ?>
                            <li class="list-group-item">
                                <div class="d-flex justify-content-between">
                                    <span class="rating-stars">
                                        <?php
                                        $s = round((int) $a['note'] / 2);
                                        for ($i = 1; $i <= 5; $i++) {
                                            echo $i <= $s ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                        }
                                        ?>
                                    </span>
                                    <small class="text-muted"><?= date('d/m', strtotime($a['created_at'])) ?></small>
                                </div>
                                <div class="small mt-1">
                                    <strong><?= (int) $a['note'] ?>/10</strong>
                                    - <?= e($a['region']) ?>
                                </div>
                                <?php if ($a['commentaire']): ?>
                                    <p class="small mb-0 text-muted mt-1">
                                        <?= e(mb_substr($a['commentaire'], 0, 80)) ?>...
                                    </p>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card card-artec mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-megaphone"></i> Mes publications recentes</span>
        <a href="<?= e(BASE_URL) ?>operator/publications.php" class="btn btn-sm btn-light">Tout voir</a>
    </div>
    <div class="card-body p-0">
        <?php if (empty($recentPubs)): ?>
            <p class="text-muted p-3 mb-0">Aucune publication encore. <a href="<?= e(BASE_URL) ?>operator/publication_edit.php">Creer la premiere</a>.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-artec mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Statut</th>
                            <th>Avis</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentPubs as $p): ?>
                            <tr>
                                <td class="fw-bold"><?= e($p['titre']) ?></td>
                                <td><span class="badge bg-secondary"><?= e(ucfirst($p['type'])) ?></span></td>
                                <td>
                                    <?php if ($p['publie']): ?>
                                        <span class="badge bg-success">Publie</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $p['nb_avis'] ?></td>
                                <td class="text-end">
                                    <a href="<?= e(BASE_URL) ?>operator/publication_edit.php?id=<?= (int) $p['id'] ?>"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
const evoCtx = document.getElementById('evoChart');
if (evoCtx) {
    new Chart(evoCtx, {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($evolution, 'jour')) ?>,
            datasets: [{
                label: 'Note moyenne',
                data: <?= json_encode(array_map('floatval', array_column($evolution, 'moyenne'))) ?>,
                borderColor: '#0b3d91',
                backgroundColor: 'rgba(30, 136, 229, .15)',
                borderWidth: 3,
                tension: .3,
                fill: true,
                pointRadius: 5,
                pointBackgroundColor: '#ffc107',
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