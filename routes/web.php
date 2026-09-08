<?php

use App\Http\Controllers\Web\Auth\LoginController;
use App\Http\Controllers\Web\Admin;
use App\Http\Controllers\Web\Member;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(
    auth()->check() ? (auth()->user()->isStaff() ? 'admin.dashboard' : 'member.home') : 'login'
));

Route::get('/locale/{lang}', function (string $lang) {
    if (in_array($lang, ['en', 'sw'], true)) {
        session(['locale' => $lang]);
    }

    return back();
})->name('locale');

/* --------------------------------- auth --------------------------------- */
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/verify-otp', [LoginController::class, 'showOtp'])->name('verify-otp');
    Route::post('/verify-otp', [LoginController::class, 'verifyOtp']);
    Route::get('/forgot-password', [LoginController::class, 'forgot'])->name('forgot');
    Route::post('/forgot-password', fn () => back()->with('status', 'If the email exists, a reset link has been sent.'));
});
Route::post('/logout', [LoginController::class, 'logout'])->middleware('auth')->name('logout');

/* -------------------------------- admin -------------------------------- */
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [Admin\DashboardController::class, 'index'])->name('dashboard');

    Route::controller(Admin\MemberController::class)->prefix('members')->name('members.')->group(function () {
        Route::get('/', 'index')->name('index')->can('members.view');
        Route::get('/create', 'create')->name('create')->can('members.create');
        Route::post('/', 'store')->name('store')->can('members.create');
        Route::get('/{member}', 'show')->name('show')->can('members.view');
        Route::get('/{member}/edit', 'edit')->name('edit')->can('members.update');
        Route::put('/{member}', 'update')->name('update')->can('members.update');
        Route::delete('/{member}', 'destroy')->name('destroy')->can('members.delete');
    });

    Route::controller(Admin\ShareController::class)->prefix('shares')->name('shares.')->middleware('feature:shares')->group(function () {
        Route::get('/', 'index')->name('index')->can('shares.view');
        Route::post('/', 'store')->name('store')->can('shares.create');
        Route::put('/{share}', 'update')->name('update')->can('shares.create');
        Route::delete('/{share}', 'destroy')->name('destroy')->can('shares.create');
    });

    Route::controller(Admin\OpeningShareController::class)->prefix('opening-shares')->name('opening-shares.')->middleware('feature:opening_shares')->group(function () {
        Route::get('/', 'index')->name('index')->can('shares.view');
        Route::post('/', 'store')->name('store')->can('shares.create');
        Route::put('/{share}', 'update')->name('update')->can('shares.create');
        Route::delete('/{share}', 'destroy')->name('destroy')->can('shares.create');
    });

    Route::get('community', [Admin\CommunityController::class, 'index'])->name('community.index')
        ->middleware('feature:community')->can('members.view');

    Route::controller(Admin\SavingsController::class)->prefix('savings')->name('savings.')->group(function () {
        Route::get('/', 'index')->name('index')->can('savings.view');
        Route::get('/{account}', 'show')->name('show')->can('savings.view');
        Route::post('/deposit', 'deposit')->name('deposit')->can('savings.deposit');
        Route::post('/withdraw', 'withdraw')->name('withdraw')->can('savings.withdraw');
        Route::post('/txn/{txn}/reverse', 'reverse')->name('reverse')->can('savings.withdraw');
    });

    Route::controller(Admin\LoanProductController::class)->prefix('loan-products')->name('products.')->group(function () {
        Route::get('/', 'index')->name('index')->can('products.view');
        Route::get('/create', 'create')->name('create')->can('products.manage');
        Route::post('/', 'store')->name('store')->can('products.manage');
        Route::get('/{product}/edit', 'edit')->name('edit')->can('products.manage');
        Route::put('/{product}', 'update')->name('update')->can('products.manage');
        Route::delete('/{product}', 'destroy')->name('destroy')->can('products.manage');
    });

    Route::controller(Admin\LoanController::class)->prefix('loans')->name('loans.')->group(function () {
        Route::get('/', 'index')->name('index')->can('loans.view');
        Route::get('/{loan}', 'show')->name('show')->can('loans.view');
        Route::post('/{loan}/approve', 'approve')->name('approve')->can('loans.approve');
        Route::post('/{loan}/reject', 'reject')->name('reject')->can('loans.reject');
        Route::post('/{loan}/disburse', 'disburse')->name('disburse')->can('loans.disburse');
        Route::post('/{loan}/repay', 'repay')->name('repay')->can('loans.repay');
        Route::post('/{loan}/cancel', 'cancel')->name('cancel')->can('loans.reject');
    });

    Route::controller(Admin\GuarantorController::class)->prefix('guarantors')->name('guarantors.')->group(function () {
        Route::get('/', 'index')->name('index')->can('guarantors.view');
        Route::post('/{guarantor}/verify', 'verify')->name('verify')->can('guarantors.verify');
        Route::delete('/{guarantor}', 'destroy')->name('destroy')->can('guarantors.verify');
    });

    Route::controller(Admin\ProjectController::class)->prefix('projects')->name('projects.')->group(function () {
        Route::get('/', 'index')->name('index')->can('projects.view');
        Route::get('/create', 'create')->name('create')->can('projects.manage');
        Route::post('/', 'store')->name('store')->can('projects.manage');
        Route::get('/{project}', 'show')->name('show')->can('projects.view');
        Route::get('/{project}/edit', 'edit')->name('edit')->can('projects.manage');
        Route::put('/{project}', 'update')->name('update')->can('projects.manage');
        Route::delete('/{project}', 'destroy')->name('destroy')->can('projects.manage');
        Route::post('/{project}/invest', 'invest')->name('invest')->can('projects.manage');
    });

    Route::controller(Admin\InsuranceController::class)->prefix('insurance')->name('insurance.')->middleware('feature:insurance')->group(function () {
        Route::get('/', 'index')->name('index')->can('insurance.view');
        Route::post('/contribute', 'contribute')->name('contribute')->can('insurance.manage');
        Route::post('/claims', 'fileClaim')->name('claims.store')->can('insurance.manage');
        Route::post('/claims/{claim}/decide', 'decideClaim')->name('claims.decide')->can('insurance.manage');
        Route::delete('/accounts/{account}', 'destroyAccount')->name('accounts.destroy')->can('insurance.manage');
        Route::delete('/claims/{claim}', 'destroyClaim')->name('claims.destroy')->can('insurance.manage');
    });

    Route::controller(Admin\PaymentController::class)->prefix('payments')->name('payments.')->group(function () {
        Route::get('/', 'index')->name('index')->can('payments.view');
        Route::post('/{payment}/verify', 'verify')->name('verify')->can('payments.verify');
        Route::post('/{payment}/reverse', 'reverse')->name('reverse')->can('payments.verify');
    });

    Route::controller(Admin\TransactionController::class)->prefix('transactions')->name('transactions.')->group(function () {
        Route::get('/', 'index')->name('index')->can('reports.view');
        Route::post('/{txn}/reverse', 'reverse')->name('reverse')->can('accounting.post');
    });

    Route::controller(Admin\AccountingController::class)->prefix('accounting')->name('accounting.')->group(function () {
        Route::get('/', 'index')->name('index')->can('accounting.view');
        Route::post('/accounts', 'storeAccount')->name('accounts.store')->can('accounting.post');
        Route::put('/accounts/{account}', 'updateAccount')->name('accounts.update')->can('accounting.post');
        Route::delete('/accounts/{account}', 'destroyAccount')->name('accounts.destroy')->can('accounting.post');
        Route::get('/journal/{entry}', 'showJournal')->name('journal.show')->can('accounting.view');
        Route::post('/journal/{entry}/reverse', 'reverseJournal')->name('journal.reverse')->can('accounting.post');
    });

    Route::controller(Admin\ProfitController::class)->prefix('profit')->name('profit.')->group(function () {
        Route::get('/', 'index')->name('index')->can('profit.view');
        Route::get('/{distribution}', 'show')->name('show')->can('profit.view');
        Route::delete('/{distribution}', 'destroy')->name('destroy')->can('profit.manage');
    });

    Route::get('/reports', [Admin\ReportController::class, 'index'])->name('reports.index')->can('reports.view');
    Route::get('/reports/{type}', [Admin\ReportController::class, 'download'])->name('reports.download')->can('reports.view');

    Route::controller(Admin\NotificationController::class)->prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/read-all', 'readAll')->name('readAll');
        Route::post('/announce', 'announce')->name('announce')->can('notifications.send');
        Route::delete('/{notification}', 'destroy')->name('destroy')->can('notifications.send');
    });

    Route::controller(Admin\UserController::class)->prefix('users')->name('users.')->group(function () {
        Route::get('/', 'index')->name('index')->can('users.view');
        Route::post('/', 'store')->name('store')->can('users.manage');
        Route::put('/{user}', 'update')->name('update')->can('users.manage');
        Route::delete('/{user}', 'destroy')->name('destroy')->can('users.manage');
    });

    Route::controller(Admin\RoleController::class)->prefix('roles')->name('roles.')->group(function () {
        Route::get('/', 'index')->name('index')->can('roles.manage');
        Route::put('/{role}', 'update')->name('update')->can('roles.manage');
    });

    Route::controller(Admin\SettingsController::class)->prefix('settings')->name('settings.')->group(function () {
        Route::get('/', 'index')->name('index')->can('settings.view');
        Route::put('/organization', 'organization')->name('organization')->can('settings.manage');
        Route::put('/config', 'config')->name('config')->can('settings.manage');
        Route::put('/modules', 'modules')->name('modules')->can('settings.manage');
    });

    Route::get('/audit-logs', [Admin\AuditController::class, 'index'])->name('audit.index')->can('audit.view');
});

/* -------------------------------- member ------------------------------- */
Route::middleware(['auth', 'role:member'])->prefix('member')->name('member.')->group(function () {
    Route::controller(Member\PortalController::class)->group(function () {
        Route::get('/', 'home')->name('home');
        Route::get('/shares', 'shares')->name('shares');
        Route::get('/savings', 'savings')->name('savings');
        Route::get('/loans', 'loans')->name('loans');
        Route::get('/loans/apply', 'applyForm')->name('loans.apply');
        Route::post('/loans/apply', 'apply')->name('loans.apply.store');
        Route::get('/loans/{loan}', 'loan')->name('loans.show');
        Route::get('/repayments', 'repayments')->name('repayments');
        Route::get('/projects', 'projects')->name('projects');
        Route::get('/projects/{project}', 'project')->name('projects.show');
        Route::get('/insurance', 'insurance')->name('insurance');
        Route::get('/transactions', 'transactions')->name('transactions');
        Route::get('/statements', 'statements')->name('statements');
        Route::get('/notifications', 'notifications')->name('notifications');
        Route::get('/profile', 'profile')->name('profile');
        Route::put('/profile', 'updateProfile')->name('profile.update');
    });
});
