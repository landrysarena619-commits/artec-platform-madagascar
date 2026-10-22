<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Messagerie', 'Conversations avec les visiteurs et l’ARTEC.', 'messages.php', 'operator');
$code = current_user()['operator_code'];
$threadsQuery = db()->prepare("SELECT conversation_key, conversation_type, publication_id, MAX(created_at) AS last_message FROM messages WHERE operator_code=? GROUP BY conversation_key, conversation_type, publication_id ORDER BY last_message DESC LIMIT 60");
$threadsQuery->execute([$code]); $threads = $threadsQuery->fetchAll();
$selectedType = ($_GET['type'] ?? '') === 'regulatory' ? 'regulatory' : 'publication';
$selectedKey = (string) ($_GET['conversation'] ?? ($selectedType === 'regulatory' ? 'reg:' . $code : ''));
$selectedPub = 0;
foreach ($threads as $thread) if ($thread['conversation_key'] === $selectedKey) $selectedPub = (int) $thread['publication_id'];
?>
<div class="chat-conversations"><a href="<?= e(url('operator/messages.php?type=regulatory&conversation=reg%3A' . $code)) ?>"><strong>Réglementaire</strong> · Conversation avec l’ARTEC</a><?php foreach ($threads as $thread): ?><a href="<?= e(url('operator/messages.php?type=publication&conversation=' . rawurlencode($thread['conversation_key']))) ?>">Visiteur · Publication #<?= (int) $thread['publication_id'] ?> <span style="float:right"><?= e(date('d/m H:i', strtotime($thread['last_message']))) ?></span></a><?php endforeach; ?></div>
<section class="panel" data-chat data-chat-key="<?= e($selectedKey) ?>" data-chat-type="<?= e($selectedType) ?>" data-publication-id="<?= $selectedPub ?>" data-operator-code="<?= e($code) ?>" data-csrf="<?= e(csrf_token()) ?>"><div class="chat-box" aria-live="polite" aria-label="Conversation"></div><form class="chat-send"><input name="body" maxlength="2000" required placeholder="Écrire un message…" aria-label="Votre message"><button class="button" type="submit">Envoyer</button></form></section>
<script defer src="<?= e(url('assets/js/chat.js')) ?>"></script>
<?php workspace_end(); ?>