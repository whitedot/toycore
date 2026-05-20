<?php

$postRow = is_array($dashboardRows[0] ?? null) ? $dashboardRows[0] : [];
$reportRow = is_array($dashboardRows[1] ?? null) ? $dashboardRows[1] : [];
?>

<div class="community-dashboard-summary admin-card card">
    <div class="community-dashboard-main">
        <p>커뮤니티 활동</p>
        <h2><?php echo sr_e($dashboardSectionTitle); ?></h2>
        <strong><?php echo sr_e((string) ($postRow['value'] ?? '0')); ?></strong>
        <span><?php echo sr_e((string) ($postRow['label'] ?? '게시글')); ?></span>
        <small><?php echo sr_e((string) ($postRow['detail'] ?? '댓글 0')); ?></small>
    </div>
    <div class="community-dashboard-side">
        <div>
            <span><?php echo sr_e((string) ($reportRow['label'] ?? '신고')); ?></span>
            <strong><?php echo sr_e((string) ($reportRow['value'] ?? '0')); ?></strong>
            <small><?php echo sr_e((string) ($reportRow['detail'] ?? '게시판 0')); ?></small>
        </div>
        <a href="/admin/community/reports" class="btn btn-outline-default">신고 확인</a>
    </div>
    <div class="community-dashboard-links">
        <a href="/admin/community/posts" class="btn btn-surface-default-soft">게시글</a>
        <a href="/admin/community/boards" class="btn btn-surface-default-soft">게시판</a>
    </div>
</div>
