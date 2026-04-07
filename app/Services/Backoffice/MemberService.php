<?php

namespace App\Services\Backoffice;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class MemberService
{
    public function getMembers(array $filters = [], int $perPage = 20)
    {
        $query = User::query()
            ->where('role', 'user')
            ->whereNull('withdrawn_at');

        if (isset($filters['join_type']) && $filters['join_type']) {
            $query->byJoinType($filters['join_type']);
        }

        if (isset($filters['join_date_start']) || isset($filters['join_date_end'])) {
            $query->byJoinDateRange($filters['join_date_start'] ?? null, $filters['join_date_end'] ?? null);
        }

        if (isset($filters['marketing_consent']) && is_array($filters['marketing_consent']) && count($filters['marketing_consent']) > 0) {
            $query->where(function ($q) use ($filters) {
                if (in_array('email', $filters['marketing_consent'], true)) {
                    $q->orWhere('email_marketing_consent', true);
                }
                if (in_array('kakao', $filters['marketing_consent'], true)) {
                    $q->orWhere('kakao_marketing_consent', true);
                }
            });
        }

        if (isset($filters['search_type']) && isset($filters['search_term'])) {
            $query->search($filters['search_type'], $filters['search_term']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function getMember(int $id): User
    {
        return User::where('role', 'user')->findOrFail($id);
    }

    public function createMember(array $data): User
    {
        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        }

        if (($data['join_type'] ?? null) === 'email' && isset($data['email'])) {
            $data['login_id'] = $data['email'];
        }

        if (!isset($data['terms_agreed_at'])) {
            $data['terms_agreed_at'] = now();
        }

        if (isset($data['phone_number']) && !str_starts_with((string) $data['phone_number'], 'sns_')) {
            $data['phone_number'] = User::normalizePhone($data['phone_number']);
        }

        $data['role'] = 'user';
        $data['is_active'] = true;
        $data['email_marketing_consent_at'] = !empty($data['email_marketing_consent']) ? now() : null;
        $data['kakao_marketing_consent_at'] = !empty($data['kakao_marketing_consent']) ? now() : null;

        return User::create($data);
    }

    public function updateMember(int $id, array $data): User
    {
        $member = $this->getMember($id);

        if (isset($data['password']) && $data['password']) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['password_confirmation']);

        if (isset($data['phone_number']) && !str_starts_with((string) $data['phone_number'], 'sns_')) {
            $data['phone_number'] = User::normalizePhone($data['phone_number']);
        }

        if (array_key_exists('email_marketing_consent', $data)) {
            $data['email_marketing_consent_at'] = !empty($data['email_marketing_consent']) ? now() : null;
        }
        if (array_key_exists('kakao_marketing_consent', $data)) {
            $data['kakao_marketing_consent_at'] = !empty($data['kakao_marketing_consent']) ? now() : null;
        }

        $member->update($data);

        return $member;
    }

    public function deleteMember(int $id): User
    {
        $member = $this->getMember($id);
        $member->update(['withdrawn_at' => now()]);
        return $member;
    }

    public function deleteMembers(array $ids): int
    {
        return User::whereIn('id', $ids)
            ->where('role', 'user')
            ->whereNull('withdrawn_at')
            ->update(['withdrawn_at' => now()]);
    }

    public function getWithdrawnMembers(array $filters = [], int $perPage = 20)
    {
        $query = User::query()
            ->where('role', 'user')
            ->whereNotNull('withdrawn_at');

        if (isset($filters['join_type']) && $filters['join_type']) {
            $query->byJoinType($filters['join_type']);
        }

        if (isset($filters['withdrawal_date_start']) || isset($filters['withdrawal_date_end'])) {
            $query->byWithdrawalDateRange($filters['withdrawal_date_start'] ?? null, $filters['withdrawal_date_end'] ?? null);
        }

        if (isset($filters['search_type']) && isset($filters['search_term'])) {
            $query->search($filters['search_type'], $filters['search_term']);
        }

        return $query->orderBy('withdrawn_at', 'desc')->paginate($perPage)->withQueryString();
    }

    public function restoreMember(int $id): User
    {
        $member = $this->getMember($id);
        $member->update(['withdrawn_at' => null]);
        return $member;
    }

    public function forceDeleteMember(int $id): User
    {
        $member = User::where('role', 'user')->whereNotNull('withdrawn_at')->findOrFail($id);
        $member->delete();
        return $member;
    }

    public function forceDeleteMembers(array $ids): int
    {
        return User::whereIn('id', $ids)
            ->where('role', 'user')
            ->whereNotNull('withdrawn_at')
            ->delete();
    }

    public function exportMembersToCsv(array $filters = [])
    {
        $query = User::query()->where('role', 'user')->whereNull('withdrawn_at');

        if (isset($filters['join_type']) && $filters['join_type']) {
            $query->byJoinType($filters['join_type']);
        }
        if (isset($filters['join_date_start']) || isset($filters['join_date_end'])) {
            $query->byJoinDateRange($filters['join_date_start'] ?? null, $filters['join_date_end'] ?? null);
        }
        if (isset($filters['marketing_consent']) && is_array($filters['marketing_consent']) && count($filters['marketing_consent']) > 0) {
            $query->where(function ($q) use ($filters) {
                if (in_array('email', $filters['marketing_consent'], true)) {
                    $q->orWhere('email_marketing_consent', true);
                }
                if (in_array('kakao', $filters['marketing_consent'], true)) {
                    $q->orWhere('kakao_marketing_consent', true);
                }
            });
        }
        if (isset($filters['search_type']) && isset($filters['search_term'])) {
            $query->search($filters['search_type'], $filters['search_term']);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function checkDuplicateEmail(string $email, ?int $excludeId = null): bool
    {
        $query = User::where('email', $email)->where('role', 'user');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    public function checkDuplicatePhone(string $phone, ?int $excludeId = null): bool
    {
        $phone = User::normalizePhone($phone);
        $query = User::where('phone_number', $phone)->where('role', 'user');
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }
}
