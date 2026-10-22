<?php
/**
 * Page d'accueil visiteur : liste des publications des 3 opérateurs.
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pageTitle = 'Accueil';

// --- Récupération des publications avec infos opérateur + notes ---
$stmt = db()->query("
    SELECT
        p.*,
        o.nom AS operateur_nom,
        o.slug AS operateur_slug,
        o.couleur AS operateur_couleur,
        o.logo AS operateur_logo,
        COALESCE(AVG(a.note), 0) AS note_moyenne,
        COUNT(a.id) AS nb_avis
    FROM publications p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    LEFT JOIN avis a ON a.operateur_id = p.operateur_id AND a.publie = 1
    WHERE p.publie = 1
      AND (p.date_fin IS NULL OR p.date_fin >= CURDATE())
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 12
");
$publications = $stmt->fetchAll();

// --- Statistiques globales pour le hero ---
$stats = db()->query("
    SELECT
        (SELECT COUNT(*) FROM publications WHERE publie = 1) AS nb_pub,
        (SELECT COUNT(*) FROM avis WHERE publie = 1) AS nb_avis,
        (SELECT ROUND(AVG(note), 1) FROM avis WHERE publie = 1) AS note_moy,
        (SELECT COUNT(*) FROM operateurs WHERE actif = 1) AS nb_op
")->fetch();

require_once __DIR__ . '/../includes/header.php';
?>

<!-- ================= HERO ================= -->
<section class="hero-artec">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <span class="badge bg-warning text-dark mb-3 px-3 py-2">
                    <i class="bi bi-shield-check"></i> Plateforme officielle ARTEC
                </span>
                <h1 class="mb-3">Régulation des télécommunications à Madagascar</h1>
                <p class="lead mb-4">
                    Comparez les offres d'Orange, Yas et Airtel, partagez votre expérience,
                    et contribuez à améliorer la qualité des services télécoms dans toute l'île.
                </p>
                <div class="d-flex flex-wrap gap-2">
                    <a href="#publications" class="btn btn-warning btn-lg fw-bold">
                        <i class="bi bi-megaphone"></i> Voir les offres
                    </a>
                    <a href="<?= e(BASE_URL) ?>visitor/statistiques.php" class="btn btn-outline-light btn-lg">
                        <i class="bi bi-bar-chart"></i> Statistiques
                    </a>
                </div>
            </div>
            <div class="col-lg-4 d-none d-lg-block text-center">
                <i class="bi bi-broadcast" style="font-size: 12rem; opacity: .2;"></i>
            </div>
        </div>
    </div>
</section>

<!-- ================= KPI RAPIDES ================= -->
<section class="container mb-4" style="margin-top: -3rem; position: relative; z-index: 2;">
    <div class="row g-3">
        <div class="col-md-3">
            <div class="kpi-card text-center">
                <div class="kpi-value"><?= (int) $stats['nb_op'] ?></div>
                <div class="kpi-label">Opérateurs suivis</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card text-center kpi-orange">
                <div class="kpi-value"><?= (int) $stats['nb_pub'] ?></div>
                <div class="kpi-label">Offres actives</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card text-center kpi-yas">
                <div class="kpi-value"><?= (int) $stats['nb_avis'] ?></div>
                <div class="kpi-label">Avis citoyens</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="kpi-card text-center kpi-airtel">
                <div class="kpi-value"><?= number_format((float) $stats['note_moy'], 1) ?><span class="small">/10</span></div>
                <div class="kpi-label">Note moyenne</div>
            </div>
        </div>
    </div>
</section>

<!-- ================= PUBLICATIONS ================= -->
<section id="publications" class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-grid-3x3-gap text-artec"></i> Dernières publications</h2>
        <span class="text-muted small"><?= count($publications) ?> offre(s) en cours</span>
    </div>

    <?php if (empty($publications)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Aucune publication pour le moment.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($publications as $p): ?>
                <div class="col-md-6 col-lg-4">
                    <article class="card card-artec h-100">
                        <div class="p-3 pb-0 d-flex justify-content-between align-items-start">
                            <span class="badge-operateur badge-<?= e($p['operateur_slug']) ?>">
                                <?= e($p['operateur_nom']) ?>
                            </span>
                            <span class="badge bg-light text-dark">
                                <?= e(SERVICES[$p['service']] ?? $p['service']) ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <h5 class="card-title fw-bold"><?= e($p['titre']) ?></h5>
                            <p class="card-text text-muted small">
                                <?= e(mb_substr(strip_tags($p['contenu']), 0, 130)) ?>…
                            </p>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="rating-stars">
                                    <?php
                                    $n = round((float) $p['note_moyenne']);
                                    for ($i = 1; $i <= 5; $i++) {
                                        echo $i <= $n / 2 ? '<i class="bi bi-star-fill"></i>' : '<i class="bi bi-star"></i>';
                                    }
                                    ?>
                                </span>
                                <span class="small text-muted">
                                    <?= number_format((float) $p['note_moyenne'], 1) ?>/10
                                    (<?= (int) $p['nb_avis'] ?> avis)
                                </span>
                            </div>
                            <?php if ($p['prix'] > 0): ?>
                                <div class="fw-bold text-artec fs-5">
                                    <?= number_format((float) $p['prix'], 0, ',', ' ') ?> Ar
                                    <span class="small text-muted">/mois</span>
                                </div>
                            <?php else: ?>
                                <div class="fw-bold text-success fs-6">Gratuit / Offert</div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 pb-3">
                            <a href="<?= e(BASE_URL) ?>visitor/publication.php?id=<?= (int) $p['id'] ?>"
                               class="btn btn-artec w-100">
                                Voir le détail <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>