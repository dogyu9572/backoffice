<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Backoffice\PopupController;

// =============================================================================
// 기본 라우트 파일
// =============================================================================

// 메인 페이지
Route::get('/', [HomeController::class, 'index'])->name('home');

// 팝업 표시 (일반 팝업용)
Route::get('/popup/{popup}', [PopupController::class, 'showPopup'])->name('popup.show');

// 인증 관련 라우트
Route::prefix('auth')->name('auth.')->group(function () {
    // 로그인
    Route::get('/login', [LoginController::class, 'showLoginForm'])
        ->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::post('/logout', [LoginController::class, 'logout'])
        ->name('logout');

    // 회원가입
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
        ->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    // 비밀번호 재설정
    Route::prefix('password')->name('password.')->group(function () {
        Route::get('/reset', [ForgotPasswordController::class, 'showLinkRequestForm'])
            ->name('request');
        Route::post('/email', [ForgotPasswordController::class, 'sendResetLinkEmail'])
            ->name('email');
        Route::get('/reset/{token}', [ResetPasswordController::class, 'showResetForm'])
            ->name('reset');
        Route::post('/reset', [ResetPasswordController::class, 'reset'])
            ->name('update');
    });
});

// =============================================================================
// 분리된 라우트 파일들 포함
// =============================================================================

// 백오피스 라우트 (관리자 전용)
require __DIR__.'/backoffice.php';