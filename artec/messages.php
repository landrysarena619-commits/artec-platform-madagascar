<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('artec');

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $operateurId = (int) ($_POST['operateur_id'] ?? 0);
    $contenu = trim($_POST['contenu'] ?? '');

    if ($operateurId > 0 && $contenu !== '') {
        $convKey = 'artec_op_' . $operateurId;

        $stmt = db()->prepare("
            INSERT INTO messages
            (conversation_type, conversation_key, sender_type, sender_id, receiver_type,
             receiver_id, operateur_id, contenu, lu)
            VALUES ('operateur_artec', ?, 'artec', ?, 'operateur', ?, ?, ?, 0)
        ");
        $stmt->execute([$convKey, $user['id'], $operateurId, $operateurId, $contenu]);

        log_action('send_message_artec', 'messages', (int) db()->lastInsertId(), "Message a operateur $operateurId");
        flash('success', 'Message envoye.', 'success');
    }
    redirect(BASE_URL . 'artec/messages.php?op=' . $operateurId);
}

$selectedOp = (int) ($_GET['op'] ?? 0);

$operateurs = db()->query("
    SELECT o.id, o.nom, o.slug, o.couleur,
        (SELECT COUNT(*) FROM messages m
         WHERE m.operateur_id = o.id
           AND m.conversation_type = 'operateur_artec'
           AND m.sender_type = 'operateur'
           AND m.lu = 0) AS nb_non_lus
    FROM operateurs o
    WHERE o.actif = 1
    ORDER BY o.nom
")->fetchAll();

if ($selectedOp === 0 && !empty($operateurs)) {
    $selectedOp = (int) $operateurs[0]['id'];
}

$selectedOperateur = null;
$messages = [];

if ($selectedOp > 0) {
    foreach ($operateurs as $op) {
        if ((int) $op['id'] === $selectedOp) {
            $selectedOperateur = $op;
            break;
        }
    }

    if ($selectedOperateur) {
        $convKey = 'artec_op_' . $selectedOp;

        db()->prepare("
            UPDATE messages SET lu = 1
            WHERE conversation_key = ? AND sender_type = 'operateur' AND lu = 0
        ")->execute([$convKey]);

        $stmt = db()->prepare("
            SELECT m.*, u.nom AS sender_nom
            FROM messages m
            LEFT JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_key = ?
            ORDER BY m.created_at ASC
            LIMIT 200
        ");
        $stmt->execute([$convKey]);
        $messages = $stmt->fetchAll();
    }
}

$pageTitle = 'Messagerie ARTEC';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-chat-dots text-artec"></i> Messagerie operateurs</h1>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card card-artec">
            <div class="card-header"><i class="bi bi-broadcast"></i> Operateurs</div>
            <div class="list-group list-group-flush">
                <?php foreach ($operateurs as $op):
                    $isActive = (int) $op['id'] === $selectedOp;
                ?>
                    <a href="?op=<?= (int) $op['id'] ?>"
                       class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?= $isActive ? 'active' : '' ?>">
                        <div>
                            <span class="badge-operateur badge-<?= e($op['slug']) ?>"><?= e($op['nom']) ?></span>
                        </div>
                        <?php if ((int) $op['nb_non_lus'] > 0): ?>
                            <span class="badge bg-danger"><?= (int) $op['nb_non_lus'] ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <?php if ($selectedOperateur): ?>
            <div class="card card-artec">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <span class="badge-operateur badge-<?= e($selectedOperateur['slug']) ?>">
                            <?= e($selectedOperateur['nom']) ?>
                        </span>
                        <span class="ms-2 small">Canal reglementaire</span>
                    </span>
                    <span class="badge bg-success"><i class="bi bi-circle-fill"></i> En ligne</span>
                </div>
                <div class="card-body">
                    <div id="chat-box" class="chat-box mb-3">
                        <?php if (empty($messages)): ?>
                            <div class="text-center text-muted my-4">
                                Aucun message pour le moment. Commencez la conversation.
                            </div>
                        <?php else: ?>
                            <?php foreach ($messages as $m):
                                $isMine = $m['sender_type'] === 'artec';
                            ?>
                                <div class="chat-message <?= $isMine ? 'msg-me' : 'msg-other' ?>">
                                    <?= nl2br(e($m['contenu'])) ?>
                                    <span class="msg-time">
                                        <?php if (!$isMine && $m['sender_nom']): ?>
                                            <?= e($m['sender_nom']) ?> -
                                        <?php endif; ?>
                                        <?= date('d/m H:i', strtotime($m['created_at'])) ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <form method="post" class="d-flex gap-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="operateur_id" value="<?= (int) $selectedOperateur['id'] ?>">
                        <input type="text" name="contenu" class="form-control"
                               placeholder="Ecrire a l'operateur..." maxlength="2000" required autocomplete="off">
                        <button type="submit" class="btn btn-artec px-4">
                            <i class="bi bi-send"></i>
                        </button>
                    </form>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', () => {
                const box = document.getElementById('chat-box');
                if (box) box.scrollTop = box.scrollHeight;
            });
            </script>
        <?php else: ?>
            <div class="card card-artec">
                <div class="card-body text-center text-muted py-5">
                    <i class="bi bi-chat-dots fs-1"></i>
                    <p class="mt-3 mb-0">Selectionnez un operateur pour afficher la conversation.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>