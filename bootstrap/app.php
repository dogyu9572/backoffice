<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use App\Http\Middleware\BackOfficeAuth;
use App\Http\Middleware\PreserveReturnUrl;
use App\Http\Middleware\TrackVisitor;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // 백오피스 경로에 대해 BackOfficeAuth 미들웨어 등록
        $middleware->group('backoffice', [
            BackOfficeAuth::class,
            PreserveReturnUrl::class,
        ]);
        
        // 방문자 추적 미들웨어를 전역에 등록
        $middleware->append(TrackVisitor::class);
        
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (TokenMismatchException $exception, Request $request) {
            // 백오피스 로그인 제출 시 419 에러 페이지 대신 로그인 화면으로 복귀
            if ($request->isMethod('post') && $request->is('backoffice/login')) {
                return redirect('/backoffice/login')
                    ->withInput($request->except('password'))
                    ->withErrors([
                        'login_id' => '세션이 만료되었습니다. 다시 로그인해 주세요.',
                    ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '요청이 만료되었습니다. 다시 시도해 주세요.',
                ], 419);
            }

            return redirect()->back()->with('error', '요청이 만료되었습니다. 다시 시도해 주세요.');
        });
    })->create();
