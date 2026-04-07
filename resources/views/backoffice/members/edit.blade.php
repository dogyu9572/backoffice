@extends('backoffice.layouts.app')

@section('title', '회원 정보 수정')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/backoffice/backoffice-crud.css') }}">
@endsection

@section('scripts')
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://t1.daumcdn.net/mapjsapi/bundle/postcode/prod/postcode.v2.js"></script>
<script src="{{ asset('js/backoffice/members.js') }}"></script>
@endsection

@section('content')
@if ($errors->any())
    <div class="alert alert-danger board-hidden-alert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="board-container">
    <div class="board-header">
        <a href="{{ route('backoffice.members.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> 목록으로
        </a>
    </div>

    <div class="board-card">
        <div class="board-card-body">
            <form action="{{ route('backoffice.members.update', $member->id) }}" method="POST" id="memberForm">
                @csrf
                @method('PUT')
                @include('backoffice.members._form', ['mode' => 'edit', 'member' => $member])
            </form>

            <div class="board-form-actions">
                <button type="submit" class="btn btn-primary" form="memberForm">
                    <i class="fas fa-save"></i> 저장
                </button>
                <a href="{{ route('backoffice.members.index') }}" class="btn btn-secondary">취소</a>
                <form action="{{ route('backoffice.members.destroy', $member->id) }}" method="POST" class="bo-inline-form" onsubmit="return confirm('정말로 삭제하시겠습니까?');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> 삭제
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@include('member.pop_search_school')
@endsection
