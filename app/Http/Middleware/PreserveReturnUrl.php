<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class PreserveReturnUrl
{
    /**
     * 수정 후 목록 필터 복귀 URL 유지
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $method = strtoupper((string) $request->input('_method', $request->method()));
        if (!in_array($method, ['PUT', 'PATCH'], true)) {
            return $response;
        }

        $returnUrl = (string) $request->input('return_url', '');
        if ($returnUrl === '' || !$response instanceof RedirectResponse) {
            return $response;
        }

        if (!$this->isAllowedReturnUrl($request, $returnUrl)) {
            return $response;
        }

        // 유효성 실패 시에는 기존 edit 페이지 리다이렉트를 유지
        $targetPath = (string) parse_url($response->getTargetUrl(), PHP_URL_PATH);
        if (Str::contains($targetPath, '/edit')) {
            return $response;
        }

        $response->setTargetUrl($returnUrl);

        return $response;
    }

    /**
     * 백오피스 내부 URL만 허용
     */
    private function isAllowedReturnUrl(Request $request, string $returnUrl): bool
    {
        $currentOrigin = $request->getSchemeAndHttpHost();
        $allowedPrefix = rtrim($currentOrigin, '/') . '/backoffice/';

        return Str::startsWith($returnUrl, $allowedPrefix);
    }
}
