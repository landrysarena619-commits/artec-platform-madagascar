<?php
function e(?string $s): string {
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function hashIp(?string $ip = null): string {
    $ip = $ip ?? ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
    return hash('sha256', 'artec_salt_' . $ip);
}

function redirect(string $url): void {
    header('Location: ' . $url);
    exit;
}

function flash(string $key, ?string $message = null, string $type = 'info'): ?array {
    if ($message !== null) {
        $_SESSION['flash'][$key] = ['message' => $message, 'type' => $type];
        return null;
    }
    if (isset($_SESSION['flash'][$key])) {
        $f = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $f;
    }
    return null;
}

function render_flash(): string {
    if (empty($_SESSION['flash'])) return '';
    $html = '';
    foreach ($_SESSION['flash'] as $key => $f) {
        $type = e($f['type']);
        $msg = e($f['message']);
        $html .= "<div class=\"alert alert-{$type} alert-dismissible fade show\" role=\"alert\">{$msg}<button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button></div>";
        unset($_SESSION['flash'][$key]);
    }
    return $html;
}

function slugify(string $s): string {
    $s = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    $s = strtolower($s);
    $s = preg_replace('/[^a-z0-9]+/', '-', $s);
    return trim($s, '-');
}

function json_response(array $data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function is_logged_in(): bool {
    return !empty($_SESSION['user']);
}

function has_role(string ...$roles): bool {
    $u = current_user();
    return $u && in_array($u['role'], $roles, true);
}

function verify_csrf(?string $token): bool {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function log_action(string $action, ?string $entite = null, ?int $entiteId = null, ?string $details = null): void {
    try {
        $u = current_user();
        $stmt = db()->prepare("INSERT INTO logs (user_id, role, action, entite, entite_id, details, ip_hash, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $u['id'] ?? null,
            $u['role'] ?? null,
            $action,
            $entite,
            $entiteId,
            $details,
            hashIp(),
            substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('log_action error: ' . $e->getMessage());
    }
}