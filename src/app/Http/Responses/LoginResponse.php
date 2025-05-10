<?php

namespace App\Http\Responses;

use Illuminate\Http\Request;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if (auth()->user()->isAdmin()) {
            
            return redirect()->route('admin.attendances.index');
        }

        return redirect('/attendance');
    }
}

