# 설치 및 초기화 계획

Toycore는 저가형 웹호스팅에서도 설치할 수 있어야 하므로 CLI 설치를 필수로 하지 않습니다. 기본 설치는 웹 설치 화면을 기준으로 설계합니다.

## 목표

- DB 접속 정보 입력
- 기본 설정 파일 생성
- 기본 코어 테이블 생성
- 기본 사이트 생성
- 기본 모듈 등록
- 최초 관리자 계정 생성
- 설치 완료 후 설치 화면 잠금

## 설치 전 조건

- PHP 최소 지원 버전 충족
- PDO MySQL 또는 MariaDB 사용 가능
- 설정 파일을 쓸 수 있는 권한
- `storage/` 디렉터리 쓰기 가능
- DB 계정에 테이블 생성 권한 존재
- 내부 디렉터리와 설정 파일이 URL로 직접 열리지 않도록 서버 접근 차단 규칙 설정 가능

## 설치 흐름

```text
1. index.php 진입
2. 설정 파일 존재 여부 확인
3. 미설치 상태면 install 화면 include
4. 환경 점검
5. DB 접속 정보 입력
6. DB 연결 테스트
7. core install.sql 실행
8. `member` 모듈 install.sql 실행
9. `admin` 모듈 install.sql 실행
10. `seo` 모듈 install.sql 실행
11. `popup_layer` 모듈 install.sql 실행
12. 기본 사이트 생성
13. 기본 모듈 등록 및 활성화
14. 스키마 버전 기록
15. 최초 관리자 계정 생성
16. 설치 잠금 파일 생성
17. 관리자 화면으로 이동
```

저가형 공유호스팅 기본 배포는 도메인이 프로젝트 루트를 직접 가리킨다고 가정합니다. 따라서 `도메인/` 요청은 루트 `index.php`로 진입합니다.

Toycore는 특정 서버용 보호 파일을 기본 배포 산출물로 제공하지 않습니다. `config/`, `storage/`, `database/`, `core/`, `modules/`, `docs/`, `examples/` 같은 내부 디렉터리는 호스팅 제어판, 서버 설정, 상위 디렉터리 배치 등 운영 환경의 방식으로 직접 접근을 차단해야 합니다. 설치기는 이 조건을 안내하고, 가능한 범위에서 직접 접근 가능 여부를 점검합니다.

운영 모드 설치에서 내부 경로 직접 접근 차단을 확인할 수 없거나, 점검 URL이 실제로 열리는 경우 설치를 중단합니다. 개발 모드에서는 경고를 표시할 수 있지만, 운영 전에는 반드시 차단 상태를 확인해야 합니다.

## 설치 상태 판단

설치 상태는 다음 기준으로 판단합니다.

```text
config/config.php 존재
storage/installed.lock 존재
```

설치가 완료되면 `storage/installed.lock` 파일을 생성합니다.

DB의 `toy_sites` 기본 레코드와 `toy_modules` 기본 레코드는 설치 무결성 점검 기준으로 사용합니다. DB 장애가 발생했을 때 설치 화면으로 되돌아가지 않도록, 설치 여부 판단과 DB 상태 점검을 분리합니다.

## 생성할 설정 파일

예상 파일:

```text
config/config.php
```

`config/config.php`는 설치 화면이 생성하는 실제 설정 파일입니다. 별도 환경 파일 이름을 전제로 하지 않고, 저가형 웹호스팅에서 파일명을 보고 용도를 바로 알 수 있게 단순하게 둡니다.

예시:

```php
<?php

return [
    'env' => 'production',
    'debug' => false,
    'app_key' => 'generate-a-random-secret-during-install',
    'db' => [
        'host' => 'localhost',
        'name' => 'toycore',
        'user' => 'toycore',
        'password' => 'change-me',
        'charset' => 'utf8mb4',
    ],
];
```

이 파일은 Git에 커밋하지 않습니다.

`app_key`는 설치 시 생성하는 사이트 비밀값입니다. CSRF, 서명, 로그인 식별자 HMAC hash처럼 같은 입력을 안전하게 다시 검증해야 하는 기능에 사용합니다. 운영 중 변경하면 기존 로그인 식별자 hash 조회가 깨질 수 있으므로, 설치 후에는 백업하고 신중하게 관리합니다.

## 기본 테이블

설치 시 기본 코어 테이블을 먼저 생성합니다.

```text
toy_sites
toy_site_settings
toy_modules
toy_module_settings
toy_schema_versions
```

`toy_schema_versions`는 설치된 core/member/admin/seo/popup_layer 스키마 버전을 기록합니다. 관리자 업데이트 화면은 이 기록을 기준으로 아직 적용되지 않은 SQL 파일만 실행합니다.

`member`, `admin`, `seo`, `popup_layer`는 기본 제공 모듈이지만, 테이블 생성은 각 모듈의 `install.sql` 책임으로 둡니다.

## 기본 모듈 등록

초기 설치 시 다음 모듈을 등록합니다.

```text
member
admin
seo
popup_layer
```

`member`와 `admin`은 코어에 내장하지 않지만, 기본 설치에 반드시 포함되는 필수 기본 모듈입니다. `seo`와 `popup_layer`는 기본 제공 모듈로 함께 활성화하되, 인증과 관리자 진입에 필요한 필수 모듈은 아닙니다.

기본 배포 설치 단위:

```text
core + member + admin + seo + popup_layer
```

각 기본 모듈은 기본 활성 상태로 시작합니다.

```text
member: enabled
admin: enabled
seo: enabled
popup_layer: enabled
```

단, 코어는 이 모듈들의 내부 테이블 구조를 직접 알지 않습니다. 설치 과정에서 각 모듈의 `install.sql`을 명시적으로 실행합니다.

`admin` 모듈은 별도 계정 체계를 만들지 않고 `member` 모듈의 계정/인증 기반에 의존합니다.

## 최초 관리자 계정

최초 관리자 계정 생성은 `member` 모듈과 `admin` 모듈의 협력으로 처리합니다.

역할:

- `member` 모듈: 계정 생성과 비밀번호 hash 저장
- `admin` 모듈: 관리자 권한 부여
- 코어: 설치 흐름과 CSRF, 입력 검증, DB 트랜잭션 보조

기본 관리자 비밀번호는 설치 화면에서 직접 입력하게 하고, 하드코딩된 기본 비밀번호를 제공하지 않습니다.

## 보안 원칙

- 설치 화면은 미설치 상태에서만 접근 가능
- 설치 완료 후 `installed.lock`이 있으면 설치 화면 접근 차단
- DB 비밀번호는 화면에 다시 출력하지 않음
- 설치 실패 시 민감 정보를 노출하지 않음
- 설치 중 생성한 관리자 비밀번호 원문은 저장하지 않음
- 설치 화면의 상태 변경 요청도 CSRF 검증

## 실패 처리

설치 중 실패하면 어느 단계에서 실패했는지 명확히 보여주되, DB 비밀번호나 상세 SQL 오류는 운영 모드에서 숨깁니다.

설치 실패 후 재시도 가능해야 합니다.

```text
DB 연결 실패 -> 설정 파일 생성 전이면 입력 화면으로 복귀
테이블 생성 실패 -> 생성된 테이블 목록 확인 안내
관리자 계정 생성 실패 -> 설치 완료 처리 금지
```

## 설치 후 처리

설치 완료 후 다음을 수행합니다.

- `storage/installed.lock` 생성
- 설정 캐시가 있다면 삭제
- 관리자 로그인 화면 또는 관리자 대시보드로 이동
- 설치 관련 임시 세션 삭제

## 금지하는 방향

- CLI 설치만 지원
- 기본 관리자 계정/비밀번호 하드코딩
- 설치 완료 후에도 설치 화면 접근 가능
- 모듈 내부 테이블을 코어가 직접 생성
- DB 비밀번호를 로그에 기록
