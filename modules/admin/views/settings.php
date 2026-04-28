<?php

$adminPageTitle = '사이트 설정';
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

<form method="post" action="/admin/settings">
    <?php echo toy_csrf_field(); ?>
    <input type="hidden" name="intent" value="site">
    <p>
        <label>사이트 이름<br>
            <input type="text" name="name" value="<?php echo toy_e($values['name']); ?>" maxlength="120" required>
        </label>
    </p>
    <p>
        <label>Base URL<br>
            <input type="url" name="base_url" value="<?php echo toy_e($values['base_url']); ?>" maxlength="255">
        </label>
    </p>
    <p>
        <label>Timezone<br>
            <input type="text" name="timezone" value="<?php echo toy_e($values['timezone']); ?>" maxlength="80" required>
        </label>
    </p>
    <p>
        <label>기본 locale<br>
            <input type="text" name="default_locale" value="<?php echo toy_e($values['default_locale']); ?>" maxlength="20" required>
        </label>
    </p>
    <p>
        <label>운영 상태<br>
            <select name="status">
                <option value="active"<?php echo $values['status'] === 'active' ? ' selected' : ''; ?>>운영</option>
                <option value="maintenance"<?php echo $values['status'] === 'maintenance' ? ' selected' : ''; ?>>점검</option>
            </select>
        </label>
    </p>
    <button type="submit">저장</button>
</form>

<section>
    <h2>추가 사이트 설정 항목</h2>
    <form method="post" action="/admin/settings">
        <?php echo toy_csrf_field(); ?>
        <input type="hidden" name="intent" value="site_setting">
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

    <table>
        <thead>
            <tr>
                <th>Key</th>
                <th>Value</th>
                <th>Type</th>
                <th>Updated</th>
                <th>삭제</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($siteSettings === []) { ?>
                <tr>
                    <td colspan="5">설정 항목이 없습니다.</td>
                </tr>
            <?php } ?>
            <?php foreach ($siteSettings as $setting) { ?>
                <tr>
                    <td><?php echo toy_e((string) $setting['setting_key']); ?></td>
                    <td><?php echo toy_e((string) ($setting['setting_value'] ?? '')); ?></td>
                    <td><?php echo toy_e((string) $setting['value_type']); ?></td>
                    <td><?php echo toy_e((string) $setting['updated_at']); ?></td>
                    <td>
                        <form method="post" action="/admin/settings">
                            <?php echo toy_csrf_field(); ?>
                            <input type="hidden" name="intent" value="delete_site_setting">
                            <input type="hidden" name="setting_key" value="<?php echo toy_e((string) $setting['setting_key']); ?>">
                            <button type="submit">삭제</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
