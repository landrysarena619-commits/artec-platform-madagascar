import os
from pathlib import Path

DB_CONFIG = {
    'host':     os.getenv('ARTEC_DB_HOST', 'localhost'),
    'port':     int(os.getenv('ARTEC_DB_PORT', 3306)),
    'database': os.getenv('ARTEC_DB_NAME', 'artec_platform'),
    'user':     os.getenv('ARTEC_DB_USER', 'root'),
    'password': os.getenv('ARTEC_DB_PASS', ''),
}

BASE_DIR = Path(__file__).resolve().parent
MODELS_DIR = BASE_DIR / 'models'
LOGS_DIR = BASE_DIR / 'logs'
MODELS_DIR.mkdir(exist_ok=True)
LOGS_DIR.mkdir(exist_ok=True)

MIN_AVIS_POUR_ENTRAINEMENT = 100
RANDOM_STATE = 42
TEST_SIZE = 0.2

RF_SATISFACTION_PARAMS = {
    'n_estimators': 100,
    'max_depth': 8,
    'min_samples_split': 5,
    'random_state': RANDOM_STATE,
}

RF_RISQUE_PARAMS = {
    'n_estimators': 100,
    'max_depth': 6,
    'min_samples_split': 5,
    'random_state': RANDOM_STATE,
}

TFIDF_PARAMS = {
    'max_features': 2000,
    'ngram_range': (1, 2),
    'min_df': 2,
    'max_df': 0.8,
}

ISOLATION_PARAMS = {
    'contamination': 0.1,
    'random_state': RANDOM_STATE,
}

SEUIL_SATISFACTION = 7
SEUIL_RISQUE_NON_CONFORMITE = 5