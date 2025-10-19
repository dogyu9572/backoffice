@extends('backoffice.layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/backoffice/boards.css') }}">
@endsection

@section('title', $pageTitle ?? '게시판 템플릿 수정')

@section('content')
<div class="board-container">
    <div class="board-header">
        <a href="{{ route('backoffice.board-templates.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> 목록으로
        </a>
    </div>

    <div class="board-card">
        <div class="board-card-header">
            <h6>템플릿 정보 수정</h6>
            @if($boardTemplate->is_system)
                <span class="badge badge-info">시스템 템플릿 (제한적 수정)</span>
            @endif
        </div>
        <div class="board-card-body">
            @if ($errors->any())
                <div class="board-alert board-alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('backoffice.board-templates.update', $boardTemplate) }}" method="POST">
                @csrf
                @method('PUT')

                @if($boardTemplate->is_system)
                    <!-- 시스템 템플릿은 활성화 여부만 수정 가능 -->
                    <div class="board-alert board-alert-info">
                        시스템 템플릿은 활성화 여부만 변경할 수 있습니다.
                    </div>
                    
                    <div class="board-form-group">
                        <label class="board-form-label">템플릿 이름</label>
                        <p class="form-control-plaintext">{{ $boardTemplate->name }}</p>
                    </div>

                    <div class="board-form-group">
                        <div class="board-checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $boardTemplate->is_active))>
                            <label for="is_active">활성화</label>
                        </div>
                    </div>

                @else
                    <!-- 일반 템플릿은 모든 필드 수정 가능 -->
                    <div class="board-form-row">
                        <div class="board-form-col board-form-col-6">
                            <div class="board-form-group">
                                <label for="name" class="board-form-label">템플릿 이름 <span class="required">*</span></label>
                                <input type="text" class="board-form-control" id="name" name="name" value="{{ old('name', $boardTemplate->name) }}" required>
                            </div>
                        </div>

                        <div class="board-form-col board-form-col-6">
                            <div class="board-form-group">
                                <label for="skin_id" class="board-form-label">스킨 <span class="required">*</span></label>
                                <select class="board-form-control" id="skin_id" name="skin_id" required>
                                    <option value="">스킨을 선택하세요</option>
                                    @foreach ($skins as $skin)
                                        <option value="{{ $skin->id }}" @selected(old('skin_id', $boardTemplate->skin_id) == $skin->id)>
                                            {{ $skin->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="board-form-group">
                        <label for="description" class="board-form-label">설명</label>
                        <textarea class="board-form-control board-form-textarea" id="description" name="description" rows="2">{{ old('description', $boardTemplate->description) }}</textarea>
                    </div>

                    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e9ecef;">

                    <!-- 기본 필드 설정 -->
                    <div class="board-form-group">
                        <label class="board-form-label">기본 필드 설정</label>
                        <small class="board-form-text">게시글에서 사용할 기본 필드를 선택하세요.</small>
                        
                        <div class="field-config-wrapper" style="margin-top: 15px;">
                            @php
                                $fieldConfig = $boardTemplate->field_config ?: $boardTemplate->getDefaultFieldConfig();
                                $defaultFields = [
                                    'title' => ['label' => '제목'],
                                    'content' => ['label' => '내용'],
                                    'category' => ['label' => '카테고리'],
                                    'author_name' => ['label' => '작성자'],
                                    'password' => ['label' => '비밀번호'],
                                    'attachments' => ['label' => '첨부파일'],
                                    'thumbnail' => ['label' => '썸네일'],
                                    'is_secret' => ['label' => '비밀글'],
                                    'created_at' => ['label' => '등록일'],
                                ];
                            @endphp

                            @foreach($defaultFields as $fieldName => $fieldInfo)
                                @php
                                    $config = $fieldConfig[$fieldName] ?? ['enabled' => false, 'required' => false];
                                @endphp
                                <div class="field-config-item">
                                    <div class="field-config-checkbox">
                                        <input type="checkbox" 
                                               id="field_{{ $fieldName }}_enabled" 
                                               name="field_{{ $fieldName }}_enabled" 
                                               value="1"
                                               @checked(old('field_' . $fieldName . '_enabled', $config['enabled']))>
                                        <label for="field_{{ $fieldName }}_enabled">{{ $fieldInfo['label'] }}</label>
                                    </div>
                                    <div class="field-config-required">
                                        <input type="checkbox" 
                                               id="field_{{ $fieldName }}_required" 
                                               name="field_{{ $fieldName }}_required" 
                                               value="1"
                                               @checked(old('field_' . $fieldName . '_required', $config['required']))>
                                        <label for="field_{{ $fieldName }}_required">필수</label>
                                    </div>
                                    <input type="hidden" name="field_{{ $fieldName }}_label" value="{{ $config['label'] ?? $fieldInfo['label'] }}">
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e9ecef;">

                    <!-- 카테고리 그룹 설정 -->
                    <div class="board-form-group">
                        <label for="category_group" class="board-form-label">카테고리 그룹</label>
                        <select class="board-form-control" id="category_group" name="category_group">
                            <option value="">카테고리 사용 안함</option>
                            @foreach($categoryGroups as $group)
                                <option value="{{ $group }}" @selected(old('category_group', $boardTemplate->category_group) == $group)>
                                    {{ $group }}
                                </option>
                            @endforeach
                        </select>
                        <small class="board-form-text">카테고리를 사용하려면 그룹을 선택하세요. 카테고리 관리 메뉴에서 등록된 그룹들이 표시됩니다.</small>
                    </div>

                    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e9ecef;">

                    <!-- 커스텀 필드 설정 -->
                    <div class="board-form-group">
                        <label class="board-form-label">커스텀 필드 설정</label>
                        <small class="board-form-text">게시글 작성 시 추가로 입력받을 필드들을 설정할 수 있습니다.</small>
                        <div class="custom-fields-container">
                            <div class="custom-fields-list" id="customFieldsList">
                                @if($boardTemplate->custom_fields_config)
                                    @foreach($boardTemplate->custom_fields_config as $index => $field)
                                        <!-- 기존 커스텀 필드 표시 (JavaScript로 로드) -->
                                    @endforeach
                                @endif
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addCustomField()">
                                <i class="fas fa-plus"></i> 커스텀 필드 추가
                            </button>
                        </div>
                    </div>

                    <hr style="margin: 30px 0; border: 0; border-top: 1px solid #e9ecef;">

                    <!-- 기능 설정 -->
                    <div class="board-form-row">
                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <div class="board-checkbox-group">
                                    <input type="checkbox" id="enable_notice" name="enable_notice" value="1" @checked(old('enable_notice', $boardTemplate->enable_notice))>
                                    <label for="enable_notice">공지사항 기능 사용</label>
                                </div>
                            </div>
                        </div>
                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <div class="board-checkbox-group">
                                    <input type="checkbox" id="enable_sorting" name="enable_sorting" value="1" @checked(old('enable_sorting', $boardTemplate->enable_sorting))>
                                    <label for="enable_sorting">정렬 기능 사용</label>
                                </div>
                            </div>
                        </div>
                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <div class="board-checkbox-group">
                                    <input type="checkbox" id="enable_category" name="enable_category" value="1" @checked(old('enable_category', $boardTemplate->enable_category))>
                                    <label for="enable_category">카테고리 기능 사용</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 목록 및 권한 설정 -->
                    <div class="board-form-row">
                        <div class="board-form-col board-form-col-6">
                            <div class="board-form-group">
                                <label for="list_count" class="board-form-label">페이지당 글 수</label>
                                <input type="number" class="board-form-control" id="list_count" name="list_count" value="{{ old('list_count', $boardTemplate->list_count) }}" min="5" max="100">
                            </div>
                        </div>
                    </div>

                    <div class="board-form-row">
                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <label class="board-form-label">읽기 권한 <span class="required">*</span></label>
                                <div class="board-radio-group">
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_read_all" name="permission_read" value="all" @checked(old('permission_read', $boardTemplate->permission_read) == 'all')>
                                        <label for="permission_read_all">모두</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_read_member" name="permission_read" value="member" @checked(old('permission_read', $boardTemplate->permission_read) == 'member')>
                                        <label for="permission_read_member">회원만</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_read_admin" name="permission_read" value="admin" @checked(old('permission_read', $boardTemplate->permission_read) == 'admin')>
                                        <label for="permission_read_admin">관리자만</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <label class="board-form-label">쓰기 권한 <span class="required">*</span></label>
                                <div class="board-radio-group">
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_write_all" name="permission_write" value="all" @checked(old('permission_write', $boardTemplate->permission_write) == 'all')>
                                        <label for="permission_write_all">모두</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_write_member" name="permission_write" value="member" @checked(old('permission_write', $boardTemplate->permission_write) == 'member')>
                                        <label for="permission_write_member">회원만</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_write_admin" name="permission_write" value="admin" @checked(old('permission_write', $boardTemplate->permission_write) == 'admin')>
                                        <label for="permission_write_admin">관리자만</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="board-form-col board-form-col-4">
                            <div class="board-form-group">
                                <label class="board-form-label">댓글 권한 <span class="required">*</span></label>
                                <div class="board-radio-group">
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_comment_all" name="permission_comment" value="all" @checked(old('permission_comment', $boardTemplate->permission_comment) == 'all')>
                                        <label for="permission_comment_all">모두</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_comment_member" name="permission_comment" value="member" @checked(old('permission_comment', $boardTemplate->permission_comment) == 'member')>
                                        <label for="permission_comment_member">회원만</label>
                                    </div>
                                    <div class="board-radio-item">
                                        <input type="radio" id="permission_comment_admin" name="permission_comment" value="admin" @checked(old('permission_comment', $boardTemplate->permission_comment) == 'admin')>
                                        <label for="permission_comment_admin">관리자만</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 상태 설정 -->
                    <div class="board-form-group">
                        <div class="board-checkbox-group">
                            <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $boardTemplate->is_active))>
                            <label for="is_active">활성화</label>
                        </div>
                    </div>
                @endif

                <div class="board-form-actions">
                    <button type="submit" class="btn btn-primary">수정</button>
                    <a href="{{ route('backoffice.board-templates.index') }}" class="btn btn-secondary">취소</a>
                </div>
            </form>
        </div>
    </div>
</div>

@section('scripts')
<script src="{{ asset('js/backoffice/board-templates.js') }}"></script>
@unless($boardTemplate->is_system)
<script>
    // 기존 커스텀 필드 로드
    document.addEventListener('DOMContentLoaded', function() {
        const customFields = @json($boardTemplate->custom_fields_config ?? []);
        if (customFields && Array.isArray(customFields)) {
            customFields.forEach((field, index) => {
                addCustomFieldWithData(field);
            });
        }
    });
</script>
@endunless
@endsection
@endsection

