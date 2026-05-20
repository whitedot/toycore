<?php

$primaryRow = is_array($dashboardRows[0] ?? null) ? $dashboardRows[0] : [];
?>

<div class="banner-dashboard-summary admin-card card">
    <div class="banner-dashboard-preview" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="banner-dashboard-body">
        <p class="banner-dashboard-kicker">노출 슬롯</p>
        <h2><?php echo sr_e($dashboardSectionTitle); ?></h2>
        <div class="banner-dashboard-value">
            <strong><?php echo sr_e((string) ($primaryRow['value'] ?? '0')); ?></strong>
            <span><?php echo sr_e((string) ($primaryRow['label'] ?? '활성 배너')); ?></span>
        </div>
        <p><?php echo sr_e((string) ($primaryRow['detail'] ?? '공개 화면에 연결된 배너 상태를 확인합니다.')); ?></p>
        <a href="/admin/banners" class="btn btn-surface-default-soft">배너 목록</a>
    </div>
</div>
