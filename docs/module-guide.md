# 모듈 작성 가이드

Toycore의 모듈은 프레임워크 패키지가 아닙니다.

모듈은 정해진 디렉터리에 놓인 절차형 PHP 파일과 DB에 저장된 설치/활성 상태로 동작합니다. 자동 발견, 서비스 프로바이더, ORM, 클래스 마이그레이션, DI 컨테이너를 전제로 하지 않습니다.

## 기본 구조

```text
modules/{module_key}/
- module.php
- paths.php
- actions/
- views/
- lang/
- install.sql
```

권장 예시:

```text
modules/member/
- module.php
- paths.php
- actions/login.php
- actions/logout.php
- actions/register.php
- views/login.php
- views/register.php
- lang/ko.php
- install.sql
```

## 파일 역할

### `module.php`

`module.php`는 모듈의 기본 정보를 설명하는 파일입니다. 코어가 모듈 목록이나 관리자 화면에서 표시할 이름, 버전, 기본 설정 후보 같은 정적 정보를 확인할 때 사용할 수 있습니다.

예상 역할:

- 모듈 이름, 설명, 버전, 기본 제공 여부 같은 메타 정보 반환
- 기본 설정 키와 기본값 후보 제공
- 필요 시 모듈 전용 helper 함수 정의

금지하는 역할:

- 요청 처리 자동 실행
- path 등록
- 다른 action 파일 자동 include
- DB 변경 자동 실행
- 활성화되지 않은 모듈의 부팅 처리

`module.php`는 Laravel Service Provider 같은 부팅 클래스가 아닙니다. 코어가 필요할 때 명시적으로 읽는 정보 파일이며, 요청 흐름을 숨기면 안 됩니다.

예시:

```php
<?php

return [
    'name' => 'Member',
    'version' => '2026.04.001',
    'description' => 'Member account and authentication module.',
    'settings' => [
        'allow_signup' => '1',
        'login_identifier' => 'email',
    ],
];
```

### `paths.php`

`paths.php`는 현재 모듈이 처리할 수 있는 method/path와 action 파일의 허용 목록입니다. 이 파일은 실행 흐름을 만들지 않고 배열만 반환합니다.

코어는 활성 모듈의 `paths.php`를 읽은 뒤 현재 요청과 일치하는 항목만 선택하고, action 파일 경로가 모듈 디렉터리 안에 있는지 검증한 뒤 include합니다.

### `actions/`

`actions/` 디렉터리는 요청을 실제로 처리하는 절차형 PHP 파일을 둡니다.

action 파일의 책임:

- 현재 요청의 입력값 읽기
- method별 처리 분기
- CSRF 검증
- 로그인/권한 검증
- DB 조회와 변경
- 감사 로그 또는 인증 로그 기록
- redirect 결정
- view에 넘길 변수 준비
- 필요한 view include

action 파일이 피해야 할 일:

- 전체 HTML 레이아웃을 긴 문자열로 직접 출력
- 사용자 입력을 escape 없이 출력
- 권한 검증을 view에 맡기기
- 다른 모듈의 내부 파일을 직접 include
- path를 새로 등록하거나 전역 dispatcher를 변경

action 파일은 화면을 만들기보다 요청의 의사결정과 상태 변경을 담당합니다. 출력이 필요하면 view에 필요한 변수만 준비하고 `views/` 파일을 include합니다.

### `views/`

`views/` 디렉터리는 화면 조각이나 페이지 본문을 출력하는 PHP/HTML 파일을 둡니다.

view의 책임:

- 일반 HTML 작성
- action 파일이 준비한 변수 출력
- `toy_e()` 같은 helper로 출력 escape
- form markup 작성
- 상태 변경 form에 CSRF hidden 필드 포함
- 번역 helper를 사용해 화면 문구 출력

view가 피해야 할 일:

- `$_GET`, `$_POST`, `$_COOKIE` 직접 읽기
- DB 조회나 DB 변경
- 권한 판단의 최종 결정
- redirect 처리
- 세션 상태 변경
- 비밀번호, 토큰, 개인정보 원문 로그 기록

view는 표시 담당입니다. 보안상 중요한 판단과 상태 변경은 action 파일에서 끝낸 뒤 view가 출력만 하도록 유지합니다.

### `install.sql`

`install.sql`은 모듈이 소유한 테이블과 초기 데이터 구조를 만드는 SQL 파일입니다. 코어는 모듈 내부 테이블 구조를 직접 만들지 않고, 설치 과정에서 해당 모듈의 `install.sql`을 명시적으로 실행합니다.

설치 SQL은 가능한 한 일반적인 MySQL/MariaDB 문법을 사용하고, 저가형 웹호스팅에서 실행 가능한 크기로 유지합니다.

## 모듈 키

`module_key`는 영문 소문자, 숫자, 밑줄만 사용합니다.

좋은 예:

```text
member
board
page
shop_order
```

피할 예:

```text
Member
shop-order
../admin
vendor/package
```

## 설치 방식

모듈 설치는 다음 방식으로 처리합니다.

```text
1. modules/{module_key}/install.sql 실행
2. toy_modules에 모듈 등록
3. toy_modules.status로 활성 상태 설정
4. toy_module_settings에 기본 설정 등록
```

설치 SQL은 가능한 한 일반적인 MySQL/MariaDB 문법을 사용합니다.

```sql
CREATE TABLE IF NOT EXISTS toy_member_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    account_identifier_hash CHAR(64) NOT NULL,
    login_id_hash CHAR(64) NULL,
    email VARCHAR(255) NOT NULL,
    email_hash CHAR(64) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_identifier (account_identifier_hash),
    UNIQUE KEY uq_toy_member_email_hash (email_hash)
);
```

## 활성화 방식

모듈 활성 여부는 초기 구현에서 `toy_modules.status`로 판단합니다.

```text
enabled
disabled
```

코어는 요청마다 현재 사이트에서 `enabled` 상태인 모듈만 대상으로 삼습니다. 비활성 모듈의 action 파일은 include하지 않습니다.

## Path 매핑

모듈이 처리할 수 있는 path는 `paths.php`에서 단순 배열로 반환합니다. 이 파일은 path를 등록하거나 실행하지 않습니다.

예시:

```php
<?php

return [
    'GET /login' => 'actions/login.php',
    'POST /login' => 'actions/login.php',
    'POST /logout' => 'actions/logout.php',
];
```

코어는 활성 모듈의 `paths.php`를 읽고, 현재 method/path와 일치하는 action 파일을 검증한 뒤 include합니다.

기본 원칙:

- `toy_route()` 같은 전역 path 등록 API를 기본 모델로 사용하지 않음
- `paths.php`는 실행 흐름을 만들지 않고 배열만 반환
- action 파일 경로는 모듈 디렉터리 내부의 허용된 상대 경로만 사용
- 파일명, 클래스명, attribute를 자동 스캔해서 path 매핑을 만들지 않음
- 현재 요청에 필요한 action 파일만 include

## action 파일 작성

action 파일은 절차형 PHP 파일입니다.

action 파일은 코어가 검증한 뒤 include하는 요청 처리 파일입니다. 하나의 action 파일은 하나의 화면을 보여줄 수도 있고, 같은 path의 `GET`과 `POST`를 함께 처리할 수도 있습니다.

```php
<?php

$loginId = toy_post_string('login_id', 100);
$password = toy_post_string('password', 255);

toy_require_csrf();

// 인증 처리
```

action 파일은 다음 원칙을 지킵니다.

- 입력값은 helper로 읽고 검증
- DB 접근은 PDO prepared statement 사용
- 출력은 view에서 escape helper 사용
- 권한 검사는 action 파일 초기에 수행
- redirect 후에는 실행을 종료
- 상태 변경 요청은 처리 전에 CSRF 검증
- view include 전에 출력에 필요한 변수 준비
- 오류 메시지는 민감 정보를 포함하지 않도록 제한

## View 작성

view는 일반 HTML 안에 필요한 PHP 출력만 섞어 작성합니다.

view는 action 파일이 준비한 값을 출력하는 파일입니다. view가 요청을 새로 해석하거나 DB를 직접 변경하지 않도록 합니다.

```php
<input type="text" name="login_id" value="<?php echo toy_e($loginId); ?>">
```

기본 원칙:

- 출력은 `toy_e()` 같은 escape helper 사용
- `<?= ... ?>` 숏 echo 태그를 사용하지 않음
- 전체 HTML 레이아웃을 `echo <<<HTML` heredoc 문자열로 출력하지 않음
- PHP를 닫고 일반 HTML을 작성한 뒤 필요한 위치에만 `<?php echo ...; ?>` 사용
- 사용자 입력 HTML은 기본적으로 출력하지 않음
- 상태 변경 폼에는 CSRF 토큰 포함
- redirect, 권한 검증, DB 변경은 action 파일에서 처리
- view 안에서 다른 모듈의 내부 파일을 직접 include하지 않음

## 번역 파일

모듈은 기본 locale 번역 파일을 제공해야 합니다.

```text
modules/{module_key}/lang/{locale}.php
```

예시:

```php
<?php

return [
    'login.title' => '로그인',
    'login.submit' => '로그인',
];
```

action 파일과 view는 화면 문구를 직접 고정하지 않고 번역 helper를 우선 사용합니다.

```php
toy_t('member::login.title');
```

## 금지하는 방향

Toycore 기본 구현에서는 다음 방식을 사용하지 않습니다.

- Laravel Service Provider 같은 부팅 클래스
- Composer 자동 패키지 발견
- Artisan 같은 CLI 필수 명령
- ORM 모델 중심의 데이터 접근
- 클래스 기반 migration 필수화
- DI 컨테이너
- 이벤트 버스 중심 실행
- reflection 기반 자동 요청 분기

도구를 쓰더라도 프로젝트 실행에 필수가 되지 않아야 합니다. 코어는 일반 웹호스팅에서 PHP 파일과 SQL만으로 설치되고 동작해야 합니다.
