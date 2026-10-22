<?php
require_once __DIR__ . '/../includes/functions.php';
$publicationId = filter_input(INPUT_GET, 'publication_id', FILTER_VALIDATE_INT) ?: 0;
$statement = db()->prepare("SELECT id, title, operator_code FROM publications WHERE id = ? AND status = 'published' LIMIT 1");
$statement->execute([$publicationId]);
$publication = $statement->fetch();
if (!$publication) { http_response_code(404); exit('Publication introuvable.'); }
if (empty($_SESSION['visitor_chat_token'])) $_SESSION['visitor_chat_token'] = bin2hex(random_bytes(24));
$conversationKey = 'pub:' . $publicationId . ':' . hash('sha256', $_SESSION['visitor_chat_token']);
$pageTitle = 'Discussion avec ' . ucfirst($publication['operator_code']);
$isPublic = true;
require __DIR__ . '/../includes/header.php';
?>
<div class="dashboard-top"><div><div class="eyebrow">Messagerie directe</div><h1>Discuter avec <?= e(ucfirst($publication['operator_code'])) ?></h1><p>À propos de : <?= e($publication['title']) ?></p></div><a class="button secondary" href="<?= e(url('visitor/publication.php?id=' . $publicationId)) ?>">← Retour à l’offre</a></div>
<section class="panel" data-chat data-chat-key="<?= e($conversationKey) ?>" data-chat-type="publication" data-publication-id="<?= (int) $publicationId ?>" data-csrf="<?= e(csrf_token()) ?>">
  <div class="chat-box" aria-live="polite" aria-label="Conversation"></div>
  <form class="chat-send"><input name="sender_name" maxlength="100" placeholder="Votre nom (facultatif)" aria-label="Votre nom"><input name="body" maxlength="2000" required placeholder="Écrire un message…" aria-label="Votre message"><button class="button" type="submit">Envoyer</button></form>
</section>
<script defer src="<?= e(url('assets/js/chat.js')) ?>"></script>
<?php require __DIR__ . '/../includes/footer.php'; ?>