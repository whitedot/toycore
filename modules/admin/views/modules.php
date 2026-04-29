<?php

$adminPageTitle = '모듈 관리';
include TOY_ROOT . '/modules/admin/views/layout-header.php';
?>

<?php if ($notice !== '') { ?>
    <p><?php echo toy_e($notice); ?></p>
<?php } ?>

<?php if ($errors !== []) { ?>
    <ul>
        <?php foreach ($errors as $error) { ?>
            <li><?php echo toy_e($error); ?></li>
        <?php } ?>
    </ul>
<?php } ?>

<table>
    <thead>
        <tr>
            <th>키</th>
            <th>이름</th>
            <th>유형</th>
            <th>설치 버전</th>
            <th>코드 버전</th>
            <th>Toycore 최소</th>
            <th>Toycore 검증</th>
            <th>상태</th>
            <th>기본 포함</th>
            <th>설치일</th>
            <th>설명</th>
            <th>변경</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules as $module) { ?>
            <?php $isRequired = in_array((string) $module['module_key'], $requiredModules, true); ?>
            <tr>
                <td><?php echo toy_e((string) $module['module_key']); ?></td>
                <td><?php echo toy_e((string) $module['name']); ?></td>
                <td><?php echo toy_e((string) ($module['code_type'] ?? 'module')); ?></td>
                <td><?php echo toy_e((string) $module['version']); ?></td>
                <td><?php echo toy_e((string) ($module['code_version'] !== '' ? $module['code_version'] : '-')); ?></td>
                <td><?php echo toy_e((string) ($module['toycore_min_version'] !== '' ? $module['toycore_min_version'] : '-')); ?></td>
                <td><?php echo toy_e((string) ($module['toycore_tested_with'] !== '' ? $module['toycore_tested_with'] : '-')); ?></td>
                <td><?php echo toy_e((string) $module['status']); ?></td>
                <td><?php echo !empty($module['is_bundled']) ? 'yes' : 'no'; ?></td>
                <td><?php echo toy_e((string) ($module['installed_at'] ?? '')); ?></td>
                <td><?php echo toy_e((string) ($module['description'] !== '' ? $module['description'] : '-')); ?></td>
                <td>
                    <?php if (in_array((string) $module['status'], ['failed', 'installing'], true)) { ?>
                        <form method="post" action="<?php echo toy_e(toy_url('/admin/modules')); ?>">
                            <?php echo toy_csrf_field(); ?>
                            <input type="hidden" name="intent" value="install">
                            <input type="hidden" name="module_key" value="<?php echo toy_e((string) $module['module_key']); ?>">
                            <select name="status">
                                <?php foreach ($allowedInstallStatuses as $status) { ?>
                                    <option value="<?php echo toy_e($status); ?>"<?php echo $status === 'enabled' ? ' selected' : ''; ?>>
                                        <?php echo toy_e($status); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="submit">재설치</button>
                        </form>
                    <?php } else { ?>
                        <form method="post" action="<?php echo toy_e(toy_url('/admin/modules')); ?>">
                            <?php echo toy_csrf_field(); ?>
                            <input type="hidden" name="intent" value="status">
                            <input type="hidden" name="module_key" value="<?php echo toy_e((string) $module['module_key']); ?>">
                            <select name="status"<?php echo $isRequired ? ' disabled' : ''; ?>>
                                <?php foreach ($allowedStatuses as $status) { ?>
                                    <option value="<?php echo toy_e($status); ?>"<?php echo $module['status'] === $status ? ' selected' : ''; ?>>
                                        <?php echo toy_e($status); ?>
                                    </option>
                                <?php } ?>
                            </select>
                            <button type="submit"<?php echo $isRequired ? ' disabled' : ''; ?>>저장</button>
                        </form>
                    <?php } ?>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<section>
    <h2>설치 가능한 모듈</h2>
    <?php if ($installableModules === []) { ?>
        <p>설치 가능한 새 모듈이 없습니다.</p>
    <?php } else { ?>
        <table>
            <thead>
                <tr>
                    <th>키</th>
                    <th>이름</th>
                    <th>유형</th>
                    <th>코드 버전</th>
                    <th>Toycore 최소</th>
                    <th>Toycore 검증</th>
                    <th>설명</th>
                    <th>설치</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($installableModules as $module) { ?>
                    <tr>
                        <td><?php echo toy_e((string) $module['module_key']); ?></td>
                        <td><?php echo toy_e((string) $module['name']); ?></td>
                        <td><?php echo toy_e((string) $module['type']); ?></td>
                        <td><?php echo toy_e((string) ($module['version'] !== '' ? $module['version'] : '-')); ?></td>
                        <td><?php echo toy_e((string) ($module['toycore_min_version'] !== '' ? $module['toycore_min_version'] : '-')); ?></td>
                        <td><?php echo toy_e((string) ($module['toycore_tested_with'] !== '' ? $module['toycore_tested_with'] : '-')); ?></td>
                        <td><?php echo toy_e((string) ($module['description'] !== '' ? $module['description'] : '-')); ?></td>
                        <td>
                            <form method="post" action="<?php echo toy_e(toy_url('/admin/modules')); ?>">
                                <?php echo toy_csrf_field(); ?>
                                <input type="hidden" name="intent" value="install">
                                <input type="hidden" name="module_key" value="<?php echo toy_e((string) $module['module_key']); ?>">
                                <select name="status">
                                    <?php foreach ($allowedInstallStatuses as $status) { ?>
                                        <option value="<?php echo toy_e($status); ?>"<?php echo $status === 'enabled' ? ' selected' : ''; ?>>
                                            <?php echo toy_e($status); ?>
                                        </option>
                                    <?php } ?>
                                </select>
                                <button type="submit">설치</button>
                            </form>
                        </td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } ?>
</section>

<section>
    <h2>모듈 설정 항목</h2>
    <p>이 영역은 전용 화면이 없는 낮은 수준의 고급 설정입니다. 저장과 삭제는 owner만 실행할 수 있습니다.</p>
    <?php if ($canManageAdvancedModuleSettings) { ?>
        <form method="post" action="<?php echo toy_e(toy_url('/admin/modules')); ?>">
            <?php echo toy_csrf_field(); ?>
            <input type="hidden" name="intent" value="module_setting">
            <p>
                <label>Module<br>
                    <select name="module_key">
                        <?php foreach ($modules as $module) { ?>
                            <option value="<?php echo toy_e((string) $module['module_key']); ?>">
                                <?php echo toy_e((string) $module['module_key']); ?>
                            </option>
                        <?php } ?>
                    </select>
                </label>
            </p>
            <p>
                <label>Key<br>
                    <input type="text" name="setting_key" maxlength="120" required>
                </label>
            </p>
            <p>
                <label>Value<br>
                    <textarea name="setting_value" maxlength="5000"></textarea>
                </label>
            </p>
            <p>
                <label>Type<br>
                    <select name="value_type">
                        <?php foreach ($allowedSettingTypes as $type) { ?>
                            <option value="<?php echo toy_e($type); ?>"><?php echo toy_e($type); ?></option>
                        <?php } ?>
                    </select>
                </label>
            </p>
            <button type="submit">항목 저장</button>
        </form>
    <?php } ?>

    <table>
        <thead>
            <tr>
                <th>Module</th>
                <th>Key</th>
                <th>Value</th>
                <th>Type</th>
                <th>Updated</th>
                <th>삭제</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($moduleSettings === []) { ?>
                <tr>
                    <td colspan="6">설정 항목이 없습니다.</td>
                </tr>
            <?php } ?>
            <?php foreach ($moduleSettings as $setting) { ?>
                <tr>
                    <td><?php echo toy_e((string) $setting['module_key']); ?></td>
                    <td><?php echo toy_e((string) $setting['setting_key']); ?></td>
                    <td><?php echo toy_e((string) ($setting['setting_value'] ?? '')); ?></td>
                    <td><?php echo toy_e((string) $setting['value_type']); ?></td>
                    <td><?php echo toy_e((string) $setting['updated_at']); ?></td>
                    <td>
                        <?php if ($canManageAdvancedModuleSettings) { ?>
                            <form method="post" action="<?php echo toy_e(toy_url('/admin/modules')); ?>">
                                <?php echo toy_csrf_field(); ?>
                                <input type="hidden" name="intent" value="delete_module_setting">
                                <input type="hidden" name="module_key" value="<?php echo toy_e((string) $setting['module_key']); ?>">
                                <input type="hidden" name="setting_key" value="<?php echo toy_e((string) $setting['setting_key']); ?>">
                                <button type="submit">삭제</button>
                            </form>
                        <?php } else { ?>
                            owner 전용
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
