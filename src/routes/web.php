<?php

use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\StaffController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 認証関連（ログイン・登録・ログアウト）
Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// 管理者ログインページへのリダイレクト
Route::get('/admin/login', function () {
    return redirect('/login?admin=1');
})->name('admin.login');
Route::post('/admin/login', [AuthenticatedSessionController::class, 'store']);

// 新規登録関連
Route::get('/register', function () {
    return view('auth.register');
});
Route::post('/register', [AuthController::class, 'store'])->name('register');

// メール認証関連
Route::controller(AuthController::class)->group(function () {
    Route::get('/email/verify', 'showVerifyNotice')
        ->name('verification.notice');
    Route::post('/email/verification-notification', 'resendVerificationEmail')
        ->name('verification.send');
    Route::get('/email/verify/{id}/{hash}', 'verifyEmail')
        ->middleware(['throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verify-redirect', 'verifyRedirect')
        ->name('verification.verify-redirect');
    Route::get('/check-email-verification', 'checkEmailVerification')
        ->middleware(['auth'])
        ->name('verification.check');
});

// 認証済み・メール確認済みユーザー用ルート（一般ユーザー）
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠記録
    Route::get('/attendance', [AttendanceController::class, 'userAttendanceForm'])->name('attendance.create');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');

    // 勤怠一覧・詳細
    Route::get('/attendances', [AttendanceController::class, 'userIndex'])->name('attendance.index');
    Route::get('/attendances/{id}', [AttendanceController::class, 'userShow'])->name('attendance.show');
    Route::post('/attendances/{id}', [AttendanceController::class, 'userUpdate'])->name('attendance.update');

    // 修正申請
    Route::get('/correction-requests', [AttendanceController::class, 'userCorrectionIndex'])->name('attendance.correction_index');
});

// 管理者専用ルート
Route::prefix('admin')->middleware(['auth', 'verified', 'admin'])->group(function () {
    // 修正申請関連のルート
    Route::get('/correction-requests', [AdminAttendanceController::class, 'adminCorrectionIndex'])
        ->name('admin.correction-requests.index');

    // 修正申請承認画面
    Route::get('/correction-requests/{id}', [AdminAttendanceController::class, 'adminCorrectionShow'])
        ->name('admin.correction-requests.show');

    // 修正申請承認の処理
    Route::post('/correction-requests/{id}/approve', [AdminAttendanceController::class, 'adminCorrectionApprove'])
        ->name('admin.correction-requests.approve.post');

    // 勤怠管理
    Route::get('/attendances', [AdminAttendanceController::class, 'adminIndex'])
        ->name('admin.attendances.index');
    Route::get('/attendances/{id}', [AdminAttendanceController::class, 'adminShow'])
        ->name('admin.attendances.show');
    Route::post('/attendances/{id}', [AdminAttendanceController::class, 'adminUpdate'])
        ->name('admin.attendances.update');

    // スタッフ管理
    Route::get('/staff', [StaffController::class, 'index'])
        ->name('staff.index');
    Route::get('/staff/{user}/monthly', [StaffController::class, 'monthlyAttendance'])
        ->name('staff.attendance');
    Route::get('/staff/{user}/monthly/export', [StaffController::class, 'exportAttendanceCsv'])
        ->name('staff.attendance.export');
});
