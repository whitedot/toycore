<?php

$adminPageTitle = '관리자 대시보드';
include TOY_ROOT . '/modules/admin/views/layout-header.php';
?>

<section>
    <h2>사이트</h2>
    <dl>
        <dt>이름</dt>
        <dd><?php echo toy_e((string) ($site['name'] ?? '')); ?></dd>
        <dt>상태</dt>
        <dd><?php echo toy_e((string) ($site['status'] ?? '')); ?></dd>
        <dt>기본 locale</dt>
        <dd><?php echo toy_e((string) ($site['default_locale'] ?? '')); ?></dd>
    </dl>
</section>

<?php if ($operationSummary !== []) { ?>
    <section>
        <h2>운영 모듈</h2>
        <table>
            <thead>
                <tr>
                    <th>항목</th>
                    <th>주요 수치</th>
                    <th>상세</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($operationSummary as $summary) { ?>
                    <tr>
                        <td><?php echo toy_e((string) $summary['label']); ?></td>
                        <td><?php echo toy_e((string) $summary['value']); ?></td>
                        <td><?php echo toy_e((string) $summary['detail']); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    </section>
<?php } ?>

<section>
    <h2>모듈</h2>
    <table>
        <thead>
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
                    <td><?php echo toy_e((string) $module['module_key']); ?></td>
                    <td><?php echo toy_e((string) $module['name']); ?></td>
                    <td><?php echo toy_e((string) $module['version']); ?></td>
                    <td><?php echo toy_e((string) $module['status']); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</section>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
