# 관리자 모듈 상세 계획

`admin` 모듈은 Toycore의 기본 배포에 포함되는 필수 모듈입니다. 관리자 화면은 제공하지만 코어에 직접 넣지 않고, `member` 모듈의 계정과 인증 기반 위에서 동작합니다.

이 문서는 관리자 권한, 화면, 설정 관리, 모듈 관리, 감사 로그, 개인정보 요청 처리의 책임 범위를 정리합니다.

## 목표

- 최초 관리자 계정이 로그인 후 운영을 시작할 수 있는 기본 화면 제공
- 사이트 설정, 모듈 상태, 회원 상태를 관리하는 최소 화면 제공
- 관리자 권한 확인을 action 파일 초기에 명시적으로 수행
- 관리자 작업을 감사 로그에 기록
- 관리자 화면은 기본 `noindex`로 출력

## 책임 범위

`admin` 모듈이 담당합니다.

- 관리자 로그인 후 진입 화면
- 관리자 레이아웃
- 관리자 권한 확인 helper
- 사이트 기본 설정 관리
- 모듈 목록, 활성화, 비활성화
- 모듈 전용 관리자 화면으로 이동할 수 있는 공통 레이아웃
- 회원 목록과 회원 상태 변경
- 관리자 권한 부여와 회수
- 감사 로그 조회
- 개인정보 요청 관리 화면

`admin` 모듈이 담당하지 않습니다.

- 계정 생성의 기본 저장 로직
- 비밀번호 hash와 로그인 처리
- 일반 회원 가입 화면
- 각 콘텐츠 모듈의 세부 관리 로직
- 코어 테이블 설치 흐름 자체

## 기본 디렉터리

```text
modules/admin/
- module.php
- paths.php
- install.sql
- actions/
  - dashboard.php
  - settings.php
- views/
  - layout-header.php
  - layout-footer.php
  - dashboard.php
  - settings.php
- helpers.php
- lang/
  - ko.php
```

구현되지 않은 관리자 action 파일은 미리 만들지 않습니다. 모듈 관리, 회원 관리, 감사 로그, 개인정보 요청, 역할 관리 화면은 각 단계에서 실제 action이 생길 때 추가합니다.

관리자 레이아웃은 `admin` 모듈의 view로 두고, 코어가 관리자 HTML을 직접 렌더링하지 않습니다.

## 기본 테이블

`admin` 모듈은 권한과 관리자 환경에 필요한 테이블만 소유합니다.

```text
toy_admin_account_roles
```

초기 구현에서는 permission 테이블을 만들지 않고 `owner`, `admin`, `manager` 같은 최소 role key로 시작합니다.

### `toy_admin_account_roles`

`member` 계정과 관리자 역할을 연결합니다.

권장 필드:

```text
id
account_id
role_key
created_at
```

기본 역할:

```text
owner
admin
manager
```

`account_id`는 `toy_member_accounts.id`를 논리적으로 참조합니다. 모듈 경계를 위해 초기 구현에서는 강제 FK를 신중하게 검토합니다.

role별 허용 작업은 `admin` 모듈 helper의 명시적 배열로 관리합니다.

```text
owner: 모든 기본 관리자 작업
admin: 설정, 모듈, 회원, 감사 로그 조회
manager: 대시보드, 회원 조회
```

permission 테이블과 권한 편집 UI는 운영 요구가 확인된 뒤 추가합니다.

## 기본 path

```php
<?php

return [
    'GET /admin' => 'actions/dashboard.php',
    'GET /admin/settings' => 'actions/settings.php',
    'POST /admin/settings' => 'actions/settings.php',
    'GET /admin/modules' => 'actions/modules.php',
    'POST /admin/modules' => 'actions/modules.php',
    'GET /admin/members' => 'actions/members.php',
    'POST /admin/members' => 'actions/members.php',
    'GET /admin/roles' => 'actions/roles.php',
    'POST /admin/roles' => 'actions/roles.php',
    'GET /admin/audit-logs' => 'actions/audit-logs.php',
    'GET /admin/privacy-requests' => 'actions/privacy-requests.php',
    'POST /admin/privacy-requests' => 'actions/privacy-requests.php',
    'GET /admin/privacy-requests/export' => 'actions/privacy-request-export.php',
    'GET /admin/retention' => 'actions/retention.php',
    'POST /admin/retention' => 'actions/retention.php',
];
```

관리자 로그인 자체는 `member` 모듈의 `/login`을 사용합니다. 로그인 후 관리자 권한이 있는 계정만 `/admin`에 접근할 수 있습니다.

각 모듈이 제공하는 전용 관리자 화면은 소유 모듈의 `paths.php`에 둡니다. 예를 들어 회원 설정은 `member` 모듈의 `/admin/member-settings`, 팝업레이어 관리는 `popup_layer` 모듈의 `/admin/popup-layers`가 담당합니다.

## 관리자 접근 흐름

```text
GET /admin
-> member helper include
-> admin helper include
-> 로그인 상태 확인
-> owner/admin/manager role 확인
-> noindex header/meta 설정
-> dashboard view 출력
```

로그인하지 않은 사용자는 로그인 화면으로 이동합니다. 로그인했지만 권한이 없으면 403 화면을 출력합니다.

## 최초 관리자 권한

설치 과정에서 다음 순서로 처리합니다.

```text
1. member 모듈이 최초 계정 생성
2. admin 모듈이 최초 계정에 owner role_key 부여
3. 설치 완료와 최초 관리자 생성 기록
```

기본 관리자 비밀번호는 설치 화면에서 입력하며, 하드코딩된 계정은 제공하지 않습니다.

## 주요 화면

### 대시보드

초기 대시보드는 최소 운영 상태만 보여줍니다.

- 사이트 이름과 상태
- 설치된 모듈 수
- 점검 모드 상태

### 사이트 설정

관리 대상:

- 사이트 이름
- base URL
- timezone
- default locale
- 운영 상태

상태 변경은 모두 CSRF 검증과 감사 로그 기록을 거칩니다.

### 모듈 관리

관리 대상:

- 설치된 모듈 목록
- 활성/비활성 상태
- 모듈 기본 정보
- 모듈 설정 진입

`member`와 `admin`은 필수 기본 모듈이므로 일반 화면에서 비활성화하지 않습니다.

### 회원 관리

관리 대상:

- 회원 목록 조회
- 상태별 필터
- 계정 상세 조회
- 계정 상태 변경
- 이메일 인증 상태 확인
- 세션 폐기

관리자 화면에서 비밀번호 원문을 조회하거나 저장하지 않습니다.

### 관리자 권한 관리

관리 대상:

- 관리자 역할 목록
- 계정별 역할 부여
- 계정별 역할 회수

초기 구현에서는 role key만 부여하고 권한 키 편집은 제공하지 않습니다.

### 감사 로그 조회

기본 필터:

- 기간
- actor
- event type
- target type
- result

감사 로그 조회 자체도 권한이 필요합니다.

### 개인정보 요청 관리

초기에는 수동 처리 중심으로 둡니다.

관리 대상:

- 요청 목록
- 요청 상태 변경
- 처리 메모
- 완료 시각 기록

개인정보 원문 대량 노출을 피하고, 필요한 최소 정보와 snapshot/hash 중심으로 조회합니다.

## helper 방향

예상 helper:

```text
toy_admin_current_roles()
toy_admin_has_role()
toy_admin_require_role()
toy_admin_grant_role()
toy_admin_revoke_role()
toy_admin_log()
```

권한 확인은 view가 아니라 action 파일 초기에 수행합니다.

## 감사 로그 기준

반드시 기록할 이벤트:

- 관리자 접근 거부
- 사이트 설정 변경
- 모듈 활성화/비활성화
- 회원 상태 변경
- 관리자 역할 부여/회수
- 개인정보 요청 상태 변경
- 개인정보 요청 내보내기
- 보관 기간 정리 실행

기록하지 않을 값:

- 비밀번호
- 토큰 원문
- 세션 ID
- DB 접속 비밀번호
- 개인정보 원문 전체

## 보안 기준

- 모든 관리자 POST 요청은 CSRF 검증
- 모든 관리자 action은 로그인과 권한을 서버에서 검증
- 관리자 화면은 기본 `noindex`
- 회원 상태 변경과 권한 변경은 감사 로그 기록
- 설정값의 key는 허용 목록으로 제한
- 모듈 key와 action 경로는 허용 형식으로 검증
- 관리자 목록과 회원 목록은 페이지네이션 적용
- 개인정보 관련 화면은 별도 권한으로 분리

## 현재 구현 범위

- 관리자 테이블 install.sql
- 최초 관리자 역할 부여
- `/admin` 대시보드
- 관리자 접근 권한 helper
- 사이트 설정 조회와 변경
- 모듈 목록과 활성 상태 변경
- 코드에 있지만 DB에 등록되지 않은 모듈 설치
- 모듈 설치 후 활성/비활성 상태 선택
- 회원 목록과 상태 변경
- 회원 세션 강제 폐기
- 관리자 역할 관리
- 감사 로그 조회
- 개인정보 요청 관리
- 보관 기간 수동 정리
- 업데이트 확인과 실행
