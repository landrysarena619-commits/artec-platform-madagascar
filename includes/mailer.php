<?php
/**
 * Envoi d'email simple.
 *
 * En LOCAL (WAMP) : les emails sont écrits dans logs/mails.log
 *                   au lieu d'être réellement envoyés (pas de serveur SMTP).
 * En PRODUCTION  : utiliser la fonction mail() de PHP ou un SMTP (PHPMailer).
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Envoie un email. En local, log uniquement.
 * @return bool
 */
function send_mail(string $to, string $subject, string $body): bool {
    $headers = "From: ARTEC Madagascar <noreply@artec.mg>\r\n";
    $headers .= "Reply-To: contact@artec.mg\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    if (APP_ENV === 'local') {
        // Écriture dans un fichier de log
        $logDir = ROOT_PATH . '/logs';
        if (!is_dir($logDir)) mkdir($logDir, 0775, true);
        $logFile = $logDir . '/mails.log';

        $entry = str_repeat('=', 60) . "\n";
        $entry .= 'Date    : ' . date('Y-m-d H:i:s') . "\n";
        $entry .= 'To      : ' . $to . "\n";
        $entry .= 'Subject : ' . $subject . "\n";
        $entry .= "Body    :\n" . strip_tags($body) . "\n";
        $entry .= str_repeat('=', 60) . "\n\n";

        file_put_contents($logFile, $entry, FILE_APPEND);
        return true;
    }

    // Production : envoi réel
    return @mail($to, $subject, $body, $headers);
}