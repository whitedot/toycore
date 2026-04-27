# SEO 대응 계획

Toycore의 SEO 대응은 코어가 모든 SEO 기능을 직접 처리하는 방식이 아닙니다. 코어는 공통 출력 기반과 기본 정책을 제공하고, 콘텐츠의 의미를 아는 모듈이 세부 SEO 값을 결정합니다.

## 결론

```text
코어: 공통 SEO 출력 기반, 기본 메타, canonical helper, robots 정책
모듈: 콘텐츠별 title, description, canonical, Open Graph, 구조화 데이터
후순위: sitemap 자동 생성, hreflang, SEO 관리자 UI, 분석 도구 연동
```

코어가 콘텐츠 SEO를 직접 관리하면 Toycore가 CMS 프레임워크처럼 커질 위험이 있습니다. 따라서 코어는 SEO를 위한 최소 인터페이스만 제공하고, 게시판, 페이지, 상품 같은 도메인별 SEO는 각 모듈이 담당합니다.

## 코어가 담당하는 것

- 사이트 기본 title suffix
- 사이트 기본 description
- 기본 robots 정책
- canonical URL 생성 helper
- HTML `<head>`에 SEO 값을 출력할 수 있는 공통 영역
- 모듈이 SEO 값을 넘길 수 있는 단순 배열 규칙
- 404, 로그인, 관리자 화면처럼 공통 페이지의 기본 robots 처리
- 운영 환경에서 `noindex` 설정이 필요한 경우의 전역 옵션

예시:

```php
$seo_title = '회원 로그인';
$seo_description = '회원 로그인을 진행합니다.';
$seo_canonical = toy_url('/login');
$seo_robots = 'noindex, nofollow';
```

view 출력은 명시적 echo를 사용합니다.

```php
<title><?php echo toy_e($seo_title); ?></title>
<meta name="description" content="<?php echo toy_e($seo_description); ?>">
<link rel="canonical" href="<?php echo toy_e($seo_canonical); ?>">
<meta name="robots" content="<?php echo toy_e($seo_robots); ?>">
```

## 모듈이 담당하는 것

모듈은 자신이 다루는 콘텐츠의 의미를 알고 있으므로 세부 SEO를 책임집니다.

예시:

- `page` 모듈: 페이지별 title, description, slug, canonical
- `board` 모듈: 게시글 title, 요약 description, 작성일, 수정일
- `shop` 모듈: 상품명, 상품 설명, 가격, 품절 여부, 상품 이미지
- `member` 모듈: 로그인/가입 페이지의 `noindex`

모듈이 담당할 항목:

- 콘텐츠별 SEO title
- 콘텐츠별 meta description
- slug와 canonical path
- Open Graph title, description, image
- Twitter card 값
- 구조화 데이터 JSON-LD
- 콘텐츠 삭제/비공개 상태의 robots 처리

## 코어가 담당하지 않는 것

- 모든 콘텐츠 테이블에 SEO 컬럼 강제
- 모든 모듈에 동일한 slug 구조 강제
- 콘텐츠별 Open Graph 이미지 자동 생성
- 제품, 게시글, FAQ 같은 schema.org 타입 자동 판단
- SEO 점수 분석
- 키워드 추천
- 검색엔진 제출 자동화
- 분석 도구와 광고 도구 자동 연동

이 기능들은 필요할 때 각 모듈이나 별도 SEO 모듈에서 다룹니다.

## 다국어 SEO

다국어 SEO는 초기 코어 범위에 넣지 않습니다.

후순위 항목:

- locale별 canonical
- `hreflang`
- locale별 sitemap
- locale별 Open Graph locale

이 항목은 URL prefix 기반 다국어 라우팅이 확정된 뒤 설계합니다.

## Sitemap

초기 코어는 sitemap 자동 생성을 필수 기능으로 두지 않습니다.

권장 방향:

- 코어는 sitemap 출력 규칙만 제공
- 각 모듈은 sitemap에 포함할 URL 목록을 반환
- 실제 `/sitemap.xml` 생성은 후순위 또는 `seo` 모듈 책임

예시 방향:

```text
page module -> 공개 페이지 URL 제공
board module -> 공개 게시글 URL 제공
seo module -> URL을 모아 sitemap.xml 출력
```

## Robots

코어는 기본 robots 정책을 제공할 수 있습니다.

초기 기본값:

```text
public page: index, follow
login/register: noindex, nofollow
admin: noindex, nofollow
error page: noindex, nofollow
```

`robots.txt`는 사이트 설정 또는 별도 파일로 관리합니다. 관리자 화면에서 직접 편집하는 기능은 후순위입니다.

## URL 정책

SEO를 위해 URL은 다음 원칙을 따릅니다.

- 같은 콘텐츠는 하나의 canonical URL을 가짐
- query string 기반 필터 페이지는 기본적으로 canonical 대상을 명확히 함
- 삭제된 콘텐츠는 404 또는 410 정책을 모듈별로 결정
- 비공개 콘텐츠는 검색엔진에 노출하지 않음

## 구현 우선순위

1. 코어 SEO 배열 규칙
2. 기본 `<title>`, description, canonical, robots 출력
3. 로그인/관리자/오류 페이지 `noindex`
4. 모듈이 SEO 값을 넘기는 규칙
5. Open Graph 기본 출력
6. sitemap 모듈 또는 SEO 모듈 검토
7. 다국어 SEO 검토
