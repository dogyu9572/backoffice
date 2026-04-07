@extends('backoffice.layouts.app')

@section('title', '전체회원 목록')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/backoffice/boards.css') }}">
<link rel="stylesheet" href="{{ asset('css/backoffice/backoffice-crud.css') }}">
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="{{ asset('js/backoffice/members.js') }}"></script>
@endsection

@section('content')
@if (session('success'))
    <div class="alert alert-success board-hidden-alert">
        {{ session('success') }}
    </div>
@endif

<div class="board-container">
    <div class="board-page-header">
        <div class="board-page-buttons">
            <button type="button" id="btnDeleteMultiple" class="btn btn-danger">
                <i class="fas fa-trash"></i> 선택 삭제
            </button>
            <button type="button" id="btnExport" class="btn btn-secondary">
                <i class="fas fa-file-excel"></i> 엑셀 다운로드
            </button>
            <a href="{{ route('backoffice.members.create') }}" class="btn btn-success">
                <i class="fas fa-plus"></i> 신규등록
            </a>
        </div>
    </div>

    <div class="board-card">
        <div class="board-card-body">
            <div class="board-filter">
                <form method="GET" action="{{ route('backoffice.members.index') }}" class="filter-form" id="searchForm">
                    <input type="hidden" name="per_page" value="{{ $perPage }}">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="join_date_start" class="filter-label">가입일 시작</label>
                            <input type="date" id="join_date_start" name="join_date_start" class="filter-input" value="{{ $filters['join_date_start'] ?? '' }}">
                        </div>
                        <div class="filter-group">
                            <label for="join_date_end" class="filter-label">가입일 끝</label>
                            <input type="date" id="join_date_end" name="join_date_end" class="filter-input" value="{{ $filters['join_date_end'] ?? '' }}">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">수신동의</label>
                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="marketing_consent[]" value="email" @checked(is_array($filters['marketing_consent'] ?? []) && in_array('email', $filters['marketing_consent']))>
                                    <span>EMAIL</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="marketing_consent[]" value="kakao" @checked(is_array($filters['marketing_consent'] ?? []) && in_array('kakao', $filters['marketing_consent']))>
                                    <span>카카오 알림톡</span>
                                </label>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label for="search_type" class="filter-label">검색 구분</label>
                            <select id="search_type" name="search_type" class="filter-select">
                                <option value="전체" @selected($filters['search_type'] == '전체')>전체</option>
                                <option value="이름" @selected($filters['search_type'] == '이름')>이름</option>
                                <option value="학교명" @selected($filters['search_type'] == '학교명')>학교명</option>
                                <option value="이메일주소" @selected($filters['search_type'] == '이메일주소')>이메일주소</option>
                                <option value="휴대폰번호" @selected($filters['search_type'] == '휴대폰번호')>휴대폰번호</option>
                                <option value="ID" @selected($filters['search_type'] == 'ID')>ID</option>
                            </select>
                        </div>
                        <div class="filter-group">
                            <label for="search_term" class="filter-label">검색어</label>
                            <input
                                type="text"
                                id="search_term"
                                name="search_term"
                                class="filter-input"
                                placeholder="검색어를 입력하세요"
                                value="{{ $filters['search_term'] ?? '' }}"
                            >
                        </div>
                        <div class="filter-group">
                            <div class="filter-buttons">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> 검색
                                </button>
                                <a href="{{ route('backoffice.members.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i> 초기화
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="board-list-header">
                <div class="list-info">
                    <span class="list-count">Total : {{ $members->total() }}</span>
                </div>
                <div class="list-controls">
                    <form method="GET" action="{{ route('backoffice.members.index') }}" class="per-page-form">
                        <input type="hidden" name="join_date_start" value="{{ $filters['join_date_start'] ?? '' }}">
                        <input type="hidden" name="join_date_end" value="{{ $filters['join_date_end'] ?? '' }}">
                        <input type="hidden" name="search_term" value="{{ $filters['search_term'] ?? '' }}">
                        <input type="hidden" name="search_type" value="{{ $filters['search_type'] ?? '' }}">
                        @if(is_array($filters['marketing_consent'] ?? []))
                            @foreach($filters['marketing_consent'] as $consent)
                                <input type="hidden" name="marketing_consent[]" value="{{ $consent }}">
                            @endforeach
                        @endif
                        <label for="per_page" class="per-page-label">표시 개수:</label>
                        <select name="per_page" id="perPageSelect" class="per-page-select" onchange="this.form.submit()">
                            <option value="20" @selected($perPage == 20)>20개</option>
                            <option value="50" @selected($perPage == 50)>50개</option>
                            <option value="100" @selected($perPage == 100)>100개</option>
                        </select>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="board-table">
                    <thead>
                        <tr>
                            <th class="w5 board-checkbox-column">
                                <input type="checkbox" id="select-all" class="form-check-input">
                            </th>
                            <th>No</th>
                            <th>가입구분</th>
                            <th>ID</th>
                            <th>학교명</th>
                            <th>이름</th>
                            <th>휴대폰번호</th>
                            <th>이메일주소</th>
                            <th>학교 대표자</th>
                            <th>가입일시</th>
                            <th>관리</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($members as $index => $member)
                            <tr data-id="{{ $member->id }}">
                                <td>
                                    <input type="checkbox" name="selected_members[]" value="{{ $member->id }}" class="form-check-input bo-row-checkbox">
                                </td>
                                <td>{{ $members->total() - ($members->currentPage() - 1) * $members->perPage() - $index }}</td>
                                <td>{{ $member->join_type === 'email' ? '이메일' : ($member->join_type === 'kakao' ? '카카오' : '네이버') }}</td>
                                <td>{{ $member->login_id ?? '-' }}</td>
                                <td>{{ $member->school_name }}</td>
                                <td>{{ $member->name }}</td>
                                <td>{{ $member->phone_number }}</td>
                                <td>{{ $member->email ?? '-' }}</td>
                                <td>{{ $member->is_school_representative ? '대표자' : '일반회원' }}</td>
                                <td>{{ $member->created_at->format('Y.m.d H:i') }}</td>
                                <td>
                                    <div class="board-btn-group">
                                        <a href="{{ route('backoffice.members.edit', $member->id) }}" class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> 수정
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm btn-delete-member" data-id="{{ $member->id }}">
                                            <i class="fas fa-trash"></i> 삭제
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">등록된 회원이 없습니다.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <x-pagination :paginator="$members" />
        </div>
    </div>
</div>
@endsection
