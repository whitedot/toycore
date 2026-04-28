# 다국어 처리 계획

Toycore는 단일 언어 사이트를 쉽게 만들 수 있어야 하지만, 구조적으로는 다국어 확장을 막지 않아야 합니다.

Toycore의 코어 다국어 기능은 UI 문구, locale 결정, fallback, 회원 선호 언어까지로 제한합니다. 게시글, 페이지, 상품, 주문 같은 사용자 콘텐츠의 다국어화는 각 모듈의 책임으로 둡니다.

다국어는 별도 플러그인으로 분리하지 않습니다. 코어가 얇은 공통 기능을 항상 제공하고, 각 모듈이 자기 UI 문구 번역 파일을 갖는 구조로 시작합니다.

## 목표

- 기본 locale 하나만으로도 동작
- 회원별 선호 locale 저장
- 코어와 모듈의 번역 문자열 분리
- URL 기반 다국어 요청 분기로 확장 가능

## 책임 범위

### 기본 구조

```text
코어
- 현재 locale 결정
- 지원 locale 목록 관리로 확장 가능
- 번역 helper 제공
- fallback 처리

각 모듈
- 자기 UI 문구 번역 파일 보유
- 콘텐츠 다국어가 필요하면 자체 테이블과 화면 설계
```

예시:

```text
lang/ko/core.php
lang/en/core.php

modules/member/lang/ko.php
modules/member/lang/en.php

modules/board/lang/ko.php
modules/board/lang/en.php
```

이 구조는 모든 모듈이 다국어 플러그인에 의존하는 방식이 아닙니다. 동시에 모듈마다 locale 결정 로직을 따로 갖는 방식도 아닙니다. locale 결정과 번역 helper는 코어가 공통으로 제공하고, 실제 문구와 콘텐츠 번역 구조는 각 모듈이 자기 영역에서 관리합니다.

### 코어가 담당하는 것

- 사이트 기본 locale 관리
- 회원 선호 locale 저장
- 현재 요청의 locale 결정
- 코어 UI 문구 번역
- 모듈 UI 문구 번역 파일 로드
- fallback locale 처리
- 날짜와 시간 출력 기준 제공

### 코어가 담당하지 않는 것

- 다국어 플러그인 설치/활성 상태에 따라 UI 번역 기능을 켜고 끄는 구조
- 게시글 본문 다국어 버전 관리
- 페이지 콘텐츠 다국어 버전 관리
- 상품명, 상품 설명, 주문 상태명 같은 도메인 데이터 번역
- locale별 SEO 메타 관리
- `hreflang` 자동 생성
- 자동 번역 API 연동
- 번역 승인 workflow
- 번역 관리자 UI
- 필드별 번역 테이블 자동 생성

이 기능들은 필요한 모듈이 각자 설계합니다. 예를 들어 `page` 모듈은 `toy_page_translations` 같은 테이블을 둘 수 있지만, 코어가 모든 모듈에 번역 테이블 구조를 강제하지 않습니다.

### 후순위로 남기는 것

- URL prefix 기반 요청 분기: `/ko/...`, `/en/...`
- 브라우저 `Accept-Language` 자동 매칭
- 관리자 화면에서 번역 문자열 편집
- locale별 캐시
- pluralization 같은 복잡한 문법 처리

후순위 항목은 기본 코어의 설치성과 단순성을 해치지 않는 범위에서만 도입합니다.

## 다국어 플러그인을 두지 않는 이유

초기 구조에서 다국어를 별도 플러그인으로 만들면 모든 모듈이 그 플러그인에 의존하게 됩니다. 플러그인이 비활성화되었을 때 기본 UI 문구 처리까지 흔들릴 수 있고, 모듈 작성 규칙도 복잡해집니다.

따라서 Toycore는 다음 기준을 따릅니다.

```text
UI 다국어: 코어 공통 기능 + 모듈별 lang 파일
콘텐츠 다국어: 필요한 모듈이 자체 구현
다국어 플러그인: 초기 구조에 두지 않음
```

## Locale 결정 순서

```text
1. URL 또는 query에서 명시한 locale
2. 로그인 회원의 locale
3. 사이트 기본 locale
4. 브라우저 Accept-Language
5. 시스템 fallback locale
```

현재 구현에서는 2번과 3번을 우선 지원합니다. 1번 URL 기반 locale은 요청 분기 구조가 안정된 뒤 도입합니다.

## 저장 구조

- `toy_sites.default_locale`: 사이트 기본 locale
- `toy_member_accounts.locale`: 회원 선호 locale

회원은 내 계정 화면에서 선호 locale을 수정할 수 있습니다.

초기 구현에서는 `toy_site_locales` 테이블을 만들지 않습니다. 여러 지원 locale을 관리자에서 관리해야 하는 요구가 생기면 후속 스키마로 추가합니다.

## 번역 파일

번역 문자열은 UI 문구에 한정하고 PHP 배열 파일로 관리합니다.

```text
lang/{locale}/core.php
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

## Helper 방향

```php
toy_t('login.title');
toy_t('member::login.title');
toy_locale();
toy_set_locale($locale);
```

초기 구현에서는 치환 기능을 최소화합니다.

```php
toy_t('welcome.name', ['name' => $name]);
```

현재 구현은 현재 locale의 번역 파일이나 key가 없으면 `ko` fallback locale을 한 번 확인하고, 그래도 없으면 key를 그대로 반환합니다. 이 덕분에 번역 파일이 준비되지 않은 모듈도 동작을 멈추지 않습니다.

## Fallback 정책

- 현재 locale에 번역 키가 없으면 `ko` fallback locale에서 찾음
- fallback locale에도 없으면 번역 키 자체를 반환
- 누락 번역은 개발 모드에서만 표시하거나 로그로 남김

## 날짜와 시간

- DB 저장은 UTC 또는 사이트 timezone 기준 중 하나로 통일
- 화면 출력은 사이트 timezone과 회원 locale을 반영
- 초기 구현에서는 `DATETIME`을 사용하되 timezone 정책을 문서화

## URL 다국어화

초기에는 다음 구조를 필수로 하지 않습니다.

```text
/ko/login
/en/login
```

다만 라우터가 추후 locale prefix를 처리할 수 있도록 요청 path 파싱을 분리합니다.

## 모듈 책임

모듈은 기본 locale 번역 파일을 반드시 제공해야 합니다.

```text
modules/member/lang/ko.php
```

다른 locale 파일은 선택입니다. 없는 번역은 fallback 정책을 따릅니다.

모듈이 사용자 콘텐츠 다국어화를 지원하려면 모듈 내부에서 별도 테이블과 화면을 설계합니다. 초기 코어는 현재 locale과 기본 locale을 제공할 뿐, 콘텐츠 저장 방식이나 지원 locale 목록 관리를 강제하지 않습니다.
