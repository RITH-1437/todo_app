<?php

return "

SET @avatar_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'avatar'
);

SET @avatar_sql = IF(
    @avatar_exists = 0,
    'ALTER TABLE users ADD COLUMN avatar VARCHAR(255) NULL',
    'SELECT 1'
);

PREPARE avatar_stmt FROM @avatar_sql;
EXECUTE avatar_stmt;
DEALLOCATE PREPARE avatar_stmt;

SET @theme_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'theme_preference'
);

SET @theme_sql = IF(
    @theme_exists = 0,
    'ALTER TABLE users ADD COLUMN theme_preference ENUM(''light'', ''dark'') NOT NULL DEFAULT ''dark''',
    'SELECT 1'
);

PREPARE theme_stmt FROM @theme_sql;
EXECUTE theme_stmt;
DEALLOCATE PREPARE theme_stmt;

";
