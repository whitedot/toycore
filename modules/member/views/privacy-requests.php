<?php

$pageTitle = '개인정보 요청';
?>
<!doctype html>
<html lang="<?php echo toy_e(toy_locale()); ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo toy_e($pageTitle); ?></title>
    <?php echo toy_stylesheet_tag(); ?>
</head>
<body>
    <main>
        <h1><?php echo toy_e($pageTitle); ?></h1>

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

        <form method="post" action="/account/privacy-requests">
            <?php echo toy_csrf_field(); ?>
            <p>
                <label>요청 유형<br>
                    <select name="request_type">
                        <?php foreach ($allowedTypes as $requestType) { ?>
                            <option value="<?php echo toy_e($requestType); ?>"><?php echo toy_e($requestType); ?></option>
                        <?php } ?>
                    </select>
                </label>
            </p>
            <p>
                <label>요청 내용<br>
                    <textarea name="request_message" rows="5" cols="60"></textarea>
                </label>
            </p>
            <button type="submit">요청 접수</button>
        </form>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>유형</th>
                    <th>상태</th>
                    <th>요청일</th>
                    <th>처리일</th>
                    <th>관리자 메모</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($requests === []) { ?>
                    <tr>
                        <td colspan="6">요청이 없습니다.</td>
                    </tr>
                <?php } ?>
                <?php foreach ($requests as $request) { ?>
                    <tr>
                        <td><?php echo toy_e((string) $request['id']); ?></td>
                        <td><?php echo toy_e((string) $request['request_type']); ?></td>
                        <td><?php echo toy_e((string) $request['status']); ?></td>
                        <td><?php echo toy_e((string) $request['created_at']); ?></td>
                        <td><?php echo toy_e((string) ($request['handled_at'] ?? '')); ?></td>
                        <td><?php echo toy_e((string) ($request['admin_note'] ?? '')); ?></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
        <p><a href="/account">내 계정</a></p>
    </main>
</body>
</html>
