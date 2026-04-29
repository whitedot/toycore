# 운영 모듈 구현 계획

이 문서는 사이트 전체 메뉴, 배너, 알림, 관리자 작업 로그를 core가 아닌 운영 모듈 중심으로 확장하기 위한 현재 계획과 1차 구현 범위를 기록한다.

## 방향

- `site_menu`, `banner`, `notification`은 독립 모듈로 둔다.
- 관리자 작업 로그는 이미 core 운영 helper와 `toy_audit_logs` 테이블이 제공하므로 별도 도메인 테이블을 만들지 않고 admin 화면을 강화한다.
- 화면 삽입은 가능하면 `toy_render_output_slot()`을 사용하고, 각 모듈은 자기 `output-slots.php`에서 출력 정책을 가진다.
- 이메일, SMS, 알림톡은 실제 provider 연동을 바로 넣지 않고 `toy_notification_deliveries` 발송 대기열에 쌓는 단계부터 시작한다.

## 1차 구현 범위

### 사이트 메뉴

- `toy_site_menus`에 메뉴 단위를 저장한다.
- `toy_site_menu_items`에 메뉴 항목, URL, target, 상태, 정렬 순서를 저장한다.
- 관리자 화면 `/admin/site-menus`에서 메뉴와 항목을 관리한다.
- 메뉴 key 변경 시 중복 key를 검증한다.
- 메뉴 삭제 시 하위 메뉴 항목을 같은 트랜잭션에서 삭제한다.
- 기본 홈 화면의 `core/site.home/navigation` 출력 위치에 `header` 메뉴를 출력한다.

### 배너

- `toy_banners`에 배너 제목, 내용, 이미지 URL, 링크 URL, 상태, 기간, 정렬을 저장한다.
- `toy_banner_targets`에 출력 대상 module/point/slot/subject 규칙을 저장한다.
- 관리자 화면 `/admin/banners`에서 배너와 대상 규칙을 관리한다.
- 관리자 화면에서 배너 상태별 조회를 제공한다.
- 기본 CSP와 맞추기 위해 이미지 URL은 `/assets/...` 같은 내부 경로만 허용한다.
- 기본 홈 화면의 `core/site.home/before_content`, `core/site.home/after_content` 위치에 배너를 출력할 수 있다.

### 알림

- `toy_notifications`에 사이트 내 알림 본문과 대상 정보를 저장한다.
- `toy_notification_reads`에 전체 공지형 알림의 회원별 읽음 상태를 저장한다.
- `toy_notification_deliveries`에 `site`, `email`, `sms`, `alimtalk` 채널별 발송 상태를 저장한다.
- 관리자 화면 `/admin/notifications`에서 알림을 등록하고 대기열을 확인한다.
- 관리자 화면에서 알림 대상과 발송 상태별 조회를 제공한다.
- 관리자 화면에서 알림 삭제 시 발송 대기열과 읽음 기록을 같은 트랜잭션에서 삭제한다.
- 실제 provider 연동 전에도 발송 대기열 상태를 수동으로 `sent`, `failed`, `canceled` 등으로 기록할 수 있다.
- 회원 화면 `/account/notifications`에서 사이트 내 알림을 확인하고, 읽음 상태별 조회와 개별/전체 읽음 처리를 제공한다.
- 회원 개인정보 JSON 내보내기에서 알림, 읽음 상태, 발송 대기열 데이터를 함께 제공한다.
- 관리자 보관 정리 화면에서 오래된 알림, 발송 대기열, 읽음 기록을 정리한다.

### 관리자 작업 로그

- 기존 `/admin/audit-logs` 화면명을 관리자 작업 로그로 정리한다.
- 이벤트 유형, 대상 유형, 처리자 계정 ID, 결과, 기간 필터를 제공한다.
- 새 운영 모듈의 저장/삭제 작업은 `toy_audit_log()`로 기록한다.

### 관리자 대시보드

- 설치된 운영 모듈 테이블이 있을 때 사이트 메뉴, 배너, 알림의 주요 수치를 요약한다.

## 후속 보완

- 메뉴 항목 계층 구조와 드래그 정렬 UI
- 배너 device, locale, member status 조건
- 이메일/SMS/알림톡 provider adapter 계약
- 작업 로그 CSV export와 변경 전/후 diff metadata 표준화
