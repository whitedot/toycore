<?php

return [
    'GET /admin' => 'actions/dashboard.php',
    'GET /admin/settings' => 'actions/settings.php',
    'POST /admin/settings' => 'actions/settings.php',
    'GET /admin/modules' => 'actions/modules.php',
    'POST /admin/modules' => 'actions/modules.php',
    'GET /admin/updates' => 'actions/updates.php',
    'POST /admin/updates' => 'actions/updates.php',
    'GET /admin/members' => 'actions/members.php',
    'POST /admin/members' => 'actions/members.php',
    'GET /admin/roles' => 'actions/roles.php',
    'POST /admin/roles' => 'actions/roles.php',
    'GET /admin/audit-logs' => 'actions/audit-logs.php',
    'GET /admin/privacy-requests' => 'actions/privacy-requests.php',
    'POST /admin/privacy-requests' => 'actions/privacy-requests.php',
    'POST /admin/privacy-requests/export' => 'actions/privacy-request-export.php',
    'GET /admin/retention' => 'actions/retention.php',
    'POST /admin/retention' => 'actions/retention.php',
];
