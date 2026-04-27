# 핵심 설계 결정

이 문서는 구현 중 흔들리면 안 되는 Toycore의 핵심 결정을 정리합니다. 다른 계획 문서가 애매할 때는 이 문서의 결정을 우선합니다.

## 1. 라우팅은 명시적 include를 기본으로 한다

Toycore는 숨은 dispatcher, service provider, annotation, reflection, 자동 라우팅을 사용하지 않습니다.

기본 흐름:

```text
public/index.php
-> path와 method 확인
-> 사이트 설정과 활성 모듈 확인
-> 허용된 모듈/handler인지 검증
-> 명시적 include
```

라우팅은 다음 방식을 피합니다.

- `toy_route()` 같은 전역 라우트 등록 API 중심 구조
- 모듈이 부팅 중 라우트를 몰래 등록하는 구조
- 클래스/attribute/reflection 자동 스캔
- Composer 자동 발견
- Laravel Service Provider 유사 구조

모듈이 라우트 정보를 제공해야 한다면 `routes.php`가 실행 중 등록하는 방식이 아니라, `paths.php`처럼 단순 배열을 반환하는 방식을 사용합니다.

예시:

```php
<?php

return [
    'GET /login' => 'handlers/login.php',
    'POST /login' => 'handlers/login.php',
    'GET /logout' => 'handlers/logout.php',
];
```

코어는 이 배열을 읽은 뒤, 모듈 활성 상태와 handler 경로를 검증하고 include합니다.

## 2. 개인정보 요청 이력은 계정 삭제 이후에도 보존 가능해야 한다

GDPR 대응 구조에서는 개인정보 요청 이력이 계정 물리 삭제로 깨지면 안 됩니다.

구현 방향:

- 계정은 기본적으로 물리 삭제보다 비활성화/익명화를 우선
- 개인정보 요청 테이블의 `account_id`는 nullable 가능성을 고려
- 요청 당시 식별에 필요한 최소 snapshot 또는 hash 저장 검토
- 개인정보 요청 이력은 법적/운영 정책에 맞춰 별도 보관 기간 적용

## 3. 토큰은 원문 저장을 기본 금지한다

세션, 장기 로그인, 비밀번호 재설정, 이메일 인증 같은 토큰은 원문을 DB에 저장하지 않습니다.

구현 방향:

- DB에는 token hash 저장
- 사용자에게 전달되는 토큰 원문은 생성 시점에만 사용
- 컬럼명은 `*_token`보다 `*_token_hash`를 우선
- 로그에는 토큰 원문과 hash 모두 남기지 않음

## 4. SEO 값의 판단은 모듈 책임이다

코어는 SEO 출력 기반만 제공합니다.

코어 담당:

- `<head>` 출력 위치
- 기본 title/description fallback
- canonical helper
- robots 기본값
- Open Graph 출력 슬롯

모듈 담당:

- 콘텐츠별 title
- 콘텐츠별 description
- 콘텐츠별 canonical
- Open Graph 값
- 구조화 데이터
- sitemap 후보 URL
- 다국어 SEO 관계

코어가 콘텐츠의 의미를 추론해서 SEO 값을 자동 생성하지 않습니다.

## 5. GDPR 기능은 최소 기반과 확장 기능을 나눈다

GDPR 대응을 모두 코어에 넣지 않습니다. 하지만 회원가입, 동의 기록, 삭제/익명화 같은 최소 기반은 초기 구조에서 빠지면 안 됩니다.

기본 방향:

- `member` 모듈: 동의 기록, 회원 탈퇴, 세션 폐기, 계정 비활성화/익명화
- 코어: 개인정보 처리에 필요한 공통 설정과 보안 helper
- `privacy` 또는 관리자 기능: 개인정보 요청 처리, 내보내기, 보관 기간 정리

자동화보다 기록과 관리자 검토 흐름을 우선합니다.
