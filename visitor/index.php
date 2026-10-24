<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Accueil';

$filterOp   = trim($_GET['op'] ?? '');
$filterType = trim($_GET['type'] ?? '');

$operateurs = db()->query("
    SELECT o.*,
        (SELECT COUNT(*) FROM publications WHERE operateur_id = o.id AND publie = 1) AS nb_pub,
        (SELECT COUNT(*) FROM avis WHERE operateur_id = o.id AND publie = 1) AS nb_avis,
        (SELECT COALESCE(ROUND(AVG(note),1),0) FROM avis WHERE operateur_id = o.id AND publie = 1) AS note_moy
    FROM operateurs o
    WHERE o.actif = 1
    ORDER BY o.id
")->fetchAll();

$where  = ['p.publie = 1'];
$params = [];
if ($filterOp !== '') {
    $where[] = 'o.slug = ?';
    $params[] = $filterOp;
}
if ($filterType !== '' && in_array($filterType, ['offre','promotion','information','nouveaute'], true)) {
    $where[] = 'p.type = ?';
    $params[] = $filterType;
}
$whereSql = implode(' AND ', $where);

$stmt = db()->prepare("
    SELECT p.*, o.nom AS operateur_nom, o.slug AS operateur_slug, o.couleur AS operateur_couleur,
        COALESCE(AVG(a.note), 0) AS note_moyenne,
        COUNT(a.id) AS nb_avis
    FROM publications p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    LEFT JOIN avis a ON a.operateur_id = p.operateur_id AND a.publie = 1
    WHERE $whereSql
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 9
");
$stmt->execute($params);
$publications = $stmt->fetchAll();

$stats = db()->query("
    SELECT
        (SELECT COUNT(*) FROM publications WHERE publie = 1) AS nb_pub,
        (SELECT COUNT(*) FROM avis WHERE publie = 1) AS nb_avis,
        (SELECT COALESCE(ROUND(AVG(note),1),0) FROM avis WHERE publie = 1) AS note_moy,
        (SELECT COUNT(*) FROM operateurs WHERE actif = 1) AS nb_op
")->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<section class="hero-strip">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-8">
                <div class="hero-strip-label mb-3">Plateforme officielle de regulation</div>
                <h1 class="hero-strip-title mb-3">Regulation des telecommunications a Madagascar</h1>
                <p class="hero-strip-text mb-0">Consultez les offres, partagez votre experience, contribuez a la qualite des services.</p>
            </div>
            <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                <a href="<?= e(BASE_URL) ?>visitor/statistiques.php" class="btn btn-hero-outline">
                    Statistiques nationales
                </a>
            </div>
        </div>
    </div>
</section>

<section class="container stats-strip">
    <div class="row g-0">
        <div class="col-6 col-md-3">
            <div class="stat-item">
                <div class="stat-value"><?= (int) $stats['nb_op'] ?></div>
                <div class="stat-label">Operateurs</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-item">
                <div class="stat-value"><?= (int) $stats['nb_pub'] ?></div>
                <div class="stat-label">Offres actives</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-item">
                <div class="stat-value"><?= (int) $stats['nb_avis'] ?></div>
                <div class="stat-label">Avis collectes</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-item">
                <div class="stat-value"><?= number_format((float) $stats['note_moy'], 1) ?><span class="stat-unit">/10</span></div>
                <div class="stat-label">Note moyenne</div>
            </div>
        </div>
    </div>
</section>

<section class="container section-block">
    <div class="section-head">
        <h2 class="section-title">Operateurs</h2>
        <span class="section-line"></span>
    </div>

    <div class="row g-4">
        <?php foreach ($operateurs as $op): ?>
            <div class="col-md-4">
                <a href="?op=<?= e($op['slug']) ?>#publications" class="op-card text-decoration-none" style="--op-color: <?= e($op['couleur']) ?>;">
                    <div class="op-card-head">
                        <span class="op-card-name"><?= e($op['nom']) ?></span>
                        <span class="op-card-score"><?= number_format((float) $op['note_moy'], 1) ?></span>
                    </div>
                    <div class="op-card-stars">
                        <?php
                        $stars = round((float) $op['note_moy'] / 2);
                        for ($i = 1; $i <= 5; $i++) {
                            echo $i <= $stars ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                        }
                        ?>
                    </div>
                    <div class="op-card-stats">
                        <div class="op-card-stat">
                            <span class="op-card-stat-val"><?= (int) $op['nb_pub'] ?></span>
                            <span class="op-card-stat-lbl">Offres</span>
                        </div>
                        <div class="op-card-stat">
                            <span class="op-card-stat-val"><?= (int) $op['nb_avis'] ?></span>
                            <span class="op-card-stat-lbl">Avis</span>
                        </div>
                    </div>
                    <div class="op-card-link">Voir les offres</div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="publications" class="container section-block">
    <div class="section-head section-head-between">
        <div>
            <h2 class="section-title">Publications</h2>
            <span class="section-line"></span>
        </div>
        <span class="section-count"><?= count($publications) ?></span>
    </div>

    <form method="get" action="#publications" class="filter-row">
        <select name="op" class="form-select form-select-sm filter-select">
            <option value="">Tous les operateurs</option>
            <?php foreach ($operateurs as $op): ?>
                <option value="<?= e($op['slug']) ?>" <?= $filterOp === $op['slug'] ? 'selected' : '' ?>>
                    <?= e($op['nom']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <select name="type" class="form-select form-select-sm filter-select">
            <option value="">Tous les types</option>
            <option value="offre"       <?= $filterType === 'offre' ? 'selected' : '' ?>>Offre</option>
            <option value="promotion"   <?= $filterType === 'promotion' ? 'selected' : '' ?>>Promotion</option>
            <option value="information" <?= $filterType === 'information' ? 'selected' : '' ?>>Information</option>
            <option value="nouveaute"   <?= $filterType === 'nouveaute' ? 'selected' : '' ?>>Nouveaute</option>
        </select>
        <button class="btn btn-sm btn-artec">Filtrer</button>
        <a href="<?= e(BASE_URL) ?>visitor/index.php#publications" class="btn btn-sm btn-outline-secondary">Reset</a>
    </form>

    <?php if (empty($publications)): ?>
        <div class="empty-state">
            Aucune publication ne correspond a vos criteres.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($publications as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="pub-card" style="--op-color: <?= e($p['operateur_couleur']) ?>;">
                        <div class="pub-card-bar"></div>
                        <div class="pub-card-body">
                            <div class="pub-card-head">
                                <span class="pub-card-op"><?= e($p['operateur_nom']) ?></span>
                                <span class="pub-card-type"><?= e(ucfirst($p['type'])) ?></span>
                            </div>
                            <h3 class="pub-card-title"><?= e($p['titre']) ?></h3>
                            <p class="pub-card-text"><?= e(mb_substr(strip_tags($p['contenu']), 0, 100)) ?>...</p>
                            <div class="pub-card-meta">
                                <span class="pub-card-service"><?= e(SERVICES[$p['service']] ?? $p['service']) ?></span>
                                <span class="pub-card-rating">
                                    <i class="bi bi-star-fill"></i>
                                    <?= number_format((float) $p['note_moyenne'], 1) ?>
                                    <span class="pub-card-avis">(<?= (int) $p['nb_avis'] ?>)</span>
                                </span>
                            </div>
                            <div class="pub-card-footer">
                                <div class="pub-card-price">
                                    <?php if ($p['prix'] > 0): ?>
                                        <?= number_format((float) $p['prix'], 0, ',', ' ') ?> Ar
                                        <span class="pub-card-price-unit">/ mois</span>
                                    <?php else: ?>
                                        Gratuit
                                    <?php endif; ?>
                                </div>
                                <a href="<?= e(BASE_URL) ?>visitor/publication.php?id=<?= (int) $p['id'] ?>" class="pub-card-btn">
                                    Consulter
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="container section-block">
    <div class="cta-block">
        <div class="row align-items-center g-3">
            <div class="col-md-9">
                <h2 class="cta-title">Votre avis compte</h2>
                <p class="cta-text">Chaque avis permet a l'ARTEC de prendre des decisions eclairees pour ameliorer la qualite des services.</p>
            </div>
            <div class="col-md-3 text-md-end">
                <a href="#publications" class="btn btn-accent">Donner mon avis</a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>