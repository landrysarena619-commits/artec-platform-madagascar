<?php
/**
 * Liste de toutes les publications de l'opérateur connecté.
 */
require_once __DIR__ . '/../includes/auth.php';
require_role('operateur');

$user = current_user();
$opId = $user['operateur_id'];

$stmt = db()->prepare("
    SELECT p.*, (SELECT COUNT(*) FROM avis WHERE publication_id = p.id) AS nb_avis,
           (SELECT COALESCE(ROUND(AVG(note),1),0) FROM avis WHERE publication_id = p.id) AS note_moy
    FROM publications p
    WHERE p.operateur_id = ?
    ORDER BY p.created_at DESC
");
$stmt->execute([$opId]);
$pubs = $stmt->fetchAll();

$pageTitle = 'Mes publications';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="fw-bold mb-0"><i class="bi bi-megaphone text-artec"></i> Mes publications</h1>
    <a href="<?= e(BASE_URL) ?>operator/publication_edit.php" class="btn btn-artec">
        <i class="bi bi-plus-circle"></i> Nouvelle
    </a>
</div>

<?php if (empty($pubs)): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> Vous n'avez encore créé aucune publication.
        <a href="<?= e(BASE_URL) ?>operator/publication_edit.php" class="alert-link">Créer la première</a>.
    </div>
<?php else: ?>
    <div class="card card-artec">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-artec mb-0">
                    <thead>
                        <tr>
                            <th>Titre</th>
                            <th>Type</th>
                            <th>Prix</th>
                            <th>Vues</th>
                            <th>Avis</th>
                            <th>Note</th>
                            <th>Statut</th>
                            <th>Créée le</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pubs as $p): ?>
                            <tr>
                                <td class="fw-bold"><?= e($p['titre']) ?></td>
                                <td><span class="badge bg-secondary"><?= e(ucfirst($p['type'])) ?></span></td>
                                <td>
                                    <?php if ($p['prix'] > 0): ?>
                                        <?= number_format((float) $p['prix'], 0, ',', ' ') ?> Ar
                                    <?php else: ?>
                                        <span class="text-success">Gratuit</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= (int) $p['vues'] ?></td>
                                <td><?= (int) $p['nb_avis'] ?></td>
                                <td>
                                    <span class="fw-bold text-artec"><?= number_format((float) $p['note_moy'], 1) ?></span>/10
                                </td>
                                <td>
                                    <?php if ($p['publie']): ?>
                                        <span class="badge bg-success">Publié</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Brouillon</span>
                                    <?php endif; ?>
                                </td>
                                <td><small><?= date('d/m/Y', strtotime($p['created_at'])) ?></small></td>
                                <td class="text-end">
                                    <a href="<?= e(BASE_URL) ?>visitor/publication.php?id=<?= (int) $p['id'] ?>"
                                       target="_blank" class="btn btn-sm btn-outline-secondary" title="Voir">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= e(BASE_URL) ?>operator/publication_edit.php?id=<?= (int) $p['id'] ?>"
                                       class="btn btn-sm btn-outline-primary" title="Modifier">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <form method="post" action="<?= e(BASE_URL) ?>operator/publication_delete.php"
                                          class="d-inline" onsubmit="return confirm('Supprimer cette publication ?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                                        <button class="btn btn-sm btn-outline-danger" title="Supprimer">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>