# 서버별 배포 예시

Toycore는 루트의 `index.php`를 공개 진입점으로 사용합니다. 저가형 웹호스팅에서는 문서 루트와 쓰기 권한을 먼저 확인합니다.

이 문서는 예시만 제공합니다. 프로젝트에는 `.htaccess`를 포함하지 않습니다.

## 공통 준비

필수 확인:

```text
PHP 8.1 이상
PDO MySQL 확장
MySQL 또는 MariaDB
config 디렉터리 쓰기 가능
storage 디렉터리 쓰기 가능
```

설치 후에는 다음 파일이 생성됩니다.

```text
config/config.php
storage/installed.lock
```

이 파일들은 Git에 포함하지 않습니다.

## 배포 패키지 종류

Toycore 본체 소스는 core/member/admin 중심으로 유지하고, 배포 산출물은 필요에 따라 선택 모듈을 조립해 나눕니다.

```text
toycore-minimal
- core
- member
- admin

toycore-standard
- toycore-minimal
- seo
- popup_layer
- point
- deposit
- reward

toycore-ops
- toycore-standard
- site_menu
- banner
- notification
```

패키지 생성:

```sh
./.tools/bin/package-distributions 2026.05.001
```

생성 위치:

```text
dist/toycore-minimal
dist/toycore-standard
dist/toycore-ops
```

`zip` 명령을 사용할 수 있으면 같은 이름의 zip 파일도 생성됩니다. minimal 배포본에는 선택 모듈 코드가 없으므로 설치 화면에서 선택 모듈 목록이 비어 있을 수 있습니다. 설치 후 필요한 모듈 zip을 `modules/{module_key}`에 업로드하고 `/admin/modules`에서 설치합니다.

standard/ops 패키지를 만들 때는 toycore.git과 같은 상위 디렉터리에 `toycore-module-seo`, `toycore-module-popup-layer` 같은 외부 모듈 리포지토리가 있어야 합니다. 다른 위치에 있다면 `TOYCORE_MODULE_REPO_ROOT` 환경변수로 상위 디렉터리를 지정합니다.

## PHP 내장 서버

로컬 확인용입니다.

```sh
./.tools/bin/php -S 127.0.0.1:8080 index.php
```

브라우저에서 `http://127.0.0.1:8080/`로 접속합니다.

## Apache 가상 호스트

서버 설정을 직접 수정할 수 있는 환경에서는 문서 루트를 프로젝트 루트로 지정하고 모든 요청을 `index.php`로 전달합니다.

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/toycore

    <Directory /var/www/toycore>
        Require all granted
        DirectoryIndex index.php
        FallbackResource /index.php
    </Directory>
</VirtualHost>
```

`FallbackResource`를 사용할 수 없는 공유호스팅에서는 호스팅 패널의 프론트 컨트롤러, 기본 문서, 오류 문서 설정을 먼저 확인합니다. 프로젝트 자체에는 `.htaccess`를 추가하지 않습니다.

## Nginx

서버 설정을 직접 수정할 수 있는 환경에서는 `try_files`로 `index.php`에 전달합니다.

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/toycore;
    index index.php;

    location / {
        try_files $uri /index.php?$query_string;
    }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

## 공유호스팅

서버 설정을 직접 수정할 수 없는 환경에서는 다음 순서로 확인합니다.

```text
1. 문서 루트를 Toycore 프로젝트 루트로 지정할 수 있는지 확인
2. index.php가 기본 문서로 실행되는지 확인
3. config와 storage가 쓰기 가능한지 확인
4. 설치 화면에서 DB host, DB 이름, DB 사용자, DB 비밀번호 입력
5. 설치 후 config/config.php와 storage/installed.lock 생성 확인
```

요청 재작성 기능을 설정할 수 없다면, 초기 운영은 루트 요청과 명시적 PHP 진입 방식부터 확인합니다. Toycore는 배포 환경의 제약을 숨기기보다 운영자가 확인할 수 있게 유지합니다.
