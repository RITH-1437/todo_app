<?php

require __DIR__ . '/../config/database.php';

$migrationPath = __DIR__ . '/../migrations';

$files = scandir($migrationPath);

foreach ($files as $file) {

    if ($file == '.' || $file == '..') {
        continue;
    }

    // Check migration history
    $stmt = $conn->prepare(
        "SELECT COUNT(*)
        FROM migrations
        WHERE migration = ?"
    );

    $stmt->execute([$file]);

    $exists = $stmt->fetchColumn();

    if ($exists) {

        echo "Skipped: $file (already migrated)". PHP_EOL;

        continue;
    }

    // Execute migration
    $sql = require $migrationPath . '/' . $file;

    $conn->exec($sql);

    // Save migration record
    $insert = $conn->prepare(
        "INSERT INTO migrations (migration)
        VALUES (?)"
    );

    $insert->execute([$file]);

    echo "Migrated: $file ". PHP_EOL;
}