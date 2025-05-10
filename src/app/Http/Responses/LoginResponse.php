<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    // LoginResponse.php
    public function toResponse($request)
    {
        if (auth()->user()->isAdmin()) {
            // AdminAttendanceController@index を呼び出すように変更
            return redirect()->route('admin.attendances.index');
        }

        return redirect('/attendance');
    }
}

