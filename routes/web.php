<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\SmsController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'home')->name('home');

Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [AuthController::class, 'register'])->name('register.submit');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::view('/otp', 'otp.select')->middleware('auth')->name('otp.select');
Route::view('/otp/phone', 'otp.phone')->name('otp.phone');
Route::view('/otp/email', 'otp.email')->name('otp.email');
Route::view('/otp/verify', 'otp.verify')->name('otp.verify');
Route::post('/otp/phone', [SmsController::class, 'send'])->name('otp.phone.send');
Route::post('/otp/email', [EmailController::class, 'send'])->name('otp.email.send');
Route::post('/otp/verify', [OtpController::class, 'verify'])->name('otp.verify.submit');

Route::view('/mailbox', 'mailbox')->middleware(['auth', 'otp.verified'])->name('mailbox');
Route::view('/ai-chatbot', 'ai-chatbot')->name('ai-chatbot');

Route::redirect('/index.html', '/');
Route::redirect('/otp-phone.html', '/otp/phone');
Route::redirect('/otp-email.html', '/otp/email');
Route::redirect('/validate-otp.html', '/otp/verify');
Route::redirect('/mailbox.html', '/mailbox');
Route::redirect('/ai-chatbot.html', '/ai-chatbot');
