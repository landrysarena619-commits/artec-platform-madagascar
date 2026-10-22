<?php
/**
 * Chat visiteur ↔ opérateur (lié à une publication).
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/functions.php';

$pubId = (int) ($_GET['pub'] ?? 0);
$opId  = (int) ($_GET['op'] ?? 0);

if ($pubId <= 0 || $opId <= 0) {
    flash('error', 'Conversation invalide.', 'danger');
    redirect(BASE_URL . 'visitor/index.php');
}

// Vérifier que la publication existe et appartient à l'opérateur
$stmt = db()->prepare("
    SELECT p.id, p.titre, o.nom AS operateur_nom, o.slug AS operateur_slug
    FROM publications p
    INNER JOIN operateurs o ON o.id = p.operateur_id
    WHERE p.id = ? AND p.operateur_id = ?
");
$stmt->execute([$pubId, $opId]);
$pub = $stmt->fetch();

if (!$pub) {
    flash('error', 'Publication introuvable.', 'danger');
    redirect(BASE_URL . 'visitor/index.php');
}

$pageTitle = 'Discussion avec ' . $pub['operateur_nom'];
require_once __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>visitor/index.php">Accueil</a></li>
        <li class="breadcrumb-item"><a href="<?= e(BASE_URL) ?>visitor/publication.php?id=<?= (int) $pub['id'] ?>">
            <?= e(mb_substr($pub['titre'], 0, 40)) ?>…</a></li>
        <li class="breadcrumb-item active">Discussion</li>
    </ol>
</nav>

<div class="card card-artec">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span>
            <span class="badge-operateur badge-<?= e($pub['operateur_slug']) ?>"><?= e($pub['operateur_nom']) ?></span>
            <span class="ms-2 small">— <?= e($pub['titre']) ?></span>
        </span>
        <span class="badge bg-success"><i class="bi bi-circle-fill"></i> En ligne</span>
    </div>
    <div class="card-body">
        <div id="chat-box" class="chat-box mb-3"></div>

        <form id="chat-form" class="d-flex gap-2">
            <input type="text" id="chat-input" class="form-control" placeholder="Écrivez votre message..." autocomplete="off" maxlength="2000">
            <button type="submit" class="btn btn-artec px-4">
                <i class="bi bi-send"></i>
            </button>
        </form>
        <small class="text-muted d-block mt-2">
            <i class="bi bi-info-circle"></i> Les messages sont rafraîchis automatiquement toutes les 3 secondes.
        </small>
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
        publication_id: <?= (int) $pub['id'] ?>,
        operateur_id: <?= (int) $opId ?>
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>