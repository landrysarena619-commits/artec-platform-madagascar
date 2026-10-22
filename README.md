# ARTEC Madagascar

Plateforme PHP/MySQL pour publier les offres des trois opérateurs, recueillir des avis anonymes, traiter les réclamations et suivre les indicateurs réglementaires. Le module de machine learning Python fonctionne séparément et écrit ses résultats dans MySQL.

## Prérequis

- WAMP avec PHP 8.1+ et les extensions `pdo_mysql`, `mbstring` et `openssl`.
- MySQL 8.0+.
- Python 3.10+ pour les modèles ML.

## Installation locale

1. Placez le dossier dans `C:\wamp64\www\artec-platform-madagascar` et démarrez Apache et MySQL.
2. Importez `database/database.sql` une fois dans phpMyAdmin ou avec `mysql -u root -p < database/database.sql`.
3. Configurez les variables d’environnement `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`, `APP_ENV` et `IP_HASH_KEY` dans Apache/PHP. La base par défaut s’appelle `artec_platform`; utilisez `APP_URL` si l’application est publiée sous un chemin personnalisé.
4. Ouvrez `http://localhost/artec-platform-madagascar/`.

Les comptes de démonstration sont `orange@demo.mg`, `yas@demo.mg`, `airtel@demo.mg`, `artec@demo.mg` et `admin@demo.mg`. Leur mot de passe initial est `password`. Remplacez-les et définissez un secret `IP_HASH_KEY` fort avant tout déploiement. N’exposez jamais les identifiants locaux `root` en production.

## Module ML

Depuis `python/`, installez `python -m pip install -r requirements.txt`, définissez les mêmes variables `DB_*`, puis lancez `python train_models.py`. L’entraînement est ignoré avant le seuil configurable (100 avis par défaut). `python predict.py` entraîne les modèles manquants lorsque le seuil est atteint, puis écrit les scores quotidiens dans `predictions`.

Les tâches Windows peuvent être enregistrées depuis PowerShell avec `python/schedule_tasks.ps1 -PythonExe "C:\chemin\vers\python.exe"`. Le script programme les prédictions chaque jour à 02:00 et l’entraînement le dimanche à 03:00. Définissez l’environnement de la tâche avec les identifiants MySQL adaptés.

Les labels de risque et de sentiment utilisent actuellement des proxies faibles dérivés des notes et des réclamations. Ils ne remplacent pas des labels expertisés et ne doivent pas être utilisés seuls pour prendre une décision réglementaire.

## Sauvegardes

Configurez `MYSQLDUMP_EXE` et `MYSQL_CNF` pour pointer vers `mysqldump.exe` et un fichier d’options MySQL protégé par les ACL Windows, puis lancez `database/backup.ps1`. Les exports SQL restent hors d’accès HTTP et les fichiers âgés de plus de 30 jours sont supprimés. Testez régulièrement une restauration sur une base isolée.

## Sécurité de déploiement

- Définissez `APP_ENV=production`, un `APP_URL` correct et un secret `IP_HASH_KEY` dédié.
- Utilisez un compte MySQL à privilèges minimaux et HTTPS.
- Changez/supprimez les comptes de démonstration et désactivez l’affichage des erreurs PHP.
- Les protections `.htaccess` supposent qu’Apache autorise `AllowOverride`; vérifiez-les avec la configuration WAMP de production.
- Les prédictions sont des aides d’analyse exploratoires; validez les modèles avant tout usage décisionnel.