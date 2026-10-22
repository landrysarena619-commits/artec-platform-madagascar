param(
    [Parameter(Mandatory = $true)]
    [string]$PythonExe
)

$predictScript = Join-Path $PSScriptRoot 'predict.py'
$trainScript = Join-Path $PSScriptRoot 'train_models.py'

$dailyAction = New-ScheduledTaskAction -Execute $PythonExe -Argument "`"$predictScript`"" -WorkingDirectory $PSScriptRoot
$dailyTrigger = New-ScheduledTaskTrigger -Daily -At 2:00AM
Register-ScheduledTask -TaskName 'ARTEC-Platform-Predictions' -Action $dailyAction -Trigger $dailyTrigger -Description 'Actualise les indicateurs ML de la plateforme ARTEC.' -Force | Out-Null

$weeklyAction = New-ScheduledTaskAction -Execute $PythonExe -Argument "`"$trainScript`"" -WorkingDirectory $PSScriptRoot
$weeklyTrigger = New-ScheduledTaskTrigger -Weekly -DaysOfWeek Sunday -At 3:00AM
Register-ScheduledTask -TaskName 'ARTEC-Platform-Retrain' -Action $weeklyAction -Trigger $weeklyTrigger -Description 'Réentraîne les modèles ARTEC si le seuil de données est atteint.' -Force | Out-Null

Write-Output 'Tâches ARTEC enregistrées : prédiction quotidienne à 02:00 et réentraînement le dimanche à 03:00.'