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
            <th>버전</th>
            <th>상태</th>
            <th>기본 포함</th>
            <th>설치일</th>
            <th>변경</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($modules as $module) { ?>
            <?php $isRequired = in_array((string) $module['module_key'], $requiredModules, true); ?>
            <tr>
                <td><?php echo toy_e((string) $module['module_key']); ?></td>
                <td><?php echo toy_e((string) $module['name']); ?></td>
                <td><?php echo toy_e((string) $module['version']); ?></td>
                <td><?php echo toy_e((string) $module['status']); ?></td>
                <td><?php echo !empty($module['is_bundled']) ? 'yes' : 'no'; ?></td>
                <td><?php echo toy_e((string) ($module['installed_at'] ?? '')); ?></td>
                <td>
                    <form method="post" action="/admin/modules">
                        <?php echo toy_csrf_field(); ?>
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
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
