<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Sauvegardes', 'Surveillez les exports de base de données présents sur le serveur.', 'backups.php', 'admin');
$directory = __DIR__ . '/../database/backups';
$files = is_dir($directory) ? glob($directory . '/*.sql') : [];
usort($files, static fn($left, $right) => filemtime($right) <=> filemtime($left));
?>
<div class="notice">Les fichiers de sauvegarde sont stockés hors d’accès HTTP. Leur génération est opérée par le script serveur dédié ; vérifiez régulièrement la restauration sur un environnement isolé.</div>
<div class="panel table-wrap" style="margin-top:18px"><table><thead><tr><th>Fichier</th><th>Créé le</th><th>Taille</th><th>État</th></tr></thead><tbody><?php foreach ($files as $file): ?><tr><td><?= e(basename($file)) ?></td><td><?= e(date('d/m/Y H:i:s', filemtime($file))) ?></td><td><?= e(number_format(filesize($file) / 1048576, 2, ',', ' ')) ?> Mo</td><td><span class="badge success">Présent</span></td></tr><?php endforeach; ?></tbody></table><?php if (!$files): ?><p class="empty-state">Aucune sauvegarde SQL trouvée dans database/backups.</p><?php endif; ?></div>
<?php workspace_end(); ?>