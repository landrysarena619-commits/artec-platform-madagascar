from __future__ import annotations

import pandas as pd
import mysql.connector

from config import DB_CONFIG


def load_reviews() -> pd.DataFrame:
    query = """
        SELECT r.id, r.operator_code, r.rating, r.comment, r.region, r.service,
               EXISTS (SELECT 1 FROM complaints c WHERE c.review_id = r.id) AS has_complaint
        FROM reviews r
        WHERE r.status = 'published'
        ORDER BY r.created_at ASC
    """
    connection = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(query)
        rows = cursor.fetchall()
        cursor.close()
        return pd.DataFrame(rows)
    finally:
        connection.close()


def load_recent_reviews(operator_code: str, limit: int = 500) -> pd.DataFrame:
    query = """
        SELECT id, operator_code, rating, comment, region, service
        FROM reviews
        WHERE status = 'published' AND operator_code = %s
        ORDER BY created_at DESC
        LIMIT %s
    """
    connection = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = connection.cursor(dictionary=True)
        cursor.execute(query, (operator_code, limit))
        frame = pd.DataFrame(cursor.fetchall())
        cursor.close()
        if not frame.empty:
            frame = frame.iloc[::-1].reset_index(drop=True)
        return frame
    finally:
        connection.close()


def load_training_threshold(default: int) -> int:
    connection = mysql.connector.connect(**DB_CONFIG)
    try:
        cursor = connection.cursor()
        cursor.execute("SELECT setting_value FROM settings WHERE setting_key = 'minimum_reviews_for_training'")
        row = cursor.fetchone()
        cursor.close()
        return int(row[0]) if row else default
    finally:
        connection.close()