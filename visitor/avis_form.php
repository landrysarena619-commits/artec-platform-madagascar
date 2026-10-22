<?php
$publicationId = filter_input(INPUT_GET, 'publication_id', FILTER_VALIDATE_INT) ?: 0;
header('Location: publication.php?id=' . $publicationId . '#avis', true, 302);
exit;