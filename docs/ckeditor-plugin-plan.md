# CKEditor 플러그인 추가 계획

이 문서는 산란에서 CKEditor 5 기반 편집기를 선택 플러그인으로 추가하기 위한 구현 계획이다.

문서 수명:

- CKEditor 플러그인을 구현하기 전까지 계획 문서로 보관한다.
- 실제 구현과 검증이 완료되면 이 문서는 삭제한다.
- 구현 후 유지해야 하는 기준은 `docs/module-guide.md`, `docs/security-model.md`, 플러그인 README 중 필요한 곳으로만 옮긴다.

## 공식 문서 확인 기준

구현 시점에는 CKEditor 공식 문서를 다시 확인한다.

- Vanilla JS self-hosted 설치: https://ckeditor.com/docs/ckeditor5/latest/getting-started/installation/self-hosted/quick-start.html
- CDN 설치: https://ckeditor.com/docs/ckeditor5/latest/getting-started/installation/cloud/quick-start.html
- 이미지 업로드 개요: https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/image-upload.html
- simple upload adapter: https://ckeditor.com/docs/ckeditor5/latest/features/images/image-upload/simple-upload-adapter.html
- custom upload adapter: https://ckeditor.com/docs/ckeditor5/latest/framework/deep-dive/upload-adapter.html

2026년 5월 기준 공식 문서에서는 CKEditor 5를 npm, ZIP, CDN 방식으로 설치할 수 있다. self-hosted 방식은 라이선스 키 또는 GPL 조건 확인이 필요하므로, 구현 전 라이선스 기준을 명확히 확인한다.

## 기본 방향

CKEditor는 코어 기능이 아니라 플러그인으로 처리한다.

권장 플러그인명:

```text
ckeditor
```

책임 분리:

- `core`: CKEditor를 알지 않는다.
- `ckeditor`: 에디터 asset, 초기화 스크립트, toolbar preset, textarea enhance 로직을 소유한다.
- 에디터를 쓰는 모듈: 어떤 필드에 에디터를 붙일지, 저장 형식과 출력 정책을 결정한다.
- 업로드 파일을 소유하는 모듈: 이미지 업로드 권한, 저장, 공개 URL, 보존 정책을 책임진다.

이 구조는 코어가 에디터 정책을 갖지 않게 하고, 게시판/팝업/배너 같은 모듈이 CKEditor에 직접 종속되지 않게 한다.

## 1차 적용 대상

초기 적용은 커뮤니티 게시글 작성 폼으로 제한한다.

1차 후보:

- `modules/community/skins/basic/form.php`의 `body_text`

1차 제외:

- 댓글 작성
- 쪽지
- 관리자 알림
- 팝업레이어 본문
- 배너 본문
- SEO/설정 textarea

이유:

- 커뮤니티 게시글은 긴 본문 작성 수요가 가장 명확하다.
- 이미 이미지/파일 첨부 정책이 커뮤니티 모듈에 있다.
- 에디터 입력값 저장/출력 정책을 게시글 도메인 안에서 검증하기 좋다.

## 설치 방식

1차는 빌드 없는 방식을 우선한다.

후보:

1. ZIP self-hosted
2. CDN

권장 기본값은 ZIP self-hosted다.

이유:

- 공유호스팅에서 Node/Vite 빌드를 요구하지 않는다.
- 외부 CDN 장애나 네트워크 정책에 덜 의존한다.
- 배포 파일이 저장소 또는 모듈 zip 안에 명시적으로 포함된다.

단, CKEditor 5 self-hosted 배포의 라이선스 조건을 구현 시점에 다시 확인한다. 라이선스 부담이 명확히 정리되기 전에는 실제 CKEditor bundle을 저장소에 포함하지 않고, 플러그인 문서에 설치 절차만 둔다.

CDN은 선택 설정으로 검토한다.

```text
ckeditor_asset_mode = self_hosted | cdn
ckeditor_license_key = GPL 또는 발급 키
```

## 플러그인 구조

예상 구조:

```text
modules/ckeditor/
- module.php
- helpers.php
- paths.php
- admin-menu.php
- editor-targets.php
- assets/
  - saanraan-ckeditor.js
  - saanraan-ckeditor.css
- vendor/
  - ckeditor5/              # 실제 bundle은 라이선스 확인 후 포함 여부 결정
- actions/
  - upload.php              # 1차에서는 보류 가능
- views/
  - admin-settings.php
- install.sql
```

`ckeditor`는 특정 도메인의 본문 테이블을 소유하지 않는다. 필요하면 설정 테이블만 가진다.

## 대상 필드 선언 방식

계약 파일을 과하게 늘리지 않기 위해 1차는 data attribute 기반으로 시작한다.

에디터 적용 대상 textarea 예:

```html
<textarea
    name="body_text"
    data-sr-editor="ckeditor"
    data-sr-editor-preset="community_post"
></textarea>
```

`ckeditor` 플러그인은 현재 페이지의 `data-sr-editor="ckeditor"` 요소만 찾아 초기화한다.

이 방식의 장점:

- 코어 계약 파일 추가가 없다.
- 화면 소유 모듈이 에디터 사용 여부를 직접 드러낸다.
- CKEditor 플러그인이 비활성화되어도 일반 textarea로 동작한다.

여러 모듈에서 반복 요구가 생기면 이후 단계에서 `editor-targets.php` 같은 선택 계약 파일을 검토한다.

## 출력과 저장 형식

중요 결정:

- CKEditor 입력값은 HTML이다.
- 현재 커뮤니티 게시글은 plain text 출력 흐름을 사용한다.
- 1차에서 HTML 저장을 도입하면 XSS 정책과 출력 정책이 함께 필요하다.

따라서 1차 구현은 두 선택지 중 하나를 먼저 결정해야 한다.

권장 1차 선택:

```text
제한된 HTML 저장 + 서버 side sanitizer + HTML 출력 helper
```

필요 변경:

- 커뮤니티 게시글 `body_format`에 `html` 허용
- 저장 전 sanitizer 적용
- 출력 시 허용 태그/속성만 렌더링
- 기존 plain text 게시글은 기존 helper 유지

허용 후보:

- `p`
- `br`
- `strong`
- `em`
- `u`
- `s`
- `blockquote`
- `ul`
- `ol`
- `li`
- `a href`
- `h2`
- `h3`
- `img src alt width height`

금지:

- `script`
- inline event handler
- `style`
- `iframe`
- `object`
- `embed`
- `form`
- `javascript:` URL
- data URL 이미지

HTML sanitizer를 직접 문자열 치환으로 만들지 않는다. 공유호스팅에서도 사용할 수 있는 작은 sanitizer 후보를 검토하거나, 허용 태그 기반 DOMDocument 정화를 별도 helper로 구현한다.

## 이미지 업로드

1차에서는 이미지 업로드를 보류하거나 커뮤니티 모듈의 기존 이미지 첨부 정책과 연결한다.

권장 단계:

1. 에디터 텍스트 기능만 먼저 적용한다.
2. 이미지 업로드는 `community`가 소유하는 action으로 구현한다.
3. CKEditor 플러그인은 upload adapter에서 해당 action을 호출한다.

예상 업로드 action:

```text
POST /community/editor-image-upload
```

책임:

- `community`: 로그인, 게시판 권한, 파일 크기, MIME, 확장자, 저장소, attachment 기록, 공개 URL 응답
- `ckeditor`: CKEditor upload adapter와 응답 파싱

업로드 응답은 CKEditor 요구 형식에 맞춘다.

보안 기준:

- CSRF 또는 업로드 전용 토큰 확인
- 로그인 확인
- 게시판 이미지 업로드 허용 여부 확인
- `sr_upload_validate_file()` 사용
- 이미지 재인코딩
- 실행 가능 확장자 차단
- 공개 URL만 응답

## 관리자 설정

예상 설정:

- asset mode: self-hosted/CDN
- license key
- 기본 toolbar preset
- 커뮤니티 게시글 에디터 사용 여부
- 이미지 업로드 사용 여부

secret 성격의 license key는 다시 표시하지 않는 방향을 검토한다. GPL 사용처럼 secret이 아닌 값은 그대로 표시해도 된다.

## toolbar preset

1차 preset:

```text
community_post_basic
```

포함 후보:

- undo/redo
- bold
- italic
- underline
- link
- heading
- bulleted list
- numbered list
- blockquote

이미지 업로드는 2차 preset에서 추가한다.

프리미엄 기능은 1차 범위에서 제외한다.

## graceful fallback

CKEditor 플러그인이 비활성화되거나 asset 로딩에 실패해도 textarea는 정상 제출되어야 한다.

원칙:

- textarea를 숨기기 전에 editor 생성 성공을 확인한다.
- JS 오류가 있어도 저장 form은 동작한다.
- 서버는 CKEditor가 보낸 값이라고 신뢰하지 않는다.
- HTML 저장 모드에서도 서버 sanitizer를 반드시 통과한다.

## 1차 구현 단계

1. `ckeditor` 플러그인 골격을 만든다.
2. 관리자 설정 화면을 만든다.
3. CKEditor asset 로딩 방식을 결정한다.
4. `data-sr-editor="ckeditor"` textarea 초기화 스크립트를 만든다.
5. 커뮤니티 게시글 작성 textarea에 선택적으로 data attribute를 붙인다.
6. HTML 저장 정책을 결정하고 sanitizer를 구현한다.
7. 커뮤니티 게시글 출력에서 `body_format`별 렌더링을 분기한다.
8. 에디터 비활성/asset 실패 시 textarea fallback을 검증한다.
9. 이미지 업로드 없는 기본 작성/수정 흐름을 검증한다.
10. 구현 완료 후 이 계획 문서를 삭제하고 필요한 기준만 유지 문서로 옮긴다.

## 2차 구현 단계

1. 커뮤니티 전용 editor image upload action을 만든다.
2. CKEditor custom upload adapter 또는 simple upload adapter 설정을 붙인다.
3. 업로드 이미지 attachment 기록과 본문 이미지 참조를 연결한다.
4. 게시글 삭제/숨김/첨부 정리 정책과 본문 이미지 보존 정책을 맞춘다.
5. 관리자에서 toolbar preset과 이미지 업로드 사용 여부를 조정할 수 있게 한다.

## 검증 항목

- 플러그인 비활성 상태에서 기존 textarea 작성이 정상 동작하는가
- CKEditor 활성 상태에서 게시글 작성/수정이 정상 동작하는가
- 저장된 HTML이 허용 태그만 남는가
- 악성 HTML이 제거되는가
- plain text 기존 게시글 출력이 깨지지 않는가
- 모바일 화면에서 editor UI가 부모 폭을 넘지 않는가
- 다크 모드에서 editor 주변 UI가 읽히는가
- asset mode별 로딩 실패 fallback이 동작하는가
- 이미지 업로드를 붙인 뒤 권한 없는 업로드가 차단되는가
- 업로드 파일 검증과 재인코딩이 적용되는가
