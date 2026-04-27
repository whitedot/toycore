# 모듈 작성 가이드

Toycore의 모듈은 프레임워크 패키지가 아닙니다.

모듈은 정해진 디렉터리에 놓인 절차형 PHP 파일과 DB에 저장된 설치/활성 상태로 동작합니다. 자동 발견, 서비스 프로바이더, ORM, 클래스 마이그레이션, DI 컨테이너를 전제로 하지 않습니다.

## 기본 구조

```text
modules/{module_key}/
- module.php
- routes.php
- handlers/
- views/
- lang/
- install.sql
```

권장 예시:

```text
modules/member/
- module.php
- routes.php
- handlers/login.php
- handlers/logout.php
- handlers/register.php
- views/login.php
- views/register.php
- lang/ko.php
- install.sql
```

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
3. toy_site_modules에 사이트별 상태 등록
4. toy_module_settings에 기본 설정 등록
```

설치 SQL은 가능한 한 일반적인 MySQL/MariaDB 문법을 사용합니다.

```sql
CREATE TABLE IF NOT EXISTS toy_member_accounts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    site_id BIGINT UNSIGNED NOT NULL,
    login_id VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    status VARCHAR(30) NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_toy_member_login (site_id, login_id),
    UNIQUE KEY uq_toy_member_email (site_id, email)
);
```

## 활성화 방식

모듈 활성 여부는 `toy_site_modules.status`로 판단합니다.

```text
enabled
disabled
```

코어는 요청마다 현재 사이트에서 `enabled` 상태인 모듈만 로드합니다. 비활성 모듈의 `routes.php`는 include하지 않습니다.

## 라우트 등록

`routes.php`는 라우트 배열이나 간단한 등록 함수를 사용합니다.

예시:

```php
toy_route('GET', '/login', 'member', 'handlers/login.php');
toy_route('POST', '/login', 'member', 'handlers/login.php');
toy_route('POST', '/logout', 'member', 'handlers/logout.php');
```

라우트는 명시적으로 등록합니다. 파일명, 클래스명, attribute를 자동 스캔해서 라우트를 만들지 않습니다.

## Handler 작성

handler는 절차형 PHP 파일입니다.

```php
<?php

$loginId = toy_post_string('login_id', 100);
$password = toy_post_string('password', 255);

toy_require_csrf();

// 인증 처리
```

handler는 다음 원칙을 지킵니다.

- 입력값은 helper로 읽고 검증
- DB 접근은 PDO prepared statement 사용
- 출력은 view에서 escape helper 사용
- 권한 검사는 handler 초기에 수행
- redirect 후에는 실행을 종료

## View 작성

view는 PHP 템플릿입니다.

```php
<input type="text" name="login_id" value="<?= toy_e($loginId) ?>">
```

기본 원칙:

- 출력은 `toy_e()` 같은 escape helper 사용
- 사용자 입력 HTML은 기본적으로 출력하지 않음
- 상태 변경 폼에는 CSRF 토큰 포함

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

handler와 view는 화면 문구를 직접 고정하지 않고 번역 helper를 우선 사용합니다.

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
- reflection 기반 자동 라우팅

도구를 쓰더라도 프로젝트 실행에 필수가 되지 않아야 합니다. 코어는 일반 웹호스팅에서 PHP 파일과 SQL만으로 설치되고 동작해야 합니다.
