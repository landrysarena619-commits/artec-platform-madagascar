<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/ml_config.php';
require_once __DIR__ . '/functions.php';

function alerte_existe_recemment(int $operateurId, string $type, ?int $intervalle = null): bool {
    $intervalle = $intervalle ?? ALERTE_INTERVALLE;
    $stmt = db()->prepare("
        SELECT COUNT(*) FROM alertes_auto
        WHERE operateur_id = ? AND type = ?
          AND created_at > (NOW() - INTERVAL ? SECOND)
    ");
    $stmt->execute([$operateurId, $type, $intervalle]);
    return (int) $stmt->fetchColumn() > 0;
}

function creer_alerte(int $operateurId, string $type, string $message, ?float $valeur = null, ?float $seuil = null): int {
    db()->prepare("
        INSERT INTO alertes_auto (operateur_id, type, message, valeur, seuil)
        VALUES (?, ?, ?, ?, ?)
    ")->execute([$operateurId, $type, $message, $valeur, $seuil]);
    $id = (int) db()->lastInsertId();
    log_action('alerte_auto', 'alertes_auto', $id, $message);
    return $id;
}

function creer_decision_auto(int $operateurId, string $type, string $titre, string $contenu, string $gravite): ?int {
    $stmt = db()->prepare("SELECT id FROM users WHERE role = 'artec' AND actif = 1 LIMIT 1");
    $stmt->execute();
    $artecUserId = $stmt->fetchColumn();
    if (!$artecUserId) return null;

    $stmt = db()->prepare("
        SELECT COUNT(*) FROM decisions
        WHERE operateur_id = ? AND titre = ? AND created_at > (NOW() - INTERVAL 24 HOUR)
    ");
    $stmt->execute([$operateurId, $titre]);
    if ((int) $stmt->fetchColumn() > 0) return null;

    db()->prepare("
        INSERT INTO decisions (operateur_id, artec_user_id, type, titre, contenu, gravite, statut, date_limite)
        VALUES (?, ?, ?, ?, ?, ?, 'ouverte', DATE_ADD(CURDATE(), INTERVAL 30 DAY))
    ")->execute([$operateurId, $artecUserId, $type, $titre, $contenu, $gravite]);

    $id = (int) db()->lastInsertId();
    log_action('decision_auto', 'decisions', $id, "Decision auto : $titre");
    return $id;
}

function envoyer_message_alerte(int $operateurId, string $message, string $priorite = 'normale'): bool {
    $stmt = db()->prepare("SELECT id FROM users WHERE role = 'artec' AND actif = 1 LIMIT 1");
    $stmt->execute();
    $artecUserId = $stmt->fetchColumn();
    if (!$artecUserId) return false;

    $stmt = db()->prepare("SELECT id FROM users WHERE role = 'operateur' AND operateur_id = ? AND actif = 1 LIMIT 1");
    $stmt->execute([$operateurId]);
    $destinataireId = $stmt->fetchColumn();

    $convKey = 'artec_op_' . $operateurId;
    $prefix = $priorite === 'haute' ? '[ALERTE URGENTE]' : '[ALERTE]';

    $stmt = db()->prepare("
        SELECT COUNT(*) FROM messages
        WHERE conversation_key = ? AND sender_type = 'artec'
          AND created_at > (NOW() - INTERVAL 6 HOUR)
          AND contenu LIKE ?
    ");
    $stmt->execute([$convKey, $prefix . '%']);
    if ((int) $stmt->fetchColumn() > 0) return false;

    db()->prepare("
        INSERT INTO messages
        (conversation_type, conversation_key, sender_type, sender_id, receiver_type,
         receiver_id, operateur_id, contenu, lu)
        VALUES ('operateur_artec', ?, 'artec', ?, 'operateur', ?, ?, ?, 0)
    ")->execute([$convKey, $artecUserId, $destinataireId ?: 0, $operateurId, $prefix . ' ' . $message]);

    log_action('message_alerte', 'messages', (int) db()->lastInsertId(), "Alerte envoyee a operateur $operateurId");
    return true;
}

function verifier_operateur_apres_avis(int $operateurId): void {
    $stmt = db()->prepare("
        SELECT
            o.id, o.nom,
            COALESCE(ROUND(AVG(a.note), 2), 0) AS note_moy,
            COUNT(a.id) AS nb_avis,
            SUM(CASE WHEN a.note <= 3 THEN 1 ELSE 0 END) AS nb_negatifs,
            (SELECT COUNT(*) FROM reclamations r
             WHERE r.operateur_id = o.id AND r.statut IN ('nouveau','en_cours')) AS nb_reclam
        FROM operateurs o
        LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
        WHERE o.id = ?
        GROUP BY o.id
    ");
    $stmt->execute([$operateurId]);
    $op = $stmt->fetch();
    if (!$op) return;

    $note = (float) $op['note_moy'];
    $nbAvis = (int) $op['nb_avis'];
    $nbNegatifs = (int) $op['nb_negatifs'];
    $nbReclam = (int) $op['nb_reclam'];

    // 1. Vérifier les réclamations (MÊME SI < 3 avis)
    if ($nbReclam >= ALERTE_RECLAMATIONS_OUVERTES && !alerte_existe_recemment($operateurId, 'reclamation')) {
        $msg = "{$op['nom']} a $nbReclam reclamations ouvertes non traitees.";
        creer_alerte($operateurId, 'reclamation', $msg, $nbReclam, ALERTE_RECLAMATIONS_OUVERTES);
        envoyer_message_alerte($operateurId, "Vous avez $nbReclam reclamations en attente.");
    }

    // 2. Le reste nécessite au moins 3 avis
    if ($nbAvis < 3) return;

    if ($note > 0 && $note < ALERTE_NOTE_BASSE && !alerte_existe_recemment($operateurId, 'note_basse')) {
        $msg = "La note moyenne de {$op['nom']} est de " . number_format($note, 1) . "/10, en dessous du seuil de " . ALERTE_NOTE_BASSE . "/10.";
        creer_alerte($operateurId, 'note_basse', $msg, $note, ALERTE_NOTE_BASSE);
        creer_decision_auto($operateurId, 'mise_en_conformite',
            "Note moyenne insuffisante - {$op['nom']}",
            "La note moyenne de votre service est de " . number_format($note, 1) . "/10, inferieure au seuil reglementaire de " . ALERTE_NOTE_BASSE . "/10.",
            $note < 3 ? 'critique' : ($note < 4 ? 'haute' : 'moyenne')
        );
        envoyer_message_alerte($operateurId, "Votre note moyenne est de " . number_format($note, 1) . "/10.", $note < 3 ? 'haute' : 'normale');
    }

    $pctNeg = $nbAvis > 0 ? ($nbNegatifs / $nbAvis * 100) : 0;
    if ($pctNeg >= ALERTE_AVIS_NEGATIFS_PCT && !alerte_existe_recemment($operateurId, 'avis_negatifs')) {
        $msg = "{$op['nom']} : " . round($pctNeg) . "% des avis sont negatifs ($nbNegatifs/$nbAvis).";
        creer_alerte($operateurId, 'avis_negatifs', $msg, $pctNeg, ALERTE_AVIS_NEGATIFS_PCT);
        creer_decision_auto($operateurId, 'avertissement',
            "Taux d'avis negatifs eleve - {$op['nom']}",
            "Vous avez " . round($pctNeg) . "% d'avis negatifs. L'ARTEC vous adresse un avertissement.",
            $pctNeg >= 60 ? 'haute' : 'moyenne'
        );
        envoyer_message_alerte($operateurId, "Attention : " . round($pctNeg) . "% de vos avis sont negatifs.", $pctNeg >= 60 ? 'haute' : 'normale');
    }
}

function verifier_et_alerter(): void {
    $operateurs = db()->query("SELECT id FROM operateurs WHERE actif = 1")->fetchAll();
    foreach ($operateurs as $op) {
        verifier_operateur_apres_avis((int) $op['id']);
    }
}