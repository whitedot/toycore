<?php

$primaryRow = is_array($dashboardRows[0] ?? null) ? $dashboardRows[0] : [];
?>

<div class="popup-layer-dashboard-summary admin-card card">
    <div class="popup-layer-dashboard-stack" aria-hidden="true">
        <span></span>
        <span></span>
        <span></span>
    </div>
    <div class="popup-layer-dashboard-content">
        <p>방문자 공지</p>
        <h2><?php echo sr_e($dashboardSectionTitle); ?></h2>
        <strong><?php echo sr_e((string) ($primaryRow['value'] ?? '0')); ?></strong>
        <span><?php echo sr_e((string) ($primaryRow['label'] ?? '활성 팝업')); ?></span>
        <small><?php echo sr_e((string) ($primaryRow['detail'] ?? '임시저장 0')); ?></small>
        <a href="/admin/popup-layers" class="btn btn-outline-default">팝업 목록</a>
    </div>
</div>
