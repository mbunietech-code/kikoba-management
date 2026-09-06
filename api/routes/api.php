<?php

use App\Http\Controllers\Api\AccountingController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\GuarantorController;
use App\Http\Controllers\Api\InsuranceController;
use App\Http\Controllers\Api\LoanController;
use App\Http\Controllers\Api\LoanProductController;
use App\Http\Controllers\Api\MeController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProfitDistributionController;
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SavingsController;
use App\Http\Controllers\Api\ShareController;
use App\Http\Controllers\Api\TransactionController;
use Illuminate\Support\Facades\Route;

Route::get('/ping', fn () => ['success' => true, 'message' => 'pong', 'data' => ['time' => now()->toIso8601String()]]);

/* -------------------------------- auth -------------------------------- */
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('otp/request', [AuthController::class, 'requestOtp']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
    });
});

/* --------------------------- member self-service --------------------------- */
Route::middleware(['auth:sanctum', 'role:member'])->prefix('me')->group(function () {
    Route::get('position', [MeController::class, 'position']);
    Route::get('shares', [MeController::class, 'shares']);
    Route::get('savings', [MeController::class, 'savings']);
    Route::get('loans', [MeController::class, 'loans']);
    Route::get('loans/{id}', [MeController::class, 'loan']);
    Route::get('repayments', [MeController::class, 'repayments']);
    Route::get('projects', [MeController::class, 'projects']);
    Route::get('insurance', [MeController::class, 'insurance']);
    Route::get('transactions', [MeController::class, 'transactions']);
    Route::patch('profile', [MeController::class, 'updateProfile']);
    Route::get('notifications', [MiscController::class, 'notifications']);
});

/* ------------------------------- staff area ------------------------------- */
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index']);

    Route::get('members', [MemberController::class, 'index'])->middleware('permission:members.view');
    Route::post('members', [MemberController::class, 'store'])->middleware('permission:members.create');
    Route::get('members/{id}', [MemberController::class, 'show'])->middleware('permission:members.view');
    Route::match(['put', 'patch'], 'members/{id}', [MemberController::class, 'update'])->middleware('permission:members.update');
    Route::delete('members/{id}', [MemberController::class, 'destroy'])->middleware('permission:members.delete');

    Route::get('shares', [ShareController::class, 'index'])->middleware('permission:shares.view');
    Route::get('shares/summary', [ShareController::class, 'summary'])->middleware('permission:shares.view');
    Route::post('shares', [ShareController::class, 'store'])->middleware('permission:shares.create');

    Route::get('savings', [SavingsController::class, 'index'])->middleware('permission:savings.view');
    Route::get('savings/summary', [SavingsController::class, 'summary'])->middleware('permission:savings.view');
    Route::get('savings/{id}', [SavingsController::class, 'show'])->middleware('permission:savings.view');
    Route::post('savings/deposit', [SavingsController::class, 'deposit'])->middleware('permission:savings.deposit');
    Route::post('savings/withdraw', [SavingsController::class, 'withdraw'])->middleware('permission:savings.withdraw');

    Route::get('loan-products', [LoanProductController::class, 'index'])->middleware('permission:products.view');
    Route::post('loan-products', [LoanProductController::class, 'store'])->middleware('permission:products.manage');
    Route::match(['put', 'patch'], 'loan-products/{id}', [LoanProductController::class, 'update'])->middleware('permission:products.manage');

    Route::get('loans', [LoanController::class, 'index'])->middleware('permission:loans.view');
    Route::get('loans/summary', [LoanController::class, 'summary'])->middleware('permission:loans.view');
    Route::post('loans/quote', [LoanController::class, 'quote'])->middleware('permission:loans.view');
    Route::post('loans/eligibility', [LoanController::class, 'eligibility'])->middleware('permission:loans.view');
    Route::post('loans/apply', [LoanController::class, 'apply'])->middleware('permission:loans.apply');
    Route::get('loans/{id}', [LoanController::class, 'show'])->middleware('permission:loans.view');
    Route::post('loans/{id}/approve', [LoanController::class, 'approve'])->middleware('permission:loans.approve');
    Route::post('loans/{id}/reject', [LoanController::class, 'reject'])->middleware('permission:loans.reject');
    Route::post('loans/{id}/disburse', [LoanController::class, 'disburse'])->middleware('permission:loans.disburse');
    Route::post('loans/{id}/repay', [LoanController::class, 'repay'])->middleware('permission:loans.repay');

    Route::get('guarantors', [GuarantorController::class, 'index'])->middleware('permission:guarantors.view');
    Route::post('guarantors/{id}/verify', [GuarantorController::class, 'verify'])->middleware('permission:guarantors.verify');

    Route::get('projects', [ProjectController::class, 'index'])->middleware('permission:projects.view');
    Route::post('projects', [ProjectController::class, 'store'])->middleware('permission:projects.manage');
    Route::get('projects/{id}', [ProjectController::class, 'show'])->middleware('permission:projects.view');
    Route::post('projects/{id}/invest', [ProjectController::class, 'invest'])->middleware('permission:projects.invest');

    Route::get('insurance/accounts', [InsuranceController::class, 'accounts'])->middleware('permission:insurance.view');
    Route::get('insurance/claims', [InsuranceController::class, 'claims'])->middleware('permission:insurance.view');
    Route::get('insurance/summary', [InsuranceController::class, 'summary'])->middleware('permission:insurance.view');
    Route::post('insurance/contributions', [InsuranceController::class, 'contribute'])->middleware('permission:insurance.manage');
    Route::post('insurance/claims', [InsuranceController::class, 'fileClaim'])->middleware('permission:insurance.claims');
    Route::post('insurance/claims/{id}/decide', [InsuranceController::class, 'decideClaim'])->middleware('permission:insurance.manage');

    Route::get('payments', [PaymentController::class, 'index'])->middleware('permission:payments.view');
    Route::post('payments/{id}/verify', [PaymentController::class, 'verify'])->middleware('permission:payments.verify');

    Route::get('transactions', [TransactionController::class, 'index'])->middleware('permission:reports.view');

    Route::get('accounting/accounts', [AccountingController::class, 'accounts'])->middleware('permission:accounting.view');
    Route::get('accounting/journal', [AccountingController::class, 'journal'])->middleware('permission:accounting.view');
    Route::get('accounting/journal/{id}', [AccountingController::class, 'showJournal'])->middleware('permission:accounting.view');
    Route::get('accounting/trial-balance', [AccountingController::class, 'trialBalance'])->middleware('permission:accounting.view');

    Route::get('profit-distributions', [ProfitDistributionController::class, 'index'])->middleware('permission:profit.view');
    Route::get('profit-distributions/{id}', [ProfitDistributionController::class, 'show'])->middleware('permission:profit.view');

    Route::get('notifications', [MiscController::class, 'notifications']);
    Route::post('notifications/read-all', [MiscController::class, 'markAllRead']);
    Route::post('notifications/announce', [MiscController::class, 'sendAnnouncement'])->middleware('permission:notifications.send');

    Route::get('settings', [MiscController::class, 'settings'])->middleware('permission:settings.view');
    Route::put('settings', [MiscController::class, 'updateSettings'])->middleware('permission:settings.manage');

    Route::get('users', [MiscController::class, 'users'])->middleware('permission:users.view');
    Route::post('users', [MiscController::class, 'storeUser'])->middleware('permission:users.manage');
    Route::get('roles', [MiscController::class, 'roles'])->middleware('permission:roles.manage');
    Route::put('roles/{name}', [MiscController::class, 'updateRole'])->middleware('permission:roles.manage');

    Route::get('audit-logs', [MiscController::class, 'auditLogs'])->middleware('permission:audit.view');
});
