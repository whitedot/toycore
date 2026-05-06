# 회원 모듈 상세 계획

`member` 모듈은 Toycore의 기본 배포에 포함되는 필수 모듈입니다. 하지만 코어에 내장된 인증 기능이 아니라, 설치 과정에서 명시적으로 설치되고 활성화되는 기본 모듈로 취급합니다.

이 문서는 회원 가입, 로그인, 세션, 비밀번호 재설정, 동의 기록, 회원 탈퇴의 책임 범위를 정리합니다.

회원 모듈은 모든 사용자 관련 데이터를 담는 범용 저장소가 아닙니다. `member`는 계정, 인증, 본인 프로필, 동의, 탈퇴/익명화의 최소 기반을 제공하고, 커뮤니티와 커머스처럼 도메인 의미가 강한 정보는 각 모듈이 자기 테이블로 확장합니다.

## 목표

- 최초 관리자 계정 생성을 지원할 수 있는 계정 기반 제공
- 일반 회원 가입과 로그인 흐름 제공
- 비밀번호 hash, 세션 token hash, 재설정 token hash 원칙 준수
- 동의 기록과 계정 비활성화/익명화 기반 제공
- `admin` 모듈이 계정 상태와 권한을 확인할 수 있는 최소 helper 제공

## 책임 범위

`member` 모듈이 담당합니다.

- 회원 계정 생성
- 로그인, 로그아웃
- 비밀번호 변경
- 비밀번호 재설정 요청과 처리
- 이메일 인증 기반
- 회원 프로필
- 회원 동의 기록
- 회원 탈퇴, 비활성화, 익명화
- 인증 로그 기록
- 현재 로그인 계정 조회 helper
- 다른 모듈이 계정 ID를 기준으로 확장 데이터를 연결할 수 있는 계약

`member` 모듈이 담당하지 않습니다.

- 관리자 화면 레이아웃
- 관리자 권한 부여와 회수
- 사이트 설정 관리
- 모듈 활성화 관리
- 전체 개인정보 요청 처리 워크플로
- 게시글, 댓글, 쪽지, 팔로우 같은 커뮤니티 데이터
- 주문, 배송지, 장바구니, 쿠폰 같은 커머스 데이터
- 포인트, 예치금, 적립금처럼 별도 회원 연계 모듈이 소유하는 잔액/거래 원장
- 콘텐츠 도메인별 회원 등급과 활동 점수

## 회원 정보 범위

회원 정보는 수집 목적에 따라 나눕니다. 기본 구현에서는 인증에 필요한 정보만 필수로 두고, 나머지는 선택 또는 별도 모듈 책임으로 둡니다.

| 분류 | 예시 | 소유 위치 | 기본 방침 |
| --- | --- | --- | --- |
| 계정 필수 | `account_identifier_hash`, `password_hash`, `status` | `member` | 가입과 로그인에 필요한 최소 정보 |
| 연락/인증 | `email`, `email_hash`, `email_verified_at` | `member` | 메일 발송과 이메일 인증에 필요한 정보 |
| 표시 기본 | `display_name`, `locale` | `member` | 여러 모듈에서 공통으로 표시 가능한 낮은 의미의 정보 |
| 프로필 선택 | `nickname`, `phone`, `birth_date`, `avatar_path` | `member` | 서비스가 선택적으로 켤 수 있는 본인 프로필 |
| 인증/보안 | 세션 token hash, 재설정 token hash, 인증 로그 | `member` | 원문 저장 금지, 보관 기간 관리 |
| 동의 | 약관, 개인정보 처리방침, 마케팅 수신 | `member` | 버전과 시점을 이력으로 기록 |
| 관리자 권한 | 역할, 권한 키 | `admin` | 계정에 연결하되 `member`에 넣지 않음 |
| 커뮤니티 활동 | 게시글 수, 댓글 수, 신고, 팔로우, 차단, 커뮤니티 등급 | `board`, `community` 등 | 커뮤니티 모듈이 정책과 테이블 소유 |
| 커머스 활동 | 주문, 배송지, 장바구니, 쿠폰, 구매 등급 | `shop`, `order` 등 | 커머스 모듈이 정책과 테이블 소유 |
| 회원 연계 원장 | 포인트, 예치금, 적립금 | `point`, `deposit`, `reward` | 포인트 모듈, 예치금 모듈, 적립금 모듈이 각각 정책과 테이블 소유 |

`display_name`은 계정 공통 표시명입니다. 커뮤니티에서 별도 닉네임 정책이 필요하면 `toy_community_profiles` 같은 모듈 테이블에 저장합니다.

`phone`, `birth_date`, `avatar_path`는 모든 사이트에 필요한 정보가 아니므로 필수 가입 항목으로 두지 않습니다. 사이트 설정 또는 모듈 설정으로 사용 여부를 결정합니다.

## 사이트 범위 결정

회원 기본 테이블에는 `site_id`를 넣지 않습니다.

Toycore의 첫 구현은 단일 사이트 운영을 기준으로 합니다. 사이트 이름, base URL, timezone, default locale, 운영 상태 같은 값은 `toy_site_settings`의 필수 키로 저장하고, 회원 계정이 별도 `site_id`를 들고 다닐 필요는 없습니다.

회원 테이블에 `site_id`를 넣지 않는 이유:

```text
- 단일 사이트에서는 모든 회원의 site_id가 같은 값이 되어 의미가 약함
- 로그인 조회, 회원 관리, 커뮤니티/커머스 확장 쿼리가 단순해짐
- 멀티사이트를 실제 기능으로 제공할지 아직 결정하지 않았음
- 미래 확장을 위해 현재 구현을 복잡하게 만들지 않는 편이 Toycore 원칙에 맞음
```

따라서 회원 로그인 중복 검사는 전역 기준으로 처리합니다.

```text
account_identifier_hash
email_hash
```

나중에 멀티사이트를 실제 기능으로 제공한다면 그때 `site_id`를 회원/관리자/모듈 데이터에 추가하는 스키마 업데이트를 설계합니다. 초기부터 모든 테이블에 `site_id`를 넣는 방식은 피합니다.

## 로그인 식별자 정책

관리자는 회원 로그인 방식을 선택할 수 있습니다.

권장 설정:

```text
member.login_identifier = email
member.login_identifier = login_id
```

기본값은 `email`을 우선 검토합니다. 이메일 로그인은 사용자가 별도 아이디를 기억하지 않아도 되고, 비밀번호 재설정과 이메일 인증 흐름도 단순해집니다.

다만 사이트 성격에 따라 이메일을 로그인 아이디로 쓰지 않고 별도 아이디를 받을 수 있어야 합니다. 예를 들어 커뮤니티형 사이트는 공개 활동명과 별개로 로그인용 아이디를 사용할 수 있습니다.

로그인 식별자는 원문 값으로 조회하지 않고, 정규화한 값의 hash로 조회하는 방향을 기본으로 합니다.

정규화 예시:

```text
email: trim + lowercase
login_id: trim + lowercase 또는 사이트 정책에 따른 정규화
```

저장/조회 예시:

```text
입력값: User@example.com
정규화: user@example.com
저장/조회: account_identifier_hash
```

hash는 단순 `hash()`보다 사이트 설정의 비밀값을 사용하는 HMAC 방식을 우선합니다.

```text
hash_hmac('sha256', normalized_identifier, app_key)
```

이 hash는 로그인 조회와 중복 검사에 사용합니다. 비밀번호 hash와 달리 같은 입력을 다시 조회해야 하므로 deterministic hash가 필요합니다. `app_key`가 유출되면 보호 수준이 낮아지므로 설정 파일과 백업 관리가 중요합니다.

원문 아이디 저장 방침:

- 로그인 방식이 `email`이면 이메일 원문은 메일 발송과 계정 안내를 위해 `email`에 저장할 수 있음
- 이메일 조회와 중복 검사는 `email_hash`로 수행
- 로그인 방식이 `login_id`이면 로그인용 아이디 원문은 기본적으로 저장하지 않고 `login_id_hash`로 관리
- 사용자가 볼 표시명은 `display_name` 또는 프로필/커뮤니티 모듈의 닉네임을 사용

따라서 로그인 아이디와 화면 표시 이름을 분리합니다. 아이디는 인증용 식별자이고, 표시 이름은 출력용 값입니다.

## 기본 디렉터리

```text
modules/member/
- module.php
- paths.php
- install.sql
- actions/
  - login.php
  - register.php
  - account.php
  - withdraw.php
  - email-verification-request.php
  - email-verify.php
  - privacy-requests.php
  - privacy-export.php
  - password-reset-request.php
  - password-reset.php
  - logout.php
- views/
  - login.php
  - register.php
  - account.php
  - withdraw.php
  - email-verified.php
  - privacy-requests.php
  - password-reset-request.php
  - password-reset.php
- helpers.php
- lang/
  - ko.php
```

구현되지 않은 회원 action 파일은 미리 만들지 않습니다. 새 회원 기능은 각 단계에서 실제 action이 생길 때 추가합니다.

`helpers.php`는 코어가 자동으로 불러오는 파일이 아닙니다. 코어 또는 `admin` 모듈이 인증 확인이 필요한 시점에 명시적으로 include합니다.

## 기본 테이블

`member` 모듈은 다음 테이블을 소유합니다.

MVP 테이블:

```text
toy_member_accounts
toy_member_auth_logs
toy_member_password_resets
toy_member_email_verifications
toy_member_consents
```

구현 테이블:

```text
toy_member_profiles
toy_member_sessions
```

확장을 위해 `member` 테이블에 도메인별 컬럼을 계속 추가하지 않습니다. 예를 들어 쇼핑몰 회원 등급, 커뮤니티 활동 점수, 배송 기본 주소는 `toy_member_accounts`나 `toy_member_profiles`에 넣지 않고 해당 모듈 테이블에 둡니다.

### `toy_member_accounts`

계정의 핵심 정보를 저장합니다.

권장 필드:

```text
id
account_identifier_hash
login_id_hash
email
email_hash
password_hash
display_name
locale
status
email_verified_at
last_login_at
created_at
updated_at
```

권장 유니크 키:

```text
account_identifier_hash
email_hash
```

`account_identifier_hash`는 현재 로그인 방식에 따른 대표 식별자 hash입니다. `member.login_identifier = email`이면 `email_hash`와 같은 값이 될 수 있고, `member.login_identifier = login_id`이면 `login_id_hash`를 사용합니다.

`login_id_hash`는 별도 아이디 로그인을 사용하는 경우에만 필요합니다. 별도 아이디 원문은 기본 저장 대상이 아닙니다.

`email`은 메일 발송과 사용자 안내에 필요한 연락처입니다. 이메일 원문으로 계정을 찾거나 중복 검사하지 않고 `email_hash`를 사용합니다.

`status` 권장 값:

```text
active
pending
suspended
withdrawn
anonymized
```

### `toy_member_profiles`

계정의 선택 프로필을 저장합니다.

필수 인증 정보와 선택 프로필 정보를 분리하기 위해 별도 테이블로 둡니다.

권장 필드:

```text
id
account_id
nickname
phone
birth_date
avatar_path
profile_text
created_at
updated_at
```

`nickname`은 기본 프로필의 선택 표시명입니다. 커뮤니티 모듈이 게시판별 닉네임, 익명 닉네임, 활동명 정책을 제공해야 한다면 별도 테이블을 사용합니다.

### `toy_member_sessions`

로그인 세션과 장기 로그인 토큰을 저장합니다.

토큰 원문은 저장하지 않습니다.

현재 구현은 PHP 기본 세션을 유지하면서 DB 세션 테이블에 로그인 세션을 기록합니다. 관리자 화면에서는 계정별 활성 세션 수를 확인하고 해당 계정의 세션을 강제로 폐기할 수 있습니다. 장기 로그인 토큰은 필드만 열어두고 별도 자동 로그인 흐름은 아직 만들지 않습니다.

권장 필드:

```text
id
account_id
session_token_hash
remember_token_hash
ip_address
user_agent
expires_at
revoked_at
created_at
last_seen_at
```

### `toy_member_auth_logs`

인증 이벤트를 기록합니다.

기록 대상:

- 회원 가입 성공/실패
- 로그인 성공/실패
- 로그아웃
- 비밀번호 변경
- 비밀번호 재설정 요청
- 비밀번호 재설정 성공/실패
- 이메일 인증 성공/실패
- 계정 잠금 또는 해제

비밀번호, 토큰 원문, 세션 ID는 기록하지 않습니다.

### `toy_member_consents`

약관, 개인정보 처리방침, 마케팅 수신 같은 동의 이력을 저장합니다.

동의는 현재 상태만 덮어쓰지 않고 버전과 시점을 기록합니다.

### `toy_member_email_verifications`

이메일 인증 토큰 hash와 만료 시점을 저장합니다.

권장 필드:

```text
id
account_id
email
verification_token_hash
expires_at
verified_at
created_at
```

### `toy_member_password_resets`

비밀번호 재설정 토큰 hash와 사용 여부를 저장합니다.

권장 필드:

```text
id
account_id
reset_token_hash
expires_at
used_at
created_at
```

## 확장 설계 원칙

다른 모듈은 `account_id`를 기준으로 회원 데이터를 확장합니다. `member` 모듈은 계정의 존재, 상태, 표시 가능한 기본값을 제공하고, 확장 모듈은 자기 도메인의 의미와 정책을 책임집니다.

기본 원칙:

- `member`는 로그인 가능한 계정의 기준만 제공
- 도메인 정보는 도메인 모듈 테이블에 저장
- 다른 모듈은 기본적으로 `account_id`를 저장
- 멀티사이트 기능이 실제로 도입되기 전까지 회원 확장 테이블에 `site_id`를 관성적으로 추가하지 않음
- 계정 삭제/익명화 후에도 필요한 운영 이력이 깨지지 않도록 snapshot 또는 표시명 캐시를 고려
- 모듈이 회원 상태를 바꾸고 싶을 때는 `member` helper를 통해 처리
- 회원 목록에 표시할 확장 정보는 직접 join보다 모듈별 요약 helper 또는 명시적 include로 제공

확장 테이블 예시:

```text
toy_community_profiles
toy_community_user_stats
toy_community_blocks
toy_shop_customers
toy_shop_addresses
toy_shop_carts
toy_shop_orders
toy_shop_reward_points
```

테이블 이름은 프로젝트 접두사 `toy_`를 사용하고, 모듈 의미가 드러나게 붙입니다.

## 커뮤니티 확장

커뮤니티 기능은 회원 계정을 사용하지만, 활동 정책은 `member`가 아니라 커뮤니티 계열 모듈이 담당합니다.

커뮤니티 모듈이 가질 수 있는 정보:

```text
account_id
community_display_name
bio
signature
activity_score
post_count
comment_count
report_count
blocked_until
created_at
updated_at
```

예상 테이블:

```text
toy_community_profiles
toy_community_user_stats
toy_community_reports
toy_community_blocks
toy_community_follows
```

연동 방식:

```text
게시글 작성
-> member 로그인 상태 확인
-> member 계정 status 확인
-> community 글쓰기 권한/차단 상태 확인
-> community 테이블에 작성자 account_id 저장
-> 필요하면 작성 당시 display_name snapshot 저장
```

작성자 이름 표시 정책은 커뮤니티 모듈이 결정합니다. 기본 계정 표시명을 그대로 사용할 수도 있고, 커뮤니티 전용 닉네임을 사용할 수도 있습니다.

탈퇴 또는 익명화 시 커뮤니티 모듈은 다음 정책 중 하나를 선택합니다.

- 게시글 유지, 작성자 익명화
- 게시글 비공개
- 회원 요청에 따라 삭제 후보로 표시

이 판단은 콘텐츠 정책이므로 `member`가 자동으로 처리하지 않습니다.

## 커머스 확장

커머스 기능은 회원 계정을 고객 식별 기반으로 사용할 수 있지만, 주문과 결제 이력은 커머스 모듈 책임입니다.

커머스 모듈이 가질 수 있는 정보:

```text
account_id
customer_status
customer_group
default_shipping_address_id
marketing_email_enabled
marketing_sms_enabled
reward_point_balance
created_at
updated_at
```

예상 테이블:

```text
toy_shop_customers
toy_shop_addresses
toy_shop_orders
toy_shop_order_items
toy_shop_carts
toy_shop_coupons
toy_shop_reward_points
```

연동 방식:

```text
주문 생성
-> member 로그인 상태 확인 또는 비회원 주문 정책 확인
-> shop customer 레코드 확인 또는 생성
-> 배송지와 주문자 정보를 주문 snapshot으로 저장
-> 주문은 account_id가 없어도 깨지지 않게 설계
```

배송지, 수령자명, 주문자 전화번호, 결제 정보는 `member` 기본 프로필에 저장하지 않습니다. 주문 당시 값은 법적/운영 이력을 위해 커머스 모듈의 주문 snapshot으로 보존합니다.

회원 탈퇴 후에도 주문/환불/세금/분쟁 대응을 위해 커머스 이력이 일정 기간 보존될 수 있습니다. 이때 `member`는 계정 상태와 익명화 기준만 제공하고, 커머스 모듈이 보관 기간과 마스킹 정책을 적용합니다.

## 확장 hook 대신 명시적 협력

초기 구현에서는 이벤트 dispatcher나 hook 자동 등록을 도입하지 않습니다. 대신 요청 흐름이 보이도록 필요한 모듈 helper를 action 파일에서 명시적으로 include합니다.

예시:

```php
<?php

require TOY_ROOT . '/modules/member/helpers.php';
require TOY_ROOT . '/modules/community/helpers.php';

$account = toy_member_require_login();
toy_community_require_can_post($account['id']);
```

탈퇴처럼 여러 모듈의 후처리가 필요한 기능은 초기에는 명시적 처리 목록을 사용합니다.

```text
member withdraw
-> member 계정 상태 변경
-> member 세션 폐기
-> community 익명화 후보 처리 helper 호출
-> shop 고객 마스킹/보관 정책 helper 호출
-> 감사 로그 기록
```

이 방식은 자동 hook보다 덜 화려하지만, 어떤 파일이 어떤 처리를 하는지 읽히는 Toycore 원칙에 더 맞습니다.

## 모듈 간 계약

다른 모듈이 의존할 수 있는 안정적인 값:

```text
account.id
account.account_identifier_hash
account.display_name
account.locale
account.status
account.email_verified_at
```

다른 모듈이 직접 변경하지 않는 값:

```text
password_hash
account_identifier_hash
login_id_hash
email
email_hash
email_verified_at
last_login_at
session_token_hash
remember_token_hash
reset_token_hash
```

다른 모듈이 회원 상태를 확인할 때는 다음 기준을 사용합니다.

```text
active: 일반 기능 사용 가능
pending: 인증 완료 전 제한 가능
suspended: 로그인 또는 활동 제한
withdrawn: 탈퇴 처리됨
anonymized: 식별 정보 제거됨
```

커뮤니티의 글쓰기 차단, 커머스의 구매 제한처럼 도메인별 제한은 계정 `status`를 남용하지 않고 각 모듈의 상태값으로 관리합니다.

## 기본 path

`paths.php`는 배열만 반환합니다.

```php
<?php

return [
    'GET /login' => 'actions/login.php',
    'POST /login' => 'actions/login.php',
    'POST /logout' => 'actions/logout.php',
];
```

```php
<?php

return [
    'GET /register' => 'actions/register.php',
    'POST /register' => 'actions/register.php',
    'GET /account' => 'actions/account.php',
    'POST /account' => 'actions/account.php',
    'GET /account/privacy-requests' => 'actions/privacy-requests.php',
    'POST /account/privacy-requests' => 'actions/privacy-requests.php',
    'GET /account/privacy-export' => 'actions/privacy-export.php',
    'POST /account/email-verification' => 'actions/email-verification-request.php',
    'GET /email/verify' => 'actions/email-verify.php',
    'GET /email/verified' => 'actions/email-verified.php',
    'GET /password/reset' => 'actions/password-reset-request.php',
    'POST /password/reset' => 'actions/password-reset-request.php',
    'GET /password/reset/confirm' => 'actions/password-reset.php',
    'POST /password/reset/confirm' => 'actions/password-reset.php',
    'GET /account/withdraw' => 'actions/withdraw.php',
    'POST /account/withdraw' => 'actions/withdraw.php',
    'POST /logout' => 'actions/logout.php',
];
```

관리자 전용 회원 관리는 `admin` 모듈의 path에서 처리합니다.

## 가입 흐름

```text
GET /register
-> 가입 허용 설정 확인
-> 가입 form 출력

POST /register
-> CSRF 검증
-> 입력값 검증
-> 로그인 식별자 정규화
-> account_identifier_hash/email_hash 중복 확인
-> password_hash() 저장
-> 필수 동의 기록
-> 이메일 인증 토큰 생성과 hash 저장
-> 인증 메일 발송 요청
-> 인증 로그 기록
-> 이메일 인증 사용 시 자동 로그인 없이 로그인 화면으로 이동
-> 이메일 인증 미사용 시 로그인 세션 생성 후 계정 화면으로 이동
```

현재 구현은 PHP `mail()` 기반 기본 mail helper로 인증 메일 발송을 시도합니다. 로컬 개발이나 발송 실패 환경에서는 토큰 구조 검증을 위해 디버그 링크를 유지합니다.

## 로그인 흐름

```text
GET /login
-> 이미 로그인 상태면 계정 화면 또는 이전 URL로 이동
-> 로그인 form 출력

POST /login
-> CSRF 검증
-> 입력한 로그인 식별자 정규화
-> account_identifier_hash로 계정 조회
-> 계정 상태 확인
-> password_verify() 검증
-> 이메일 인증 사용 시 email_verified_at 확인
-> 미인증 계정은 비밀번호가 맞을 때 인증 메일을 제한 정책 안에서 재발송
-> 필요 시 password_hash를 현재 PHP 기본 알고리즘/비용으로 재해시
-> session_regenerate_id(true)
-> session token 생성과 hash 저장
-> session token 생성 실패 시 기존 session token hash 정리
-> last_login_at 갱신
-> 인증 로그 기록
-> 목적지로 redirect
```

로그인 실패 메시지는 계정 존재 여부를 노출하지 않는 문구를 사용합니다.

## 로그아웃 흐름

```text
POST /logout
-> CSRF 검증
-> 현재 session token hash 폐기
-> remember token hash 폐기
-> PHP session 정리
-> 인증 로그 기록
-> 홈 또는 로그인 화면으로 redirect
```

## 비밀번호 재설정 흐름

```text
GET /password/reset
-> 요청 form 출력

POST /password/reset
-> CSRF 검증
-> email 입력 검증
-> 계정이 있으면 reset token 생성과 hash 저장
-> 메일 발송 요청
-> 인증 로그 기록
-> 계정 존재 여부와 무관하게 동일한 안내 출력

GET /password/reset/confirm
-> token 형식 확인
-> token hash 조회
-> 만료/사용 여부 확인
-> session에 token hash와 보관 시각만 저장하고 token 없는 confirm URL로 redirect
-> 새 비밀번호 form 출력

POST /password/reset/confirm
-> CSRF 검증
-> session token hash 보관 시간과 DB token 상태 재검증
-> 새 비밀번호 검증
-> password_hash() 저장
-> reset token used_at 기록
-> 기존 session/remember token 폐기
-> 현재 PHP session이 같은 계정이면 즉시 정리
-> 인증 로그 기록
-> 로그인 화면으로 redirect
```

## 회원 탈퇴와 익명화

기본 정책은 물리 삭제보다 비활성화와 익명화를 우선합니다.

권장 흐름:

```text
POST /account/withdraw
-> CSRF 검증
-> 현재 비밀번호 재확인
-> status를 withdrawn으로 변경
-> 세션과 장기 로그인 토큰 폐기
-> 프로필 선택 정보 삭제 또는 익명화
-> 동의 철회 기록
-> 인증 로그 기록
```

개인정보 요청 이력과 감사 로그는 계정 삭제 후에도 깨지지 않도록 논리 참조 또는 snapshot/hash를 사용합니다.

## helper 방향

`member` 모듈은 다른 모듈이 인증 상태를 확인할 수 있도록 작은 helper만 제공합니다.

현재 로그인 계정 조회는 세션의 계정 ID가 비정상이거나 계정 레코드가 사라진 경우 PHP 세션을 즉시 정리합니다.

예상 helper:

```text
toy_member_current_account()
toy_member_require_login()
toy_member_find_account_by_id()
toy_member_account_has_status()
toy_member_can_login()
toy_member_can_use_site()
toy_member_create_account()
toy_member_update_account_status()
toy_member_verify_password()
toy_member_destroy_sessions()
toy_member_anonymize_account()
```

helper는 화면 출력이나 redirect를 과도하게 숨기지 않습니다. action 파일에서 요청 흐름이 보이도록 유지합니다.

확장 모듈을 위한 조회 helper는 최소 정보만 반환합니다.

```text
toy_member_public_account_summary()
```

반환 후보:

```text
id
display_name
locale
status
```

이메일, 전화번호, 생년월일 같은 개인정보는 공개 요약 helper에 포함하지 않습니다.

## 보안 기준

- 비밀번호는 `password_hash()`로 저장
- 비밀번호 검증은 `password_verify()` 사용
- 로그인 성공 후 `session_regenerate_id(true)` 실행
- 비밀번호 변경 후 현재 PHP session ID, CSRF token, 회원 session token 회전
- 비밀번호 변경/탈퇴의 현재 비밀번호 재확인 실패는 계정과 IP 기준으로 제한
- 토큰 원문은 DB와 로그에 저장하지 않음
- 이메일 인증 token은 발급 이메일과 현재 계정 이메일이 일치할 때만 사용
- 모든 상태 변경 요청은 CSRF 검증
- 로그인 실패와 재설정 요청은 계정 존재 여부를 노출하지 않음
- 회원 본인 정보 조회와 변경은 서버에서 계정 ID를 검증
- 출력은 view에서 escape
- 인증 로그에는 민감 정보 원문을 남기지 않음

## 현재 구현 범위

- 회원 테이블 install.sql
- 로그인
- 로그아웃
- 현재 로그인 계정 helper
- 인증 로그
- 설치 과정의 최초 관리자 계정 생성 helper
- 공개 회원 가입
- 계정 화면
- 공개 계정 요약 helper
- 동의 기록
- 비밀번호 변경
- 비밀번호 재설정
- 이메일 인증
- 프로필 선택 항목 설정
- 회원 탈퇴
- 익명화
- 로그인 실패 제한
- 관리자 회원 관리와 연동
- 개인정보 요청 접수와 내보내기
- DB 세션 기록과 관리자 세션 폐기 연동

장기 로그인은 토큰 컬럼만 열어둔 상태이며, 자동 로그인 흐름은 아직 구현하지 않습니다.
