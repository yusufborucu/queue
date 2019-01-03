<?php

namespace App\Http\Controllers;

use App\Jobs\SendMernis;

class UserController extends Controller
{
    public function register()
    {
        $user['name'] = request()->name;
        $user['surname'] = request()->surname;
        $user['birth_year'] = request()->birth_year;
        $user['nationality_id'] = request()->nationality_id;
        $user['email'] = request()->email;
        $this->dispatch(new SendMernis($user));
        return response()->json([
            'message' => 'Bilgileriniz Mernis kontrolünden geçirilerek tarafınıza e-posta gönderilecektir.'
        ], 200);
    }
}
