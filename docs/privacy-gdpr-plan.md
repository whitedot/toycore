# 개인정보 및 GDPR 대응 계획

Toycore는 개인정보 처리 기능을 제공할 수 있는 웹 솔루션 코어입니다. 따라서 GDPR 적용 가능성이 있는 운영 환경을 염두에 두고, 개인정보 수집과 처리 구조를 보수적으로 설계합니다.

이 문서는 법률 자문이 아니라 구현 관점의 준비 계획입니다. 실제 서비스 운영 시에는 운영 지역, 사용자 대상, 수집 항목, 처리 목적에 맞는 법률 검토가 필요합니다.

## 기준

- 필요한 개인정보만 수집
- 수집 목적과 보관 기간을 명확히 관리
- 동의가 필요한 항목은 버전과 시점을 기록
- 회원이 자신의 개인정보에 대해 요청할 수 있는 구조 마련
- 삭제와 익명화를 구분
- 관리자 접근과 처리 이력을 기록

## 기능 위치

GDPR 대응을 모두 코어에 넣지 않습니다. 최소 기반과 확장 기능을 나눕니다.

```text
member 모듈:
- 회원가입 시 약관/개인정보 처리방침 동의 기록
- 회원 탈퇴
- 세션/장기 로그인 토큰 폐기
- 계정 비활성화/익명화

core:
- 개인정보 처리에 필요한 공통 설정
- 보안 helper
- 로그에서 민감값 제외 원칙

admin 또는 privacy 모듈:
- 개인정보 요청 접수/처리
- 개인정보 내보내기
- 보관 기간 기반 정리
- 관리자 처리 화면
```

초기 구현에서는 회원가입과 탈퇴에 필요한 최소 기능을 `member` 모듈에 포함합니다. 개인정보 요청 처리와 내보내기는 기본적으로 `admin` 모듈에서 제공하고, 규모가 커지면 별도 `privacy` 모듈로 분리할 수 있습니다.

참고:

- [European Commission GDPR principles](https://commission.europa.eu/law/law-topic/data-protection/rules-business-and-organisations/principles-gdpr_en)
- [European Data Protection Board: GDPR FAQ](https://www.edpb.europa.eu/sme-data-protection-guide/faq-frequently-asked-questions/answer/what-gdpr_en)
- [European Data Protection Board: individuals' rights](https://www.edpb.europa.eu/sme-data-protection-guide/respect-individuals-rights_en)

## 데이터 분류

| 분류 | 예시 | 기본 방침 |
| --- | --- | --- |
| 계정 필수 | account_identifier_hash, email_hash, password_hash | 인증 목적에 한정 |
| 연락처 | email | 메일 발송과 사용자 안내 목적 |
| 프로필 선택 | nickname, phone, birth_date, avatar_path | 선택 수집, 수정/삭제 가능 |
| 보안 로그 | IP, User-Agent, 로그인 결과 | 보관 기간 설정 |
| 동의 기록 | 약관, 개인정보 처리방침, 마케팅 | 버전과 시점 보관 |
| 운영 로그 | 관리자 변경 이력 | 최소한의 범위로 기록 |

## 동의 기록

동의는 현재 상태만 저장하지 않고 이력으로 저장합니다.

사용 테이블:

- `toy_member_consents`

기록 항목:

- `account_id`
- `consent_key`
- `consent_version`
- `is_granted`
- `granted_at`
- `withdrawn_at`
- `ip_address`
- `user_agent`

예시 동의 키:

```text
terms
privacy_policy
marketing_email
marketing_sms
```

## 권리 요청

사용 테이블:

- `toy_privacy_requests`

`account_id`는 계정이 남아 있을 때만 연결될 수 있습니다. 삭제/익명화 이후에도 요청 이력을 보존하기 위해 요청 당시 식별 정보의 hash 또는 snapshot을 별도로 저장합니다.

요청 유형:

```text
access
rectification
erasure
restriction
portability
objection
withdrawal
```

처리 상태:

```text
requested
reviewing
completed
rejected
cancelled
```

초기 구현은 자동화보다 기록과 관리자 검토를 우선합니다.

## 삭제와 익명화

탈퇴 또는 삭제 요청 시 즉시 모든 데이터를 물리 삭제하지 않습니다. 데이터 성격별로 정책을 분리합니다.

- 세션, 장기 로그인 토큰: 즉시 폐기
- 프로필 선택 정보: 삭제 또는 빈 값 처리
- 계정 식별 정보: 필요 시 익명화 후 비활성화
- 보안 로그: 보관 기간 동안 최소 정보 유지
- 게시물/댓글 등 콘텐츠: 모듈 정책에 따라 삭제 또는 작성자 익명화

## 내보내기

개인정보 이동권 요청을 고려해 회원 데이터 내보내기를 준비합니다.

초기 포맷:

- JSON
- CSV

포함 후보:

- 계정 기본 정보
- 프로필 정보
- 동의 이력
- 최근 로그인 이력
- 모듈이 제공하는 사용자 데이터

## 관리자 화면 요구사항

- 개인정보 요청 목록
- 요청 상세
- 처리 상태 변경
- 처리 메모
- 처리 완료일
- 처리 관리자 기록

관리자는 필요한 경우에만 개인정보 원문을 볼 수 있어야 하며, 조회와 변경 이력을 남깁니다.

## 쿠키 정책

- 필수 세션 쿠키와 선택 쿠키를 구분
- 분석, 마케팅, 외부 위젯은 기본 비활성
- 선택 쿠키는 동의 후 활성화
- 동의 철회 시 선택 쿠키 제거 안내 또는 제거 처리

## 보관 기간

초기 설정값으로 다음 항목을 둘 수 있습니다.

```text
privacy.auth_log_retention_days
privacy.admin_log_retention_days
privacy.session_retention_days
privacy.deleted_account_retention_days
privacy.export_file_retention_hours
```

저가형 웹호스팅을 고려해 자동 스케줄러가 없어도 관리자 화면에서 수동 정리 기능을 제공할 수 있어야 합니다.

## 구현 우선순위

1. 동의 기록 테이블
2. 회원가입 시 약관/개인정보 처리방침 동의 저장
3. 탈퇴 시 세션 폐기와 계정 비활성화
4. 개인정보 요청 접수 테이블
5. 관리자 처리 화면
6. 개인정보 내보내기
7. 보관 기간 기반 정리 기능
