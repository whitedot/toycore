<?php

declare(strict_types=1);

$notificationAdminPage = 'deliveries';
$_POST['intent'] = 'delivery_status';

include TOY_ROOT . '/modules/notification/actions/admin-notifications.php';
