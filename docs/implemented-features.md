# 구현된 기능 리스트

이 문서는 현재 코드 기준으로 Toycore에 구현된 기능을 사용자 관점에서 정리한다.

Toycore는 전체 CMS가 아니라 절차형 PHP 기반 웹 솔루션 코어를 목표로 한다. 따라서 아래 기능은 코어와 기본 제공 모듈이 제공하는 현재 운영 기능으로 본다.

## 설치와 실행

- 웹 설치 화면 제공
- 설치 전/설치 후 요청 분기
- `config/config.php` 설정 파일 생성
- `storage/installed.lock` 설치 완료 파일 생성
- core, member, admin, seo, popup_layer 설치 SQL 실행
- 기본 seo, popup_layer 모듈 등록
- 스키마 버전 기록
- Docker 기반 로컬 PHP 실행 래퍼 제공
- 설치 시 운영 URL의 HTTPS 여부 확인
- 설치 시 내부 파일 직접 접근 노출 점검
- 기본 홈 화면과 오류 화면 제공
- 공통 CSS 파일 제공

## 코어 기반

- HTTP method와 path 해석
- 활성화된 모듈의 `paths.php` 배열 기반 요청 처리
- action 파일 상대 경로 검증
- 모듈 `module.php` 메타데이터 조회
- 모듈 type 조회
- 활성 모듈 계약 파일 조회 helper
- 사이트 설정 조회
- 단일 사이트 기본값의 설정 테이블 저장
- 모듈 설정 조회
- 요청 단위 설정 조회 메모리 캐시
- 현재 locale 결정
- 기본 번역 helper
- 기본 locale 번역 fallback
- HTML escape helper
- CSRF token 생성과 검증
- 안전한 redirect helper
- canonical URL helper
- SEO meta tag 출력 helper
- title, description, canonical, robots, Open Graph 출력 슬롯
- `/robots.txt` 출력
- `/sitemap.xml` 출력
- SEO 관리자 설정 화면
- 활성 모듈 `sitemap.php` 기반 sitemap URL 확장
- 활성 모듈 `extension-points.php` 기반 확장 지점 조회
- 다운로드 응답 헤더 helper
- 기본 mail helper
- HMAC hash helper
- 감사 로그 기록 helper
- 예외 요약 파일 로그 기록

## 보안 응답과 요청 보호

- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: SAMEORIGIN`
- `Referrer-Policy: no-referrer`
- 기본 Content Security Policy
- 운영 HTTPS 요청의 HSTS 헤더
- 동적 PHP 응답의 `no-store`, `no-cache` 헤더
- 로그인, 회원, 관리자, 설치, 오류 화면의 `noindex, nofollow`
- 리다이렉트 URL 제어 문자 차단
- 사이트 Base URL의 `http`/`https` scheme 검증
- 클라이언트 IP 형식 검증
- 클라이언트 IP와 User-Agent 길이 정규화
- 토큰 URL의 referrer 전송 방지

## 회원 계정

- 회원가입
- 회원가입 허용 설정
- 로그인
- 로그아웃
- 내 계정 화면
- 표시 이름 수정
- 선호 locale 수정
- 선택 프로필 저장과 조회
- 회원 탈퇴 상태 처리
- 탈퇴 시 선택 프로필 삭제
- 약관 동의 기록
- 개인정보 처리방침 동의 기록
- 회원 모듈 전용 관리자 설정

## 회원 인증 보안

- 계정 식별자와 이메일 hash 저장
- 비밀번호 `password_hash()` 저장
- 로그인 성공 시 PHP 세션 ID 재생성
- 로그인 성공 시 CSRF token 재생성
- 로그인 실패 시 더미 비밀번호 검증으로 계정 존재 타이밍 노출 완화
- 로그인 실패 로그 기반 시도 제한
- 로그인 시도 제한 기준의 모듈 설정화
- 이메일 인증 사용 여부 설정
- 회원가입 요청 빈도 제한
- 비밀번호 재설정 요청 빈도 제한
- 이메일 인증 재발송 빈도 제한
- DB 기반 로그인 세션 기록
- PHP 세션 strict 모드 적용
- PHP 세션 cookie-only 모드 적용
- 세션 쿠키 `HttpOnly`, `Secure`, `SameSite=Lax` 적용
- 비활성 계정의 기존 로그인 세션 차단
- 로그인 세션 최종 활동 시간 갱신 주기 완화

## 이메일 인증

- 회원가입 후 이메일 인증 token 생성
- 이메일 인증 메일 발송 요청
- 이메일 인증 링크 검증
- 이메일 인증 완료 처리
- 새 이메일 인증 token 발급 시 기존 미사용 token 무효화
- 이메일 인증 token hash 저장
- 이메일 인증 token 원자적 사용 처리

## 비밀번호 재설정

- 비밀번호 재설정 메일 발송 요청
- 비밀번호 재설정 token 생성
- 비밀번호 재설정 token 검증
- 새 비밀번호 저장
- 새 비밀번호 설정 후 전체 로그인 세션 폐기
- 새 비밀번호 재설정 token 발급 시 기존 미사용 token 무효화
- 비밀번호 재설정 token hash 저장
- 비밀번호 재설정 token 원자적 사용 처리

## 개인정보 기능

- 회원 개인정보 요청 접수
- 회원 개인정보 요청 목록
- 회원 개인정보 JSON 내보내기
- 관리자 개인정보 요청 처리
- 관리자 개인정보 요청 JSON 내보내기
- 개인정보 JSON 내보내기 POST/CSRF 보호
- 개인정보 JSON 내보내기 다운로드 헤더 적용
- 개인정보 JSON 내보내기에서 내부 token/hash 제외
- 개인정보 JSON 내보내기에 프로필 포함
- 개인정보 JSON 내보내기에 세션 이력 포함
- 활성 모듈의 개인정보 JSON 내보내기 확장 지점 제공

## 관리자

- 관리자 대시보드
- 사이트 기본 설정 조회와 저장
- 사이트 설정 항목 조회, 저장, 삭제
- 모듈 목록 조회
- 모듈 코드 버전과 설명 표시
- 모듈 활성화 상태 관리
- 기본 모듈 비활성화 차단
- 모듈 설정 항목 조회, 저장, 삭제
- 회원 모듈 전용 설정 화면
- 회원 목록 조회
- 회원 상태 변경
- 회원 비활성화 시 세션 자동 폐기
- 회원 활성 세션 수 조회
- 회원 세션 강제 폐기
- 관리자 역할 부여
- 관리자 역할 회수
- 마지막 owner 역할 회수 차단
- 감사 로그 조회
- 개인정보 요청 처리
- 보관 기간 수동 정리
- 업데이트 확인과 실행
- 업데이트 전 백업 확인
- 적용된 스키마 버전 목록 표시
- 업데이트 파일 checksum 표시
- 업데이트 실행 감사 로그 기록
- 업데이트 실행 owner 권한 제한

## 팝업레이어

- 팝업레이어 목록 조회
- 팝업레이어 등록, 수정, 삭제
- 팝업레이어 활성/비활성 상태 관리
- 노출 시작/종료 시간 설정
- 닫기 유지 일수 설정
- 활성 모듈의 `extension-points.php` 기반 노출 대상 선택
- `module -> point -> subject` 깊이의 대상 규칙 저장
- 내부 `overlay` slot 기준 팝업 출력
- 사용자 요청 시 저장된 대상 규칙 테이블 조회

## 운영과 보관

- 사용 완료 비밀번호 재설정 token 정리
- 사용 완료 이메일 인증 token 정리
- 만료 또는 폐기된 로그인 세션 정리
- 오래된 인증 로그 정리
- 오래된 감사 로그 정리
- 인증 로그 기록
- 감사 로그 기록
- `storage/logs/error.log` 오류 기록
- 모듈 업데이트 SQL 적용

## 데이터베이스 기반

- 사이트 설정 테이블
- 모듈 테이블
- 모듈 설정 테이블
- 스키마 버전 테이블
- 감사 로그 테이블
- 개인정보 요청 테이블
- 회원 계정 테이블
- 회원 인증 로그 테이블
- 회원 프로필 테이블
- 회원 세션 테이블
- 비밀번호 재설정 token 테이블
- 이메일 인증 token 테이블
- 회원 동의 기록 테이블
- 관리자 역할 테이블
- 팝업레이어 테이블
- 팝업레이어 대상 규칙 테이블
- 인증 로그 조회용 인덱스
- 세션, token, 개인정보 요청, 감사 로그 조회용 기본 인덱스
- 팝업레이어 대상 조회용 인덱스

## 제공되는 주요 경로

### 회원 경로

- `GET /login`
- `POST /login`
- `GET /register`
- `POST /register`
- `GET /account`
- `POST /account`
- `GET /account/withdraw`
- `POST /account/withdraw`
- `GET /account/privacy-requests`
- `POST /account/privacy-requests`
- `POST /account/privacy-export`
- `POST /account/email-verification`
- `GET /email/verify`
- `GET /password/reset`
- `POST /password/reset`
- `GET /password/reset/confirm`
- `POST /password/reset/confirm`
- `POST /logout`

### 팝업레이어 출력 지점

- `GET /login`
- `GET /register`
- `GET /account`

### 관리자 경로

- `GET /admin`
- `GET /admin/settings`
- `POST /admin/settings`
- `GET /admin/modules`
- `POST /admin/modules`
- `GET /admin/member-settings`
- `POST /admin/member-settings`
- `GET /admin/updates`
- `POST /admin/updates`
- `GET /admin/members`
- `POST /admin/members`
- `GET /admin/roles`
- `POST /admin/roles`
- `GET /admin/audit-logs`
- `GET /admin/privacy-requests`
- `POST /admin/privacy-requests`
- `POST /admin/privacy-requests/export`
- `GET /admin/retention`
- `POST /admin/retention`

### 팝업레이어 관리자 경로

- `GET /admin/popup-layers`
- `POST /admin/popup-layers`

### SEO 경로

- `GET /admin/seo`
- `POST /admin/seo`
- `GET /robots.txt`
- `GET /sitemap.xml`

## 현재 포함하지 않는 기능

- 게시판, 게시글, 댓글
- 페이지 빌더
- 상품, 주문, 결제
- 메뉴 관리
- 포인트, 쿠폰, 마케팅 자동화
- 파일 업로드 관리
- 고급 CMS workflow
- 모듈별 도메인 관리자 화면 자동 생성
- `.htaccess` 배포 파일

