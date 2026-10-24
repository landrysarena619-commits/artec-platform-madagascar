<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

$filterOp   = (int) ($_GET['op'] ?? 0);
$filterPlat = trim($_GET['plateforme'] ?? '');

$where  = ['1=1'];
$params = [];
if ($filterOp > 0) {
    $where[] = 's.operateur_id = ?';
    $params[] = $filterOp;
}
if (in_array($filterPlat, ['facebook', 'instagram', 'tiktok'], true)) {
    $where[] = 's.plateforme = ?';
    $params[] = $filterPlat;
}
$whereSql = implode(' AND ', $where);

$stmt = db()->prepare("
    SELECT s.*, o.nom AS op_nom, o.slug AS op_slug, o.couleur AS op_couleur
    FROM social_posts s
    INNER JOIN operateurs o ON o.id = s.operateur_id
    WHERE $whereSql
    ORDER BY s.date_post DESC
    LIMIT 60
");
$stmt->execute($params);
$posts = $stmt->fetchAll();

$stats = db()->query("
    SELECT
        o.nom AS op_nom,
        o.slug AS op_slug,
        s.plateforme,
        COUNT(*) AS nb_posts,
        SUM(s.likes) AS total_likes,
        SUM(s.commentaires) AS total_comments,
        SUM(s.vues) AS total_views
    FROM social_posts s
    INNER JOIN operateurs o ON o.id = s.operateur_id
    GROUP BY o.id, s.plateforme
    ORDER BY o.nom, s.plateforme
")->fetchAll();

$parOp = [];
foreach ($stats as $s) {
    $parOp[$s['op_nom']][$s['plateforme']] = $s;
}

$totalPosts = (int) db()->query("SELECT COUNT(*) FROM social_posts")->fetchColumn();

$operateurs = db()->query("SELECT id, nom, slug FROM operateurs WHERE actif = 1 ORDER BY nom")->fetchAll();

$plateformeIcons = [
    'facebook' => 'facebook',
    'instagram' => 'instagram',
    'tiktok' => 'tiktok',
];

$plateformeColors = [
    'facebook' => '#1877F2',
    'instagram' => '#E4405F',
    'tiktok' => '#000000',
];

$pageTitle = 'Reseaux sociaux';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h1 class="fw-bold mb-1"><i class="bi bi-share text-artec"></i> Reseaux sociaux</h1>
        <p class="text-muted mb-0"><?= $totalPosts ?> publication(s) collectee(s) sur Facebook, Instagram et TikTok</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <?php foreach ($parOp as $opNom => $plats): ?>
        <?php
        $slug = '';
        $totalOpLikes = 0;
        $totalOpPosts = 0;
        foreach ($plats as $p) {
            $slug = $p['op_slug'];
            $totalOpLikes += (int) $p['total_likes'];
            $totalOpPosts += (int) $p['nb_posts'];
        }
        ?>
        <div class="col-md-4">
            <div class="card card-artec" style="border-top: 4px solid <?= e($slug === 'orange' ? '#FF7900' : ($slug === 'yas' ? '#00A651' : '#E40000')) ?>;">
                <div class="card-body">
                    <h5 class="fw-bold mb-2">
                        <span class="badge-operateur badge-<?= e($slug) ?>"><?= e($opNom) ?></span>
                    </h5>
                    <div class="row g-2 mb-2">
                        <?php foreach (['facebook', 'instagram', 'tiktok'] as $plat): ?>
                            <?php if (isset($plats[$plat])): ?>
                                <div class="col-4 text-center">
                                    <div style="font-size: 1.4rem; color: <?= e($plateformeColors[$plat]) ?>;">
                                        <i class="bi bi-<?= e($plateformeIcons[$plat]) ?>"></i>
                                    </div>
                                    <div class="fw-bold"><?= (int) $plats[$plat]['nb_posts'] ?></div>
                                    <div class="small text-muted">posts</div>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between small">
                        <span><i class="bi bi-hand-thumbs-up"></i> <?= number_format($totalOpLikes, 0, ',', ' ') ?> likes</span>
                        <span><i class="bi bi-collection"></i> <?= $totalOpPosts ?> posts</span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="filter-row">
    <form method="get" class="d-flex flex-wrap gap-2 align-items-center w-100">
        <select name="op" class="form-select form-select-sm filter-select">
            <option value="">Tous les operateurs</option>
            <?php foreach ($operateurs as $op): ?>
                <option value="<?= (int) $op['id'] ?>" <?= $filterOp === (int) $op['id'] ? 'selected' : '' ?>>
                    <?= e($op['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="plateforme" class="form-select form-select-sm filter-select">
            <option value="">Toutes les plateformes</option>
            <option value="facebook"  <?= $filterPlat === 'facebook' ? 'selected' : '' ?>>Facebook</option>
            <option value="instagram" <?= $filterPlat === 'instagram' ? 'selected' : '' ?>>Instagram</option>
            <option value="tiktok"    <?= $filterPlat === 'tiktok' ? 'selected' : '' ?>>TikTok</option>
        </select>
        <button class="btn btn-sm btn-artec">Filtrer</button>
        <a href="<?= e(BASE_URL) ?>artec/social.php" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>
</div>

<?php if (empty($posts)): ?>
    <div class="empty-state">
        Aucun post collecte. Lance <code>python scraping.py</code> pour en generer.
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($posts as $p): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card social-card h-100">
                    <div class="social-card-head" style="background: <?= e($plateformeColors[$p['plateforme']]) ?>;">
                        <span>
                            <i class="bi bi-<?= e($plateformeIcons[$p['plateforme']]) ?>"></i>
                            <?= e(ucfirst($p['plateforme'])) ?>
                        </span>
                        <span class="badge-operateur badge-<?= e($p['op_slug']) ?>"><?= e($p['op_nom']) ?></span>
                    </div>
                    <div class="card-body">
                        <p class="social-text"><?= e(mb_substr($p['texte'], 0, 220)) ?></p>
                        <div class="social-date">
                            <i class="bi bi-calendar"></i>
                            <?= $p['date_post'] ? date('d/m/Y', strtotime($p['date_post'])) : '-' ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="social-metrics">
                            <span><i class="bi bi-hand-thumbs-up"></i> <?= number_format((int) $p['likes'], 0, ',', ' ') ?></span>
                            <span><i class="bi bi-chat"></i> <?= number_format((int) $p['commentaires'], 0, ',', ' ') ?></span>
                            <?php if ($p['partages'] > 0): ?>
                                <span><i class="bi bi-share"></i> <?= number_format((int) $p['partages'], 0, ',', ' ') ?></span>
                            <?php endif; ?>
                            <?php if ($p['vues'] > 0): ?>
                                <span><i class="bi bi-eye"></i> <?= number_format((int) $p['vues'], 0, ',', ' ') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>