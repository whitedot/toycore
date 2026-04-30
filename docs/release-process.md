# 릴리스 절차

이 문서는 Toycore 본체와 공식 모듈 zip을 릴리스할 때의 최소 절차를 정리한다.

## 1. 준비

- `main` 브랜치가 배포할 커밋을 가리키는지 확인한다.
- 외부 모듈 리포지토리가 toycore.git과 같은 상위 디렉터리에 있는지 확인한다.
- 다른 위치를 쓰면 `TOYCORE_MODULE_REPO_ROOT`에 모듈 리포지토리 상위 디렉터리를 지정한다.
- 각 모듈의 `module/module.php` version과 `CHANGELOG.md`를 확인한다.

## 2. 본체 배포 패키지 생성

```sh
./.tools/bin/package-distributions 2026.05.001
```

생성 결과:

```text
dist/toycore-minimal
dist/toycore-standard
dist/toycore-ops
dist/toycore-minimal-2026.05.001.zip
dist/toycore-standard-2026.05.001.zip
dist/toycore-ops-2026.05.001.zip
```

각 배포 디렉터리의 `distribution-manifest.json`에서 포함 모듈과 버전을 확인한다.

## 3. 공식 모듈 zip 확인

각 모듈 리포지토리에서 설치용 zip을 만든다.

```sh
./.tools/bin/package-module
```

확인 기준:

- zip 압축 해제 시 `{module_key}/module.php` 구조가 나오는가
- `module.php` version이 릴리스 버전과 맞는가
- `install.sql`과 필요한 `updates/` 파일이 포함되어 있는가
- 같은 버전의 update SQL을 이미 배포한 적이 있다면 내용이 바뀌지 않았는가

## 4. Checksum 기록

공식 모듈 release zip을 업로드한 뒤 sha256 checksum을 계산한다.

```sh
sha256sum module-name.zip
```

`docs/module-index.json`에 다음 값을 갱신한다.

```json
{
  "latest_version": "2026.05.001",
  "zip_url": "https://github.com/whitedot/toycore-module-example/releases/download/v2026.05.001/example-2026.05.001.zip",
  "checksum": "..."
}
```

URL과 checksum이 모두 채워진 항목만 `/admin/modules`의 공식 registry 다운로드 대상으로 사용된다.

## 5. 릴리스 노트

릴리스 노트에는 다음을 포함한다.

- 본체 버전
- `minimal`, `standard`, `ops` 패키지 차이
- 각 패키지의 `distribution-manifest.json` 내용
- 포함 모듈 버전
- DB update SQL이 있는 모듈 목록
- 수동 백업과 `/admin/updates` 실행 안내

## 6. 배포 후 확인

- `toycore-standard.zip`으로 신규 설치가 가능한지 확인한다.
- `/admin/modules`에서 설치 버전과 코드 버전이 일치하는지 확인한다.
- `/admin/updates`에 미적용 SQL이 남아 있지 않은지 확인한다.
- 공식 registry 항목의 checksum 불일치가 없는지 확인한다.
