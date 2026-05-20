<?php

$primaryRow = is_array($dashboardRows[0] ?? null) ? $dashboardRows[0] : [];
?>

<div class="notification-dashboard-summary admin-card card">
    <div class="notification-dashboard-head">
        <p>발송 흐름</p>
        <h2><?php echo sr_e($dashboardSectionTitle); ?></h2>
    </div>
    <div class="notification-dashboard-count">
        <span><?php echo sr_e((string) ($primaryRow['label'] ?? '전체 알림')); ?></span>
        <strong><?php echo sr_e((string) ($primaryRow['value'] ?? '0')); ?></strong>
    </div>
    <div class="notification-dashboard-detail">
        <span><?php echo sr_e((string) ($primaryRow['detail'] ?? '발송 대기 0')); ?></span>
        <a href="/admin/notifications" class="btn btn-surface-default-soft">알림 목록</a>
    </div>
</div>
