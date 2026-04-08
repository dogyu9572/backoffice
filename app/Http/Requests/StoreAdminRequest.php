<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdminRequest extends FormRequest
{
    /**
     * 요청에 대한 권한을 확인합니다.
     */
    public function authorize(): bool
    {
        return true; // 컨트롤러에서 권한 체크
    }

    /**
     * 유효성 검사 규칙을 정의합니다.
     */
    public function rules(): array
    {
        return [
            'login_id' => 'required|string|max:255|unique:users,login_id',
            'login_id_verified' => 'accepted',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:4|confirmed',
            'department' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'contact' => 'nullable|string|max:50',
            'is_active' => 'boolean',
            'admin_group_id' => 'nullable|exists:admin_groups,id',
        ];
    }

    /**
     * 서비스로 넘길 때 중복 확인용 필드는 제외합니다.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($key === null && is_array($validated)) {
            unset($validated['login_id_verified']);
        }

        return $validated;
    }

    /**
     * 유효성 검사 메시지를 정의합니다.
     */
    public function messages(): array
    {
        return AdminValidationMessages::getStoreMessages();
    }
}
