<?php

$adminPageTitle = '개인정보 요청';
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

<form method="get" action="/admin/privacy-requests">
    <label>상태<br>
        <select name="status">
            <option value="">전체</option>
            <?php foreach ($allowedStatuses as $status) { ?>
                <option value="<?php echo toy_e($status); ?>"<?php echo $statusFilter === $status ? ' selected' : ''; ?>>
                    <?php echo toy_e($status); ?>
                </option>
            <?php } ?>
        </select>
    </label>
    <button type="submit">조회</button>
</form>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>계정</th>
            <th>유형</th>
            <th>상태</th>
            <th>요청자</th>
            <th>요청 내용</th>
            <th>처리일</th>
            <th>변경</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($requests === []) { ?>
            <tr>
                <td colspan="8">개인정보 요청이 없습니다.</td>
            </tr>
        <?php } ?>
        <?php foreach ($requests as $request) { ?>
            <tr>
                <td><?php echo toy_e((string) $request['id']); ?></td>
                <td><?php echo toy_e((string) ($request['account_id'] ?? '')); ?></td>
                <td><?php echo toy_e((string) $request['request_type']); ?></td>
                <td><?php echo toy_e((string) $request['status']); ?></td>
                <td><?php echo toy_e((string) $request['requester_snapshot']); ?></td>
                <td><?php echo toy_e((string) ($request['request_message'] ?? '')); ?></td>
                <td><?php echo toy_e((string) ($request['handled_at'] ?? '')); ?></td>
                <td>
                    <p><a href="/admin/privacy-requests/export?id=<?php echo toy_e((string) $request['id']); ?>">JSON</a></p>
                    <form method="post" action="/admin/privacy-requests">
                        <?php echo toy_csrf_field(); ?>
                        <input type="hidden" name="request_id" value="<?php echo toy_e((string) $request['id']); ?>">
                        <select name="status">
                            <?php foreach ($allowedStatuses as $status) { ?>
                                <option value="<?php echo toy_e($status); ?>"<?php echo $request['status'] === $status ? ' selected' : ''; ?>>
                                    <?php echo toy_e($status); ?>
                                </option>
                            <?php } ?>
                        </select>
                        <textarea name="admin_note" rows="3" cols="30"><?php echo toy_e((string) ($request['admin_note'] ?? '')); ?></textarea>
                        <button type="submit">저장</button>
                    </form>
                </td>
            </tr>
        <?php } ?>
    </tbody>
</table>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
