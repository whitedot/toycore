# 진입점 및 홈 요청 분기 계획

Toycore의 최초 진입점은 코어가 책임집니다. 특정 모듈이 루트 `index.php`나 `/` 요청 자체를 소유하지 않습니다.

요청 분기는 전역 path 등록 API가 아니라, 코어의 명시적 분기와 검증된 include를 기본으로 합니다.

## 결론

```text
최초 진입점: 코어 책임
요청 path 해석: 코어 책임
홈 제공자 결정: 사이트 설정
홈 콘텐츠 출력: 설정된 모듈 또는 코어 기본 화면
```

`page` 모듈은 홈 화면 제공자가 될 수 있지만 필수 의존성은 아닙니다. `page` 모듈이 없더라도 Toycore는 설치 확인 화면, 기본 안내 화면, 점검 화면, 오류 화면을 출력할 수 있어야 합니다.

## 코어가 담당하는 것

- `index.php` 진입
- 부트스트랩 실행
- 사이트 설정 로드
- 현재 path 확인
- 활성 모듈 확인
- 홈 path 설정 조회
- 설정된 홈 모듈이 활성 상태인지 확인
- 현재 요청에 맞는 action 파일 경로 검증
- 404, 점검 모드, 설치 오류, 기본 홈 화면 처리

## 모듈이 담당하는 것

모듈은 자신이 제공하는 콘텐츠와 action 파일을 담당합니다.

예시:

- `page` 모듈: slug 기반 페이지 조회, 페이지 본문 출력, 페이지별 SEO, 페이지 콘텐츠 다국어
- `board` 모듈: 게시판 목록, 게시글 상세, 게시글 SEO
- `shop` 모듈: 상품 목록, 상품 상세, 상품 SEO
- `member` 모듈: 로그인, 회원가입, 계정 관리

## 홈 제공 방식

홈 화면은 사이트 설정으로 결정합니다.

예시 설정:

```text
home.module = page
home.action = home
home.value = home
```

더 단순한 표현도 허용할 수 있습니다.

```text
home_route = page:home
```

요청 흐름:

```text
GET /
-> 코어가 사이트 설정 확인
-> home.module 확인
-> 해당 모듈 활성 상태 확인
-> 모듈 action 파일 include
-> 모듈이 콘텐츠 출력
```

## 절차형 예시

```php
if ($path === '/') {
    $home_module = toy_setting('home.module', 'core');

    if ($home_module === 'page' && toy_module_enabled('page')) {
        include TOY_ROOT . '/modules/page/actions/home.php';
        exit;
    }

    include TOY_ROOT . '/core/views/home.php';
    exit;
}
```

이 예시는 방향을 보여주기 위한 것입니다. 실제 구현에서는 include 경로를 조립하기 전에 모듈 키와 action 파일 이름을 허용 목록 또는 정해진 규칙으로 검증합니다.

## 일반 path 처리 방식

홈 이외의 path도 같은 원칙을 따릅니다.

모듈은 실행형 path 등록을 하지 않고, 처리 가능한 method/path 목록만 배열로 제공합니다.

```php
<?php

return [
    'GET /login' => 'actions/login.php',
    'POST /login' => 'actions/login.php',
];
```

코어 처리 흐름:

```text
1. 현재 method/path 확인
2. 활성 모듈 목록 확인
3. 활성 모듈의 paths.php 배열 읽기
4. 현재 method/path와 일치하는 항목 찾기
5. action 파일 상대 경로 검증
6. 검증된 action 파일 include
```

금지하는 방향:

- `toy_route()` 같은 전역 등록 함수 중심 요청 분기
- 모듈 include 시점에 path가 암묵적으로 등록되는 구조
- 파일명, 클래스명, attribute 자동 스캔
- 활성화되지 않은 모듈의 action 파일 include
- 설정값을 검증 없이 파일 경로로 조립

## page 모듈의 위치

`page` 모듈은 홈 제공자가 될 수 있습니다.

하지만 다음을 전제로 하지 않습니다.

- `page` 모듈이 항상 설치되어 있어야 함
- `/` 요청은 항상 `page` 모듈이 처리해야 함
- 코어가 `page` 모듈의 테이블 구조를 알아야 함
- 모든 사이트 홈이 페이지 콘텐츠여야 함

홈은 나중에 `board`, `shop`, `landing`, `member` 같은 다른 모듈이 제공할 수도 있습니다.

## 기본 fallback

홈 제공자로 설정된 모듈이 없거나 비활성 상태라면 코어는 기본 화면을 출력합니다.

우선순위:

```text
1. 설정된 home.module이 활성 상태면 해당 모듈 action 파일 실행
2. 설정이 없으면 core 기본 home 출력
3. 설정은 있으나 모듈이 비활성/누락이면 관리자에게 알 수 있는 기본 오류 화면 출력
```

## SEO와 다국어 관계

홈이 어떤 모듈에서 제공되든, 홈 콘텐츠의 SEO와 콘텐츠 다국어는 해당 모듈이 담당합니다.

코어는 다음만 제공합니다.

- 현재 locale
- 지원 locale 목록
- 기본 SEO 출력 변수
- canonical helper

예를 들어 `page` 모듈이 홈을 제공한다면, 홈 페이지의 locale별 콘텐츠, title, description, canonical, hreflang 대상은 `page` 모듈이 결정합니다.
