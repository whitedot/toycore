# 운영 모드 및 에러 처리 계획

Toycore는 저가형 웹호스팅에서도 안전하게 운영되어야 합니다. 따라서 개발 모드와 운영 모드의 에러 처리 기준을 명확히 나눕니다.

## 목표

- 개발 중에는 오류를 빠르게 확인
- 운영 중에는 민감한 오류 정보 노출 방지
- 설치 전/설치 후 상태를 명확히 처리
- 404, 500, 점검 모드 화면 제공
- 로그 파일과 DB 로그의 역할 분리

## 실행 모드

기본 모드:

```text
development
production
maintenance
install
```

설정 위치:

```text
config/config.php
```

예시:

```php
<?php

return [
    'env' => 'production',
    'debug' => false,
];
```

## 모드별 정책

### `install`

- 설정 파일 또는 설치 잠금 파일이 없을 때 진입
- 설치 화면만 접근 허용
- 설치 완료 후 `installed.lock` 생성

### `development`

- PHP 오류 표시 가능
- SQL 오류 상세 표시 가능
- 캐시 사용 최소화
- 보안 토큰이나 비밀번호는 어떤 경우에도 출력하지 않음

### `production`

- 오류 상세 화면 출력 금지
- 사용자에게는 일반 오류 화면 표시
- 오류 상세는 로그에 기록
- `debug`는 false

### `maintenance`

- 일반 사용자에게 점검 화면 표시
- 관리자 또는 허용 IP는 접근 가능하게 할 수 있음
- 상태 변경 요청은 차단

## 에러 화면

코어는 최소 에러 화면을 제공합니다.

```text
404 Not Found
500 Server Error
503 Maintenance
Install Required
```

각 화면은 일반 HTML/PHP view로 작성합니다.

원칙:

- 숏태그 금지
- 변수 출력 시 escape
- 운영 모드에서 stack trace 출력 금지
- 관리자 화면도 동일한 에러 정책 적용

## 로그

파일 로그:

```text
storage/logs/error.log
```

DB 감사 로그:

```text
toy_audit_logs
```

파일 로그는 시스템 오류 중심, DB 감사 로그는 관리자 작업과 설정 변경 중심으로 사용합니다. 현재 구현은 `storage/logs/error.log`에 예외 요약을 기록합니다.

복구 marker:

```text
storage/install-failed.json
storage/update-failed.json
```

설치나 업데이트가 중간에 실패하면 운영자가 재시도 전 상태를 확인할 수 있도록 최소 복구 marker를 남깁니다. marker에는 실패 단계, 오류 요약, 업데이트 scope/module/version/checksum 같은 운영 식별자만 저장하고 DB 비밀번호, 토큰, 요청 원문은 저장하지 않습니다. 다음 성공 시 해당 marker는 삭제됩니다.

관리자 대시보드는 남아 있는 복구 marker를 운영자가 먼저 볼 수 있도록 요약 표시합니다. 설치 실패 marker는 정상 설치 성공 후 삭제되어야 하며, 업데이트 실패 marker는 다음 업데이트 성공 후 삭제되어야 합니다. marker가 남아 있다면 운영자는 `storage/logs/error.log`, 감사 로그, 관련 SQL 또는 모듈 파일 상태를 확인한 뒤 재시도합니다.

모듈 파일 교체 과정에서 만들어진 백업 디렉터리는 `storage/module-backups` 아래에 보관합니다. 관리자 대시보드는 백업 개수와 최근 백업 이름을 요약 표시해 파일 교체 이후 운영자가 백업 존재 여부를 바로 확인할 수 있게 합니다.

오래된 모듈 백업은 `/admin/retention`에서 보관 기간 기준으로 정리합니다. 백업 정리는 다른 보관 정리 작업과 동일하게 owner 권한, CSRF 검증, 삭제 후보 확인, 확인 문구 입력, 감사 로그 기록을 거칩니다.

## 오류 처리 흐름

```text
1. index.php 진입
2. 설정 로드 시도
3. 설치 상태 확인
4. 에러 핸들러 등록
5. 요청 처리
6. 예외 발생 시 모드별 응답
7. 로그 기록
```

## 보안 원칙

- DB 비밀번호 출력 금지
- 토큰 출력 금지
- 세션 값 출력 금지
- SQL query 전체 로그는 기본 비활성
- 개인정보가 포함된 request body 로그 금지
- 복구 marker에 비밀번호, 토큰, CSRF 값 저장 금지
- 복구 marker나 백업 경로를 일반 사용자 화면에 노출 금지
- 모듈 백업을 자동으로 즉시 삭제하지 말고 운영자가 보관 기간 정리에서 명시적으로 실행

## 금지하는 방향

- 운영 모드에서 `display_errors=1`
- 오류 화면에 파일 경로와 stack trace 노출
- 로그에 비밀번호, 토큰, CSRF 값 저장
- 점검 모드에서 상태 변경 요청 허용
