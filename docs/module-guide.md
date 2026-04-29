# 모듈 작성 가이드

Toycore의 모듈은 프레임워크 패키지가 아닙니다.

모듈은 정해진 디렉터리에 놓인 절차형 PHP 파일과 DB에 저장된 설치/활성 상태로 동작합니다. 자동 발견, 서비스 프로바이더, ORM, 클래스 마이그레이션, DI 컨테이너를 전제로 하지 않습니다.

## 기본 구조

```text
modules/{module_key}/
- module.php
- paths.php
- admin-menu.php (optional)
- output-slots.php (optional)
- actions/
- views/
- lang/
- install.sql
- sitemap.php (optional)
```

## 모듈과 플러그인

Toycore에서 설치/활성화 가능한 확장 단위는 같은 `toy_modules` 등록 흐름을 사용합니다. 다만 개념은 구분합니다.

```text
module = 자기 도메인과 정책을 소유하는 확장
plugin = 특정 모듈이나 계약 파일에 붙어 동작하는 확장
```

판단 기준:

- 자기 테이블, 관리자 화면, route, 정책을 소유하면 모듈입니다.
- 다른 모듈의 확장 지점에 붙어 기능을 보강하면 플러그인입니다.
- `member`, `admin`, `seo`, `popup_layer`, `point`, `deposit`, `reward`처럼 독립 기능을 소유하는 기본 확장은 모듈입니다.
- 소셜 로그인 제공자, 특정 에디터 연동, 결제 수단 어댑터처럼 다른 모듈의 계약에 붙는 확장은 플러그인입니다.

현재 DB registry는 `toy_modules`를 유지합니다. 모듈/플러그인 구분은 `module.php`의 `type` 값으로 표시합니다.

```php
<?php

return [
    'name' => 'Popup Layer',
    'version' => '2026.04.001',
    'type' => 'module',
];
```

`type`은 `module` 또는 `plugin`만 사용합니다. 타입은 운영자가 확장 성격을 이해하기 위한 값이며, 요청 흐름을 자동으로 바꾸지 않습니다.

## 모듈 의존성

다른 모듈이나 계약 파일이 있어야 정상 동작하는 모듈은 `module.php`에 `requires`를 선언합니다.

```php
<?php

return [
    'name' => 'Example Plugin',
    'version' => '2026.04.001',
    'type' => 'plugin',
    'requires' => [
        'modules' => [
            'member',
            'seo' => '2026.04.002',
        ],
        'contracts' => [
            [
                'module' => 'member',
                'file' => 'extension-points.php',
            ],
        ],
    ],
];
```

관리자 모듈 설치와 활성화 흐름은 `enabled` 상태로 만들기 전에 선언된 의존 모듈이 활성화되어 있는지 확인합니다. `module_key => version` 형태로 쓰면 최소 설치 버전도 확인합니다. 설치 후 `disabled` 상태로 둘 때는 의존성 검사를 강제하지 않습니다.

## 모듈 계약 파일

모듈 간 영향은 숨은 event bus나 자동 등록 대신 명시적인 계약 파일로 연결합니다.

계약 파일은 다음 원칙을 지킵니다.

- 활성 모듈만 계약 파일을 제공할 수 있습니다.
- 계약 파일은 모듈 디렉터리 바로 아래에 둡니다.
- 계약 파일은 실행 흐름을 만들지 않고 배열 또는 callable만 반환합니다.
- 소비 모듈은 `toy_enabled_module_contract_files()`로 활성 모듈의 계약 파일 목록을 얻은 뒤 직접 검증하고 읽습니다.
- 계약 파일을 제공하는 모듈은 자기 도메인의 공개 가능한 정보만 선언합니다.

## Output Slots

화면 출력 지점에 여러 확장 모듈이 붙을 수 있을 때는 소유 모듈 view에서 특정 확장 모듈 helper를 직접 호출하지 않습니다.

소유 모듈은 공통 helper를 호출합니다.

```php
<?php echo toy_render_output_slot($pdo, ['module_key' => 'member', 'point_key' => 'member.login']); ?>
```

출력 확장 모듈은 `output-slots.php`에서 renderer callable을 반환합니다.

```php
<?php

return static function (PDO $pdo, array $context): string {
    return '';
};
```

renderer는 현재 요청에서 즉시 출력할 HTML 문자열을 반환합니다. 아무것도 출력하지 않을 때는 빈 문자열을 반환합니다.

## Admin Menu

관리자 공통 레이아웃은 활성 모듈의 `admin-menu.php`를 읽어 모듈별 관리자 메뉴를 표시할 수 있습니다.

`admin-menu.php`는 화면을 등록하거나 실행하지 않습니다. 메뉴에 표시할 label/path/order만 반환하고, 실제 요청 처리는 같은 모듈의 `paths.php`, `actions/`, `views/`가 소유합니다.

```php
<?php

return [
    [
        'label' => '포인트',
        'path' => '/admin/points',
        'order' => 40,
    ],
];
```

규칙:

- `path`는 `/admin/...` 아래 경로만 사용합니다.
- `path`는 같은 모듈의 `paths.php`에 선언된 관리자 경로와 일치해야 합니다.
- 메뉴 노출은 admin 모듈이 조정하지만, 권한 검사와 상태 변경 처리는 소유 모듈 action에서 수행합니다.
- admin 모듈은 도메인 모듈의 메뉴 label/path를 하드코딩하지 않습니다.

## Extension Points

Toycore용 모듈은 외부 확장이 붙을 수 있는 화면이나 기능 위치를 `extension-points.php`로 선언할 수 있습니다.

`extension-points.php`는 팝업레이어 전용 파일이 아닙니다. 팝업레이어, 배너, 쿠폰, 추천, 분석 같은 확장 모듈이 자기 정책에 맞게 읽어 사용할 수 있는 표준 확장 지점 목록입니다.

기본 용어:

```text
extension point = 확장 가능한 화면/기능 단위
slot = extension point 안의 구체적인 출력 위치
subject = 특정 상품, 게시판, 글 같은 세부 대상
```

선택 깊이:

```text
1단계: module
2단계: point
3단계: slot
4단계: subject
```

Toycore의 확장 선택 UI는 4단계를 최대치로 봅니다. 5단계 이상이 필요해 보이면 단계를 늘리지 않고 `filters`, `schedule`, `device`, `locale`, `member_status` 같은 조건 필드로 분리합니다.

확장 모듈은 자기에게 필요한 깊이까지만 사용합니다.

```text
popup_layer = module -> point -> slot -> subject
banner = module -> point -> slot -> subject
analytics = module -> point
```

규칙:

- `extension-points.php`는 배열 또는 callable을 반환합니다.
- 프론트 요청에서는 이 파일을 읽지 않습니다.
- 관리자 설정 화면처럼 확장 대상을 고르는 시점에만 읽습니다.
- 실제 출력 위치는 각 모듈의 화면 파일에서 helper를 명시적으로 호출합니다.
- 코어는 파일을 안전하게 찾는 helper까지만 제공합니다.
- 계약 값의 의미, 필터링, 출력 정책은 확장 모듈이 책임집니다.

예:

```text
modules/member/extension-points.php
```

```php
<?php

return [
    [
        'point_key' => 'member.login',
        'label' => '로그인',
        'surface' => 'public',
        'output' => true,
        'slots' => [
            [
                'slot_key' => 'overlay',
                'label' => '화면',
                'kind' => 'overlay',
            ],
        ],
    ],
];
```

필드 의미:

- `point_key`: 모듈 안에서 안정적으로 유지되는 확장 지점 key
- `label`: 관리자 화면에 표시할 이름
- `surface`: `public`, `admin` 같은 노출 영역
- `output`: 출력형 확장이 붙을 수 있는지 여부
- `slots`: 실제 출력 위치 목록
- `slot_key`: point 안에서 안정적으로 유지되는 위치 key
- `kind`: `content`, `overlay`, `head`, `script` 같은 위치 성격
- `subjects`: 선택 사항. 특정 상품/게시판/글 같은 세부 대상 선택 정보를 제공할 때 사용

현재 `popup_layer` 구현은 관리자 UI에서 `module_key`, `point_key`, `slot_key`, 수동 `subject_id`를 사용합니다. `slots`가 비어 있으면 호환을 위해 `overlay` 기본 위치로 취급하고, 선언된 slot이 있으면 실제 slot 목록을 노출 대상으로 표시합니다. `subjects.options`와 `subjects.selector`는 커뮤니티, 커머스처럼 세부 대상 선택 UI가 필요한 모듈을 위한 표준 필드이며, 검색/선택 UI는 후속 관리자 화면에서 이 규격을 읽어 확장합니다.

작은 목록은 `subjects.options`로 직접 제공할 수 있습니다.

```php
<?php

return [
    [
        'point_key' => 'community.board.view',
        'label' => '게시판 보기',
        'surface' => 'public',
        'output' => true,
        'slots' => [
            [
                'slot_key' => 'before_content',
                'label' => '본문 위',
                'kind' => 'content',
            ],
        ],
        'subjects' => [
            'type' => 'board',
            'label' => '게시판',
            'options' => [
                ['value' => 'notice', 'label' => '공지사항'],
                ['value' => 'free', 'label' => '자유게시판'],
            ],
        ],
    ],
];
```

상품처럼 대상이 많을 수 있으면 전체 options를 반환하지 않고 검색형 selector 정보를 제공합니다.

```php
'subjects' => [
    'type' => 'product',
    'label' => '상품',
    'selector' => [
        'mode' => 'search',
        'action' => '/admin/commerce/products/search',
    ],
],
```

화면을 소유한 모듈은 필요한 위치에서 공통 출력 슬롯 helper를 호출합니다.

```php
<?php
echo toy_render_output_slot($pdo, [
    'module_key' => 'member',
    'point_key' => 'member.login',
]);
?>
```

특정 subject에만 출력하려면 화면 모듈이 현재 subject id를 전달합니다.

```php
<?php
echo toy_render_output_slot($pdo, [
    'module_key' => 'commerce',
    'point_key' => 'commerce.product.view',
    'subject_id' => (string) $product['id'],
]);
?>
```

이 구조에서 팝업레이어 모듈은 커머스나 회원 모듈 내부 파일을 직접 include하지 않고, 화면 소유 모듈은 팝업레이어 테이블 구조를 직접 조회하지 않습니다.

성능 기준:

- 사용자 요청에서 `extension-points.php`를 읽지 않습니다.
- 사용자 요청에서는 저장된 규칙 테이블만 조회합니다.
- 조회 조건에는 `module_key`, `point_key`, `slot_key`, `match_type`, `subject_id` 인덱스를 둡니다.
- 한 요청에서 같은 slot이 반복 호출될 가능성이 생기면 확장 모듈 helper 안에 요청 단위 static cache를 둡니다.
- 대량 subject는 options 전체 반환을 금지하고 검색형 selector를 사용합니다.

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
- sitemap.php
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

관리자 모듈 화면은 DB에 기록된 설치 버전과 함께 `module.php`의 코드 버전, 설명을 표시합니다. 이 표시는 운영자가 배포된 코드와 설치 기록의 차이를 확인하기 위한 정보이며, 모듈을 자동 등록하거나 자동 업데이트하지 않습니다.

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

### `helpers.php`와 helper 하위 파일

모듈의 공통 함수는 `helpers.php`에서 명시적으로 제공합니다. 함수가 적을 때는 한 파일로 시작할 수 있지만, 역할이 섞이기 시작하면 `helpers/` 하위 파일로 나눕니다.

권장 구조:

```text
modules/{module_key}/
- helpers.php
- helpers/{area}.php
```

예:

```php
<?php

declare(strict_types=1);

require_once TOY_ROOT . '/modules/admin/helpers/roles.php';
require_once TOY_ROOT . '/modules/admin/helpers/updates.php';
```

원칙:

- `helpers.php`는 모듈 helper의 명시적 진입점입니다.
- 하위 helper 파일은 `roles`, `updates`, `sessions`, `privacy`처럼 소유 책임이 드러나는 이름을 사용합니다.
- 너무 잘게 나누지 않고, 한 화면 흐름을 이해할 때 같이 읽는 함수는 같은 파일에 둡니다.
- helper 하위 파일은 보통 모듈당 4~7개 안쪽으로 유지합니다.
- action 파일은 가능하면 모듈의 `helpers.php`만 require하고, helper 내부의 하위 파일 순서는 `helpers.php`에서 관리합니다.
- 하위 helper 파일은 로드 시 DB 변경, route 등록, 출력 같은 부작용을 만들지 않습니다.
- 다른 모듈의 내부 helper 하위 파일을 직접 require하지 않습니다. 공개 helper가 필요하면 해당 모듈의 `helpers.php`를 통해 사용합니다.

### `paths.php`

`paths.php`는 현재 모듈이 처리할 수 있는 method/path와 action 파일의 허용 목록입니다. 이 파일은 실행 흐름을 만들지 않고 배열만 반환합니다.

코어는 활성 모듈의 `paths.php`를 읽은 뒤 현재 요청과 일치하는 항목만 선택하고, action 파일 경로가 모듈 디렉터리 안에 있는지 검증한 뒤 include합니다.

활성 모듈 둘 이상이 같은 `METHOD /path`를 선언하면 코어는 한쪽을 임의로 선택하지 않고 요청을 중단합니다. 경로 충돌은 `storage/logs/error.log`에 기록되므로, 모듈 작성자는 공개 path와 관리자 path를 안정적으로 소유해야 합니다.

### 설정 화면

모듈은 운영자가 자주 변경하거나 동작에 직접 영향을 주는 설정에 대해 전용 관리자 화면을 최대한 제공합니다.

기본 방향:

- 범용 `/admin/modules` 설정 key/value 화면은 비상용 또는 낮은 수준의 관리 도구로 둡니다.
- 모듈 설정은 가능하면 `modules/{module_key}/actions/admin-settings.php`와 전용 view에서 저장합니다.
- 전용 화면은 설정 이름, 단위, 허용 범위, 기본값의 의미가 드러나야 합니다.
- 상태 변경은 POST와 CSRF 검증을 사용합니다.
- 서버에서 허용 범위와 타입을 다시 검증합니다.
- 설정 변경은 감사 로그에 남깁니다.

예:

```text
member -> /admin/member-settings
seo -> /admin/seo
popup_layer -> /admin/popup-layers
```

범용 모듈 설정 화면은 전용 화면이 아직 없거나, 긴급히 key/value를 확인해야 할 때만 사용합니다.

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

웹 설치 화면에서는 `member`, `admin`을 필수 모듈로 설치하고, 기본 제공 선택 모듈은 선택한 경우에만 설치합니다. 설치 후에는 `/admin/modules`에서 코드에 있지만 DB에 등록되지 않은 모듈을 설치할 수 있습니다.

모듈 설정을 읽을 때는 코어 helper를 사용합니다.

```php
$settings = toy_module_settings($pdo, 'member');
$loginIdentifier = toy_module_setting($pdo, 'member', 'login_identifier', 'email');
```

이 helper는 요청 단위로 값을 메모리에 보관하지만, 파일 캐시나 외부 캐시 서버를 필수로 요구하지 않습니다.

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

## 개인정보 내보내기 확장

회원 모듈은 계정, 인증, 동의처럼 자신이 소유한 데이터만 기본 JSON 내보내기에 포함합니다. 게시판, 커머스, 예약 같은 확장 모듈의 개인정보는 각 모듈이 직접 export 계약을 제공합니다.

확장 모듈은 필요한 경우 다음 파일을 둡니다.

```text
modules/{module_key}/privacy-export.php
```

파일은 callable을 반환합니다.

```php
<?php

return function (PDO $pdo, int $accountId): array {
    return [
        'items' => [],
    ];
};
```

회원 모듈은 활성 모듈의 `privacy-export.php`만 명시적으로 include하고, 반환값을 `module_exports.{module_key}` 아래에 넣습니다. 확장 모듈은 회원 테이블에 도메인 컬럼을 추가하지 않고 `account_id`로 자기 테이블의 데이터를 조회합니다.

## Sitemap 확장

`seo` 모듈은 `toy_enabled_module_contract_files()`로 활성 모듈의 `sitemap.php` 파일을 선택적으로 읽습니다. 이 파일은 배열을 반환하거나 callable을 반환할 수 있습니다.

예:

```php
<?php

return [
    [
        'loc' => '/example',
        'lastmod' => '2026-04-28',
        'changefreq' => 'weekly',
        'priority' => '0.5',
    ],
];
```

또는:

```php
<?php

return function (PDO $pdo, ?array $site): array {
    return [
        ['loc' => '/example'],
    ];
};
```

모듈은 공개 가능한 URL만 반환해야 합니다. `seo` 모듈은 URL 형식과 XML escape만 처리하고, 콘텐츠의 공개 여부나 의미를 추론하지 않습니다.

`robots.txt`는 `seo` 모듈이 기본 운영 경로 차단과 sitemap 위치 안내만 제공합니다. 콘텐츠별 색인 여부는 각 모듈의 화면에서 meta robots 값을 정하거나, 공개 가능한 URL만 sitemap에 반환하는 방식으로 처리합니다.

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
- 활성 모듈 간 같은 method/path 중복은 오류로 처리
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

## DB 접근

모듈 action/helper는 코어가 전달한 `PDO` 인스턴스를 사용합니다.

규칙:

- 동적 값은 `PDO::prepare()`와 named placeholder로 바인딩
- `PDO::query()`는 외부 값이 섞이지 않는 고정 SQL에만 사용
- `PDO::exec()`는 설치/업데이트 SQL 파일 같은 정적 SQL 실행에만 사용
- 테이블명, 컬럼명, 정렬 방향은 사용자 입력을 그대로 쓰지 않고 허용 목록에서 선택
- 자세한 기준은 [DB 접근 정책](database-access-policy.md)을 따름

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
