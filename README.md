# Plateforme Intelligente de Regulation des Telecommunications a Madagascar

Plateforme web de regulation des telecommunications a Madagascar pour l'ARTEC (Autorite de Regulation des Technologies de Communication) et les 3 operateurs : Orange Madagascar, Yas Madagascar et Airtel Madagascar.

## Objectif du Projet

Developper une plateforme moderne, securisee et professionnelle permettant a l'ARTEC de :
- Centraliser les informations des operateurs.
- Surveiller la qualite des services.
- Collecter les avis des visiteurs.
- Analyser les donnees via Machine Learning (Python).
- Detecter les manquements reglementaires.

## Fonctionnement General (4 Espaces)

La plateforme est divisee en 4 espaces distincts, chacun avec ses propres fonctionnalites.

### 1. Visiteur (Anonyme, sans compte)
- Consulte les publications des 3 operateurs.
- Donne son avis (note 0-10, commentaire, region, service) sans creer de compte.
- Consulte les statistiques publiques comparatives (graphiques Chart.js).
- Discute en direct avec l'operateur (messagerie instantanee).
- Signale un probleme.

### 2. Operateur (Login requis)
- Publie et gere ses offres/promotions (CRUD complet).
- Consulte ses avis et statistiques detaillees.
- Repond aux reclamations des visiteurs.
- Discute avec les visiteurs et avec l'ARTEC (messagerie instantanee).
- Consulte les decisions et avertissements de l'ARTEC.
- Exporte ses avis en CSV/Excel.

### 3. ARTEC (Login requis)
- Dashboard comparatif des 3 operateurs.
- Modere les avis signales.
- Emet des decisions (avertissements, mises en conformite).
- Discute avec les operateurs.
- Consulte les predictions Machine Learning (satisfaction, risque, sentiment, anomalies).
- Consulte les alertes automatiques.
- Exporte des rapports CSV.

### 4. Administrateur (Login requis)
- Gere les comptes utilisateurs (creation, validation, suppression).
- Surveille les logs d'activite.
- Configure la plateforme.

## Systeme d'Alertes Automatiques

Le projet integre un systeme intelligent qui surveille en permanence les donnees :
- Des qu'un visiteur laisse un avis (ou un signalement), PHP verifie immediatement si les seuils sont depasses.
- Si la note moyenne < 5/10, l'ARTEC cree automatiquement une decision de "Mise en conformite" et envoie un message prive a l'operateur.
- Si plus de 40% des avis sont negatifs, l'ARTEC envoie un avertissement automatique.
- Si un operateur a 3 reclamations ouvertes, il recoit une alerte immediate.
- Toutes ces alertes sont visibles dans l'espace ARTEC et dans la messagerie de l'operateur.

## Module Machine Learning (Python)

Un module Python separe entraine 4 modeles scikit-learn sur les avis collectes :

| Modele | Algorithme | Role |
|--------|-----------|------|
| satisfaction_rf.pkl | Random Forest Regressor | Predit la note /10 par operateur |
| risque_rf.pkl | Random Forest Classifier | Probabilite de non-conformite |
| sentiment_tfidf.pkl | TF-IDF + Logistic Regression | Sentiment des commentaires |
| anomaly_if.pkl | Isolation Forest | Detection d'avis anormaux |

Automatisation : les predictions se lancent automatiquement toutes les heures (ou apres 60 min), des qu'une page est chargee.

## Module Scraping (Reseaux Sociaux)

Le projet inclut un simulateur de collecte de donnees provenant de Facebook, Instagram et TikTok. Il genere des posts realistes pour les 3 operateurs et les stocke dans la table social_posts. Ces donnees sont visibles dans l'espace ARTEC (page artec/social.php).

## Stack Technique

| Composant | Technologie |
|-----------|-------------|
| Backend | PHP 8.1+ (PDO, sessions securisees) |
| Base de donnees | MySQL 8 (10 tables, InnoDB, utf8mb4) |
| Frontend | HTML5, Bootstrap 5.3, JavaScript (AJAX polling) |
| Graphiques | Chart.js 4 |
| ML | Python 3.10+, scikit-learn, pandas |
| Scraping | Apify (simulation) |
| Serveur local | WAMP |

## Installation Locale (WAMP)

### Prerequis
- WAMP installe (Apache + PHP 8.1+ + MySQL 8)
- Extension php_pdo_mysql activee
- Git

### 1. Cloner le projet
cd C:\wamp64\www
git clone https://github.com/landrysarena619-commits/artec-platform-madagascar.git

### 2. Creer la base de donnees
- Ouvrir http://localhost/phpmyadmin
- Creer une base artec_platform (utf8mb4_general_ci)
- Onglet Importer -> selectionner database/database.sql -> Executer

### 3. Configurer la connexion
- Copier config/db.example.php en config/db.php
- Editer config/db.php avec tes identifiants MySQL (par defaut WAMP : root / vide)

### 4. Lancer la plateforme
Ouvrir : http://localhost/artec-platform-madagascar/

## Comptes de Test

| Role | Email | Mot de passe |
|------|-------|--------------|
| Administrateur | admin@artec.mg | Admin@2025 |
| ARTEC | artec@artec.mg | Artec@2025 |
| Orange Madagascar | orange@orange.mg | Orange@2025 |
| Yas Madagascar | yas@yas.mg | Yas@2025 |
| Airtel Madagascar | airtel@airtel.mg | Airtel@2025 |

Important : Les comptes doivent etre crees manuellement lors de la premiere installation (le premier compte de chaque role est auto-active).

## Structure du Projet

artec-platform-madagascar/
- config/          -> Connexion PDO, constantes, sessions
- includes/        -> Header, footer, auth, fonctions utilitaires, alertes
- assets/          -> CSS, JS, images
- visitor/         -> Espace visiteur (public)
- operator/        -> Espace operateur (login requis)
- artec/           -> Espace ARTEC (login requis)
- admin/           -> Espace administrateur (login requis)
- api/             -> Endpoints AJAX (JSON)
- database/        -> Script SQL + donnees de demo
- python/          -> Module ML (scikit-learn) + Scraping
- docs/            -> Documentation, captures d'ecran

## Securite

- Hash BCRYPT pour les mots de passe.
- Token CSRF sur tous les formulaires POST.
- Sessions HttpOnly + SameSite Lax.
- Hash SHA-256 des IP (RGPD).
- Requetes preparees PDO (anti-injection SQL).
- Echappement HTML systematique (anti-XSS).
- Anti-spam sur les avis (1/IP/24h) et messages (10/min).
- Verification stricte des roles (require_role()).
- Logs de toutes les actions sensibles.

## Auteur

Landry Sarena
GitHub : https://github.com/landrysarena619-commits
