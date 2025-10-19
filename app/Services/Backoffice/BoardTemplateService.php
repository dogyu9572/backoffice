<?php

namespace App\Services\Backoffice;

use App\Models\BoardTemplate;
use App\Models\BoardSkin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BoardTemplateService
{
    /**
     * 템플릿 목록을 가져옵니다.
     */
    public function getTemplates(int $perPage = 10)
    {
        return BoardTemplate::with('skin')
            ->orderBy('is_system', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * 필터링된 템플릿 목록을 가져옵니다.
     */
    public function getTemplatesWithFilters(Request $request)
    {
        $query = BoardTemplate::with('skin');
        
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }
        
        if ($request->filled('skin_id')) {
            $query->where('skin_id', $request->skin_id);
        }
        
        if ($request->filled('is_system')) {
            $query->where('is_system', $request->is_system);
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 10;
        
        return $query->orderBy('is_system', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    /**
     * 활성화된 스킨 목록을 가져옵니다.
     */
    public function getActiveSkins()
    {
        return BoardSkin::where('is_active', true)->get();
    }

    /**
     * 활성화된 템플릿 목록을 가져옵니다.
     */
    public function getActiveTemplates()
    {
        return BoardTemplate::with('skin')
            ->where('is_active', true)
            ->orderBy('is_system', 'desc')
            ->orderBy('name')
            ->get();
    }

    /**
     * 템플릿을 생성합니다.
     */
    public function createTemplate(array $data): BoardTemplate
    {
        // 필드 설정 처리
        $data['field_config'] = $this->processFieldConfig($data);
        
        // 커스텀 필드 설정 처리
        $data['custom_fields_config'] = $this->processCustomFieldsConfig($data);
        
        // 체크박스 기본값 처리
        $data['enable_notice'] = $data['enable_notice'] ?? false;
        $data['enable_sorting'] = $data['enable_sorting'] ?? false;
        $data['enable_category'] = $data['enable_category'] ?? false;
        $data['is_system'] = $data['is_system'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        
        return BoardTemplate::create($data);
    }

    /**
     * 템플릿을 업데이트합니다.
     */
    public function updateTemplate(BoardTemplate $template, array $data): bool
    {
        // 시스템 템플릿은 일부 필드만 수정 가능
        if ($template->is_system) {
            // 시스템 템플릿은 활성화 여부만 변경 가능
            return $template->update([
                'is_active' => $data['is_active'] ?? $template->is_active,
            ]);
        }
        
        // 필드 설정 처리
        $data['field_config'] = $this->processFieldConfig($data);
        
        // 커스텀 필드 설정 처리
        $data['custom_fields_config'] = $this->processCustomFieldsConfig($data);
        
        // 체크박스 기본값 처리
        $data['enable_notice'] = $data['enable_notice'] ?? false;
        $data['enable_sorting'] = $data['enable_sorting'] ?? false;
        $data['enable_category'] = $data['enable_category'] ?? false;
        $data['is_active'] = $data['is_active'] ?? true;
        
        return $template->update($data);
    }

    /**
     * 템플릿을 삭제합니다.
     */
    public function deleteTemplate(BoardTemplate $template): bool
    {
        // 시스템 템플릿은 삭제 불가
        if ($template->is_system) {
            return false;
        }
        
        // 사용 중인 템플릿인지 확인
        if ($template->boards()->count() > 0) {
            return false;
        }
        
        return $template->delete();
    }

    /**
     * 템플릿을 복제합니다.
     */
    public function duplicateTemplate(BoardTemplate $template): BoardTemplate
    {
        $newTemplate = $template->replicate();
        $newTemplate->name = $template->name . ' (복사본)';
        $newTemplate->is_system = false;
        $newTemplate->save();
        
        return $newTemplate;
    }

    /**
     * 필드 설정을 처리합니다.
     */
    private function processFieldConfig(array $data): array
    {
        $fieldConfig = [];
        
        // 기본 필드들
        $fields = ['title', 'content', 'category', 'author_name', 'password', 'attachments', 'thumbnail', 'is_secret', 'created_at'];
        
        foreach ($fields as $field) {
            $enabled = isset($data['field_' . $field . '_enabled']) && $data['field_' . $field . '_enabled'];
            $required = isset($data['field_' . $field . '_required']) && $data['field_' . $field . '_required'];
            $label = $data['field_' . $field . '_label'] ?? $this->getDefaultFieldLabel($field);
            
            $fieldConfig[$field] = [
                'enabled' => $enabled,
                'required' => $required,
                'label' => $label,
            ];
        }
        
        return $fieldConfig;
    }

    /**
     * 커스텀 필드 설정을 처리합니다.
     */
    private function processCustomFieldsConfig(array $data): ?array
    {
        if (!isset($data['custom_fields']) || !is_array($data['custom_fields'])) {
            return null;
        }
        
        $customFieldsConfig = [];
        
        foreach ($data['custom_fields'] as $field) {
            if (empty($field['name']) || empty($field['label']) || empty($field['type'])) {
                continue;
            }
            
            $customFieldsConfig[] = [
                'name' => $field['name'],
                'label' => $field['label'],
                'type' => $field['type'],
                'max_length' => $field['max_length'] ?? null,
                'required' => (bool) ($field['required'] ?? false),
                'options' => $field['options'] ?? null,
                'placeholder' => $field['placeholder'] ?? null,
            ];
        }
        
        return !empty($customFieldsConfig) ? $customFieldsConfig : null;
    }

    /**
     * 기본 필드 라벨을 반환합니다.
     */
    private function getDefaultFieldLabel(string $field): string
    {
        $labels = [
            'title' => '제목',
            'content' => '내용',
            'category' => '카테고리',
            'author_name' => '작성자',
            'password' => '비밀번호',
            'attachments' => '첨부파일',
            'thumbnail' => '썸네일',
            'is_secret' => '비밀글',
            'created_at' => '등록일',
        ];
        
        return $labels[$field] ?? $field;
    }

    /**
     * 필드 설정 유효성 검사
     */
    public function validateFieldConfig(array $fieldConfig): bool
    {
        // 제목과 내용은 반드시 활성화되어야 함
        if (!isset($fieldConfig['title']['enabled']) || !$fieldConfig['title']['enabled']) {
            return false;
        }
        
        if (!isset($fieldConfig['content']['enabled']) || !$fieldConfig['content']['enabled']) {
            return false;
        }
        
        return true;
    }
}

