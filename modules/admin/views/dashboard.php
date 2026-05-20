<?php

$adminPageTitle = '관리자 대시보드';
include SR_ROOT . '/modules/admin/views/layout-header.php';
?>

<div class="admin-dashboard-toolbar">
    <button type="button" class="btn btn-surface-default-soft" data-admin-dashboard-manager-toggle aria-expanded="false">
        <?php echo sr_material_icon_html('dashboard_customize'); ?>
        <span>섹션 관리</span>
    </button>
</div>

<section class="admin-card card admin-dashboard-manager" data-admin-dashboard-manager hidden>
    <div class="admin-dashboard-manager-header">
        <h2>섹션 관리</h2>
        <button type="button" class="btn btn-ghost-default btn-icon" data-admin-dashboard-manager-close aria-label="섹션 관리 닫기"><?php echo sr_material_icon_html('close'); ?></button>
    </div>
    <div class="admin-dashboard-manager-list" data-admin-dashboard-manager-list></div>
    <div class="admin-dashboard-manager-actions">
        <button type="button" class="btn btn-outline-default" data-admin-dashboard-visibility-reset>기본값 복원</button>
    </div>
</section>

<div class="admin-dashboard-sections" data-admin-dashboard-sections>
<section class="admin-card admin-list-card card admin-dashboard-site-card admin-dashboard-section" data-admin-dashboard-section="site" data-admin-dashboard-label="사이트" data-admin-dashboard-default-visible="1">
    <div class="card-header">
        <h2 class="card-title">사이트</h2>
        <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="사이트 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
    </div>
    <dl class="admin-dashboard-site-grid">
        <div>
            <dt>이름</dt>
            <dd><?php echo sr_e((string) ($site['name'] ?? '')); ?></dd>
        </div>
        <div>
            <dt>상태</dt>
            <dd><?php echo sr_e(sr_admin_code_label((string) ($site['status'] ?? ''), 'site_status')); ?></dd>
        </div>
        <div>
            <dt>기본 locale</dt>
            <dd><?php echo sr_e((string) ($site['default_locale'] ?? '')); ?></dd>
        </div>
    </dl>
</section>

<section class="admin-card admin-list-card card admin-list-form admin-dashboard-section" data-admin-dashboard-section="install_protection" data-admin-dashboard-label="설치 보호" data-admin-dashboard-default-visible="1">
    <div class="card-header">
        <h2 class="card-title">설치 보호</h2>
        <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="설치 보호 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
    </div>
    <div class="table-wrapper">
    <table class="table">
        <thead class="ui-table-head">
            <tr>
                <th>항목</th>
                <th>상태</th>
                <th>판정</th>
                <th>상세</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($installProtectionSummary as $summary) { ?>
                <tr>
                    <td><?php echo sr_e((string) $summary['label']); ?></td>
                    <td><?php echo sr_e((string) $summary['value']); ?></td>
                    <td><?php echo sr_e((string) $summary['state']); ?></td>
                    <td><?php echo sr_e((string) $summary['detail']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</section>

<section class="admin-card admin-list-card card admin-list-form admin-dashboard-section" data-admin-dashboard-section="sensitive_settings" data-admin-dashboard-label="고위험 설정" data-admin-dashboard-default-visible="1">
    <div class="card-header">
        <h2 class="card-title">고위험 설정</h2>
        <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="고위험 설정 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
    </div>
    <div class="table-wrapper">
    <table class="table">
        <thead class="ui-table-head">
            <tr>
                <th>항목</th>
                <th>키</th>
                <th>상태</th>
                <th>판정</th>
                <th>수정일</th>
                <th>상세</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sensitiveSettingSummary as $summary) { ?>
                <tr>
                    <td><?php echo sr_e((string) $summary['label']); ?></td>
                    <td><?php echo sr_e((string) $summary['setting_key']); ?></td>
                    <td><?php echo sr_e((string) $summary['value']); ?></td>
                    <td><?php echo sr_e((string) $summary['state']); ?></td>
                    <td><?php echo sr_e((string) $summary['updated_at']); ?></td>
                    <td><?php echo sr_e((string) $summary['detail']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</section>

<section class="admin-card admin-list-card card admin-list-form admin-dashboard-section" data-admin-dashboard-section="auth_runtime" data-admin-dashboard-label="인증 런타임" data-admin-dashboard-default-visible="1">
    <div class="card-header">
        <h2 class="card-title">인증 런타임</h2>
        <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="인증 런타임 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
    </div>
    <div class="table-wrapper">
    <table class="table">
        <thead class="ui-table-head">
            <tr>
                <th>항목</th>
                <th>상태</th>
                <th>판정</th>
                <th>상세</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($authRuntimeSummary as $summary) { ?>
                <tr>
                    <td><?php echo sr_e((string) $summary['label']); ?></td>
                    <td><?php echo sr_e((string) $summary['value']); ?></td>
                    <td><?php echo sr_e((string) $summary['state']); ?></td>
                    <td><?php echo sr_e((string) $summary['detail']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</section>

<?php if ($recoveryMarkers !== [] || (int) $moduleBackupSummary['count'] > 0) { ?>
    <section class="admin-card admin-list-card card admin-list-form admin-dashboard-section" data-admin-dashboard-section="recovery" data-admin-dashboard-label="복구 상태" data-admin-dashboard-default-visible="1">
        <div class="card-header">
            <h2 class="card-title">복구 상태</h2>
            <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="복구 상태 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
        </div>

        <?php if ($recoveryMarkers !== []) { ?>
            <div class="table-wrapper">
            <table class="table">
                <thead class="ui-table-head">
                    <tr>
                        <th>항목</th>
                        <th>단계</th>
                        <th>대상</th>
                        <th>기록 시각</th>
                        <th>요약</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recoveryMarkers as $marker) { ?>
                        <?php
                        $target = trim((string) ($marker['scope'] ?? '') . ' ' . (string) ($marker['module_key'] ?? '') . ' ' . (string) ($marker['version'] ?? ''));
                        ?>
                        <tr>
                            <td><?php echo sr_e((string) $marker['label']); ?></td>
                            <td><?php echo sr_e((string) $marker['stage']); ?></td>
                            <td><?php echo sr_e($target); ?></td>
                            <td><?php echo sr_e((string) $marker['recorded_at']); ?></td>
                            <td><?php echo sr_e((string) $marker['message']); ?></td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
            </div>
        <?php } ?>

        <?php if ((int) $moduleBackupSummary['count'] > 0) { ?>
            <p>
                모듈 백업 <?php echo sr_e((string) $moduleBackupSummary['count']); ?>개
                <?php if ((string) $moduleBackupSummary['latest_name'] !== '') { ?>
                    / 최근 백업:
                    <?php echo sr_e((string) $moduleBackupSummary['latest_name']); ?>
                    <?php echo sr_e((string) $moduleBackupSummary['latest_modified_at']); ?>
                <?php } ?>
            </p>
        <?php } ?>
    </section>
<?php } ?>

<?php if (($moduleDashboardSections ?? []) !== []) { ?>
    <?php foreach ($moduleDashboardSections as $section) { ?>
        <section class="admin-dashboard-section admin-dashboard-module-section" data-admin-dashboard-section="module_<?php echo sr_e((string) $section['key']); ?>" data-admin-dashboard-label="<?php echo sr_e((string) $section['title']); ?>" data-admin-dashboard-default-visible="<?php echo !empty($section['default_visible']) ? '1' : '0'; ?>" data-admin-dashboard-layout="<?php echo sr_e((string) ($section['layout'] ?? 'table')); ?>"<?php echo !empty($section['default_visible']) ? '' : ' hidden'; ?>>
            <button type="button" class="admin-dashboard-section-handle admin-dashboard-module-section-handle" draggable="true" aria-label="<?php echo sr_e((string) $section['title']); ?> 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
            <?php echo sr_admin_dashboard_module_section_body($pdo, $section); ?>
        </section>
    <?php } ?>
<?php } ?>

<section class="admin-card admin-list-card card admin-list-form admin-dashboard-section" data-admin-dashboard-section="modules" data-admin-dashboard-label="모듈" data-admin-dashboard-default-visible="1">
    <div class="card-header">
        <h2 class="card-title">모듈</h2>
        <button type="button" class="admin-dashboard-section-handle" draggable="true" aria-label="모듈 섹션 이동"><?php echo sr_material_icon_html('apps', 'admin-dashboard-section-handle-icon'); ?></button>
    </div>
    <div class="table-wrapper">
    <table class="table">
        <thead class="ui-table-head">
            <tr>
                <th>키</th>
                <th>이름</th>
                <th>버전</th>
                <th>상태</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($modules as $module) { ?>
                <tr>
                    <td><?php echo sr_e((string) $module['module_key']); ?></td>
                    <td><?php echo sr_e(sr_admin_module_name_label((string) $module['name'])); ?></td>
                    <td><?php echo sr_e((string) $module['version']); ?></td>
                    <td><?php echo sr_e(sr_admin_code_label((string) $module['status'], 'module_status')); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    </div>
</section>
</div>

<?php include SR_ROOT . '/modules/admin/views/layout-footer.php'; ?>
