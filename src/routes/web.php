<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;



/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/register', function () {
    return view('auth.register');
});


Route::post('login', [AuthenticatedSessionController::class, 'store']);
Route::post('/register', [AuthController::class, 'store']);


// 認証関連のルート
Route::controller(AuthController::class)->group(function () {
    // メール認証関連
    Route::get('/email/verify', 'showVerifyNotice')->name('verification.notice');
    Route::post('/email/verification-notification', 'resendVerificationEmail')->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', 'verifyEmail')->middleware(['throttle:6,1'])->name('verification.verify');
    Route::post('/email/verify-redirect', 'verifyRedirect')->name('verification.verify-redirect');

    // 新規ユーザー登録処理
    Route::post('/register', 'store')->name('register');

    // 認証確認
    Route::get('/check-email-verification', 'checkEmailVerification')->name('verification.check');
});

// 認証済みユーザー用ルート
Route::middleware(['auth', 'verified'])->group(function () {
    // 商品一覧画面
    Route::get('/attendance', function () {
        return view('attendance.create');
    })->name('products.index');

    // その他の認証が必要なルート...
});

// ログイン画面表示用のGETルート
Route::get('/login', [AuthenticatedSessionController::class, 'create'])
    ->name('login');

