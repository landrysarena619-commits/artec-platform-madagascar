<?php
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? '')) {
    $contenu = trim($_POST['contenu'] ?? '');
    $visitorToken = trim($_POST['visitor_token'] ?? '');
    $publicationId = (int) ($_POST['publication_id'] ?? 0);
    $type = $_POST['type'] ?? 'visiteur';

    if ($contenu !== '') {
        if ($type === 'artec') {
            $convKey = 'artec_op_' . $opId;
            $stmt = db()->prepare("
                INSERT INTO messages
                (conversation_type, conversation_key, sender_type, sender_id, receiver_type,
                 operateur_id, contenu, lu)
                VALUES ('operateur_artec', ?, 'operateur', ?, 'artec', ?, ?, 0)
            ");
            $stmt->execute([$convKey, $user['id'], $opId, $contenu]);
            flash('success', 'Message envoye a l\'ARTEC.', 'success');
            redirect(BASE_URL . 'operator/messages.php?tab=artec');
        } else {
            if ($visitorToken !== '' && $publicationId > 0) {
                $convKey = 'pub_' . $publicationId . '_' . substr(hash('sha256', $visitorToken), 0, 24);
                $stmt = db()->prepare("
                    INSERT INTO messages
                    (conversation_type, conversation_key, sender_type, sender_id, receiver_type,
                     operateur_id, publication_id, contenu, lu)
                    VALUES ('visiteur_operateur', ?, 'operateur', ?, 'visiteur', ?, ?, ?, 0)
                ");
                $stmt->execute([$convKey, $user['id'], $opId, $publicationId, $contenu]);
                flash('success', 'Message envoye.', 'success');
                redirect(BASE_URL . 'operator/messages.php?visitor=' . urlencode($visitorToken) . '&pub=' . $publicationId);
            }
        }
    }
    redirect(BASE_URL . 'operator/messages.php');
}

$tab = $_GET['tab'] ?? 'visiteurs';
$selectedVisitor = trim($_GET['visitor'] ?? '');
$selectedPub = (int) ($_GET['pub'] ?? 0);

$conversations = db()->prepare("
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
$conversations->execute([$opId]);
$conversations = $conversations->fetchAll();

if ($selectedVisitor === '' && !empty($conversations)) {
    $selectedVisitor = $conversations[0]['visitor_token'];
    $selectedPub = (int) $conversations[0]['publication_id'];
}

$artecConvKey = 'artec_op_' . $opId;
$artecMessages = db()->prepare("
    SELECT m.*, u.nom AS sender_nom
    FROM messages m
    LEFT JOIN users u ON u.id = m.sender_id
    WHERE m.conversation_key = ?
    ORDER BY m.created_at ASC
    LIMIT 200
");
$artecMessages->execute([$artecConvKey]);
$artecMessages = $artecMessages->fetchAll();

db()->prepare("
    UPDATE messages SET lu = 1
    WHERE conversation_key = ? AND sender_type = 'artec' AND lu = 0
")->execute([$artecConvKey]);

$pageTitle = 'Messagerie';
require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="fw-bold mb-4"><i class="bi bi-chat-dots text-artec"></i> Messagerie</h1>

<ul class="nav nav-tabs mb-4">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'visiteurs' ? 'active' : '' ?>" href="?tab=visiteurs">
            <i class="bi bi-people"></i> Visiteurs
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'artec' ? 'active' : '' ?>" href="?tab=artec">
            <i class="bi bi-shield-check"></i> ARTEC
        </a>
    </li>
</ul>

<?php if ($tab === 'artec'): ?>

    <div class="card card-artec">
        <div class="card-header"><i class="bi bi-shield-check"></i> Canal reglementaire avec l'ARTEC</div>
        <div class="card-body">
            <div id="chat-artec-box" class="chat-box mb-3">
                <?php if (empty($artecMessages)): ?>
                    <div class="text-center text-muted my-4">
                        Aucun message avec l'ARTEC pour le moment.
                    </div>
                <?php else: ?>
                    <?php foreach ($artecMessages as $m):
                        $isMine = $m['sender_type'] === 'operateur';
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
                <input type="hidden" name="type" value="artec">
                <input type="text" name="contenu" class="form-control"
                       placeholder="Ecrire a l'ARTEC..." maxlength="2000" required autocomplete="off">
                <button type="submit" class="btn btn-artec px-4">
                    <i class="bi bi-send"></i>
                </button>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const box = document.getElementById('chat-artec-box');
        if (box) box.scrollTop = box.scrollHeight;
    });
    </script>

<?php else: ?>

    <div class="row g-3">
        <div class="col-lg-4">
            <div class="card card-artec">
                <div class="card-header"><i class="bi bi-inbox"></i> Conversations (<?= count($conversations) ?>)</div>
                <div class="list-group list-group-flush" style="max-height: 600px; overflow-y: auto;">
                    <?php if (empty($conversations)): ?>
                        <p class="text-muted p-3 mb-0">Aucune conversation pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($conversations as $c):
                            $isActive = ($c['visitor_token'] === $selectedVisitor && (int) $c['publication_id'] === $selectedPub);
                        ?>
                            <a href="?tab=visiteurs&visitor=<?= urlencode($c['visitor_token']) ?>&pub=<?= (int) $c['publication_id'] ?>"
                               class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>">
                                <div class="d-flex justify-content-between">
                                    <strong class="small">
                                        <i class="bi bi-person-circle"></i>
                                        Visiteur <?= e(substr($c['visitor_token'], 0, 8)) ?>...
                                    </strong>
                                    <?php if ($c['nb_non_lus'] > 0): ?>
                                        <span class="badge bg-danger"><?= (int) $c['nb_non_lus'] ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="small <?= $isActive ? 'text-white-50' : 'text-muted' ?>">
                                    <i class="bi bi-megaphone"></i> <?= e(mb_substr($c['publication_titre'] ?? '(supprimee)', 0, 30)) ?>
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

        <div class="col-lg-8">
            <?php if ($selectedVisitor !== '' && $selectedPub > 0): ?>
                <?php
                $tStmt = db()->prepare("SELECT titre FROM publications WHERE id = ?");
                $tStmt->execute([$selectedPub]);
                $pubTitre = $tStmt->fetchColumn() ?: '(supprimee)';

                $convKey = 'pub_' . $selectedPub . '_' . substr(hash('sha256', $selectedVisitor), 0, 24);
                $convMessages = db()->prepare("
                    SELECT * FROM messages
                    WHERE conversation_key = ?
                    ORDER BY created_at ASC
                    LIMIT 200
                ");
                $convMessages->execute([$convKey]);
                $convMessages = $convMessages->fetchAll();

                db()->prepare("
                    UPDATE messages SET lu = 1
                    WHERE conversation_key = ? AND sender_type = 'visiteur' AND lu = 0
                ")->execute([$convKey]);
                ?>
                <div class="card card-artec">
                    <div class="card-header">
                        <i class="bi bi-person-circle"></i>
                        Visiteur <?= e(substr($selectedVisitor, 0, 12)) ?>...
                        <span class="ms-2 small opacity-75">- <?= e($pubTitre) ?></span>
                    </div>
                    <div class="card-body">
                        <div id="chat-visitor-box" class="chat-box mb-3">
                            <?php foreach ($convMessages as $m):
                                $isMine = $m['sender_type'] === 'operateur';
                            ?>
                                <div class="chat-message <?= $isMine ? 'msg-me' : 'msg-other' ?>">
                                    <?= nl2br(e($m['contenu'])) ?>
                                    <span class="msg-time"><?= date('d/m H:i', strtotime($m['created_at'])) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <form method="post" class="d-flex gap-2">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type" value="visiteur">
                            <input type="hidden" name="visitor_token" value="<?= e($selectedVisitor) ?>">
                            <input type="hidden" name="publication_id" value="<?= (int) $selectedPub ?>">
                            <input type="text" name="contenu" class="form-control"
                                   placeholder="Repondre au visiteur..." maxlength="2000" required autocomplete="off">
                            <button type="submit" class="btn btn-artec px-4">
                                <i class="bi bi-send"></i>
                            </button>
                        </form>
                    </div>
                </div>

                <script>
                document.addEventListener('DOMContentLoaded', () => {
                    const box = document.getElementById('chat-visitor-box');
                    if (box) box.scrollTop = box.scrollHeight;
                });
                </script>
            <?php else: ?>
                <div class="card card-artec">
                    <div class="card-body text-center text-muted py-5">
                        <i class="bi bi-chat-dots fs-1"></i>
                        <p class="mt-3 mb-0">Selectionnez une conversation pour afficher les messages.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>