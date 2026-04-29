# Toycore

## 토이코어 뜻

Toycore(토이코어)는 [G5 Codex 프로젝트](https://github.com/whitedot/g5codex)를 진행하며 얻은 경험을 바탕으로 시작한 실험적 코어 프로젝트입니다.

토이 프로젝트처럼 가벼운 마음으로 아이디어가 떠오를 때마다 조금씩 다듬어 가는 작업에 가깝습니다.

완성된 제품을 서둘러 만들기보다, 부담 없이 시도하고, 구조를 바꿔 보고, 재미있는 가능성을 확인해 보는 것을 목표로 합니다.

Toycore는 절차형 웹 솔루션 코어를 다시 쓰는 프로젝트입니다.

AI의 발전으로 코드 생성이 쉬워진 시대에도, 여전히 절차형 개발 방식에 익숙한 개발자와 운영자가 있습니다. Toycore는 그런 사람들을 위해 복잡한 프레임워크에 의존하지 않고, 읽기 쉽고 수정하기 쉬운 방식으로 웹 솔루션의 기본 구조를 다시 설계하는 것을 목표로 합니다.

## 프로젝트 관점

이 프로젝트는 최신 개발 흐름을 부정하기 위한 것이 아닙니다. 대신 AI와 자동화 도구를 활용하더라도, 결과물은 사람이 직접 읽고 고칠 수 있어야 한다는 관점에서 출발합니다.

Toycore는 절차형 개발 방식의 단순함을 유지하면서도, 현대적인 웹 솔루션에 필요한 확장성과 유지보수성을 함께 갖추는 것을 지향합니다.

## 핵심 원칙

- 코어는 똑똑해지지 말 것
- 흐름은 파일을 열면 보일 것
- 자동 등록보다 명시적 include
- 기능보다 경계 우선
- 처음부터 완성형 CMS를 만들지 말 것

## 예상되는 한계

Toycore는 단순한 배포와 절차형 코드의 접근성을 우선하기 때문에, 다음과 같은 한계를 가질 수 있습니다.

- 대규모 서비스에서 요구되는 복잡한 아키텍처에는 적합하지 않을 수 있음
- 프레임워크 기반 프로젝트에 비해 기본 제공 기능이 적을 수 있음
- 코드 구조와 규칙을 명확히 관리하지 않으면 절차형 코드의 복잡도가 빠르게 증가할 수 있음
- 최신 프론트엔드 개발 방식에 비해 화면 상태 관리와 컴포넌트 재사용성이 제한될 수 있음
- 저가형 웹호스팅 환경을 고려하기 때문에 고성능 서버 기능이나 백그라운드 작업 처리에는 제약이 있을 수 있음
- 보안, 인증, 권한 관리 같은 핵심 기능은 단순함보다 엄격한 검증과 지속적인 개선이 더 중요함

이러한 한계를 인식한 상태에서, Toycore는 모든 상황에 맞는 범용 프레임워크가 아니라 작고 명확한 웹 솔루션 코어를 목표로 합니다.

## 목표

- 회원 중심의 웹 솔루션 코어 구축
- PHP, 바닐라 JavaScript, plain CSS 기반 개발
- 저가형 웹호스팅 환경에서도 사용할 수 있는 구조 지향
- 작게 시작해 점진적으로 확장할 수 있는 솔루션 설계
- 절차형 코드에 익숙한 사람도 이해하고 유지보수할 수 있는 코드 구성
- 운영에 필요한 관리자 화면 제공

## 기술 방향

Toycore는 다음과 같은 기술 구성을 기본으로 합니다.

- PHP
- Vanilla JavaScript
- Plain CSS
- 일반적인 웹호스팅 환경

특정 빌드 도구, 복잡한 프론트엔드 프레임워크, 고사양 서버 환경을 전제로 하지 않습니다. 필요한 기능을 명확하게 나누고, 배포와 운영이 단순한 구조를 우선합니다.

## 현재 구현 범위

현재 코드는 웹 설치, 기본 관리자 진입, 회원 인증, SEO 출력, 업데이트 실행, 개인정보/감사 로그 기반, 팝업레이어 모듈, 포인트 모듈, 예치금 모듈, 적립금 모듈의 1차 구조를 포함합니다.

기본 설치 흐름은 필수 모듈을 항상 설치/활성화하고, 나머지 기본 제공 모듈은 설치 화면에서 설치 여부를 선택합니다.

```text
필수: core + member + admin
선택: seo + popup_layer + point + deposit + reward
```

- `member`: 회원가입, 로그인/로그아웃, 계정 화면, 비밀번호 재설정, 이메일 인증, 동의 기록, 탈퇴/익명화, DB 세션, 인증 로그, 전용 관리자 설정
- `admin`: 관리자 대시보드, 사이트 설정, 모듈 관리, 회원 관리, 권한, 감사 로그, 개인정보 요청, 보관 정리, 업데이트 실행
- `seo`: SEO meta helper, `/robots.txt`, `/sitemap.xml`, SEO 관리자 설정, 활성 모듈 `sitemap.php` 확장
- `popup_layer`: 관리자 팝업 등록/수정/삭제, 활성 모듈의 `extension-points.php` slot 기반 노출 대상 선택, 화면별 팝업 출력
- `point`: 회원별 포인트 잔액/거래 원장, 관리자 수동 지급/차감
- `deposit`: 회원별 예치금 잔액/거래 원장, 관리자 수동 입금/사용/환불/출금 기록
- `reward`: 회원별 적립금 잔액/거래 원장, 관리자 수동 지급/차감

자세한 구현 범위는 [현재 구현 상태](docs/current-implementation-status.md)를 기준으로 확인합니다.

기본 점검은 다음 명령으로 실행합니다.

```sh
./.tools/bin/check
```

Docker 또는 OrbStack이 꺼져 있어도 공백, SQL 파일, 모듈 기본 구조 검사는 먼저 실행됩니다. PHP 문법 검사는 Docker 또는 OrbStack 실행 상태가 필요합니다.

## 모듈 구조

Toycore의 모듈은 프레임워크 패키지가 아니라, 정해진 디렉터리에 놓인 절차형 PHP 파일과 DB에 저장된 설치/활성 상태로 동작합니다.

```text
modules/{module_key}/
- module.php
- paths.php
- admin-menu.php
- output-slots.php
- actions/
- views/
- lang/
- install.sql
- updates/
```

요청 흐름은 숨은 dispatcher 대신 명시적 파일 읽기를 따릅니다.

```text
index.php
-> method/path 확인
-> 활성 모듈 조회
-> 각 모듈의 paths.php 확인
-> 현재 요청에 맞는 action 파일 검증 후 include
```

모듈과 플러그인은 같은 설치/활성화 registry를 사용할 수 있지만 개념은 구분합니다.

```text
module = 자기 도메인과 정책을 소유하는 확장
plugin = 특정 모듈이나 계약 파일에 붙어 동작하는 확장
```

현재 DB registry 이름은 `toy_modules`를 유지하고, 확장의 성격은 `module.php`의 `type` 값으로 표시합니다. 자세한 작성 규칙은 [모듈 작성 가이드](docs/module-guide.md)를 따릅니다.

최소 모듈 구조 예시는 [sample_module](examples/sample_module/README.md)에서 확인할 수 있습니다.

`banner`, `popup_layer`, `site_menu`, `notification`, `seo`, `point`, `deposit`, `reward`는 1차로 별도 모듈 리포지토리에도 분리되어 있습니다. 현재 Toycore 본체의 모듈 복사본은 기본 번들 검증과 설치 호환성을 위해 유지합니다. 자세한 기준과 리포지토리 목록은 [모듈 별도 리포지토리 관리 방안](docs/module-repository-strategy.md)을 따릅니다.

## Extension Points

Toycore는 전역 hook/event dispatcher를 기본 구조로 두지 않습니다. 모듈 간 영향이 필요하면 각 모듈이 명시적 계약 파일을 제공하고, 소비 모듈이 필요한 시점에 그 파일을 읽습니다.

외부 출력이나 확장이 붙을 수 있는 화면/기능 위치는 `extension-points.php`로 선언합니다.

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

선택 깊이는 기본적으로 다음 4단계를 최대치로 봅니다.

```text
module -> point -> slot -> subject
```

팝업레이어도 선언된 slot을 읽어 `module -> point -> slot -> subject` 범위에서 대상을 선택합니다. slot 선언이 없으면 호환을 위해 `overlay` 기본 위치로 처리하고, 선언된 slot이 있으면 `kind`가 `overlay`인 위치만 팝업 대상으로 사용합니다. 5단계 이상이 필요하면 단계를 늘리지 않고 `filters`, `schedule`, `device`, `locale`, `member_status` 같은 조건 필드로 분리합니다.

성능 기준도 명확히 나눕니다.

- 관리자 설정 시점: 활성 모듈의 `extension-points.php`를 읽어 선택 가능한 대상을 구성
- 사용자 요청 시점: `extension-points.php`를 읽지 않고 저장된 규칙 테이블만 조회
- 대량 subject: 전체 options를 반환하지 않고 검색형 selector로 확장

## 설계 문서

- [문서 분류](docs/documentation-index.md)
- [기본환경 테이블 ERD](docs/erd-basic-environment.md)
- [DB 접근 정책](docs/database-access-policy.md)
- [구현된 기능 리스트](docs/implemented-features.md)
- [구현 방향 및 보안 계획](docs/implementation-security-plan.md)
- [핵심 설계 결정](docs/core-decisions.md)
- [설치 및 초기화 계획](docs/install-plan.md)
- [업데이트 및 스키마 버전 계획](docs/update-plan.md)
- [운영 모드 및 에러 처리 계획](docs/runtime-ops-plan.md)
- [감사 로그 계획](docs/audit-log-plan.md)
- [보안 체크리스트](docs/security-checklist.md)
- [배포 보호 기준](docs/deployment-protection.md)
- [서버별 배포 예시](docs/deployment-examples.md)
- [현재 구현 상태](docs/current-implementation-status.md)
- [모듈 작성 가이드](docs/module-guide.md)
- [모듈 별도 리포지토리 관리 방안](docs/module-repository-strategy.md)
- [회원 모듈 상세 계획](docs/member-plan.md)
- [관리자 모듈 상세 계획](docs/admin-plan.md)
- [다국어 처리 계획](docs/i18n-plan.md)
- [개인정보 및 GDPR 대응 계획](docs/privacy-gdpr-plan.md)
- [SEO 대응 계획](docs/seo-plan.md)
- [진입점 및 홈 요청 분기 계획](docs/entry-request-plan.md)
- [캐시 계획](docs/cache-plan.md)

## 예제

- [절차형 요청 흐름 예제](examples/procedural-flow-sample.php.txt)
