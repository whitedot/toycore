<?php

return [
    'GET /login' => 'actions/login.php',
    'POST /login' => 'actions/login.php',
    'GET /register' => 'actions/register.php',
    'POST /register' => 'actions/register.php',
    'GET /account' => 'actions/account.php',
    'POST /account' => 'actions/account.php',
    'GET /account/withdraw' => 'actions/withdraw.php',
    'POST /account/withdraw' => 'actions/withdraw.php',
    'GET /account/privacy-requests' => 'actions/privacy-requests.php',
    'POST /account/privacy-requests' => 'actions/privacy-requests.php',
    'POST /account/privacy-export' => 'actions/privacy-export.php',
    'POST /account/email-verification' => 'actions/email-verification-request.php',
    'GET /email/verify' => 'actions/email-verify.php',
    'GET /password/reset' => 'actions/password-reset-request.php',
    'POST /password/reset' => 'actions/password-reset-request.php',
    'GET /password/reset/confirm' => 'actions/password-reset.php',
    'POST /password/reset/confirm' => 'actions/password-reset.php',
    'POST /logout' => 'actions/logout.php',
];
