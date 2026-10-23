<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

$opId = (int) ($_GET['id'] ?? 0);

$stmt = db()->query("SELECT * FROM operateurs WHERE actif = 1 ORDER BY id");
$operateurs = $stmt->fetchAll();

$selected = null;
$stats = null;
$publications = [];
$avis = [];

if ($opId > 0) {
    $s = db()->prepare("SELECT * FROM operateurs WHERE id = ? AND actif = 1");
    $s->execute([$opId]);
    $selected = $s->fetch();

    if ($selected) {
        $s = db()->prepare("
            SELECT
                (SELECT COUNT(*) FROM publications WHERE operateur_id = ?) AS nb_pub,
                (SELECT COUNT(*) FROM avis WHERE operateur_id = ? AND publie = 1) AS nb_avis,
                (SELECT COALESCE(ROUND(AVG(note),2),0) FROM avis WHERE operateur_id = ? AND publie = 1) AS note_moy,
                (SELECT COUNT(*) FROM reclamations WHERE operateur_id = ? AND statut IN ('nouveau','en_cours')) AS nb_reclam,
                (SELECT COUNT(*) FROM decisions WHERE operateur_id = ? AND statut IN ('ouverte','en_cours')) AS nb_decision
        ");
        $s->execute([$opId, $opId, $opId, $opId, $opId]);
        $stats = $s->fetch();

        $s = db()->prepare("SELECT * FROM publications WHERE operateur_id = ? ORDER BY created_at DESC LIMIT 10");
        $s->execute([$opId]);
        $publications = $s->fetchAll();

        $s = db()->prepare("SELECT * FROM avis WHERE operateur_id = ? AND publie = 1 ORDER BY created_at DESC LIMIT 20");
        $s->execute([$opId]);
        $avis = $s->fetchAll();
    }
}

$pageTitle = 'Opérateurs';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-briefcase text-artec"></i> Opérateurs</h1>

<div class="row g-3 mb-4">
    <?php foreach ($operateurs as $op): ?>
        <div class="col-md-4">
            <a href="?id=<?= (int) $op['id'] ?>"
               class="card card-artec text-decoration-none h-100 <?= $opId === (int) $op['id'] ? 'border border-3' : '' ?>"
               style="border-color: <?= e($op['couleur']) ?> !important;">
                <div class="card-body text-center">
                    <div class="mb-2" style="font-size: 2rem; color: <?= e($op['couleur']) ?>;">
                        <i class="bi bi-broadcast"></i>
                    </div>
                    <h5 class="fw-bold text-dark"><?= e($op['nom']) ?></h5>
                </div>
            </a>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($selected && $stats): ?>
    <div class="card card-artec mb-4">
        <div class="card-header">Détail : <?= e($selected['nom']) ?></div>
        <div class="card-body">
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="kpi-card"><div class="kpi-value"><?= (int) $stats['nb_pub'] ?></div><div class="kpi-label">Publications</div></div></div>
                <div class="col-md-3"><div class="kpi-card kpi-orange"><div class="kpi-value"><?= (int) $stats['nb_avis'] ?></div><div class="kpi-label">Avis</div></div></div>
                <div class="col-md-3"><div class="kpi-card kpi-yas"><div class="kpi-value"><?= number_format((float) $stats['note_moy'], 1) ?></div><div class="kpi-label">Note moyenne</div></div></div>
                <div class="col-md-3"><div class="kpi-card kpi-airtel"><div class="kpi-value"><?= (int) $stats['nb_decision'] ?></div><div class="kpi-label">Décisions ouvertes</div></div></div>
            </div>

            <h6 class="fw-bold mt-3">Publications récentes</h6>
            <div class="table-responsive mb-4">
                <table class="table table-sm table-artec">
                    <thead><tr><th>Titre</th><th>Type</th><th>Statut</th><th>Date</th></tr></thead>
                    <tbody>
                        <?php foreach ($publications as $p): ?>
                            <tr>
                                <td><?= e($p['titre']) ?></td>
                                <td><span class="badge bg-secondary"><?= e($p['type']) ?></span></td>
                                <td><?= $p['publie'] ? '<span class="badge bg-success">Publié</span>' : '<span class="badge bg-warning text-dark">Brouillon</span>' ?></td>
                                <td><small><?= date('d/m/Y', strtotime($p['created_at'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <h6 class="fw-bold">Derniers avis</h6>
            <ul class="list-group">
                <?php foreach ($avis as $a): ?>
                    <li class="list-group-item">
                        <div class="d-flex justify-content-between">
                            <span class="rating-stars">
                                <?php
                                $s = round((int) $a['note'] / 2);
                                for ($i = 1; $i <= 5; $i++) echo $i <= $s ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                ?>
                            </span>
                            <strong><?= (int) $a['note'] ?>/10</strong>
                        </div>
                        <small class="text-muted"><?= e($a['region']) ?> · <?= date('d/m/Y', strtotime($a['created_at'])) ?></small>
                        <?php if ($a['commentaire']): ?><p class="mb-0 small"><?= e($a['commentaire']) ?></p><?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>