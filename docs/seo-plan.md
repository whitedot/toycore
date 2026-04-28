# SEO 대응 계획

Toycore의 SEO 대응은 코어가 모든 SEO 기능을 직접 처리하는 방식이 아닙니다. 코어는 공통 출력 기반과 기본 정책을 제공하고, 콘텐츠의 의미를 아는 모듈이 세부 SEO 값을 결정합니다.

## 결론

```text
코어: 공통 SEO 출력 기반, 기본 메타 fallback, canonical helper, robots 정책, Open Graph 출력 슬롯
모듈: 콘텐츠별 title, description, canonical, Open Graph 값, 구조화 데이터
후순위: hreflang, 분석 도구 연동
```

코어가 콘텐츠 SEO를 직접 관리하면 Toycore가 CMS 프레임워크처럼 커질 위험이 있습니다. 따라서 코어는 SEO를 위한 최소 인터페이스만 제공하고, 게시판, 페이지, 상품 같은 도메인별 SEO는 각 모듈이 담당합니다.

## 코어가 담당하는 것

- 사이트 기본 title suffix
- 사이트 기본 description
- 기본 robots 정책
- canonical URL 생성 helper
- HTML `<head>`에 SEO 값을 출력할 수 있는 공통 영역
- 모듈이 SEO 값을 넘길 수 있는 단순 배열 규칙
- Open Graph 값을 출력할 수 있는 슬롯
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
- sitemap 후보 URL
- 다국어 SEO 관계
- 콘텐츠 삭제/비공개 상태의 robots 처리

## 코어가 담당하지 않는 것

- 콘텐츠 의미를 추론해 Open Graph 값을 자동 생성
- 모든 콘텐츠 테이블에 SEO 컬럼 강제
- 모든 모듈에 동일한 slug 구조 강제
- 콘텐츠별 Open Graph 이미지 자동 생성
- 제품, 게시글, FAQ 같은 schema.org 타입 자동 판단
- SEO 점수 분석
- 키워드 추천
- 검색엔진 제출 자동화
- 분석 도구와 광고 도구 자동 연동

이 기능들은 필요할 때 각 모듈이나 별도 SEO 모듈에서 다룹니다. 코어는 값을 판단하지 않고 출력 형식만 제공합니다.

## Open Graph

Open Graph는 다음처럼 역할을 분리합니다.

```text
코어: og:title, og:description, og:image 등을 출력할 수 있는 슬롯 제공
모듈: 실제 og 값 결정
```

코어는 사이트 기본값을 fallback으로 사용할 수 있지만, 콘텐츠 의미를 추론해 OG 값을 생성하지 않습니다.

## 다국어 SEO

다국어 SEO 판단은 코어 범위에 넣지 않습니다.

후순위 항목:

- locale별 canonical
- `hreflang`
- locale별 sitemap
- locale별 Open Graph locale

이 항목은 URL prefix 기반 다국어 요청 분기가 확정된 뒤 설계합니다. 실제 locale별 canonical, `hreflang`, locale별 sitemap URL은 콘텐츠 관계를 아는 모듈이 제공합니다.

## Sitemap

초기 코어는 sitemap 자동 생성을 필수 기능으로 두지 않습니다.

권장 방향:

- 코어는 sitemap 출력 규칙만 제공
- 각 모듈은 sitemap에 포함할 URL 목록을 반환
- 실제 `/sitemap.xml` 생성은 `seo` 모듈 책임

예시 방향:

```text
page module -> 공개 페이지 URL 후보 제공
board module -> 공개 게시글 URL 후보 제공
seo module -> URL 후보를 모아 sitemap.xml 출력
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

`seo` 모듈은 기본 `/robots.txt`를 제공합니다. 기본 출력은 관리자, 계정, 로그인, 가입, 비밀번호 재설정 경로를 `Disallow`하고 `/sitemap.xml` 위치를 `Sitemap`으로 안내합니다. 관리자 화면에서 차단 경로를 설정할 수 있습니다.

## URL 정책

SEO를 위해 URL은 다음 원칙을 따릅니다.

- 같은 콘텐츠는 하나의 canonical URL을 가짐
- query string 기반 필터 페이지는 기본적으로 canonical 대상을 명확히 함
- 삭제된 콘텐츠는 404 또는 410 정책을 모듈별로 결정
- 비공개 콘텐츠는 검색엔진에 노출하지 않음

## 구현 우선순위

1. 코어 SEO 배열 규칙: 구현됨
2. 기본 `<title>`, description, canonical, robots 출력: 구현됨
3. 로그인/관리자/오류 페이지 `noindex`: 구현됨
4. 모듈이 SEO 값을 넘기는 규칙: 구현됨
5. Open Graph 출력 슬롯: 구현됨
6. SEO 모듈의 sitemap 출력: 구현됨
7. SEO 모듈의 robots.txt 출력: 구현됨
8. SEO 관리자 UI: 구현됨
9. 다국어 SEO 검토

현재 구현은 `toy_seo_tags()`와 `toy_canonical_url()`을 제공한다. 각 view 또는 모듈 action은 `$seo` 배열에 필요한 값만 명시하고, 코어 helper는 전달된 값을 escape한 뒤 `<head>` 출력 태그로 변환한다. 코어는 콘텐츠 의미를 추론하지 않으며, `hreflang`과 locale별 sitemap 같은 다국어 SEO 판단은 후순위로 둔다.

`seo` 모듈은 `/sitemap.xml`을 제공한다. 기본으로 홈 URL만 출력하고, 활성 모듈이 `modules/{module_key}/sitemap.php`를 제공하면 해당 모듈의 URL 후보를 합쳐 출력한다. 코어와 `seo` 모듈은 URL의 의미나 공개 여부를 추론하지 않으므로 콘텐츠 모듈은 공개 가능한 URL만 반환해야 한다.

`seo` 모듈은 `/robots.txt`도 제공한다. 코어가 콘텐츠 공개 여부를 판단하지 않도록 기본 운영 경로만 차단하고, 콘텐츠별 검색 노출 판단은 각 모듈의 meta robots와 sitemap 후보 반환 정책에 둔다.

`seo` 모듈의 `/admin/seo` 화면은 사이트 공통 title suffix, 기본 description, 기본 OG image, sitemap 홈 URL 포함 여부, robots 차단 경로를 관리한다. 콘텐츠별 SEO 값은 해당 콘텐츠 모듈 책임으로 남긴다.
