# 로컬 개발 환경

이 저장소는 시스템 PHP 설치가 없어도 Docker 기반 래퍼로 PHP 명령을 사용할 수 있다.

## PHP 명령

```sh
./.tools/bin/php -v
```

첫 실행 때 `toycore-php:8.3-cli` 이미지를 만들며, `pdo_mysql` 확장을 포함한다.

## 내장 서버

```sh
./.tools/bin/php -S 127.0.0.1:8080 index.php
```

브라우저에서 `http://127.0.0.1:8080/`로 접속한다.

Docker 래퍼로 실행하는 PHP는 컨테이너 안에서 동작한다. 호스트에서 실행 중인 MySQL에 연결할 때는 설치 화면의 DB host에 `host.docker.internal`을 입력한다.

## 문법 검사

```sh
find . -path './.git' -prune -o -path './.tools' -prune -o -name '*.php' -print0 | xargs -0 -n1 ./.tools/bin/php -l
```
