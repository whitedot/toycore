<?php

$adminPageTitle = '감사 로그';
include TOY_ROOT . '/modules/admin/views/layout-header.php';
?>

<form method="get" action="<?php echo toy_e(toy_url('/admin/audit-logs')); ?>">
    <p>
        <label>이벤트 유형<br>
            <input type="text" name="event_type" value="<?php echo toy_e($filters['event_type']); ?>" maxlength="80">
        </label>
    </p>
    <p>
        <label>대상 유형<br>
            <input type="text" name="target_type" value="<?php echo toy_e($filters['target_type']); ?>" maxlength="60">
        </label>
    </p>
    <p>
        <label>결과<br>
            <input type="text" name="result" value="<?php echo toy_e($filters['result']); ?>" maxlength="30">
        </label>
    </p>
    <button type="submit">조회</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>시각</th>
            <th>처리자</th>
            <th>이벤트</th>
            <th>대상</th>
            <th>결과</th>
            <th>IP</th>
            <th>메시지</th>
            <th>메타</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($logs === []) { ?>
            <tr>
                <td colspan="9">감사 로그가 없습니다.</td>
            </tr>
        <?php } ?>
        <?php foreach ($logs as $log) { ?>
            <tr>
                <td><?php echo toy_e((string) $log['id']); ?></td>
                <td><?php echo toy_e((string) $log['created_at']); ?></td>
                <td><?php echo toy_e((string) ($log['actor_account_id'] ?? $log['actor_type'])); ?></td>
                <td><?php echo toy_e((string) $log['event_type']); ?></td>
                <td><?php echo toy_e((string) $log['target_type'] . ':' . (string) $log['target_id']); ?></td>
                <td><?php echo toy_e((string) $log['result']); ?></td>
                <td><?php echo toy_e((string) $log['ip_address']); ?></td>
                <td><?php echo toy_e((string) $log['message']); ?></td>
                <td><?php echo toy_e((string) ($log['metadata_json'] ?? '')); ?></td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
