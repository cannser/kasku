<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\LogController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

// Pintu masuk: arahkan sesuai status login dan peran
Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return redirect()->route(auth()->user()->isAdmin() ? 'admin.dashboard' : 'dashboard');
})->name('home');

// ---- Tamu (belum login) ----
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    // throttle:register = rate limit registrasi per IP (didefinisikan di AppServiceProvider)
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:register')->name('register.store');

    // Lupa password: permintaan dicatat untuk admin + (jika email aktif) tautan reset lewat email
    Route::get('/forgot-password', [ForgotPasswordController::class, 'create'])->name('password.forgot');
    // throttle:forgot = rate limit per IP (didefinisikan di AppServiceProvider)
    Route::post('/forgot-password', [ForgotPasswordController::class, 'store'])
        ->middleware('throttle:forgot')->name('password.email');
    // Token acak 64 karakter alfanumerik; pola lain langsung 404
    Route::get('/reset-password/{token}', [ResetPasswordController::class, 'create'])
        ->where('token', '[A-Za-z0-9]{64}')->name('password.reset');
    Route::post('/reset-password', [ResetPasswordController::class, 'store'])
        ->middleware('throttle:10,1')->name('password.store');
});

// ---- Sudah login: 'auth' (wajib login) + 'active' (akun harus aktif) ----
Route::middleware(['auth', 'active'])->group(function () {

    // Logout hanya lewat POST (+ token CSRF otomatis dari middleware web)
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])
        ->middleware('throttle:5,1')->name('password.update');

    // ---- Khusus pengguna biasa ----
    Route::middleware('role:user')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])
            ->whereNumber('category')->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])
            ->whereNumber('category')->name('categories.destroy');

        Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])
            ->whereNumber('transaction')->name('transactions.edit');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])
            ->whereNumber('transaction')->name('transactions.update');
        // Hapus WAJIB lewat DELETE (form POST + _method), tidak pernah lewat GET
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])
            ->whereNumber('transaction')->name('transactions.destroy');

        Route::get('/report', [ReportController::class, 'show'])->name('report.show');
        Route::get('/report/export', [ReportController::class, 'export'])->name('report.export');
    });

    // ---- Khusus admin ----
    Route::prefix('admin')->name('admin.')->middleware('role:admin')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/users', [UserController::class, 'index'])->name('users');
        Route::post('/users/{user}/toggle', [UserController::class, 'toggle'])
            ->whereNumber('user')->name('users.toggle');
        Route::post('/users/{user}/reset', [UserController::class, 'reset'])
            ->whereNumber('user')->name('users.reset');
        Route::get('/logs', [LogController::class, 'index'])->name('logs');
    });
});
