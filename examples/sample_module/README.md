# Sample Module

이 예시는 Toycore 모듈이 어떤 파일 계약으로 구성되는지 보여주는 최소 샘플입니다.

실제 설치 대상으로 쓰려면 이 디렉터리를 `modules/sample_notice`로 복사한 뒤 필요한 action/view/table을 프로젝트 목적에 맞게 수정하세요.

포함된 파일:

- `module.php`: 모듈 메타데이터와 의존성 선언
- `paths.php`: 요청 method/path와 action 파일 매핑
- `admin-menu.php`: 관리자 메뉴 항목
- `output-slots.php`: 출력 슬롯 renderer 예시
- `install.sql`: 모듈 소유 테이블 예시
