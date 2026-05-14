<?php

$adminPageTitle = '디자인 토큰';
$adminPageSubtitle = 'assets/common.css에 정의된 토큰과 시맨틱 클래스를 한 화면에서 확인합니다.';
$adminContainerClass = 'admin-page-design-tokens';

function sr_admin_design_tokens_view_codes(array $values): void
{
    if ($values === []) {
        echo '<span class="admin-design-tokens-empty">없음</span>';
        return;
    }

    foreach ($values as $value) {
        echo '<code class="admin-design-tokens-code-line">' . sr_e((string) $value) . '</code>';
    }
}

function sr_admin_design_tokens_view_class_list(array $classes): void
{
    if ($classes === []) {
        echo '<p class="admin-design-tokens-empty">표시할 클래스가 없습니다.</p>';
        return;
    }

    foreach ($classes as $class) {
        echo '<code class="admin-design-tokens-class-chip">.' . sr_e((string) $class['name']) . '</code>';
    }
}

function sr_admin_design_tokens_view_has_class(array $availableClassNames, string $className): bool
{
    return isset($availableClassNames[$className]);
}

$designAvailableClassNames = [];
foreach ($designClassRecords as $classRecord) {
    $designAvailableClassNames[(string) $classRecord['name']] = true;
}

$tokenGroupLabels = array_values(array_unique(array_merge($designTokenCategoryOrder, array_keys($designTokenGroups))));
$classGroupLabels = array_values(array_unique(array_merge($designClassCategoryOrder, array_keys($designClassGroups))));
$buttonSampleGroups = [
    'Solid' => ['btn-solid-primary', 'btn-solid-secondary', 'btn-solid-success', 'btn-solid-danger', 'btn-solid-warning', 'btn-solid-info', 'btn-solid-light', 'btn-solid-dark'],
    'Outline' => ['btn-outline-primary', 'btn-outline-secondary', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-warning', 'btn-outline-info', 'btn-outline-light', 'btn-outline-dark'],
    'Soft' => ['btn-soft-primary', 'btn-soft-secondary', 'btn-soft-success', 'btn-soft-danger', 'btn-soft-warning', 'btn-soft-info', 'btn-soft-dark'],
    'Ghost' => ['btn-ghost-primary', 'btn-ghost-secondary', 'btn-ghost-success', 'btn-ghost-danger', 'btn-ghost-warning', 'btn-ghost-info', 'btn-ghost-dark'],
    'Surface' => ['btn-surface', 'btn-surface-default', 'btn-surface-default-soft', 'btn-subtle-light', 'btn-subtle-light-muted'],
    'Project' => ['btn-primary', 'btn-secondary', 'btn-tertiary', 'btn-inline'],
];

include SR_ROOT . '/modules/admin/views/layout-header.php';
?>

<div class="admin-design-tokens">
    <nav class="tab-nav-bordered admin-design-tokens-nav" aria-label="디자인 토큰 미리보기 목차">
        <a class="tab-trigger-underline active" href="#ds-summary">요약</a>
        <a class="tab-trigger-underline" href="#ds-colors">색상</a>
        <a class="tab-trigger-underline" href="#ds-type">타이포그래피</a>
        <a class="tab-trigger-underline" href="#ds-tokens">토큰 전체</a>
        <a class="tab-trigger-underline" href="#ds-buttons">버튼</a>
        <a class="tab-trigger-underline" href="#ds-forms">폼</a>
        <a class="tab-trigger-underline" href="#ds-data">카드/테이블</a>
        <a class="tab-trigger-underline" href="#ds-overlays">탭/모달</a>
        <a class="tab-trigger-underline" href="#ds-classes">클래스 전체</a>
    </nav>

    <section id="ds-summary" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>common.css 요약</h2>
            <p>이 화면은 현재 로드되는 <code>assets/common.css</code>의 디자인 토큰과 클래스를 그대로 미리보기합니다.</p>
        </div>
        <div class="admin-design-tokens-summary">
            <div>
                <span>CSS 파일</span>
                <strong><?php echo sr_e((string) $designTokenSummary['css_path']); ?></strong>
            </div>
            <div>
                <span>토큰</span>
                <strong><?php echo sr_e((string) $designTokenSummary['token_count']); ?></strong>
            </div>
            <div>
                <span>클래스</span>
                <strong><?php echo sr_e((string) $designTokenSummary['class_count']); ?></strong>
            </div>
        </div>
    </section>

    <section id="ds-colors" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>색상</h2>
            <p>색상 토큰은 실제 CSS 변수 값으로 스와치를 렌더링합니다.</p>
        </div>
        <div class="admin-design-tokens-color-grid">
            <?php foreach (($designTokenGroups['색상'] ?? []) as $token) { ?>
                <div class="admin-design-tokens-swatch">
                    <span class="admin-design-tokens-swatch-color" style="background: var(<?php echo sr_e((string) $token['name']); ?>);"></span>
                    <strong><?php echo sr_e((string) $token['name']); ?></strong>
                    <?php sr_admin_design_tokens_view_codes($token['root_values'] ?: $token['values']); ?>
                    <?php if ($token['dark_values'] !== []) { ?>
                        <span class="admin-design-tokens-muted">dark</span>
                        <?php sr_admin_design_tokens_view_codes($token['dark_values']); ?>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <section id="ds-type" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>타이포그래피</h2>
            <p>폰트, 글자 크기, 행간, 굵기 토큰을 실제 텍스트 크기와 함께 확인합니다.</p>
        </div>
        <div class="admin-design-tokens-type-grid">
            <?php foreach (($designTokenGroups['타이포그래피'] ?? []) as $token) { ?>
                <div>
                    <?php if (str_starts_with((string) $token['name'], '--text-') && !str_contains((string) $token['name'], 'line-height')) { ?>
                        <p style="font-size: var(<?php echo sr_e((string) $token['name']); ?>);">Saanraan 디자인 토큰</p>
                    <?php } elseif (str_starts_with((string) $token['name'], '--font-weight-')) { ?>
                        <p style="font-weight: var(<?php echo sr_e((string) $token['name']); ?>);">Saanraan 디자인 토큰</p>
                    <?php } else { ?>
                        <p>Saanraan 디자인 토큰</p>
                    <?php } ?>
                    <strong><?php echo sr_e((string) $token['name']); ?></strong>
                    <?php sr_admin_design_tokens_view_codes($token['root_values'] ?: $token['values']); ?>
                </div>
            <?php } ?>
        </div>
    </section>

    <section id="ds-tokens" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>토큰 전체</h2>
            <p><code>assets/common.css</code>의 CSS custom property를 카테고리별로 모두 표시합니다.</p>
        </div>
        <?php foreach ($tokenGroupLabels as $groupLabel) { ?>
            <?php $tokens = $designTokenGroups[$groupLabel] ?? []; ?>
            <?php if ($tokens === []) { continue; } ?>
            <div class="admin-design-tokens-row">
                <h3><?php echo sr_e((string) $groupLabel); ?> <span class="badge badge-label"><?php echo sr_e((string) count($tokens)); ?></span></h3>
                <div class="table-wrapper admin-design-tokens-table">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>미리보기</th>
                                <th>토큰</th>
                                <th>:root</th>
                                <th>dark</th>
                                <th>@property</th>
                                <th>전체값</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tokens as $token) { ?>
                                <tr>
                                    <td class="admin-design-tokens-preview-cell">
                                        <?php if ($token['values'] !== [] && $token['category'] === '색상') { ?>
                                            <span class="admin-design-tokens-token-preview admin-design-tokens-token-preview-color" style="background: var(<?php echo sr_e((string) $token['name']); ?>);"></span>
                                        <?php } elseif ($token['values'] !== [] && $token['category'] === '그림자') { ?>
                                            <span class="admin-design-tokens-token-preview" style="box-shadow: var(<?php echo sr_e((string) $token['name']); ?>);"></span>
                                        <?php } elseif ($token['values'] !== [] && $token['category'] === '모서리') { ?>
                                            <span class="admin-design-tokens-token-preview" style="border-radius: var(<?php echo sr_e((string) $token['name']); ?>);"></span>
                                        <?php } else { ?>
                                            <span class="admin-design-tokens-token-preview"></span>
                                        <?php } ?>
                                    </td>
                                    <td><code><?php echo sr_e((string) $token['name']); ?></code></td>
                                    <td><?php sr_admin_design_tokens_view_codes($token['root_values']); ?></td>
                                    <td><?php sr_admin_design_tokens_view_codes($token['dark_values']); ?></td>
                                    <td><?php sr_admin_design_tokens_view_codes($token['property_values']); ?></td>
                                    <td><?php sr_admin_design_tokens_view_codes($token['values']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php } ?>
    </section>

    <section id="ds-buttons" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>버튼과 배지</h2>
            <p>버튼과 배지는 <code>common.css</code>에 정의된 시맨틱 클래스 조합으로 렌더링합니다.</p>
        </div>
        <?php foreach ($buttonSampleGroups as $sampleGroupLabel => $sampleClasses) { ?>
            <div class="admin-design-tokens-row">
                <h3><?php echo sr_e((string) $sampleGroupLabel); ?></h3>
                <div class="admin-design-tokens-preview-grid">
                    <?php foreach ($sampleClasses as $sampleClass) { ?>
                        <?php if (!sr_admin_design_tokens_view_has_class($designAvailableClassNames, $sampleClass)) { continue; } ?>
                        <div class="admin-design-tokens-preview-item">
                            <?php if ($sampleClass === 'btn-inline') { ?>
                                <button type="button" class="btn-inline">btn-inline</button>
                            <?php } else { ?>
                                <button type="button" class="btn <?php echo sr_e($sampleClass); ?>"><?php echo sr_e($sampleClass); ?></button>
                            <?php } ?>
                            <code>.<?php echo sr_e($sampleClass); ?></code>
                        </div>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
        <div class="admin-design-tokens-row">
            <h3>Size / Shape</h3>
            <div class="admin-design-tokens-preview-grid">
                <div class="admin-design-tokens-preview-item"><button type="button" class="btn btn-sm btn-outline-primary">btn-sm</button><code>.btn .btn-sm</code></div>
                <div class="admin-design-tokens-preview-item"><button type="button" class="btn btn-lg btn-outline-primary">btn-lg</button><code>.btn .btn-lg</code></div>
                <div class="admin-design-tokens-preview-item"><button type="button" class="btn btn-pill btn-outline-primary">btn-pill</button><code>.btn .btn-pill</code></div>
                <div class="admin-design-tokens-preview-item"><button type="button" class="btn btn-icon btn-outline-primary" aria-label="아이콘"><span class="close-icon" aria-hidden="true"></span></button><code>.btn .btn-icon</code></div>
            </div>
        </div>
        <div class="admin-design-tokens-row">
            <h3>Badges</h3>
            <div class="admin-design-tokens-inline">
                <span class="badge">badge</span>
                <span class="badge badge-label">badge-label</span>
            </div>
            <div class="admin-design-tokens-class-list">
                <?php sr_admin_design_tokens_view_class_list($designButtonClasses); ?>
                <?php sr_admin_design_tokens_view_class_list($designBadgeClasses); ?>
            </div>
        </div>
    </section>

    <section id="ds-forms" class="admin-design-tokens-panel ui-form-theme">
        <div class="admin-design-tokens-panel-header">
            <h2>폼 컨트롤</h2>
            <p>입력, 선택, 체크, 라디오, 스위치, 범위, 파일 입력 계열을 표시합니다.</p>
        </div>
        <div class="admin-design-tokens-form-grid">
            <label>
                <span class="form-label">form-input</span>
                <input type="text" class="form-input" value="Saanraan">
            </label>
            <label>
                <span class="form-label">form-input-sm</span>
                <input type="text" class="form-input form-input-sm" value="Small">
            </label>
            <label>
                <span class="form-label">form-input-lg</span>
                <input type="text" class="form-input form-input-lg" value="Large">
            </label>
            <label>
                <span class="form-label">form-select</span>
                <select class="form-select">
                    <option>기본 옵션</option>
                    <option>보조 옵션</option>
                </select>
            </label>
            <label class="admin-design-tokens-form-wide">
                <span class="form-label">form-textarea</span>
                <textarea class="form-textarea" rows="4">textarea sample</textarea>
            </label>
            <label>
                <span class="form-label">file form-input</span>
                <input type="file" class="form-input">
            </label>
        </div>
        <div class="admin-design-tokens-inline admin-design-tokens-control-row">
            <label class="af-check form-label"><input type="checkbox" class="form-checkbox" checked> form-checkbox</label>
            <label class="af-check form-label"><input type="radio" name="design_tokens_radio" class="form-radio" checked> form-radio</label>
            <label class="af-check form-label"><input type="checkbox" class="form-switch" checked> form-switch</label>
            <input type="range" class="form-range" value="60" aria-label="form-range">
        </div>
        <div class="admin-design-tokens-row">
            <h3>Form Classes <span class="badge badge-label"><?php echo sr_e((string) count($designFormClasses)); ?></span></h3>
            <div class="admin-design-tokens-class-list">
                <?php sr_admin_design_tokens_view_class_list($designFormClasses); ?>
                <?php sr_admin_design_tokens_view_class_list($designFeedbackClasses); ?>
            </div>
        </div>
    </section>

    <section id="ds-data" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>카드와 테이블</h2>
            <p>카드, 테이블, 페이지네이션 계열을 실제 구조로 표시합니다.</p>
        </div>
        <div class="admin-design-tokens-grid">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">card-header</h3>
                    <span class="badge badge-label">card</span>
                </div>
                <div class="card-body">
                    <p>card-body</p>
                </div>
                <div class="card-footer">
                    <button type="button" class="btn btn-surface-default-soft">취소</button>
                    <button type="button" class="btn btn-solid-primary">저장</button>
                </div>
            </div>
            <div>
                <div class="table-wrapper">
                    <table class="table table-hover table-striped">
                        <thead>
                            <tr>
                                <th>항목</th>
                                <th>상태</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>table-hover</td>
                                <td><span class="badge">기본</span></td>
                            </tr>
                            <tr>
                                <td>table-striped</td>
                                <td><span class="badge badge-label">라벨</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <ul class="pagination admin-design-tokens-pagination" aria-label="페이지네이션 미리보기">
                    <li class="page-item disabled"><span class="page-link">‹</span></li>
                    <li class="page-item active"><span class="page-link">1</span></li>
                    <li class="page-item"><a class="page-link" href="#ds-data">2</a></li>
                    <li class="page-item"><a class="page-link" href="#ds-data">›</a></li>
                </ul>
            </div>
        </div>
        <div class="admin-design-tokens-row">
            <h3>Data Classes</h3>
            <div class="admin-design-tokens-class-list">
                <?php sr_admin_design_tokens_view_class_list($designCardClasses); ?>
                <?php sr_admin_design_tokens_view_class_list($designTableClasses); ?>
            </div>
        </div>
    </section>

    <section id="ds-overlays" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>탭, 드롭다운, 모달</h2>
            <p>내비게이션과 오버레이 계열을 실제 구조로 표시합니다.</p>
        </div>
        <div class="admin-design-tokens-grid">
            <div>
                <nav class="tab-nav-bordered-tight" aria-label="탭 미리보기">
                    <button type="button" class="tab-trigger-underline active">전체</button>
                    <button type="button" class="tab-trigger-underline">활성</button>
                    <button type="button" class="tab-trigger-underline">보류</button>
                </nav>
                <div class="tab-panel-space">
                    <p>tab-panel-space</p>
                </div>
            </div>
            <div class="dropdown hs-dropdown admin-design-tokens-dropdown-sample">
                <button type="button" class="dropdown-toggle hs-dropdown-toggle btn btn-surface" aria-haspopup="menu" aria-expanded="true">
                    옵션 선택 <span class="dropdown-caret" aria-hidden="true">⌄</span>
                </button>
                <div class="hs-dropdown-menu admin-design-tokens-dropdown-menu-sample" role="menu" aria-orientation="vertical">
                    <a class="dropdown-item" href="#ds-overlays">프로필 설정</a>
                    <a class="dropdown-item" href="#ds-overlays">알림</a>
                    <span class="dropdown-divider"></span>
                    <a class="dropdown-item" href="#ds-overlays">로그아웃</a>
                </div>
            </div>
            <div class="modal-content admin-design-tokens-modal-sample">
                <div class="modal-header">
                    <strong class="modal-title">modal-title</strong>
                    <button type="button" class="btn btn-sm btn-icon modal-close" aria-label="닫기">
                        <span class="close-icon" aria-hidden="true"></span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>modal-body</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-surface-default-soft">취소</button>
                    <button type="button" class="btn btn-solid-primary">확인</button>
                </div>
            </div>
            <div class="admin-design-tokens-feedback-stack">
                <div class="admin-flash-message admin-flash-message-success" data-admin-toast>
                    <strong>저장되었습니다.</strong>
                    <span>admin-flash-message-success</span>
                    <button type="button" class="btn btn-sm btn-icon" data-admin-toast-close aria-label="닫기"><span class="close-icon" aria-hidden="true"></span></button>
                </div>
                <div class="admin-flash-message admin-flash-message-error" data-admin-toast>
                    <strong>처리하지 못했습니다.</strong>
                    <span>admin-flash-message-error</span>
                    <button type="button" class="btn btn-sm btn-icon" data-admin-toast-close aria-label="닫기"><span class="close-icon" aria-hidden="true"></span></button>
                </div>
            </div>
        </div>
        <div class="admin-design-tokens-row">
            <h3>Navigation / Overlay Classes</h3>
            <div class="admin-design-tokens-class-list">
                <?php sr_admin_design_tokens_view_class_list($designTabClasses); ?>
                <?php sr_admin_design_tokens_view_class_list($designDropdownClasses); ?>
                <?php sr_admin_design_tokens_view_class_list($designModalClasses); ?>
            </div>
        </div>
    </section>

    <section id="ds-classes" class="admin-design-tokens-panel">
        <div class="admin-design-tokens-panel-header">
            <h2>클래스 전체</h2>
            <p><code>assets/common.css</code>에 정의된 클래스 선택자를 그룹별로 모두 나열합니다.</p>
        </div>
        <div class="admin-design-tokens-class-groups">
            <?php foreach ($classGroupLabels as $groupLabel) { ?>
                <?php $classes = $designClassGroups[$groupLabel] ?? []; ?>
                <?php if ($classes === []) { continue; } ?>
                <section class="admin-design-tokens-class-group">
                    <h3><?php echo sr_e((string) $groupLabel); ?> <span class="badge badge-label"><?php echo sr_e((string) count($classes)); ?></span></h3>
                    <div class="admin-design-tokens-class-list">
                        <?php sr_admin_design_tokens_view_class_list($classes); ?>
                    </div>
                </section>
            <?php } ?>
        </div>
    </section>
</div>

<?php include SR_ROOT . '/modules/admin/views/layout-footer.php'; ?>
