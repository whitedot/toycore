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
config/local.php
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
storage/logs/app.log
storage/logs/error.log
```

DB 감사 로그:

```text
toy_audit_logs
```

파일 로그는 시스템 오류 중심, DB 감사 로그는 관리자 작업과 설정 변경 중심으로 사용합니다.

## 오류 처리 흐름

```text
1. public/index.php 진입
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

## 금지하는 방향

- 운영 모드에서 `display_errors=1`
- 오류 화면에 파일 경로와 stack trace 노출
- 로그에 비밀번호, 토큰, CSRF 값 저장
- 점검 모드에서 상태 변경 요청 허용
