<?php
require_once __DIR__ . '/../config/ml_config.php';

function ml_run_script(string $batFile): bool {
    if (!file_exists($batFile)) {
        ml_log("ERREUR : script introuvable : $batFile");
        return false;
    }
    if (stripos(PHP_OS, 'WIN') === 0) {
        $cmd = 'start /B "" "' . $batFile . '" > NUL 2>&1';
        @pclose(@popen($cmd, 'r'));
    } else {
        @exec('nohup bash ' . escapeshellarg($batFile) . ' > /dev/null 2>&1 &');
    }
    return true;
}

function ml_log(string $msg): void {
    if (!is_dir(ML_LOGS_DIR)) @mkdir(ML_LOGS_DIR, 0775, true);
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL;
    @file_put_contents(ML_RUNNER_LOG, $line, FILE_APPEND);
}

function ml_read_state(): array {
    if (!file_exists(ML_STATE_FILE)) return [];
    $json = @file_get_contents(ML_STATE_FILE);
    return $json ? (json_decode($json, true) ?: []) : [];
}

function ml_write_state(array $state): void {
    if (!is_dir(ML_LOGS_DIR)) @mkdir(ML_LOGS_DIR, 0775, true);
    @file_put_contents(ML_STATE_FILE, json_encode($state, JSON_PRETTY_PRINT));
}

function ml_auto_run(): void {
    if (!ML_AUTO_ENABLED) return;

    $state = ml_read_state();
    $now = time();

    $lastPredict = $state['last_predict'] ?? 0;
    if (($now - $lastPredict) >= ML_PREDICT_INTERVAL) {
        if (ml_run_script(ML_PREDICT_BAT)) {
            ml_log('predict.py lancé automatiquement');
            $state['last_predict'] = $now;
        } else {
            ml_log('Échec du lancement de predict.py');
        }
    }

    $lastTrain = $state['last_train'] ?? 0;
    if (($now - $lastTrain) >= ML_TRAIN_INTERVAL) {
        try {
            require_once __DIR__ . '/../config/db.php';
            $nbAvis = (int) db()->query("SELECT COUNT(*) FROM avis WHERE publie = 1")->fetchColumn();
            if ($nbAvis >= ML_MIN_AVIS_TRAIN) {
                if (ml_run_script(ML_TRAIN_BAT)) {
                    ml_log("train_models.py lancé automatiquement ($nbAvis avis)");
                    $state['last_train'] = $now;
                }
            } else {
                ml_log("Pas assez d'avis ($nbAvis / " . ML_MIN_AVIS_TRAIN . "), entraînement reporté");
            }
        } catch (Throwable $e) {
            ml_log('Erreur BDD lors du check training : ' . $e->getMessage());
        }
    }

    ml_write_state($state);
}