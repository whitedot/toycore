# 감사 로그 계획

감사 로그는 관리자 작업과 보안상 중요한 변경을 추적하기 위한 코어 기반 기능입니다. Toycore는 모든 기능을 코어에 넣지 않지만, 감사 로그 helper와 기본 테이블은 코어에서 제공하는 것이 적절합니다.

## 목표

- 관리자 작업 추적
- 설정 변경 추적
- 모듈 설치/활성화/비활성화 추적
- 개인정보 요청 처리 이력 추적
- 보안 사고 분석에 필요한 최소 정보 보관

## 기본 테이블

```text
toy_audit_logs
```

권장 필드:

```text
id
site_id
actor_account_id (nullable, logical reference)
actor_type
event_type
target_type
target_id
result
ip_address
user_agent
message
metadata_json
created_at
```

## 기록 대상

`actor_account_id`는 `member` 모듈의 계정을 가리킬 수 있지만, 코어의 `toy_audit_logs` 테이블은 `toy_member_accounts`에 DB FK를 강제하지 않습니다. 설치 순서와 모듈 경계를 유지하기 위해 계정 참조는 nullable 논리 참조로 처리합니다.

코어:

- 설치 완료
- 설정 변경
- 모듈 설치
- 모듈 활성화
- 모듈 비활성화
- 업데이트 실행
- 캐시 삭제
- 점검 모드 변경

admin 모듈:

- 관리자 로그인 성공/실패
- 관리자 로그아웃
- 관리자 권한 변경
- 회원 상태 변경
- 개인정보 요청 처리

각 모듈:

- 자기 설정 변경
- 콘텐츠 공개/비공개 변경
- 중요한 데이터 삭제

## 기록하지 않을 값

다음 값은 로그에 남기지 않습니다.

- 비밀번호
- 세션 ID
- CSRF 토큰
- 비밀번호 재설정 토큰
- 장기 로그인 토큰
- DB 접속 비밀번호
- 개인정보 원문 전체

필요하면 원문 대신 hash, ID, 변경 전후 상태 요약만 남깁니다.

## Helper 방향

```php
toy_audit_log([
    'event_type' => 'module.enabled',
    'target_type' => 'module',
    'target_id' => 'page',
    'result' => 'success',
]);
```

helper는 실패해도 사용자 요청 전체를 중단하지 않는 방향을 우선합니다. 다만 보안상 반드시 기록되어야 하는 이벤트는 별도 정책을 둡니다.

## 보관 기간

보관 기간은 사이트 설정으로 둡니다.

```text
audit.retention_days
audit.privacy_retention_days
```

저가형 웹호스팅을 고려해 자동 스케줄러가 없어도 관리자 화면에서 수동 정리 기능을 제공합니다.

## 관리자 화면

`admin` 모듈은 감사 로그 조회 화면을 제공할 수 있습니다.

기본 필터:

- 기간
- 이벤트 유형
- 처리자
- 대상 유형
- 결과

개인정보와 관련된 로그는 접근 권한을 더 엄격하게 둡니다.

## 금지하는 방향

- 모든 request body 자동 저장
- SQL 전체 자동 저장
- 토큰/비밀번호 저장
- 개인정보 원문 대량 저장
- 로그 실패 시 무조건 화면 오류 노출
