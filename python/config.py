import logging
import os
from pathlib import Path

BASE_DIR = Path(__file__).resolve().parent
MODEL_DIR = BASE_DIR / "models"
LOG_DIR = BASE_DIR / "logs"
MODEL_DIR.mkdir(exist_ok=True)
LOG_DIR.mkdir(exist_ok=True)

DB_CONFIG = {
    "host": os.getenv("DB_HOST", "127.0.0.1"),
    "database": os.getenv("DB_NAME", "artec_platform"),
    "user": os.getenv("DB_USER", "root"),
    "password": os.getenv("DB_PASSWORD", ""),
}
MINIMUM_TRAINING_REVIEWS = int(os.getenv("MINIMUM_TRAINING_REVIEWS", "100"))

logging.basicConfig(
    filename=LOG_DIR / "ml.log",
    level=logging.INFO,
    format="%(asctime)s %(levelname)s %(message)s",
)