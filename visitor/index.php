<?php
require_once __DIR__ . '/../includes/functions.php';
$pageTitle = 'Observatoire des offres';
$isPublic = true;
$query = db()->query("SELECT p.*, COUNT(r.id) AS review_count, ROUND(AVG(r.rating), 1) AS average_rating FROM publications p LEFT JOIN reviews r ON r.publication_id = p.id AND r.status = 'published' WHERE p.status = 'published' AND (p.ends_at IS NULL OR p.ends_at >= CURDATE()) GROUP BY p.id ORDER BY p.created_at DESC");
$publications = $query->fetchAll();
require __DIR__ . '/../includes/header.php';
?>
<section class="hero">
  <div class="eyebrow">Le numérique, en toute transparence</div>
  <h1>Votre expérience réseau mérite d’être entendue.</h1>
  <p>Consultez les offres des opérateurs, partagez votre expérience et suivez la qualité des services télécoms à Madagascar.</p>
  <div class="hero-meta"><strong><?= count($publications) ?></strong> offres en cours <span aria-hidden="true">·</span> 3 opérateurs</div>
</section>
<section aria-labelledby="offers-title">
  <div class="section-heading"><div><div class="eyebrow">À la une</div><h2 id="offers-title">Offres & actualités</h2></div><p>Les dernières publications des opérateurs</p></div>
  <div class="filters" aria-label="Filtrer par opérateur">
    <button class="filter-chip active" data-filter="all" type="button">Tous</button>
    <button class="filter-chip" data-filter="orange" type="button">Orange</button>
    <button class="filter-chip" data-filter="yas" type="button">Yas</button>
    <button class="filter-chip" data-filter="airtel" type="button">Airtel</button>
  </div>
  <?php if (!$publications): ?><div class="empty-state">Aucune offre n’est publiée pour le moment.</div>
  <?php else: ?><div class="publication-grid">
    <?php foreach ($publications as $item): ?><article class="publication-card operator-<?= e($item['operator_code']) ?>" data-operator="<?= e($item['operator_code']) ?>">
      <div class="card-band"></div><div class="card-content">
        <div class="operator-label"><span class="operator-dot"></span><?= e(ucfirst($item['operator_code'])) ?> Madagascar</div>
        <h3><?= e($item['title']) ?></h3><p><?= e($item['summary']) ?></p>
        <div class="card-foot"><span><?= e($item['region']) ?> · <?= (int) $item['review_count'] ?> avis</span><a href="<?= e(url('visitor/publication.php?id=' . (int) $item['id'])) ?>">Découvrir <span aria-hidden="true">→</span></a></div>
      </div>
    </article><?php endforeach; ?>
  </div><?php endif; ?>
</section>
<section style="padding-top:52px">
  <div class="section-heading"><div><div class="eyebrow">Les indicateurs</div><h2>Un observatoire ouvert à tous</h2></div><a class="button secondary" href="<?= e(url('visitor/statistiques.php')) ?>">Voir les statistiques →</a></div>
  <div class="notice">Les avis sont recueillis sans création de compte. Chaque contribution est protégée par des contrôles anti-spam et peut être signalée pour modération.</div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>