<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Configuration', 'Réglez les seuils de modération et d’analyse.', 'config.php', 'admin');
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verify_csrf($_POST['csrf_token'] ?? null)) {
    if (($_POST['action'] ?? '') === 'category_add') {
        $category = trim((string) ($_POST['category_name'] ?? ''));
        if (mb_strlen($category) < 2 || mb_strlen($category) > 100) $message = 'Le nom de catégorie doit contenir entre 2 et 100 caractères.';
        else { try { db()->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$category]); flash('success','Catégorie ajoutée.'); header('Location: ' . url('admin/config.php')); exit; } catch (PDOException $exception) { $message = 'Cette catégorie existe déjà.'; } }
    } elseif (($_POST['action'] ?? '') === 'category_toggle') {
        $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT);
        if ($categoryId) db()->prepare('UPDATE categories SET is_active=1-is_active WHERE id=?')->execute([$categoryId]);
        flash('success','Catégorie mise à jour.'); header('Location: ' . url('admin/config.php')); exit;
    } else {
        $limit = filter_var($_POST['review_daily_limit'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 10]]);
        $threshold = filter_var($_POST['minimum_reviews_for_training'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 20, 'max_range' => 10000]]);
        if (!$limit || !$threshold) $message = 'Le plafond d’avis doit être entre 1 et 10, le seuil ML entre 20 et 10 000.';
        else { $save = db()->prepare('INSERT INTO settings (setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)'); $save->execute(['review_daily_limit',(string)$limit]); $save->execute(['minimum_reviews_for_training',(string)$threshold]); log_activity('settings_updated',current_user()['id']); flash('success','Configuration enregistrée.'); header('Location: ' . url('admin/config.php')); exit; }
    }
}
$settings = db()->query('SELECT setting_key,setting_value FROM settings')->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<?php if ($message): ?><div class="flash flash-error"><?= e($message) ?></div><?php endif; ?><form method="post" class="panel"><?= csrf_field() ?><div class="form-grid"><div class="field"><label for="daily">Avis maximum par adresse et par jour</label><input id="daily" type="number" min="1" max="10" name="review_daily_limit" value="<?= e($settings['review_daily_limit'] ?? 3) ?>" required></div><div class="field"><label for="threshold">Avis minimum avant entraînement ML</label><input id="threshold" type="number" min="20" max="10000" name="minimum_reviews_for_training" value="<?= e($settings['minimum_reviews_for_training'] ?? 100) ?>" required></div></div><div class="toolbar"><span class="badge">Fuseau horaire : Indian/Antananarivo</span><button class="button" type="submit">Enregistrer</button></div></form>
<div class="panel" style="margin-top:18px"><div class="toolbar"><h2 style="font-size:18px;margin:0">Catégories</h2><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="category_add"><input name="category_name" maxlength="100" placeholder="Nouvelle catégorie" required><button class="button secondary" type="submit">Ajouter</button></form></div><div class="table-wrap"><table><thead><tr><th>Nom</th><th>Statut</th><th>Action</th></tr></thead><tbody><?php foreach (db()->query('SELECT id,name,is_active FROM categories ORDER BY name')->fetchAll() as $category): ?><tr><td><?= e($category['name']) ?></td><td><?= $category['is_active'] ? 'Active' : 'Désactivée' ?></td><td><form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="category_toggle"><input type="hidden" name="category_id" value="<?= (int) $category['id'] ?>"><button class="button secondary" type="submit"><?= $category['is_active'] ? 'Désactiver' : 'Réactiver' ?></button></form></td></tr><?php endforeach; ?></tbody></table></div></div>
<?php workspace_end(); ?>