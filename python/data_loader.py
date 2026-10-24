import mysql.connector
import pandas as pd
from config import DB_CONFIG, SEUIL_SATISFACTION


def get_connection():
    return mysql.connector.connect(**DB_CONFIG)


def load_avis():
    query = """
        SELECT
            a.id,
            a.operateur_id,
            a.note,
            a.commentaire,
            a.region,
            a.service,
            a.created_at,
            o.nom AS operateur_nom,
            o.slug AS operateur_slug
        FROM avis a
        INNER JOIN operateurs o ON o.id = a.operateur_id
        WHERE a.publie = 1
    """
    conn = get_connection()
    try:
        df = pd.read_sql(query, conn)
    finally:
        conn.close()

    df['note'] = df['note'].astype(int)
    df['commentaire'] = df['commentaire'].fillna('')
    df['satisfait'] = (df['note'] >= SEUIL_SATISFACTION).astype(int)
    return df


def load_operateurs():
    query = "SELECT id, nom, slug FROM operateurs WHERE actif = 1"
    conn = get_connection()
    try:
        return pd.read_sql(query, conn)
    finally:
        conn.close()


def load_stats_par_operateur():
    query = """
        SELECT
            o.id AS operateur_id,
            o.nom AS operateur_nom,
            COUNT(a.id) AS nb_avis,
            COALESCE(AVG(a.note), 0) AS note_moyenne,
            SUM(CASE WHEN a.note >= 7 THEN 1 ELSE 0 END) AS nb_satisfaits,
            SUM(CASE WHEN a.note <= 3 THEN 1 ELSE 0 END) AS nb_insatisfaits,
            SUM(CASE WHEN a.note BETWEEN 4 AND 6 THEN 1 ELSE 0 END) AS nb_neutres,
            (SELECT COUNT(*) FROM reclamations r WHERE r.operateur_id = o.id AND r.statut IN ('nouveau','en_cours')) AS nb_reclamations_ouvertes,
            (SELECT COUNT(*) FROM decisions d WHERE d.operateur_id = o.id AND d.statut IN ('ouverte','en_cours')) AS nb_decisions_ouvertes
        FROM operateurs o
        LEFT JOIN avis a ON a.operateur_id = o.id AND a.publie = 1
        WHERE o.actif = 1
        GROUP BY o.id
    """
    conn = get_connection()
    try:
        return pd.read_sql(query, conn)
    finally:
        conn.close()


def save_predictions(predictions):
    if not predictions:
        return 0

    query = """
        INSERT INTO predictions
        (operateur_id, type_prediction, valeur, label, score_confiance, details, modele_version)
        VALUES (%(operateur_id)s, %(type_prediction)s, %(valeur)s, %(label)s,
                %(score_confiance)s, %(details)s, %(modele_version)s)
    """
    conn = get_connection()
    try:
        cursor = conn.cursor()
        cursor.executemany(query, predictions)
        conn.commit()
        count = cursor.rowcount
        cursor.close()
        return count
    finally:
        conn.close()


def save_model_metadata(nom, version, type_, fichier, accuracy=None, rmse=None):
    query = """
        INSERT INTO ml_models
        (nom_modele, version, type, fichier, score_accuracy, score_rmse, actif)
        VALUES (%s, %s, %s, %s, %s, %s, 1)
        ON DUPLICATE KEY UPDATE
            fichier = VALUES(fichier),
            score_accuracy = VALUES(score_accuracy),
            score_rmse = VALUES(score_rmse),
            date_entrainement = CURRENT_TIMESTAMP
    """
    conn = get_connection()
    try:
        cursor = conn.cursor()
        cursor.execute(query, (nom, version, type_, fichier, accuracy, rmse))
        conn.commit()
        cursor.close()
    finally:
        conn.close()