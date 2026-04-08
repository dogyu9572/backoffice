---
name: backoffice-menu-scaffold
description: 백오피스 신규 메뉴를 members 기반 CRUD 레이아웃으로 일관되게 생성한다. 새 메뉴 추가, index/create/edit/withdrawn 화면 시작점 구성, bo-* 공용 네이밍 적용 요청 시 사용한다.
---

# Backoffice Menu Scaffold

## 목적
신규 백오피스 메뉴를 `members` 기준의 공용 CRUD 패턴으로 생성하고, 디자인 드리프트를 방지한다.

## 적용 시점
- "새 백오피스 메뉴 만들어줘" 요청
- 기존 메뉴와 동일한 레이아웃으로 기능만 바꾸는 요청
- create/edit/index 화면이 매번 다르게 만들어지는 문제를 막고 싶을 때

## 기본 원칙
- 레이아웃은 `members`를 기준으로 재사용한다.
- 클래스/셀렉터는 공용 접두사 `bo-*`를 사용한다.
- 공용 스타일은 `public/css/backoffice/backoffice-crud.css`를 우선 사용한다.
- 도메인 전용 차이는 텍스트/필드/엔드포인트만 변경한다.

## 실행 절차
1. 범위를 확정한다. (`index/create/edit/withdrawn` 중 필요한 화면만)
2. 기존 `members` 구조를 복제하지 말고 partial/component 재사용 가능성을 먼저 확인한다.
3. 라우트/컨트롤러/뷰/JS의 endpoint naming을 일치시킨다.
4. 마지막에 아래 체크를 수행한다.
   - 인라인 코드 제거 여부
   - 한 줄 몰림 마크업 정렬 여부
   - 도메인 종속 접두사(`member-*`) 잔존 여부

## 산출물 형식
- 생성/수정 파일 목록
- 공용화(재사용)한 부분
- 도메인 특화로 분리한 부분
- 남은 TODO 없이 동작 가능한 상태 여부
