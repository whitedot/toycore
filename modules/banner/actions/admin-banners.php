<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/member/helpers.php';
require_once TOY_ROOT . '/modules/admin/helpers.php';
require_once TOY_ROOT . '/modules/banner/helpers.php';

$account = toy_member_require_login($pdo);
toy_admin_require_role($pdo, (int) $account['id'], ['owner', 'admin']);

$allowedStatuses = ['draft', 'enabled', 'disabled'];
$allowedMatchTypes = ['all', 'exact'];
$errors = [];
$notice = '';
$availableTargets = toy_banner_available_targets($pdo);

if (toy_request_method() === 'POST') {
    toy_require_csrf();

    $intent = toy_post_string('intent', 40);
    $bannerId = (int) toy_post_string('banner_id', 20);

    if ($intent === 'delete') {
        if ($bannerId <= 0) {
            $errors[] = '삭제할 배너를 찾을 수 없습니다.';
        }

        if ($errors === []) {
            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare('DELETE FROM toy_banner_targets WHERE banner_id = :banner_id');
                $stmt->execute(['banner_id' => $bannerId]);
                $stmt = $pdo->prepare('DELETE FROM toy_banners WHERE id = :id');
                $stmt->execute(['id' => $bannerId]);
                $pdo->commit();

                toy_audit_log($pdo, [
                    'actor_account_id' => (int) $account['id'],
                    'actor_type' => 'admin',
                    'event_type' => 'banner.deleted',
                    'target_type' => 'banner',
                    'target_id' => (string) $bannerId,
                    'result' => 'success',
                    'message' => 'Banner deleted.',
                ]);

                $notice = '배너를 삭제했습니다.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = '배너 삭제 중 오류가 발생했습니다.';
            }
        }
    } elseif ($intent === 'save') {
        $title = toy_banner_clean_single_line(toy_post_string('title', 120), 120);
        $bodyText = toy_banner_clean_text(toy_post_string('body_text', 3000), 3000);
        $linkUrl = toy_banner_clean_url(toy_post_string('link_url', 255));
        $imageUrl = toy_banner_clean_url(toy_post_string('image_url', 255));
        $status = toy_post_string('status', 30);
        $startsAtInput = toy_post_string('starts_at', 30);
        $endsAtInput = toy_post_string('ends_at', 30);
        $startsAt = toy_banner_clean_admin_datetime($startsAtInput);
        $endsAt = toy_banner_clean_admin_datetime($endsAtInput);
        $sortOrder = max(-100000, min(100000, (int) toy_post_string('sort_order', 20)));
        $target = toy_banner_find_target($availableTargets, toy_post_string('target_option', 300));
        $matchType = toy_post_string('match_type', 20);
        $subjectId = toy_banner_clean_single_line(toy_post_string('subject_id', 80), 80);

        if ($title === '') {
            $errors[] = '제목을 입력하세요.';
        }
        if (!in_array($status, $allowedStatuses, true)) {
            $errors[] = '상태 값이 올바르지 않습니다.';
        }
        if ($startsAtInput !== '' && $startsAt === null) {
            $errors[] = '시작 시각 형식이 올바르지 않습니다.';
        }
        if ($endsAtInput !== '' && $endsAt === null) {
            $errors[] = '종료 시각 형식이 올바르지 않습니다.';
        }
        if ($startsAt !== null && $endsAt !== null && $startsAt > $endsAt) {
            $errors[] = '종료 시각은 시작 시각 이후여야 합니다.';
        }
        if ($target === null) {
            $errors[] = '노출 대상을 선택하세요.';
        }
        if (!in_array($matchType, $allowedMatchTypes, true)) {
            $errors[] = '매칭 방식이 올바르지 않습니다.';
        }
        if ($matchType === 'exact' && $subjectId === '') {
            $errors[] = '특정 subject ID를 입력하세요.';
        }

        if ($errors === [] && $bannerId > 0) {
            $stmt = $pdo->prepare('SELECT id FROM toy_banners WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $bannerId]);
            if (!is_array($stmt->fetch())) {
                $errors[] = '수정할 배너를 찾을 수 없습니다.';
            }
        }

        if ($errors === [] && $target !== null) {
            try {
                $now = toy_now();
                $pdo->beginTransaction();

                if ($bannerId > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE toy_banners
                         SET title = :title, body_text = :body_text, link_url = :link_url, image_url = :image_url,
                             status = :status, starts_at = :starts_at, ends_at = :ends_at, sort_order = :sort_order, updated_at = :updated_at
                         WHERE id = :id'
                    );
                    $stmt->execute([
                        'title' => $title,
                        'body_text' => $bodyText,
                        'link_url' => $linkUrl,
                        'image_url' => $imageUrl,
                        'status' => $status,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'sort_order' => $sortOrder,
                        'updated_at' => $now,
                        'id' => $bannerId,
                    ]);
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO toy_banners
                            (title, body_text, link_url, image_url, status, starts_at, ends_at, sort_order, created_at, updated_at)
                         VALUES
                            (:title, :body_text, :link_url, :image_url, :status, :starts_at, :ends_at, :sort_order, :created_at, :updated_at)'
                    );
                    $stmt->execute([
                        'title' => $title,
                        'body_text' => $bodyText,
                        'link_url' => $linkUrl,
                        'image_url' => $imageUrl,
                        'status' => $status,
                        'starts_at' => $startsAt,
                        'ends_at' => $endsAt,
                        'sort_order' => $sortOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $bannerId = (int) $pdo->lastInsertId();
                }

                $stmt = $pdo->prepare('DELETE FROM toy_banner_targets WHERE banner_id = :banner_id');
                $stmt->execute(['banner_id' => $bannerId]);

                $stmt = $pdo->prepare(
                    'INSERT INTO toy_banner_targets
                        (banner_id, module_key, point_key, slot_key, subject_id, match_type, created_at)
                     VALUES
                        (:banner_id, :module_key, :point_key, :slot_key, :subject_id, :match_type, :created_at)'
                );
                $stmt->execute([
                    'banner_id' => $bannerId,
                    'module_key' => (string) $target['module_key'],
                    'point_key' => (string) $target['point_key'],
                    'slot_key' => (string) $target['slot_key'],
                    'subject_id' => $matchType === 'exact' ? $subjectId : '',
                    'match_type' => $matchType,
                    'created_at' => $now,
                ]);

                $pdo->commit();

                toy_audit_log($pdo, [
                    'actor_account_id' => (int) $account['id'],
                    'actor_type' => 'admin',
                    'event_type' => 'banner.saved',
                    'target_type' => 'banner',
                    'target_id' => (string) $bannerId,
                    'result' => 'success',
                    'message' => 'Banner saved.',
                    'metadata' => [
                        'module_key' => (string) $target['module_key'],
                        'point_key' => (string) $target['point_key'],
                        'slot_key' => (string) $target['slot_key'],
                    ],
                ]);

                $notice = '배너를 저장했습니다.';
            } catch (Throwable $exception) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $errors[] = '배너 저장 중 오류가 발생했습니다.';
            }
        }
    } else {
        $errors[] = '요청한 작업을 처리할 수 없습니다.';
    }
}

$editBanner = null;
$editId = (int) toy_get_string('edit_id', 20);
if ($editId > 0) {
    $stmt = $pdo->prepare(
        'SELECT b.id, b.title, b.body_text, b.link_url, b.image_url, b.status, b.starts_at, b.ends_at, b.sort_order,
                t.module_key, t.point_key, t.slot_key, t.subject_id, t.match_type
         FROM toy_banners b
         LEFT JOIN toy_banner_targets t ON t.banner_id = b.id
         WHERE b.id = :id
         LIMIT 1'
    );
    $stmt->execute(['id' => $editId]);
    $row = $stmt->fetch();
    if (is_array($row)) {
        $editBanner = $row;
    }
}

$banners = [];
$stmt = $pdo->query(
    'SELECT b.id, b.title, b.status, b.starts_at, b.ends_at, b.sort_order, b.updated_at,
            t.module_key, t.point_key, t.slot_key, t.subject_id, t.match_type
     FROM toy_banners b
     LEFT JOIN toy_banner_targets t ON t.banner_id = b.id
     ORDER BY b.sort_order ASC, b.id DESC'
);
foreach ($stmt->fetchAll() as $row) {
    $banners[] = $row;
}

include TOY_ROOT . '/modules/banner/views/admin-banners.php';
