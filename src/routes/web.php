<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Models\Attendance;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

use const Dom\INDEX_SIZE_ERR;

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


// 認証関連のルート
Route::controller(AuthController::class)->group(function ()
 {
    // メール認証関連
    Route::get('/email/verify', 'showVerifyNotice')->name('verification.notice');
    Route::post('/email/verification-notification', 'resendVerificationEmail')->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', 'verifyEmail')->middleware(['throttle:6,1'])->name('verification.verify');
    Route::post('/email/verify-redirect', 'verifyRedirect')->name('verification.verify-redirect');

    // 新規ユーザー登録処理
    Route::post('/register', 'store')->name('register');

    // 認証確認
    Route::get('/check-email-verification', 'checkEmailVerification')
    ->middleware(['auth'])
    ->name('verification.check');
});

// 認証済みユーザー用ルート
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠入力画面
    Route::get('/attendance', function () {
        return view('attendance.create');
    })->name('attendance.create');
});

// ログイン画面表示用のGETルート
Route::get('/login', [AuthenticatedSessionController::class, 'create'])
->name('login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendance.create');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
});