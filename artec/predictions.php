<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ml_config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

if (isset($_GET['force'])) {
    require_once __DIR__ . '/../includes/auto_ml.php';
    ml_run_script(ML_PREDICT_BAT);
    ml_log('Prediction forcee manuellement depuis predictions.php');
    $state = ml_read_state();
    $state['last_predict'] = time();
    ml_write_state($state);
    sleep(3);
    flash('success', 'Prediction lancee. Rechargement dans quelques secondes.', 'success');
    redirect(BASE_URL . 'artec/predictions.php');
}

$stmt = db()->query("
    SELECT p.*, o.nom AS op_nom, o.slug AS op_slug, o.couleur AS op_couleur
    FROM predictions p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    INNER JOIN (
        SELECT operateur_id, type_prediction, MAX(date_prediction) AS max_date
        FROM predictions
        GROUP BY operateur_id, type_prediction
    ) latest ON latest.operateur_id = p.operateur_id
              AND latest.type_prediction = p.type_prediction
              AND latest.max_date = p.date_prediction
    ORDER BY p.type_prediction, o.nom
");
$predictions = $stmt->fetchAll();

$parType = [];
foreach ($predictions as $p) {
    $parType[$p['type_prediction']][] = $p;
}

$stmt = db()->query("SELECT * FROM ml_models ORDER BY date_entrainement DESC");
$modeles = $stmt->fetchAll();

$lastPredict = null;
if (file_exists(ML_STATE_FILE)) {
    $state = json_decode(file_get_contents(ML_STATE_FILE), true);
    if (!empty($state['last_predict'])) {
        $lastPredict = (int) $state['last_predict'];
    }
}

$labelsType = [
    'satisfaction' => [
        'label' => 'Satisfaction predite',
        'icon' => 'star-fill',
        'color' => 'primary',
    ],
    'risque_non_conformite' => [
        'label' => 'Risque de non-conformite',
        'icon' => 'exclamation-triangle-fill',
        'color' => 'danger',
    ],
    'sentiment' => [
        'label' => 'Sentiment des avis',
        'icon' => 'emoji-smile',
        'color' => 'success',
    ],
    'anomalie' => [
        'label' => 'Anomalies detectees',
        'icon' => 'shield-exclamation',
        'color' => 'warning',
    ],
];

$pageTitle = 'Predictions ML';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-bold mb-1"><i class="bi bi-cpu text-artec"></i> Predictions Machine Learning</h1>
        <p class="text-muted mb-0">Resultats des 4 modeles scikit-learn</p>
    </div>
    <div class="d-flex gap-2 align-items-center flex-wrap">
        <?php if ($lastPredict): ?>
            <small class="text-muted">
                Derniere prediction : <?= date('d/m/Y H:i', $lastPredict) ?>
            </small>
        <?php else: ?>
            <small class="text-muted">Aucune prediction auto</small>
        <?php endif; ?>
        <a href="?force=1" class="btn btn-artec">
            <i class="bi bi-arrow-clockwise"></i> Actualiser
        </a>
    </div>
</div>

<?php if (empty($predictions)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        <strong>Aucune prediction disponible.</strong><br>
        Pour generer les predictions :
        <ol class="mb-0 mt-2">
            <li>Ouvrir un terminal dans <code>python/</code></li>
            <li>Activer l'environnement : <code>venv\Scripts\activate</code></li>
            <li>Entrainer : <code>python train_models.py</code></li>
            <li>Predire : <code>python predict.py</code></li>
            <li>Ou cliquer sur <strong>Actualiser</strong> ci-dessus</li>
        </ol>
    </div>
<?php else: ?>
    <div class="row g-4 mb-4">
        <?php foreach ($labelsType as $type => $meta): ?>
            <?php if (!empty($parType[$type])): ?>
                <div class="col-lg-6">
                    <div class="card card-artec h-100">
                        <div class="card-header bg-<?= e($meta['color']) ?> <?= in_array($meta['color'], ['warning', 'success', 'info']) ? 'text-dark' : 'text-white' ?>">
                            <i class="bi bi-<?= e($meta['icon']) ?>"></i> <?= e($meta['label']) ?>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-artec mb-0">
                                <thead>
                                    <tr>
                                        <th>Operateur</th>
                                        <th>Valeur</th>
                                        <th>Interpretation</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($parType[$type] as $p): ?>
                                        <tr>
                                            <td>
                                                <span class="badge-operateur badge-<?= e($p['op_slug']) ?>">
                                                    <?= e($p['op_nom']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong>
                                                    <?php if ($type === 'satisfaction'): ?>
                                                        <?= number_format((float) $p['valeur'], 1) ?>/10
                                                    <?php elseif ($type === 'risque_non_conformite' || $type === 'sentiment'): ?>
                                                        <?= number_format((float) $p['valeur'] * 100, 1) ?>%
                                                    <?php else: ?>
                                                        <?= (int) $p['valeur'] ?>
                                                    <?php endif; ?>
                                                </strong>
                                            </td>
                                            <td>
                                                <?php
                                                $badge = 'secondary';
                                                $lbl = $p['label'] ?? '-';
                                                if ($type === 'risque_non_conformite') {
                                                    $badge = $lbl === 'Eleve' ? 'danger' : ($lbl === 'Moyen' ? 'warning text-dark' : 'success');
                                                } elseif ($type === 'sentiment') {
                                                    $badge = $lbl === 'Positif' ? 'success' : ($lbl === 'Negatif' ? 'danger' : 'secondary');
                                                } elseif ($type === 'anomalie') {
                                                    $badge = $p['valeur'] > 0 ? 'warning text-dark' : 'success';
                                                } elseif ($type === 'satisfaction') {
                                                    $badge = $p['valeur'] >= 7 ? 'success' : ($p['valeur'] >= 5 ? 'warning text-dark' : 'danger');
                                                }
                                                ?>
                                                <span class="badge bg-<?= e($badge) ?>"><?= e($lbl) ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer bg-white small text-muted">
                            Mise a jour : <?= date('d/m/Y H:i', strtotime($parType[$type][0]['date_prediction'])) ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="card card-artec">
    <div class="card-header"><i class="bi bi-box-seam"></i> Modeles entraines (<?= count($modeles) ?>)</div>
    <div class="card-body p-0">
        <?php if (empty($modeles)): ?>
            <p class="text-muted p-3 mb-0">Aucun modele entraine pour le moment.</p>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-artec mb-0">
                    <thead>
                        <tr>
                            <th>Modele</th>
                            <th>Version</th>
                            <th>Type</th>
                            <th>Accuracy</th>
                            <th>RMSE</th>
                            <th>Entraine le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($modeles as $m): ?>
                            <tr>
                                <td><strong><?= e($m['nom_modele']) ?></strong></td>
                                <td><span class="badge bg-secondary">v<?= e($m['version']) ?></span></td>
                                <td><?= e($m['type']) ?></td>
                                <td><?= $m['score_accuracy'] !== null ? number_format((float) $m['score_accuracy'] * 100, 1) . '%' : '-' ?></td>
                                <td><?= $m['score_rmse'] !== null ? number_format((float) $m['score_rmse'], 2) : '-' ?></td>
                                <td><small><?= date('d/m/Y H:i', strtotime($m['date_entrainement'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>