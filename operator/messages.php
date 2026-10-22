<?php
/**
 * Messagerie opérateur : liste des conversations + chat.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

// Conversation sélectionnée (visiteur_token + publication_id)
$selectedVisitor = trim($_GET['visitor'] ?? '');
$selectedPub     = (int) ($_GET['pub'] ?? 0);

// --- Liste des conversations (regroupées par visitor_token + publication_id) ---
$stmt = db()->prepare("
    SELECT
        m.conversation_key,
        m.publication_id,
        p.titre AS publication_titre,
        MAX(m.created_at) AS last_date,
        SUM(CASE WHEN m.sender_type = 'visiteur' AND m.lu = 0 THEN 1 ELSE 0 END) AS nb_non_lus,
        (SELECT contenu FROM messages m2 WHERE m2.conversation_key = m.conversation_key ORDER BY m2.id DESC LIMIT 1) AS last_message,
        (SELECT sender_token FROM messages m3 WHERE m3.conversation_key = m.conversation_key AND m3.sender_type = 'visiteur' LIMIT 1) AS visitor_token
    FROM messages m
    LEFT JOIN publications p ON p.id = m.publication_id
    WHERE m.operateur_id = ?
      AND m.conversation_type = 'visiteur_operateur'
    GROUP BY m.conversation_key, m.publication_id, p.titre
    ORDER BY last_date DESC
");
$stmt->execute([$opId]);
$conversations = $stmt->fetchAll();

// Si aucune conversation sélectionnée mais il y en a, prendre la première
if ($selectedVisitor === '' && !empty($conversations)) {
    $selectedVisitor = $conversations[0]['visitor_token'];
    $selectedPub     = (int) $conversations[0]['publication_id'];
}

$pageTitle = 'Messagerie';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-chat-dots text-artec"></i> Messagerie</h1>

<div class="row g-3">
    <!-- ===== LISTE CONVERSATIONS ===== -->
    <div class="col-lg-4">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-inbox"></i> Conversations (<?= count($conversations) ?>)</div>
            <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                <?php if (empty($conversations)): ?>
                    <p class="text-muted p-3 mb-0">Aucune conversation pour le moment.</p>
                <?php else: ?>
                    <?php foreach ($conversations as $c): ?>
                        <?php
                        $isActive = ($c['visitor_token'] === $selectedVisitor && (int) $c['publication_id'] === $selectedPub);
                        ?>
                        <a href="?visitor=<?= urlencode($c['visitor_token']) ?>&pub=<?= (int) $c['publication_id'] ?>"
                           class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>">
                            <div class="d-flex justify-content-between">
                                <strong class="small">
                                    <i class="bi bi-person-circle"></i>
                                    Visiteur <?= e(substr($c['visitor_token'], 0, 8)) ?>…
                                </strong>
                                <?php if ($c['nb_non_lus'] > 0): ?>
                                    <span class="badge bg-danger"><?= (int) $c['nb_non_lus'] ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="small <?= $isActive ? 'text-white-50' : 'text-muted' ?>">
                                <i class="bi bi-megaphone"></i> <?= e(mb_substr($c['publication_titre'] ?? '(supprimée)', 0, 30)) ?>
                            </div>
                            <div class="small <?= $isActive ? 'text-white-50' : 'text-muted' ?> text-truncate">
                                <?= e(mb_substr($c['last_message'] ?? '', 0, 50)) ?>
                            </div>
                            <small class="<?= $isActive ? 'text-white-50' : 'text-muted' ?>">
                                <?= date('d/m H:i', strtotime($c['last_date'])) ?>
                            </small>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== CHAT ===== -->
    <div class="col-lg-8">
        <?php if ($selectedVisitor !== '' && $selectedPub > 0): ?>
            <?php
            // Titre de la publication sélectionnée
            $tStmt = db()->prepare("SELECT titre FROM publications WHERE id = ?");
            $tStmt->execute([$selectedPub]);
            $pubTitre = $tStmt->fetchColumn() ?: '(supprimée)';
            ?>
            <div class="card card-artec">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <i class="bi bi-person-circle"></i>
                        Visiteur <?= e(substr($selectedVisitor, 0, 12)) ?>…
                        <span class="ms-2 small opacity-75">— <?= e($pubTitre) ?></span>
                    </span>
                </div>
                <div class="card-body">
                    <div id="chat-box" class="chat-box mb-3"></div>
                    <form id="chat-form" class="d-flex gap-2">
                        <input type="text" id="chat-input" class="form-control"
                               placeholder="Répondre au visiteur..." autocomplete="off" maxlength="2000">
                        <button type="submit" class="btn btn-artec px-4">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>

            <script src="<?= e(ASSETS_URL) ?>js/chat.js"></script>
            <script>
            new ArtectChat({
                apiSend: '<?= e(BASE_URL) ?>api/send_message.php',
                apiGet:  '<?= e(BASE_URL) ?>api/get_messages.php',
                container: 'chat-box',
                form: 'chat-form',
                input: 'chat-input',
                csrfToken: '<?= e(csrf_token()) ?>',
                params: {
                    publication_id: <?= (int) $selectedPub ?>,
                    operateur_id: <?= (int) $opId ?>,
                    visitor_token: '<?= e($selectedVisitor) ?>'
                }
            });
            </script>
        <?php else: ?>
            <div class="card card-artec">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mt-3 mb-0">Sélectionnez une conversation pour afficher les messages.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>