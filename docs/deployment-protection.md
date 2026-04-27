# 배포 보호 기준

Toycore는 기본 배포 산출물에 특정 웹서버용 차단 파일을 포함하지 않는다. Apache `.htaccess`, Nginx location 규칙, 호스팅 패널 접근 제한은 운영 환경에 맞게 별도로 설정한다.

## 공개 진입점

운영 환경에서 웹 요청은 루트 `index.php`만 공개 진입점으로 사용한다.

직접 웹 접근을 차단해야 하는 경로:

```text
config/
core/
database/
docs/
examples/
modules/
storage/
.git/
.tools/
AGENTS.md
README.md
```

위 경로를 직접 열 수 있는 환경에서는 운영 설치를 진행하지 않는다.

## 설치 전 확인

설치 화면에서 DB 정보를 입력하기 전에 다음을 확인한다.

```text
/config/config.php 직접 접근 차단
/storage/installed.lock 직접 접근 차단
/database/core/install.sql 직접 접근 차단
/modules/member/install.sql 직접 접근 차단
/.git/ 직접 접근 차단
```

서버가 차단 규칙을 지원하지 않는다면, 문서 루트를 `index.php`만 노출되는 별도 공개 디렉터리로 조정하거나 호스팅 패널의 접근 제한 기능을 사용한다.

## 서버별 처리

서버별 예시는 프로젝트 기본 파일로 제공하지 않는다.

운영자는 다음 방식 중 환경에 맞는 방법을 선택한다.

```text
Apache: 가상호스트 또는 호스팅 패널의 접근 제한
Nginx: server/location 규칙
공유호스팅: 파일 관리자 또는 보안 메뉴의 디렉터리 접근 차단
```

## 원칙

- 설정 파일과 저장소 메타데이터는 웹에서 읽을 수 없어야 한다.
- SQL 파일과 모듈 내부 PHP 파일은 직접 실행 대상이 아니다.
- 업로드나 생성 파일은 가능한 한 `storage/` 아래에 두고 직접 접근을 차단한다.
- 서버별 보호 규칙은 운영 환경 문서나 배포 자동화에서 관리한다.
