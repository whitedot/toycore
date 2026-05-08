# 운영 모듈 구현 계획

이 문서는 사이트 전체 메뉴, 배너, 알림, 관리자 작업 로그를 core가 아닌 운영 모듈 중심으로 확장하기 위한 현재 계획과 1차 구현 범위를 기록한다.

## 방향

- `site_menu`, `banner`, `notification`은 독립 모듈로 둔다.
- 관리자 작업 로그는 이미 core 운영 helper와 `toy_audit_logs` 테이블이 제공하므로 별도 도메인 테이블을 만들지 않고 admin 화면을 강화한다.
- 배너처럼 화면 안에 붙는 콘텐츠는 `toy_render_output_slot()`을 사용하고, 각 화면 소유 모듈이 자기 출력 위치를 선언한다.
- 메뉴는 사이트 구조에 가까우므로 `site_menu`가 메뉴 그룹을 소유하고, 코어 화면은 출력 슬롯만 열어 둔다.
- 이메일, SMS, 알림톡은 실제 provider 연동을 바로 넣지 않고 `toy_notification_deliveries` 발송 대기열에 쌓는 단계부터 시작한다.

## 1차 구현 범위

### 사이트 메뉴

- `toy_site_menus`에 메뉴 단위를 저장한다.
- `toy_site_menu_items`에 메뉴 항목, URL, target, 상태, 정렬 순서를 저장한다.
- 설치 시 기본 `header` 메뉴와 홈/로그인/회원가입 항목을 생성한다.
- 관리자 화면 `/admin/site-menus`에서 메뉴와 항목을 관리한다.
- 메뉴 key 변경 시 중복 key를 검증한다.
- 메뉴 삭제 시 하위 메뉴 항목을 같은 트랜잭션에서 삭제한다.
- 기본 홈 화면은 `site.header` 출력 슬롯을 열고, `site_menu` 모듈이 `header` 메뉴를 렌더링한다.
- 활성 모듈은 `menu-links.php`로 운영자가 선택할 수 있는 메뉴 후보 URL을 제공한다.

### 배너

- `toy_banners`에 배너 제목, 내용, 이미지 URL, 링크 URL, 상태, 기간, 정렬을 저장한다.
- `toy_banner_targets`에 출력 대상 module/point/slot/subject 규칙을 저장한다.
- 관리자 화면 `/admin/banners`에서 배너와 대상 규칙을 관리한다.
- 관리자 화면에서 배너 상태별 조회를 제공한다.
- 기본 CSP와 맞추기 위해 이미지 URL은 `/assets/...` 또는 `/modules/{module_key}/assets/...` 같은 내부 경로만 허용한다.
- 기본 홈 화면의 `core/site.home/before_content`, `core/site.home/after_content` 위치에 배너를 출력할 수 있다.

### 알림

- `toy_notifications`에 사이트 내 알림 본문과 대상 정보를 저장한다.
- `toy_notification_reads`에 전체 공지형 알림의 회원별 읽음 상태를 저장한다.
- `toy_notification_deliveries`에 `site`, `email`, `sms`, `alimtalk` 채널별 발송 상태를 저장한다.
- 관리자 화면 `/admin/notifications`에서 알림을 등록하고 알림 운영 현황과 대기열을 확인한다.
- 관리자 화면에서 알림 대상, 발송 채널, 발송 상태별 조회를 제공한다.
- 관리자 목록은 알림 제목, 본문, 외부 수신자, 생성자 계정 ID 같은 세부 내용을 기본 노출하지 않고 상태와 처리 흐름 중심으로 유지한다.
- 생성 주체 확인은 알림 목록이 아니라 관리자 작업 로그에서 처리한다.
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

## 후속 운영 모듈 후보

공식 선택 모듈은 비활성 상태에서도 코어와 다른 모듈이 동작해야 한다. 여러 모듈이 활용할 수 있더라도 코어 필수 의존으로 만들지 않고, 안정 식별자 기반 단방향 참조나 명시적 계약 파일을 우선한다.

구현된 코어 primitive:

- `core/helpers/upload.php`: 업로드 에러 정규화, 최대 용량 검사, 확장자 allowlist, MIME 검사, 실행 가능 확장자 차단, 파일명 정규화, 랜덤 저장명 생성, 경로 traversal 방지, 안전한 `move_uploaded_file()` wrapper를 제공한다. 파일의 의미, 공개 범위, 다운로드 권한, DB 메타데이터, 삭제/보존 정책은 소유 모듈이 책임진다.
- 다운로드 token helper: 비공개 파일을 직접 URL로 노출하지 않도록 단기 만료 token 발급과 HMAC 저장 원칙을 제공한다. token 사용 정책과 감사 로그 범위는 파일을 소유한 모듈이 정한다.
- 이미지 재인코딩 wrapper: GD 또는 Imagick이 있을 때만 활성화되는 선택 helper로 둔다. 이미지 업로드 모듈은 원본을 신뢰하지 않고 재인코딩할 수 있어야 하지만, 저가형 호스팅에서 해당 확장이 없으면 기능이 비활성화되어도 설치가 막히면 안 된다.

우선순위 1:

- `healthcheck`: PHP 버전, 필수/선택 확장, 쓰기 권한, 보호 경로, 업로드 제한, ZipArchive 여부, trusted proxy 설정 같은 운영 환경을 관리자에서 점검한다.
- `backup`: 기존 `storage/module-backups` 가시화와 DB dump 생성/다운로드/운영 확인을 제공한다. 자동 복원은 초기 범위에서 제외한다.

우선순위 2:

- `attachment` 또는 `file_vault`: 여러 모듈이 파일 첨부를 반복 구현하기 시작하면 선택 모듈로 검토한다. 다른 모듈은 attachment ID를 자기 테이블에 단방향으로 저장하고, attachment 모듈이 도메인별 양방향 관계 테이블을 소유하지 않는다.
- `mailer`: 코어 mail helper와 발송 감사 로그 위에 운영 화면을 제공한다. SMTP/API 설정, 테스트 발송, 발송 로그 조회까지만 다루고 템플릿 마케팅 메일은 범위에서 제외한다.

우선순위 3:

- `captcha` 또는 `anti_abuse`: `member` 모듈이 회원가입/비밀번호 재설정 같은 지점에 provider 계약을 먼저 노출한 뒤 플러그인으로 붙인다. 코어 rate limit와 중복하지 않는다.
- `cache`: 설정/번역 요청 단위 메모리 캐시는 코어에 두고, 파일 캐시나 페이지 캐시는 선택 모듈에서 다룬다. 계정 권한, locale, CSRF 영향을 받는 화면은 캐시 대상에서 제외한다.
- `search`: 수요가 확인되기 전에는 보류한다. 도입할 때는 별도 자동 검색 구조를 만들기보다 기존 계약 파일 패턴에 맞춰 각 모듈이 공개 가능한 검색 후보만 제공한다.
