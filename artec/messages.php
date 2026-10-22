<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Messagerie réglementaire', 'Échangez directement avec chaque opérateur.', 'messages.php', 'artec');
$threads = db()->query("SELECT operator_code, MAX(created_at) AS last_message FROM messages WHERE conversation_type='regulatory' GROUP BY operator_code ORDER BY operator_code")->fetchAll();
$operator = in_array($_GET['operator'] ?? '', ['orange', 'yas', 'airtel'], true) ? $_GET['operator'] : ($threads[0]['operator_code'] ?? 'orange');
$key = 'reg:' . $operator;
?>
<div class="chat-conversations"><?php foreach (['orange' => 'Orange', 'yas' => 'Yas', 'airtel' => 'Airtel'] as $code => $name): ?><a href="<?= e(url('artec/messages.php?operator=' . $code)) ?>"><strong><?= e($name) ?></strong> · <?= in_array($code, array_column($threads, 'operator_code'), true) ? 'Conversation' : 'Échanges réglementaires' ?></a><?php endforeach; ?></div>
<section class="panel" data-chat data-chat-key="<?= e($key) ?>" data-chat-type="regulatory" data-operator-code="<?= e($operator) ?>" data-csrf="<?= e(csrf_token()) ?>"><div class="chat-box" aria-live="polite" aria-label="Conversation"></div><form class="chat-send"><input name="body" maxlength="2000" required placeholder="Écrire un message à l’opérateur…" aria-label="Votre message"><button class="button" type="submit">Envoyer</button></form></section><script defer src="<?= e(url('assets/js/chat.js')) ?>"></script>
<?php workspace_end(); ?>