<?php

namespace App\Http\Controllers\Backoffice;

use App\Http\Controllers\Controller;
use App\Http\Requests\BackofficeMemberRequest;
use App\Models\User;
use App\Services\Backoffice\MemberService;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function __construct(private readonly MemberService $memberService)
    {
    }

    public function index(Request $request)
    {
        $filters = [
            'join_type' => $request->get('join_type', '전체'),
            'join_date_start' => $request->get('join_date_start'),
            'join_date_end' => $request->get('join_date_end'),
            'marketing_consent' => $request->get('marketing_consent', []),
            'search_type' => $request->get('search_type', '전체'),
            'search_term' => $request->get('search_term', ''),
        ];

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
        $members = $this->memberService->getMembers($filters, $perPage);

        return view('backoffice.members.index', compact('members', 'filters', 'perPage'));
    }

    public function create()
    {
        return view('backoffice.members.create');
    }

    public function store(BackofficeMemberRequest $request)
    {
        $this->memberService->createMember($request->validated());

        return redirect()->route('backoffice.members.index')
            ->with('success', '회원이 등록되었습니다.');
    }

    public function show(User $user)
    {
        return redirect()->route('backoffice.members.edit', $user);
    }

    public function edit(User $user)
    {
        $member = $this->memberService->getMember($user->id);
        return view('backoffice.members.edit', compact('member'));
    }

    public function update(BackofficeMemberRequest $request, User $user)
    {
        $this->memberService->updateMember($user->id, $request->validated());

        return redirect()->route('backoffice.members.index')
            ->with('success', '회원 정보가 수정되었습니다.');
    }

    public function destroy(User $user)
    {
        $this->memberService->deleteMember($user->id);

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('backoffice.members.index')
            ->with('success', '회원이 삭제되었습니다.');
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => '선택된 회원이 없습니다.'], 400);
        }

        $this->memberService->deleteMembers($ids);
        return response()->json(['success' => true, 'message' => '선택된 회원이 삭제되었습니다.']);
    }

    public function checkDuplicateEmail(Request $request)
    {
        $email = (string) $request->input('email');
        $excludeId = $request->input('exclude_id');
        if ($email === '') {
            return response()->json(['available' => false, 'message' => '이메일을 입력해주세요.'], 400);
        }

        $exists = $this->memberService->checkDuplicateEmail($email, $excludeId ? (int) $excludeId : null);
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? '이미 사용 중인 이메일입니다.' : '사용 가능한 이메일입니다.',
        ]);
    }

    public function checkDuplicatePhone(Request $request)
    {
        $phone = (string) $request->input('phone');
        $excludeId = $request->input('exclude_id');
        if ($phone === '') {
            return response()->json(['available' => false, 'message' => '휴대폰번호를 입력해주세요.'], 400);
        }

        $exists = $this->memberService->checkDuplicatePhone($phone, $excludeId ? (int) $excludeId : null);
        return response()->json([
            'available' => !$exists,
            'message' => $exists ? '이미 사용 중인 휴대폰번호입니다.' : '사용 가능한 휴대폰번호입니다.',
        ]);
    }

    public function searchSchool(Request $request)
    {
        $keyword = trim((string) $request->query('keyword', ''));

        if ($keyword === '') {
            return response()->json([
                'schools' => [],
                'message' => '검색어를 입력해주세요.',
            ]);
        }

        $schools = User::query()
            ->select('school_name')
            ->whereNotNull('school_name')
            ->where('school_name', '<>', '')
            ->where('school_name', 'like', '%' . $keyword . '%')
            ->groupBy('school_name')
            ->orderBy('school_name')
            ->limit(20)
            ->pluck('school_name');

        return response()->json([
            'schools' => $schools,
            'message' => $schools->isEmpty() ? '검색 결과가 없습니다.' : null,
        ]);
    }

    public function export(Request $request)
    {
        $filters = [
            'join_type' => $request->get('join_type', '전체'),
            'join_date_start' => $request->get('join_date_start'),
            'join_date_end' => $request->get('join_date_end'),
            'marketing_consent' => $request->get('marketing_consent', []),
            'search_type' => $request->get('search_type', '전체'),
            'search_term' => $request->get('search_term', ''),
        ];

        $members = $this->memberService->exportMembersToCsv($filters);
        $filename = 'members_' . date('YmdHis') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = static function () use ($members) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, ['No', '가입구분', 'ID', '학교명', '이름', '휴대폰번호', '이메일주소', '학교 대표자', '가입일시']);

            foreach ($members as $index => $member) {
                $joinTypeLabel = $member->join_type === 'email' ? '이메일' : ($member->join_type === 'kakao' ? '카카오' : '네이버');
                fputcsv($file, [
                    $index + 1,
                    $joinTypeLabel,
                    $member->login_id ?? '',
                    $member->school_name ?? '',
                    $member->name,
                    $member->phone_number ?? '',
                    $member->email ?? '',
                    $member->is_school_representative ? 'Y' : 'N',
                    optional($member->created_at)->format('Y.m.d H:i'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function withdrawn(Request $request)
    {
        $filters = [
            'join_type' => $request->get('join_type', '전체'),
            'withdrawal_date_start' => $request->get('withdrawal_date_start'),
            'withdrawal_date_end' => $request->get('withdrawal_date_end'),
            'search_type' => $request->get('search_type', '전체'),
            'search_term' => $request->get('search_term', ''),
        ];

        $perPage = (int) $request->get('per_page', 20);
        $perPage = in_array($perPage, [20, 50, 100], true) ? $perPage : 20;
        $members = $this->memberService->getWithdrawnMembers($filters, $perPage);
        $joinTypes = ['전체' => '전체', 'email' => '이메일', 'kakao' => '카카오', 'naver' => '네이버'];

        return view('backoffice.members.withdrawn', compact('members', 'filters', 'joinTypes', 'perPage'));
    }

    public function restore(Request $request, int $id)
    {
        $this->memberService->restoreMember($id);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => '회원이 복원되었습니다.',
                'redirect' => route('backoffice.members.index'),
            ]);
        }

        return redirect()->route('backoffice.members.index')->with('success', '회원이 복원되었습니다.');
    }

    public function forceDelete(int $id)
    {
        $this->memberService->forceDeleteMember($id);
        return redirect()->route('backoffice.withdrawn')->with('success', '회원이 영구 삭제되었습니다.');
    }

    public function forceDeleteMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
        if (empty($ids)) {
            return response()->json(['success' => false, 'message' => '선택된 회원이 없습니다.'], 400);
        }

        $this->memberService->forceDeleteMembers($ids);
        return response()->json(['success' => true, 'message' => '선택된 회원이 영구 삭제되었습니다.']);
    }
}
