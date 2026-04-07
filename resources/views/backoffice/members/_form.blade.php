@php
    $isEdit = ($mode ?? 'create') === 'edit';
@endphp

<div class="bo-form-section">
    <h3 class="bo-section-title">기본 정보</h3>

    <div class="bo-form-list">
        <div class="bo-form-row">
            <label class="bo-form-label">가입 구분 @unless($isEdit)<span class="required">*</span>@endunless</label>
            <div class="bo-form-field">
                @if($isEdit)
                    <div class="board-form-control bo-readonly-box">
                        {{ $member->join_type === 'email' ? '이메일' : ($member->join_type === 'kakao' ? '카카오' : '네이버') }}
                    </div>
                @else
                    <div class="board-radio-group">
                        <div class="board-radio-item">
                            <input type="radio" id="join_type_email" name="join_type" value="email" class="board-radio-input" @checked(old('join_type') == 'email') required>
                            <label for="join_type_email">이메일</label>
                        </div>
                        <div class="board-radio-item">
                            <input type="radio" id="join_type_kakao" name="join_type" value="kakao" class="board-radio-input" @checked(old('join_type') == 'kakao')>
                            <label for="join_type_kakao">카카오</label>
                        </div>
                        <div class="board-radio-item">
                            <input type="radio" id="join_type_naver" name="join_type" value="naver" class="board-radio-input" @checked(old('join_type') == 'naver')>
                            <label for="join_type_naver">네이버</label>
                        </div>
                    </div>
                    @error('join_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">아이디 @unless($isEdit)<span class="required">*</span>@endunless</label>
            <div class="bo-form-field">
                @if($isEdit)
                    <input type="text" class="board-form-control bo-readonly-box" id="login_id" value="{{ $member->login_id }}" readonly>
                @else
                    <input type="text" class="board-form-control @error('login_id') is-invalid @enderror" id="login_id" name="login_id" value="{{ old('login_id') }}" required>
                    @error('login_id')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                @endif
            </div>
        </div>

        <div class="bo-form-row" id="passwordGroup">
            <label class="bo-form-label">비밀번호 @unless($isEdit)<span class="required">*</span>@endunless</label>
            <div class="bo-form-field">
                @if($isEdit)
                    <input type="password" class="board-form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="변경 시에만 입력해주세요.">
                    <small class="form-text text-muted bo-password-help">비밀번호를 변경하지 않으려면 비워두세요.</small>
                @else
                    <input type="password" class="board-form-control @error('password') is-invalid @enderror" id="password" name="password" placeholder="영문/숫자/특수문자 조합 두가지 이상(8~10자 이내 입력)">
                @endif
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row" id="passwordConfirmationGroup">
            <label class="bo-form-label">비밀번호 확인 @unless($isEdit)<span class="required">*</span>@endunless</label>
            <div class="bo-form-field">
                @if($isEdit)
                    <input type="password" class="board-form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="변경 시에만 입력해주세요.">
                @else
                    <input type="password" class="board-form-control @error('password_confirmation') is-invalid @enderror" id="password_confirmation" name="password_confirmation" placeholder="비밀번호를 한 번 더 입력해주세요.">
                @endif
                @error('password_confirmation')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">이름 <span class="required">*</span></label>
            <div class="bo-form-field">
                <input type="text" class="board-form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $member->name ?? '') }}" maxlength="8" required>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">휴대폰번호 <span class="required">*</span></label>
            <div class="bo-form-field">
                <div class="input-with-button bo-gap-bottom">
                    <input type="text" class="board-form-control @error('phone_number') is-invalid @enderror" id="phone_number" name="phone_number" value="{{ old('phone_number', $member->phone_number ?? '') }}" required>
                    <button type="button" class="btn btn-secondary btn-sm" id="btnCheckPhone" @if($isEdit) data-exclude-id="{{ $member->id }}" @endif>중복확인</button>
                </div>
                <div id="phoneCheckResult" class="check-result"></div>
                @error('phone_number')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">이메일</label>
            <div class="bo-form-field">
                <div class="input-with-button bo-gap-bottom">
                    <input type="email" class="board-form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $member->email ?? '') }}">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnCheckEmail" @if($isEdit) data-exclude-id="{{ $member->id }}" @endif>중복확인</button>
                </div>
                <div id="emailCheckResult" class="check-result"></div>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">주소</label>
            <div class="bo-form-field">
                <div class="input-with-button bo-gap-bottom">
                    <input type="text" class="board-form-control" id="address_postcode" name="address_postcode" value="{{ old('address_postcode', $member->address_postcode ?? '') }}" placeholder="우편번호를 검색해주세요." readonly>
                    <button type="button" class="btn btn-secondary btn-sm" id="btnSearchAddress">우편번호 검색</button>
                </div>
                <input type="text" class="board-form-control bo-gap-bottom" id="address_base" name="address_base" value="{{ old('address_base', $member->address_base ?? '') }}" placeholder="기본주소" readonly>
                <input type="text" class="board-form-control" id="address_detail" name="address_detail" value="{{ old('address_detail', $member->address_detail ?? '') }}" placeholder="상세주소를 입력해주세요.">
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">학교명 <span class="required">*</span></label>
            <div class="bo-form-field">
                <div class="input-with-button bo-gap-bottom">
                    <input type="text" class="board-form-control @error('school_name') is-invalid @enderror" id="school_name" name="school_name" value="{{ old('school_name', $member->school_name ?? '') }}" required placeholder="학교명을 검색해주세요.">
                    <button type="button" class="btn btn-secondary btn-sm" id="btnSearchSchool">검색</button>
                </div>
                <input type="text" class="board-form-control bo-school-direct" id="school_name_direct" placeholder="학교명을 직접 입력해주세요.">
                @error('school_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="bo-form-row">
            <label class="bo-form-label">학교 대표자</label>
            <div class="bo-form-field">
                <div class="board-radio-group">
                    <div class="board-radio-item">
                        <input type="radio" id="is_school_representative_y" name="is_school_representative" value="1" class="board-radio-input" @checked(old('is_school_representative', $member->is_school_representative ?? 0) == 1)>
                        <label for="is_school_representative_y">Y</label>
                    </div>
                    <div class="board-radio-item">
                        <input type="radio" id="is_school_representative_n" name="is_school_representative" value="0" class="board-radio-input" @checked(old('is_school_representative', $member->is_school_representative ?? 0) == 0)>
                        <label for="is_school_representative_n">N</label>
                    </div>
                </div>
                @error('is_school_representative')
                    <div class="invalid-feedback bo-inline-error">{{ $message }}</div>
                @enderror
            </div>
        </div>

        @if($isEdit)
            <div class="bo-form-row">
                <label class="bo-form-label">가입일시</label>
                <div class="bo-form-field">
                    <div class="board-form-control bo-readonly-box">
                        {{ $member->created_at->format('Y.m.d H:i') }}
                    </div>
                </div>
            </div>
        @endif

        <div class="bo-form-row">
            <label class="bo-form-label">수신동의</label>
            <div class="bo-form-field">
                <div class="checkbox-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="email_marketing_consent" value="1" @checked(old('email_marketing_consent', $member->email_marketing_consent ?? false))>
                        <span>이메일 수신동의</span>
                    </label>
                    <label class="checkbox-label">
                        <input type="checkbox" name="kakao_marketing_consent" value="1" @checked(old('kakao_marketing_consent', $member->kakao_marketing_consent ?? false))>
                        <span>카카오 알림톡 수신동의</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
