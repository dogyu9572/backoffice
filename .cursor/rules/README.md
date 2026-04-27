# .cursor Rules 운영 가이드

## 목적
규칙 파일의 단일 진입점(SSOT)을 제공해 중복 해석과 충돌을 줄입니다.

## 우선순위
- 판단 우선순위: `금지 > 우선 > 허용(예외)`
- 협업 우선순위: `질문형/애매 요청은 실행 보류 후 선확인`

## 규칙 맵
- `00-global-principles.mdc`: 전역 공통 원칙, 협업 게이트 상위 원칙
- `10-laravel-architecture.mdc`: Laravel 계층 분리(Controller/Service/FormRequest/Model)
- `20-blade-and-assets.mdc`: Blade 표시 전용, CSS/JS 분리, 인라인 금지
- `25-backoffice-ui-consistency.mdc`: 백오피스 UI 레퍼런스 일관성
- `26-backoffice-naming-and-shared-crud.mdc`: `bo-*` 공용 네이밍, CRUD 공용화
- `27-change-preflight-checklist.mdc`: 변경 전/후 점검 및 최종 보고 형식
- `28-collaboration-ambiguity-gate.mdc`: 질문형/애매/위험 작업 선확인 절차
- `29-subagent-orchestration-policy.mdc`: 난이도 기반 단일/서브에이전트 자동 선택 정책
- `30-migrations-policy.mdc`: 마이그레이션/시더 안전 정책
- `40-dev-environment-ops.mdc`: WSL + Docker + Sail 운영 규칙

## 레거시 인덱스 파일
- `project.mdc`, `laravel.mdc`는 호환성 유지를 위한 안내 파일입니다.
- 신규/수정 규칙은 위 규칙 맵의 실체 파일에서만 관리합니다.

## 작업자 자기검증(하네스)
- 스킬 문서: `.cursor/skills/backoffice-self-verification-harness/SKILL.md`
- 기본 루틴: 진입 확인 → CRUD 왕복 → 필터 유지 → 유효성 실패 → 인라인 정적 검색 → 린트/구문 검증
