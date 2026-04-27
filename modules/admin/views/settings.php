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

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
