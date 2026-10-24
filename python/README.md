# Module Machine Learning - ARTEC Madagascar

Ce module Python entraine 4 modeles sur les avis collectes par la plateforme et ecrit les predictions dans la table MySQL `predictions`. PHP lit ensuite cette table pour les afficher.

## Modeles

| Modele | Algorithme | Role |
|--------|-----------|------|
| `satisfaction_rf.pkl` | Random Forest Regressor | Predit la note /10 par operateur |
| `risque_rf.pkl` | Random Forest Classifier | Probabilite de non-conformite |
| `sentiment_tfidf.pkl` | TF-IDF + Logistic Regression | Sentiment des commentaires |
| `anomaly_if.pkl` | Isolation Forest | Detection d'avis anormaux |

## Prerequis

- Python 3.10 ou superieur
- MySQL (WAMP) avec la base `artec_platform` importee
- Acces en lecture/ecriture a la base `artec_platform`

## Installation

```bash
cd python
python -m venv venv

# Windows
venv\Scripts\activate

# Linux/Mac
source venv/bin/activate

pip install -r requirements.txt