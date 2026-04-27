<?php

namespace App\Support;

use Illuminate\Http\Request;

class ClientIpResolver
{
    /**
     * 프록시/로드밸런서 환경에서 실제 클라이언트 IP를 최대한 정확히 반환
     */
    public static function resolve(Request $request): string
    {
        $headerCandidates = [
            $request->header('x-forwarded-for'),
            $request->header('x-real-ip'),
            $request->header('cf-connecting-ip'),
            $request->header('x-client-ip'),
            $request->header('forwarded'),
        ];

        $ips = [];

        foreach ($headerCandidates as $value) {
            if (empty($value)) {
                continue;
            }

            foreach (explode(',', (string) $value) as $rawIp) {
                $ip = trim($rawIp);
                $ip = preg_replace('/^for=/i', '', $ip ?? '');
                $ip = trim((string) $ip, "\"'[]");

                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    $ips[] = $ip;
                }
            }
        }

        // 공인 IP가 있으면 우선 사용
        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }

        // 헤더에 유효 IP가 있지만 공인 IP가 없는 경우 첫 번째 사용
        if (!empty($ips)) {
            return $ips[0];
        }

        // Laravel 기본 해석 결과 사용
        $requestIp = $request->ip();
        if (!empty($requestIp) && filter_var($requestIp, FILTER_VALIDATE_IP)) {
            return $requestIp;
        }

        // 마지막 폴백
        return $request->server('REMOTE_ADDR', '0.0.0.0');
    }
}

