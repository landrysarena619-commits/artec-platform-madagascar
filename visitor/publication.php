<?php
require_once __DIR__ . '/../includes/functions.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$statement = db()->prepare("SELECT p.*, c.name AS category_name, COUNT(r.id) AS review_count, ROUND(AVG(r.rating), 1) AS average_rating FROM publications p LEFT JOIN categories c ON c.id = p.category_id LEFT JOIN reviews r ON r.publication_id = p.id AND r.status = 'published' WHERE p.id = ? AND p.status = 'published' GROUP BY p.id LIMIT 1");
$statement->execute([$id]);
$publication = $statement->fetch();
if (!$publication) { http_response_code(404); exit('Publication introuvable.'); }
$pageTitle = $publication['title'];
$isPublic = true;
$reviewsQuery = db()->prepare("SELECT rating, comment, region, service, visitor_name, operator_reply, created_at FROM reviews WHERE publication_id = ? AND status = 'published' ORDER BY created_at DESC LIMIT 20");
$reviewsQuery->execute([$id]);
$reviews = $reviewsQuery->fetchAll();
$chatHref = url('visitor/chat.php?publication_id=' . (int) $publication['id']);
require __DIR__ . '/../includes/header.php';
?>
<header class="detail-head">
  <div class="eyebrow"><?= e(ucfirst($publication['operator_code'])) ?> Madagascar <span>·</span> <?= e($publication['category_name'] ?: 'Actualité') ?></div>
  <h1><?= e($publication['title']) ?></h1>
  <p><?= e($publication['summary']) ?></p>
</header>
<div class="detail-layout">
  <div>
    <article class="content-panel"><p><?= nl2br(e($publication['body'])) ?></p><p><strong>Région :</strong> <?= e($publication['region']) ?><?php if ($publication['ends_at']): ?> · <strong>Fin de l’offre :</strong> <?= e(date('d/m/Y', strtotime($publication['ends_at']))) ?><?php endif; ?></p></article>
    <section class="review-form" id="avis"><div class="section-heading"><div><div class="eyebrow">Votre parole compte</div><h2>Partager un avis</h2></div></div>
      <form class="panel" action="<?= e(url('api/submit_avis.php')) ?>" method="post" data-ajax-form>
        <?= csrf_field() ?><input type="hidden" name="publication_id" value="<?= (int) $publication['id'] ?>">
        <div class="honeypot" aria-hidden="true"><label>Ne pas remplir<input name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-grid">
          <div class="field"><label for="rating">Votre note sur 10</label><input id="rating" name="rating" type="number" min="0" max="10" required></div>
          <div class="field"><label for="region">Région</label><select id="region" name="region" required><option value="">Choisir une région</option><?php foreach (['Analamanga','Atsinanana','Boeny','Diana','Haute Matsiatra','Itasy','Menabe','Sava','Vakinankaratra','Autre'] as $region): ?><option><?= e($region) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="service">Service concerné</label><select id="service" name="service" required><option value="">Choisir un service</option><?php foreach (['Internet mobile','Internet fixe','Voix & SMS','Service client','Paiement mobile','Couverture réseau','Autre'] as $service): ?><option><?= e($service) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label for="visitor_name">Nom (facultatif)</label><input id="visitor_name" name="visitor_name" maxlength="100" autocomplete="name"></div>
          <div class="field full"><label for="comment">Commentaire</label><textarea id="comment" name="comment" minlength="8" maxlength="2000" required></textarea></div>
        </div>
        <p style="font-size:12px;color:#71817c">Aucun compte n’est nécessaire. Votre adresse réseau est protégée par hachage pour limiter les abus.</p>
        <button class="button" type="submit">Publier mon avis</button><div class="form-result" role="status" aria-live="polite"></div>
      </form>
    </section>
    <section style="margin-top:38px"><div class="section-heading"><div><div class="eyebrow">Retours vérifiés</div><h2>Les derniers avis</h2></div></div>
      <?php if (!$reviews): ?><div class="empty-state">Soyez le premier à partager votre expérience.</div><?php else: ?><div class="review-list"><?php foreach ($reviews as $review): ?><article class="review-item"><div class="review-top"><span><?= e($review['visitor_name'] ?: 'Visiteur') ?> · <?= e($review['region']) ?> · <?= e(date('d/m/Y', strtotime($review['created_at']))) ?></span><span class="rating-pill"><?= (int) $review['rating'] ?>/10</span></div><p><?= nl2br(e($review['comment'])) ?></p><?php if ($review['operator_reply']): ?><p><strong>Réponse opérateur :</strong> <?= nl2br(e($review['operator_reply'])) ?></p><?php endif; ?></article><?php endforeach; ?></div><?php endif; ?>
    </section>
  </div>
  <aside class="side-panel"><div class="operator-label operator-<?= e($publication['operator_code']) ?>"><span class="operator-dot"></span><?= e(ucfirst($publication['operator_code'])) ?> Madagascar</div><div class="rating-big"><?= $publication['average_rating'] !== null ? e(number_format((float) $publication['average_rating'], 1)) : '—' ?><small>/10</small></div><p><?= (int) $publication['review_count'] ?> avis publiés</p><a class="button" style="width:100%" href="<?= e($chatHref) ?>">Discuter avec l’opérateur</a><p style="margin:16px 0 0;font-size:12px;color:#71817c">Conversation privée et sans intelligence artificielle.</p></aside>
</div>
<section class="review-form" id="reclamation"><div class="section-heading"><div><div class="eyebrow">Besoin d’aide ?</div><h2>Signaler un problème</h2></div></div>
  <form class="panel" action="<?= e(url('api/submit_complaint.php')) ?>" method="post" data-ajax-form>
    <?= csrf_field() ?><input type="hidden" name="publication_id" value="<?= (int) $publication['id'] ?>">
    <div class="form-grid"><div class="field"><label for="complaint_subject">Sujet</label><input id="complaint_subject" name="subject" maxlength="180" required></div><div class="field"><label for="complaint_region">Région</label><input id="complaint_region" name="region" required maxlength="100" value="<?= e($publication['region']) ?>"></div><div class="field full"><label for="complaint_description">Description</label><textarea id="complaint_description" name="description" minlength="15" maxlength="2000" required></textarea></div></div>
    <button class="button secondary" type="submit">Transmettre à l’opérateur</button><div class="form-result" role="status" aria-live="polite"></div>
  </form>
</section>
<script defer src="<?= e(url('assets/js/chat.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>