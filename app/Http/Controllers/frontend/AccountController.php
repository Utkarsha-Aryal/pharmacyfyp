<?php

namespace App\Http\Controllers\frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function login(){
        return view('frontend.account.login');
    }
    public function register(){
        return view('frontend.account.register');
    }
}
