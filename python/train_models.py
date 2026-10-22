from __future__ import annotations

import logging

import joblib
import pandas as pd
from sklearn.compose import ColumnTransformer
from sklearn.ensemble import IsolationForest, RandomForestClassifier, RandomForestRegressor
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import OneHotEncoder, StandardScaler

from config import MINIMUM_TRAINING_REVIEWS, MODEL_DIR
from data_loader import load_reviews, load_training_threshold


def structured_features() -> ColumnTransformer:
    return ColumnTransformer(
        transformers=[
            ("comment", TfidfVectorizer(max_features=2500, ngram_range=(1, 2), min_df=1), "comment"),
            ("categories", OneHotEncoder(handle_unknown="ignore"), ["operator_code", "region", "service"]),
        ]
    )


def main() -> int:
    logging.info("Starting model training")
    reviews = load_reviews()
    minimum_reviews = load_training_threshold(MINIMUM_TRAINING_REVIEWS)
    if len(reviews) < minimum_reviews:
        logging.warning("Training skipped: %s/%s reviews", len(reviews), minimum_reviews)
        print(f"Entraînement ignoré : {len(reviews)} avis disponibles, {minimum_reviews} requis.")
        return 0

    features = reviews[["comment", "operator_code", "region", "service"]].fillna("")
    satisfaction = Pipeline(
        [
            ("features", structured_features()),
            ("model", RandomForestRegressor(n_estimators=250, min_samples_leaf=2, random_state=42, n_jobs=-1)),
        ]
    )
    satisfaction.fit(features, reviews["rating"].astype(float))
    joblib.dump(satisfaction, MODEL_DIR / "satisfaction_rf.pkl")

    # Compliance labels are only a weak proxy until expert-labelled data exists.
    compliance_labels = ((reviews["rating"] <= 3) | reviews["has_complaint"].astype(bool)).astype(int)
    compliance = Pipeline(
        [
            ("features", structured_features()),
            ("model", RandomForestClassifier(n_estimators=250, class_weight="balanced", random_state=42, n_jobs=-1)),
        ]
    )
    compliance.fit(features, compliance_labels)
    joblib.dump(compliance, MODEL_DIR / "risque_rf.pkl")

    sentiment_labels = pd.Series("neutral", index=reviews.index)
    sentiment_labels.loc[reviews["rating"] >= 7] = "positive"
    sentiment_labels.loc[reviews["rating"] <= 4] = "negative"
    if sentiment_labels.nunique() >= 2:
        sentiment = Pipeline(
            [
                ("tfidf", TfidfVectorizer(max_features=4000, ngram_range=(1, 2), min_df=1)),
                ("model", LogisticRegression(max_iter=1000, class_weight="balanced", random_state=42)),
            ]
        )
        sentiment.fit(reviews["comment"].fillna(""), sentiment_labels)
        joblib.dump(sentiment, MODEL_DIR / "sentiment_tfidf.pkl")
    else:
        logging.warning("Sentiment model skipped: fewer than two weak-label classes")

    anomaly_features = pd.DataFrame(
        {
            "rating": reviews["rating"].astype(float),
            "comment_length": reviews["comment"].fillna("").str.len().clip(upper=2000),
        }
    )
    anomaly = Pipeline(
        [
            ("scale", StandardScaler()),
            ("model", IsolationForest(n_estimators=200, contamination="auto", random_state=42)),
        ]
    )
    anomaly.fit(anomaly_features)
    joblib.dump(anomaly, MODEL_DIR / "anomaly_if.pkl")
    logging.info("Model training completed with %s reviews", len(reviews))
    print(f"Modèles entraînés avec {len(reviews)} avis.")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())