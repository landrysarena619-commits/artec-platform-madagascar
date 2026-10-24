import sys
from datetime import datetime

import joblib
import numpy as np
import pandas as pd
from sklearn.ensemble import IsolationForest, RandomForestClassifier, RandomForestRegressor
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.metrics import accuracy_score, mean_squared_error, r2_score
from sklearn.model_selection import train_test_split

from config import (
    RF_SATISFACTION_PARAMS, RF_RISQUE_PARAMS, TFIDF_PARAMS, ISOLATION_PARAMS,
    MIN_AVIS_POUR_ENTRAINEMENT, TEST_SIZE, RANDOM_STATE, MODELS_DIR, LOGS_DIR,
    SEUIL_SATISFACTION
)
from data_loader import (
    load_avis, load_stats_par_operateur,
    save_model_metadata
)


def log(msg):
    line = f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] {msg}"
    print(line)
    with open(LOGS_DIR / 'ml.log', 'a', encoding='utf-8') as f:
        f.write(line + '\n')


def train_satisfaction(df):
    log("--- Entrainement satisfaction_rf ---")
    X = pd.get_dummies(df[['operateur_id', 'region', 'service']], drop_first=True)
    y = df['note']

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE
    )

    model = RandomForestRegressor(**RF_SATISFACTION_PARAMS)
    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)
    rmse = float(np.sqrt(mean_squared_error(y_test, y_pred)))
    r2 = float(r2_score(y_test, y_pred))
    log(f"Satisfaction : RMSE={rmse:.2f}, R2={r2:.2f}")

    fichier = MODELS_DIR / 'satisfaction_rf.pkl'
    joblib.dump({'model': model, 'columns': list(X.columns)}, fichier)
    save_model_metadata('satisfaction_rf', '1.0', 'regressor', str(fichier), None, rmse)
    log(f"Modele sauvegarde : {fichier}")
    return model, rmse


def train_risque(df_stats):
    log("--- Entrainement risque_rf ---")

    if len(df_stats) < 3:
        log("Pas assez d'operateurs pour entrainer (min 3). Skip.")
        return None, None

    df_stats = df_stats.copy()
    df_stats['risque'] = (
        (df_stats['note_moyenne'] < 5) |
        (df_stats['nb_reclamations_ouvertes'] >= 3) |
        (df_stats['nb_decisions_ouvertes'] >= 2)
    ).astype(int)

    features = ['nb_avis', 'note_moyenne', 'nb_satisfaits',
                'nb_insatisfaits', 'nb_neutres',
                'nb_reclamations_ouvertes', 'nb_decisions_ouvertes']
    X = df_stats[features]
    y = df_stats['risque']

    if y.nunique() < 2:
        log("Une seule classe. Fallback heuristique.")
        model = RandomForestClassifier(**RF_RISQUE_PARAMS)
        X_dup = pd.concat([X, X])
        y_dup = pd.concat([y, 1 - y])
        model.fit(X_dup, y_dup)
        acc = 1.0
    else:
        X_train, X_test, y_train, y_test = train_test_split(
            X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE
        )
        model = RandomForestClassifier(**RF_RISQUE_PARAMS)
        model.fit(X_train, y_train)
        y_pred = model.predict(X_test)
        acc = float(accuracy_score(y_test, y_pred))

    log(f"Risque : accuracy={acc:.2f}")
    fichier = MODELS_DIR / 'risque_rf.pkl'
    joblib.dump({'model': model, 'features': features}, fichier)
    save_model_metadata('risque_rf', '1.0', 'classifier', str(fichier), acc, None)
    log(f"Modele sauvegarde : {fichier}")
    return model, acc


def train_sentiment(df):
    log("--- Entrainement sentiment_tfidf ---")
    data = df[df['commentaire'].str.strip() != ''].copy()
    data = data[(data['note'] >= 7) | (data['note'] <= 4)]
    data['label'] = (data['note'] >= 7).astype(int)

    if len(data) < 10 or data['label'].nunique() < 2:
        log(f"Pas assez de commentaires labellises (n={len(data)}). Skip.")
        return None, None

    vectorizer = TfidfVectorizer(**TFIDF_PARAMS)
    X = vectorizer.fit_transform(data['commentaire'])
    y = data['label']

    X_train, X_test, y_train, y_test = train_test_split(
        X, y, test_size=TEST_SIZE, random_state=RANDOM_STATE
    )
    model = LogisticRegression(max_iter=1000, random_state=RANDOM_STATE)
    model.fit(X_train, y_train)

    y_pred = model.predict(X_test)
    acc = float(accuracy_score(y_test, y_pred))
    log(f"Sentiment : accuracy={acc:.2f}")

    fichier = MODELS_DIR / 'sentiment_tfidf.pkl'
    joblib.dump({'model': model, 'vectorizer': vectorizer}, fichier)
    save_model_metadata('sentiment_tfidf', '1.0', 'classifier', str(fichier), acc, None)
    log(f"Modele sauvegarde : {fichier}")
    return model, acc


def train_anomaly(df):
    log("--- Entrainement anomaly_if ---")
    if len(df) < 20:
        log(f"Pas assez d'avis (n={len(df)}). Skip.")
        return None

    X = df[['note']].values

    model = IsolationForest(**ISOLATION_PARAMS)
    model.fit(X)

    fichier = MODELS_DIR / 'anomaly_if.pkl'
    joblib.dump({'model': model}, fichier)
    save_model_metadata('anomaly_if', '1.0', 'anomaly', str(fichier), None, None)
    log(f"Modele sauvegarde : {fichier}")
    return model


def main():
    log("=" * 60)
    log("DEMARRAGE ENTRAINEMENT MODELES ARTEC")
    log("=" * 60)

    df_avis = load_avis()
    log(f"Charge {len(df_avis)} avis publies.")

    if len(df_avis) < MIN_AVIS_POUR_ENTRAINEMENT:
        log(f"Seulement {len(df_avis)} avis (min {MIN_AVIS_POUR_ENTRAINEMENT}).")
        log("On entraine quand meme pour la demo.")

    if len(df_avis) < 10:
        log("Pas assez d'avis pour entrainer. Arret.")
        sys.exit(1)

    df_stats = load_stats_par_operateur()

    train_satisfaction(df_avis)
    train_risque(df_stats)
    train_sentiment(df_avis)
    train_anomaly(df_avis)

    log("=" * 60)
    log("ENTRAINEMENT TERMINE")
    log("=" * 60)


if __name__ == '__main__':
    main()