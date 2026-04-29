<?php

declare(strict_types=1);

return static function (PDO $pdo, array $context): string {
    unset($pdo);

    if (($context['module_key'] ?? '') !== 'member' || ($context['point_key'] ?? '') !== 'member.login') {
        return '';
    }

    return '<p>Sample output slot content.</p>';
};
