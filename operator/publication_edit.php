<?php
require_once __DIR__ . '/../includes/workspace.php';
workspace_start('Publication', 'Créez une offre claire et actualisée.', 'publications.php', 'operator');
$code = current_user()['operator_code'];
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$item = ['title' => '', 'summary' => '', 'body' => '', 'category_id' => '', 'region' => 'National', 'status' => 'published', 'starts_at' => '', 'ends_at' => ''];
if ($id) {
    $statement = db()->prepare('SELECT * FROM publications WHERE id = ? AND operator_code = ?'); $statement->execute([$id, $code]);
    $item = $statement->fetch() ?: exit('Publication introuvable.');
}
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf($_POST['csrf_token'] ?? null)) $error = 'La session a expiré.';
    else {
        $title = trim((string) ($_POST['title'] ?? ''));
        $summary = trim((string) ($_POST['summary'] ?? ''));
        $body = trim((string) ($_POST['body'] ?? ''));
        $region = trim((string) ($_POST['region'] ?? 'National'));
        $status = in_array($_POST['status'] ?? '', ['draft', 'published'], true) ? $_POST['status'] : 'draft';
        if (mb_strlen($title) < 5 || mb_strlen($summary) < 10 || mb_strlen($body) < 20) $error = 'Complétez le titre, le résumé et le contenu.';
        else {
            $categoryId = filter_var($_POST['category_id'] ?? null, FILTER_VALIDATE_INT) ?: null;
            $starts = $_POST['starts_at'] ?: null; $ends = $_POST['ends_at'] ?: null;
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $title)), '-'));
            if ($id) {
                $save = db()->prepare('UPDATE publications SET title=?, slug=?, summary=?, body=?, category_id=?, region=?, status=?, starts_at=?, ends_at=? WHERE id=? AND operator_code=?');
                $save->execute([$title, $slug, $summary, $body, $categoryId, $region, $status, $starts, $ends, $id, $code]);
            } else {
                $save = db()->prepare('INSERT INTO publications (operator_code,title,slug,summary,body,category_id,region,status,starts_at,ends_at) VALUES (?,?,?,?,?,?,?,?,?,?)');
                $save->execute([$code, $title, $slug, $summary, $body, $categoryId, $region, $status, $starts, $ends]);
            }
            flash('success', 'Publication enregistrée.'); header('Location: ' . url('operator/publications.php')); exit;
        }
    }
}
$categories = db()->query('SELECT id, name FROM categories WHERE is_active=1 ORDER BY name')->fetchAll();
?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?><form class="panel" method="post"><?= csrf_field() ?><div class="form-grid"><div class="field full"><label for="title">Titre</label><input id="title" name="title" maxlength="180" required value="<?= e($_POST['title'] ?? $item['title']) ?>"></div><div class="field full"><label for="summary">Résumé</label><input id="summary" name="summary" maxlength="350" required value="<?= e($_POST['summary'] ?? $item['summary']) ?>"></div><div class="field full"><label for="body">Description</label><textarea id="body" name="body" minlength="20" required><?= e($_POST['body'] ?? $item['body']) ?></textarea></div><div class="field"><label for="category_id">Catégorie</label><select id="category_id" name="category_id"><option value="">Sans catégorie</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) ($item['category_id'] ?? '') === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select></div><div class="field"><label for="region">Région</label><input id="region" name="region" required value="<?= e($_POST['region'] ?? $item['region']) ?>"></div><div class="field"><label for="status">Statut</label><select id="status" name="status"><option value="published">Publié</option><option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : '' ?>>Brouillon</option></select></div><div class="field"><label for="starts_at">Début de l’offre</label><input id="starts_at" type="date" name="starts_at" value="<?= e($item['starts_at'] ?? '') ?>"></div><div class="field"><label for="ends_at">Fin de l’offre</label><input id="ends_at" type="date" name="ends_at" value="<?= e($item['ends_at'] ?? '') ?>"></div></div><div class="toolbar"><a class="button secondary" href="<?= e(url('operator/publications.php')) ?>">Annuler</a><button class="button" type="submit">Enregistrer</button></div></form>
<?php workspace_end(); ?>