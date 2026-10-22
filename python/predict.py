from __future__ import annotations

import logging
from datetime import date

import joblib
import mysql.connector
import pandas as pd

from config import DB_CONFIG, MODEL_DIR
from data_loader import load_recent_reviews
from train_models import main as train_models


def main() -> int:
    required = ["satisfaction_rf.pkl", "risque_rf.pkl", "anomaly_if.pkl"]
    missing = [name for name in required if not (MODEL_DIR / name).exists()]
    if missing:
        train_models()
        missing = [name for name in required if not (MODEL_DIR / name).exists()]
        if missing:
            logging.error("Prediction skipped; missing models: %s", ", ".join(missing))
            print("Modèles manquants : " + ", ".join(missing) + ". Le seuil d’entraînement n’est pas encore atteint.")
            return 1

    satisfaction = joblib.load(MODEL_DIR / "satisfaction_rf.pkl")
    compliance = joblib.load(MODEL_DIR / "risque_rf.pkl")
    anomaly = joblib.load(MODEL_DIR / "anomaly_if.pkl")
    sentiment_path = MODEL_DIR / "sentiment_tfidf.pkl"
    sentiment = joblib.load(sentiment_path) if sentiment_path.exists() else None
    connection = mysql.connector.connect(**DB_CONFIG)
    written = 0
    try:
        for operator_code in ("orange", "yas", "airtel"):
            reviews = load_recent_reviews(operator_code)
            if reviews.empty:
                continue
            features = reviews[["comment", "operator_code", "region", "service"]].fillna("")
            score = float(satisfaction.predict(features).mean())
            classifier = compliance.named_steps["model"]
            if 1 in classifier.classes_:
                positive_index = list(classifier.classes_).index(1)
                risk = float(compliance.predict_proba(features)[:, positive_index].mean() * 100)
            else:
                risk = 0.0
            labels = sentiment.predict(reviews["comment"].fillna("")) if sentiment else []
            sentiment_label = str(pd.Series(labels).mode().iloc[0]) if len(labels) else "indisponible"
            anomaly_features = pd.DataFrame(
                {
                    "rating": reviews["rating"].astype(float),
                    "comment_length": reviews["comment"].fillna("").str.len().clip(upper=2000),
                }
            )
            anomaly_count = int((anomaly.predict(anomaly_features) == -1).sum())
            cursor = connection.cursor()
            cursor.execute(
                """INSERT INTO predictions
                   (operator_code, prediction_date, satisfaction_score, compliance_risk, sentiment_label, anomaly_count, review_count, model_version)
                   VALUES (%s, %s, %s, %s, %s, %s, %s, %s)
                   ON DUPLICATE KEY UPDATE satisfaction_score=VALUES(satisfaction_score),
                     compliance_risk=VALUES(compliance_risk), sentiment_label=VALUES(sentiment_label),
                     anomaly_count=VALUES(anomaly_count), review_count=VALUES(review_count),
                     model_version=VALUES(model_version)""",
                (
                    operator_code,
                    date.today(),
                    round(max(0, min(10, score)), 2),
                    round(max(0, min(100, risk)), 2),
                    sentiment_label,
                    anomaly_count,
                    len(reviews),
                    "rf-v1",
                ),
            )
            cursor.close()
            written += 1
        connection.commit()
    except Exception:
        connection.rollback()
        logging.exception("Daily prediction failed")
        raise
    finally:
        connection.close()
    logging.info("Daily predictions written for %s operators", written)
    print(f"Prédictions actualisées pour {written} opérateur(s).")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())