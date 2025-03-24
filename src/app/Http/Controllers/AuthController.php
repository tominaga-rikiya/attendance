<?php

namespace App\Http\Controllers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use Illuminate\Http\Request;
use App\Actions\Fortify\CreateNewUser;
use App\Http\Requests\EmailVerificationRequest;


class AuthController extends Controller
{
    /**
     * 新規ユーザー登録
     */
    public function store(Request $request, CreateNewUser $creator)
    {
        // CreateNewUser クラスを使用してユーザーを作成（バリデーション含む）
        $user = $creator->create($request->all());

        // 登録イベントを発火してメール送信をトリガー
        event(new Registered($user));

        // 未認証ユーザーをセッションに保存
        session()->put('unauthenticated_user', $user);

        return redirect()->route('verification.notice');
    }

    /**
     * メール認証通知画面を表示
     */
    public function showVerifyNotice()
    {
        return view('auth.verify-email');
    }

    /**
     * 認証メールを再送信
     */
    public function resendVerificationEmail(Request $request)
    {
        $user = session()->get('unauthenticated_user');

        if (!$user) {
            return redirect()->route('login')
                ->with('error', '認証情報が見つかりません。再度ログインしてください。');
        }

        $user->sendEmailVerificationNotification();
        session()->put('resent', true);

        return back()->with('message', '認証メールを再送信しました！');
    }

    /**
     * メール認証を完了し、ログインさせる
     */
    public function verifyEmail(Request $request, $id, $hash)
    {
        // ユーザーを取得
        $user = User::findOrFail($id);

        // ハッシュが一致するか確認
        if (!hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            abort(403, 'Invalid verification link');
        }

        // すでに認証済みでなければ認証済みにする
        if (!$user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        // セッションのクリーンアップ
        session()->forget('unauthenticated_user');

        // ユーザーをログインさせる
        auth()->login($user);

        // 認証完了後、商品一覧ページへリダイレクト
        return redirect()->route('attendance.create');
    }

    /**
     * 認証メールを確認するためのリダイレクト
     */
    public function verifyRedirect()
    {
        return redirect()->away('http://localhost:8025')
           ->with('info', 'メールボックスを確認してください。');
    }

    /**
     * ログイン後に認証状態を確認しリダイレクト
     */
    public function checkEmailVerification(Request $request)
    {
        if (!$request->user()) {
            return redirect()->route('login');
        }

        if (!$request->user()->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        return redirect()->intended(route('attendance.create'));
    }

    public function logout(Request $request)
    {
        auth()->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }
}

