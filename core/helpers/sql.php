<?php

declare(strict_types=1);

function toy_execute_sql_file(PDO $pdo, string $file): void
{
    if (!is_file($file)) {
        throw new RuntimeException('SQL file does not exist: ' . $file);
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        throw new RuntimeException('SQL file cannot be read: ' . $file);
    }

    foreach (toy_split_sql_statements($sql) as $statement) {
        $pdo->exec($statement);
    }
}

function toy_split_sql_statements(string $sql): array
{
    $statements = [];
    $statement = '';
    $quote = '';
    $lineComment = false;
    $blockComment = false;
    $length = strlen($sql);

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $i + 1 < $length ? $sql[$i + 1] : '';

        if ($lineComment) {
            $statement .= $char;
            if ($char === "\n") {
                $lineComment = false;
            }
            continue;
        }

        if ($blockComment) {
            $statement .= $char;
            if ($char === '*' && $next === '/') {
                $statement .= $next;
                $i++;
                $blockComment = false;
            }
            continue;
        }

        if ($quote !== '') {
            $statement .= $char;
            if ($char === '\\' && $quote !== '`' && $next !== '') {
                $statement .= $next;
                $i++;
                continue;
            }

            if ($char === $quote) {
                if ($next === $quote) {
                    $statement .= $next;
                    $i++;
                    continue;
                }
                $quote = '';
            }
            continue;
        }

        if ($char === '\'' || $char === '"' || $char === '`') {
            $quote = $char;
            $statement .= $char;
            continue;
        }

        if ($char === '-' && $next === '-' && ($i + 2 >= $length || ctype_space($sql[$i + 2]))) {
            $statement .= $char . $next;
            $i++;
            $lineComment = true;
            continue;
        }

        if ($char === '#') {
            $statement .= $char;
            $lineComment = true;
            continue;
        }

        if ($char === '/' && $next === '*') {
            $statement .= $char . $next;
            $i++;
            $blockComment = true;
            continue;
        }

        if ($char === ';') {
            $trimmed = trim($statement);
            if ($trimmed !== '') {
                $statements[] = $trimmed;
            }
            $statement = '';
            continue;
        }

        $statement .= $char;
    }

    $trimmed = trim($statement);
    if ($trimmed !== '') {
        $statements[] = $trimmed;
    }

    return $statements;
}

function toy_record_schema_version(PDO $pdo, string $scope, string $moduleKey, string $version): void
{
    $stmt = $pdo->prepare(
        'INSERT IGNORE INTO toy_schema_versions (scope, module_key, version, applied_at)
         VALUES (:scope, :module_key, :version, :applied_at)'
    );
    $stmt->execute([
        'scope' => $scope,
        'module_key' => $moduleKey,
        'version' => $version,
        'applied_at' => toy_now(),
    ]);
}
