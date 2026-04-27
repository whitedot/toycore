<?php

$adminPageTitle = '업데이트';
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

<section>
    <h2>대기 중인 업데이트</h2>
    <?php if ($pendingUpdates === []) { ?>
        <p>적용할 업데이트가 없습니다.</p>
    <?php } else { ?>
        <table>
            <thead>
                <tr>
                    <th scope="col">범위</th>
                    <th scope="col">버전</th>
                    <th scope="col">파일</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pendingUpdates as $update) { ?>
                    <tr>
                        <td><?php echo toy_e((string) $update['label']); ?></td>
                        <td><?php echo toy_e((string) $update['version']); ?></td>
                        <td><?php echo toy_e(str_replace(TOY_ROOT . '/', '', (string) $update['path'])); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>

        <form method="post" action="/admin/updates">
            <?php echo toy_csrf_field(); ?>
            <button type="submit">업데이트 적용</button>
        </form>
    <?php } ?>
</section>

<?php if ($appliedUpdates !== []) { ?>
    <section>
        <h2>적용한 업데이트</h2>
        <ul>
            <?php foreach ($appliedUpdates as $update) { ?>
                <li><?php echo toy_e((string) $update['label'] . ' ' . (string) $update['version']); ?></li>
            <?php } ?>
        </ul>
    </section>
<?php } ?>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
