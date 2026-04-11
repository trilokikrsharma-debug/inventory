<?php
/**
 * Restore-safety service for backup imports.
 *
 * Keeps SQL validation, statement splitting, and execution out of the
 * controller so restore rules are reusable and independently testable.
 */
class BackupRestoreService {
    /**
     * @return array<int, string>
     */
    public function validateRestoreSql(string $sqlContent): array {
        $blocked = [
            '/\bGRANT\b/i',
            '/\bREVOKE\b/i',
            '/\bINTO\s+OUTFILE\b/i',
            '/\bINTO\s+DUMPFILE\b/i',
            '/\bLOAD_FILE\s*\(/i',
            '/\bDROP\s+DATABASE\b/i',
            '/\bCREATE\s+USER\b/i',
            '/\bALTER\s+USER\b/i',
            '/\bSET\s+PASSWORD\b/i',
            '/\bSYSTEM\s*\(/i',
            '/\bSHELL\b/i',
            '/\bDEFINER\s*=/i',
            '/\bCREATE\s+TRIGGER\b/i',
            '/\bCREATE\s+PROCEDURE\b/i',
            '/\bCREATE\s+FUNCTION\b/i',
            '/\bCREATE\s+EVENT\b/i',
            '/\bTRUNCATE\s+TABLE\b/i',
        ];

        foreach ($blocked as $pattern) {
            if (preg_match($pattern, $sqlContent)) {
                Helper::securityLog('RESTORE_BLOCKED', 'Prohibited SQL pattern detected: ' . $pattern);
                throw new \RuntimeException('Restore blocked: SQL file contains prohibited statements.');
            }
        }

        $statements = $this->splitSqlStatements($sqlContent);
        if (empty($statements)) {
            throw new \RuntimeException('Restore blocked: SQL file does not contain executable statements.');
        }

        $hasSchemaStatement = false;
        $allowedPrefixes = ['CREATE ', 'INSERT ', 'DROP TABLE', 'SET ', 'START ', 'COMMIT', 'ALTER TABLE', 'LOCK ', 'UNLOCK '];

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') {
                continue;
            }

            $upperStatement = strtoupper(ltrim($statement));
            $isAllowed = false;
            foreach ($allowedPrefixes as $prefix) {
                if (str_starts_with($upperStatement, $prefix)) {
                    $isAllowed = true;
                    break;
                }
            }

            if (!$isAllowed) {
                throw new \RuntimeException('Restore blocked: SQL contains unsupported statement type.');
            }

            if (
                str_starts_with($upperStatement, 'CREATE ')
                || str_starts_with($upperStatement, 'ALTER TABLE')
                || str_starts_with($upperStatement, 'DROP TABLE')
            ) {
                $hasSchemaStatement = true;
            }
        }

        if (!$hasSchemaStatement) {
            throw new \RuntimeException('Restore blocked: SQL file does not look like a full schema backup.');
        }

        return $statements;
    }

    public function executeRestoreStatements(\PDO $pdo, array $statements): int {
        $executed = 0;
        foreach ($statements as $statement) {
            $statement = trim((string)$statement);
            if ($statement === '') {
                continue;
            }

            $pdo->exec($statement);
            $executed++;
        }

        return $executed;
    }

    /**
     * @return array<int, string>
     */
    public function splitSqlStatements(string $sqlContent): array {
        $statements = [];
        $buffer = '';
        $length = strlen($sqlContent);
        $inSingle = false;
        $inDouble = false;
        $inBacktick = false;
        $inLineComment = false;
        $inBlockComment = false;
        $escaped = false;

        for ($i = 0; $i < $length; $i++) {
            $char = $sqlContent[$i];
            $next = $i + 1 < $length ? $sqlContent[$i + 1] : '';

            if ($inLineComment) {
                if ($char === "\n") {
                    $inLineComment = false;
                }
                continue;
            }

            if ($inBlockComment) {
                if ($char === '*' && $next === '/') {
                    $inBlockComment = false;
                    $i++;
                }
                continue;
            }

            if (!$inSingle && !$inDouble && !$inBacktick) {
                if ($char === '-' && $next === '-') {
                    $inLineComment = true;
                    $i++;
                    continue;
                }
                if ($char === '#') {
                    $inLineComment = true;
                    continue;
                }
                if ($char === '/' && $next === '*') {
                    $inBlockComment = true;
                    $i++;
                    continue;
                }
            }

            if ($inSingle) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === "'") {
                    $inSingle = false;
                }
                continue;
            }

            if ($inDouble) {
                $buffer .= $char;
                if ($escaped) {
                    $escaped = false;
                } elseif ($char === '\\') {
                    $escaped = true;
                } elseif ($char === '"') {
                    $inDouble = false;
                }
                continue;
            }

            if ($inBacktick) {
                $buffer .= $char;
                if ($char === '`') {
                    $inBacktick = false;
                }
                continue;
            }

            if ($char === "'") {
                $inSingle = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '"') {
                $inDouble = true;
                $buffer .= $char;
                continue;
            }

            if ($char === '`') {
                $inBacktick = true;
                $buffer .= $char;
                continue;
            }

            if ($char === ';') {
                $statement = trim($buffer);
                if ($statement !== '') {
                    $statements[] = $statement;
                }
                $buffer = '';
                continue;
            }

            $buffer .= $char;
        }

        $tail = trim($buffer);
        if ($tail !== '') {
            $statements[] = $tail;
        }

        return $statements;
    }
}
