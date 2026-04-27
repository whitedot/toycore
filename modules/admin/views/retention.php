<?php

$adminPageTitle = '보관 정리';
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

<form method="post" action="/admin/retention">
    <?php echo toy_csrf_field(); ?>
    <p>
        <label>인증 로그 보관일<br>
            <input type="number" name="auth_logs_days" value="<?php echo toy_e((string) $values['auth_logs_days']); ?>" min="1" max="3650" required>
        </label>
    </p>
    <p>
        <label>감사 로그 보관일<br>
            <input type="number" name="audit_logs_days" value="<?php echo toy_e((string) $values['audit_logs_days']); ?>" min="1" max="3650" required>
        </label>
    </p>
    <p>
        <label>사용 완료 토큰 보관일<br>
            <input type="number" name="used_tokens_days" value="<?php echo toy_e((string) $values['used_tokens_days']); ?>" min="1" max="3650" required>
        </label>
    </p>
    <p>
        <label>만료/폐기 세션 보관일<br>
            <input type="number" name="sessions_days" value="<?php echo toy_e((string) $values['sessions_days']); ?>" min="1" max="3650" required>
        </label>
    </p>
    <button type="submit">정리 실행</button>
</form>

<table>
    <thead>
        <tr>
            <th>대상</th>
            <th>기준 시각</th>
            <th>삭제 후보</th>
            <th>이번 삭제</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>인증 로그</td>
            <td><?php echo toy_e($previewCutoffs['auth_logs']); ?></td>
            <td><?php echo toy_e((string) $previewCounts['auth_logs']); ?></td>
            <td><?php echo toy_e((string) ($deletedCounts['auth_logs'] ?? '')); ?></td>
        </tr>
        <tr>
            <td>감사 로그</td>
            <td><?php echo toy_e($previewCutoffs['audit_logs']); ?></td>
            <td><?php echo toy_e((string) $previewCounts['audit_logs']); ?></td>
            <td><?php echo toy_e((string) ($deletedCounts['audit_logs'] ?? '')); ?></td>
        </tr>
        <tr>
            <td>비밀번호 재설정 토큰</td>
            <td><?php echo toy_e($previewCutoffs['used_tokens']); ?></td>
            <td><?php echo toy_e((string) $previewCounts['password_resets']); ?></td>
            <td><?php echo toy_e((string) ($deletedCounts['password_resets'] ?? '')); ?></td>
        </tr>
        <tr>
            <td>이메일 인증 토큰</td>
            <td><?php echo toy_e($previewCutoffs['used_tokens']); ?></td>
            <td><?php echo toy_e((string) $previewCounts['email_verifications']); ?></td>
            <td><?php echo toy_e((string) ($deletedCounts['email_verifications'] ?? '')); ?></td>
        </tr>
        <tr>
            <td>만료/폐기 세션</td>
            <td><?php echo toy_e($previewCutoffs['sessions']); ?></td>
            <td><?php echo toy_e((string) $previewCounts['sessions']); ?></td>
            <td><?php echo toy_e((string) ($deletedCounts['sessions'] ?? '')); ?></td>
        </tr>
    </tbody>
</table>

<?php include TOY_ROOT . '/modules/admin/views/layout-footer.php'; ?>
