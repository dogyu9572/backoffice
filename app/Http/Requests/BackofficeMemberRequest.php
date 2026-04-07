<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class BackofficeMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $member = $this->route('user');
        $memberId = $member instanceof User ? $member->id : $member;
        $isUpdate = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => 'required|string|max:8',
            'phone_number' => 'required|string|unique:users,phone_number' . ($isUpdate ? ',' . $memberId : ''),
            'email' => 'nullable|email|unique:users,email' . ($isUpdate ? ',' . $memberId : ''),
            'birth_date' => 'nullable|string|regex:/^\d{8}$/',
            'school_name' => 'required|string',
            'is_school_representative' => 'boolean',
            'email_marketing_consent' => 'boolean',
            'kakao_marketing_consent' => 'boolean',
            'address_postcode' => 'nullable|string',
            'address_base' => 'nullable|string',
            'address_detail' => 'nullable|string',
        ];

        if ($isUpdate) {
            $rules['password'] = 'nullable|string|min:8|max:10|confirmed';
            $rules['password_confirmation'] = 'nullable|string|same:password';
        } else {
            $rules['join_type'] = 'required|in:email,kakao,naver';
            $rules['login_id'] = 'required|string|unique:users,login_id';

            if ($this->input('join_type') === 'email') {
                $rules['password'] = 'required|string|min:8|max:10|confirmed';
                $rules['password_confirmation'] = 'required|string|same:password';
            } else {
                $rules['password'] = 'nullable|string|min:8|max:10|confirmed';
                $rules['password_confirmation'] = 'nullable|string|same:password';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => '이름은 필수 입력 항목입니다.',
            'name.max' => '이름은 최대 8자까지 입력 가능합니다.',
            'phone_number.required' => '휴대폰번호는 필수 입력 항목입니다.',
            'phone_number.unique' => '이미 사용 중인 휴대폰번호입니다.',
            'email.email' => '올바른 이메일 형식이 아닙니다.',
            'email.unique' => '이미 사용 중인 이메일입니다.',
            'birth_date.regex' => '생년월일은 8자리 숫자(YYYYMMDD) 형식으로 입력해주세요.',
            'school_name.required' => '학교명은 필수 입력 항목입니다.',
            'join_type.required' => '가입 구분은 필수 선택 항목입니다.',
            'join_type.in' => '가입 구분은 이메일, 카카오, 네이버 중 하나를 선택해야 합니다.',
            'login_id.required' => '아이디는 필수 입력 항목입니다.',
            'login_id.unique' => '이미 사용 중인 아이디입니다.',
            'password.required' => '비밀번호는 필수 입력 항목입니다.',
            'password.min' => '비밀번호는 최소 8자 이상이어야 합니다.',
            'password.max' => '비밀번호는 최대 10자까지 입력 가능합니다.',
            'password.confirmed' => '비밀번호와 비밀번호 확인이 일치하지 않습니다.',
            'password_confirmation.same' => '비밀번호와 비밀번호 확인이 일치하지 않습니다.',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $isRep = $this->boolean('is_school_representative');
            $schoolName = trim((string) $this->input('school_name', ''));
            if (!$isRep || $schoolName === '') {
                return;
            }

            $query = User::whereNull('withdrawn_at')
                ->where('role', 'user')
                ->where('school_name', $schoolName)
                ->where('is_school_representative', true);

            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                $member = $this->route('user');
                $memberId = $member instanceof User ? $member->id : $member;
                if ($memberId) {
                    $query->where('id', '!=', $memberId);
                }
            }

            if ($query->exists()) {
                $validator->errors()->add('is_school_representative', '해당 학교의 대표자가 이미 등록되어 있습니다.');
            }
        });
    }
}
