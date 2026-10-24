import sys
from datetime import datetime

import joblib
import numpy as np
import pandas as pd

from config import MODELS_DIR, LOGS_DIR, SEUIL_RISQUE_NON_CONFORMITE
from data_loader import (
    load_avis, load_stats_par_operateur, save_predictions
)


def log(msg):
    line = f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] {msg}"
    print(line)
    with open(LOGS_DIR / 'ml.log', 'a', encoding='utf-8') as f:
        f.write(line + '\n')


def model_exists(name):
    return (MODELS_DIR / name).exists()


def predict_satisfaction(df):
    log("--- Prediction satisfaction par operateur ---")
    path = MODELS_DIR / 'satisfaction_rf.pkl'
    if not path.exists():
        log("Modele satisfaction introuvable. Skip.")
        return []

    bundle = joblib.load(path)
    model = bundle['model']
    columns = bundle['columns']

    results = []
    for op_id in df['operateur_id'].unique():
        sub = df[df['operateur_id'] == op_id]
        row = pd.DataFrame([{
            'operateur_id': op_id,
            'region': sub['region'].mode().iloc[0] if len(sub['region'].mode()) else 'Antananarivo',
            'service': sub['service'].mode().iloc[0] if len(sub['service'].mode()) else 'mobile',
        }])
        X = pd.get_dummies(row, drop_first=True)
        X = X.reindex(columns=columns, fill_value=0)

        try:
            valeur = float(model.predict(X)[0])
            valeur = max(0.0, min(10.0, valeur))
            results.append({
                'operateur_id': int(op_id),
                'type_prediction': 'satisfaction',
                'valeur': round(valeur, 2),
                'label': 'Satisfaction predite',
                'score_confiance': None,
                'details': None,
                'modele_version': '1.0',
            })
        except Exception as e:
            log(f"Erreur prediction satisfaction op {op_id} : {e}")

    log(f"{len(results)} predictions satisfaction.")
    return results


def predict_risque(df_stats):
    log("--- Prediction risque par operateur ---")
    path = MODELS_DIR / 'risque_rf.pkl'
    if not path.exists():
        log("Modele risque introuvable. Skip.")
        return []

    bundle = joblib.load(path)
    model = bundle['model']
    features = bundle['features']

    results = []
    for _, row in df_stats.iterrows():
        X = row[features].to_frame().T.astype(float)
        try:
            proba = model.predict_proba(X)[0]
            proba_risque = float(proba[1]) if len(proba) > 1 else 0.0
            label = 'Eleve' if proba_risque >= 0.6 else ('Moyen' if proba_risque >= 0.3 else 'Faible')
            results.append({
                'operateur_id': int(row['operateur_id']),
                'type_prediction': 'risque_non_conformite',
                'valeur': round(proba_risque, 4),
                'label': label,
                'score_confiance': round(float(max(proba)), 4),
                'details': None,
                'modele_version': '1.0',
            })
        except Exception as e:
            log(f"Erreur prediction risque op {row['operateur_id']} : {e}")

    log(f"{len(results)} predictions risque.")
    return results


def predict_sentiment(df):
    log("--- Prediction sentiment par operateur ---")
    path = MODELS_DIR / 'sentiment_tfidf.pkl'
    if not path.exists():
        log("Modele sentiment introuvable. Skip.")
        return []

    bundle = joblib.load(path)
    model = bundle['model']
    vectorizer = bundle['vectorizer']

    results = []
    for op_id in df['operateur_id'].unique():
        sub = df[(df['operateur_id'] == op_id) & (df['commentaire'].str.strip() != '')]
        if sub.empty:
            continue

        X = vectorizer.transform(sub['commentaire'])
        try:
            probas = model.predict_proba(X)
            if probas.shape[1] > 1:
                moyenne_positif = float(probas[:, 1].mean())
            else:
                moyenne_positif = float(probas[:, 0].mean())

            label = 'Positif' if moyenne_positif >= 0.6 else ('Negatif' if moyenne_positif <= 0.4 else 'Neutre')
            results.append({
                'operateur_id': int(op_id),
                'type_prediction': 'sentiment',
                'valeur': round(moyenne_positif, 4),
                'label': label,
                'score_confiance': None,
                'details': None,
                'modele_version': '1.0',
            })
        except Exception as e:
            log(f"Erreur prediction sentiment op {op_id} : {e}")

    log(f"{len(results)} predictions sentiment.")
    return results


def predict_anomalies(df):
    log("--- Prediction anomalies ---")
    path = MODELS_DIR / 'anomaly_if.pkl'
    if not path.exists():
        log("Modele anomalies introuvable. Skip.")
        return []

    bundle = joblib.load(path)
    model = bundle['model']

    results = []
    for op_id in df['operateur_id'].unique():
        sub = df[df['operateur_id'] == op_id]
        if sub.empty:
            continue
        X = sub[['note']].values.astype(float)
        try:
            preds = model.predict(X)
            nb_anomalies = int((preds == -1).sum())
            results.append({
                'operateur_id': int(op_id),
                'type_prediction': 'anomalie',
                'valeur': nb_anomalies,
                'label': f'{nb_anomalies} avis suspect(s)',
                'score_confiance': None,
                'details': None,
                'modele_version': '1.0',
            })
        except Exception as e:
            log(f"Erreur anomalies op {op_id} : {e}")

    log(f"{len(results)} predictions anomalies.")
    return results


def main():
    log("=" * 60)
    log("DEMARRAGE PREDICTIONS ARTEC")
    log("=" * 60)

    df_avis = load_avis()
    df_stats = load_stats_par_operateur()

    if len(df_avis) < 5:
        log("Pas assez d'avis. Arret.")
        sys.exit(1)

    all_predictions = []
    all_predictions += predict_satisfaction(df_avis)
    all_predictions += predict_risque(df_stats)
    all_predictions += predict_sentiment(df_avis)
    all_predictions += predict_anomalies(df_avis)

    if all_predictions:
        count = save_predictions(all_predictions)
        log(f"{count} predictions ecrites dans la table predictions.")
    else:
        log("Aucune prediction generee.")

    log("=" * 60)
    log("PREDICTIONS TERMINEES")
    log("=" * 60)


if __name__ == '__main__':
    main()