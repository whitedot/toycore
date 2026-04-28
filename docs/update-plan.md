# 업데이트 및 스키마 버전 계획

Toycore는 프레임워크형 migration 클래스를 사용하지 않습니다. 대신 SQL 파일과 버전 테이블로 코어와 모듈의 설치/업데이트 상태를 추적합니다.

## 목표

- 현재 코어 스키마 버전 확인
- 현재 모듈 스키마 버전 확인
- 필요한 SQL 업데이트만 순차 실행
- 실패 시 어느 단계에서 실패했는지 확인 가능
- CLI 없이 관리자 화면에서 실행 가능

## 기본 테이블

코어 버전 기록:

```text
toy_schema_versions
```

권장 필드:

```text
id
scope
module_key
version
applied_at
```

예:

```text
scope = core
module_key = ''
version = 2026.04.001

scope = module
module_key = member
version = 2026.04.001
```

코어 버전 기록은 `module_key`에 `NULL`을 쓰지 않고 빈 문자열을 사용합니다. MySQL/MariaDB의 UNIQUE 인덱스에서 `NULL` 중복이 허용되는 문제를 피하기 위해 `scope`, `module_key`, `version`은 모두 비교 가능한 값으로 저장합니다.

## 파일 구조

코어 SQL:

```text
database/core/install.sql
database/core/updates/2026.04.001.sql
database/core/updates/2026.05.001.sql
```

모듈 SQL:

```text
modules/{module_key}/install.sql
modules/{module_key}/updates/2026.04.001.sql
modules/{module_key}/updates/2026.05.001.sql
```

## 업데이트 흐름

```text
1. 관리자 화면에서 업데이트 확인
2. 현재 core version 조회
3. database/core/updates 목록 확인
4. 아직 적용되지 않은 SQL만 순서대로 실행
5. 각 SQL 성공 시 toy_schema_versions 기록
6. 활성 모듈별 업데이트 목록 확인
7. 모듈별 미적용 SQL 실행
8. 캐시 삭제
9. 완료 로그 기록
```

## 실행 원칙

- SQL 파일명은 정렬 가능한 버전 형식 사용
- 이미 적용된 버전은 다시 실행하지 않음
- 업데이트 전 현재 버전을 화면에 표시
- 적용된 스키마 버전 목록을 관리자 화면에 표시
- 각 SQL 파일 실행 전후 감사 로그 기록
- 실패하면 이후 SQL 실행 중단
- 가능한 경우 DB 트랜잭션 사용

## 저가형 웹호스팅 고려

모든 DB가 DDL 트랜잭션을 완전하게 지원하지 않을 수 있습니다. 따라서 업데이트 SQL은 가능한 한 작게 나눕니다.

권장:

- 한 파일에 너무 많은 변경을 넣지 않음
- 오래 걸리는 대량 데이터 변경은 피함
- 실패 시 수동 복구 안내를 남김
- 업데이트 전 DB 백업을 안내

## 관리자 화면

`admin` 모듈에서 업데이트 화면을 제공합니다.

표시 항목:

- 사용 가능한 코어 업데이트
- 활성 모듈별 사용 가능한 업데이트
- 적용 대상 SQL 파일
- 적용 결과
- 실패 시 감사 로그

## 캐시 무효화

업데이트 후 다음 캐시를 삭제합니다.

```text
사이트 설정 캐시
활성 모듈 목록 캐시
번역 병합 캐시
모듈별 공개 콘텐츠 캐시
```

## 금지하는 방향

- PHP 클래스 기반 migration 필수화
- Artisan 같은 CLI 명령 필수화
- 적용된 SQL을 다시 실행
- 실패 후에도 다음 업데이트 계속 실행
- DB 백업 안내 없이 구조 변경 실행
