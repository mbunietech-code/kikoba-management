<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Guarantor;
use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\InsuranceContribution;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\Loan;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\ProfitAllocation;
use App\Models\ProfitDistribution;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Setting;
use App\Models\Share;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Minimal starter dataset: one meaningful example of each entity so a fresh
 * install has a working template without carrying demo bulk.
 */
class DemoSeeder extends Seeder
{
    private Organization $org;
    private Carbon $today;

    public function run(): void
    {
        // One-shot starter dataset. Most rows below use create(), so re-running
        // after a successful seed would raise duplicate-key errors — bail early
        // if the sample data is already in place (e.g. `migrate --seed` on a
        // database that has nothing to migrate).
        if (Member::query()->exists()) {
            $this->command?->warn('DemoSeeder: sample data already present — skipping.');

            return;
        }

        $this->today = Carbon::create(2026, 9, 6);

        $this->org = Organization::firstOrCreate(
            ['code' => 'BK'],
            [
                'name' => 'Benja Kikoba',
                'registration_number' => 'TZ-SACCO-2021-0473',
                'phone' => '+255 27 254 0000',
                'email' => 'info@benjakikoba.co.tz',
                'address' => 'Njiro, Arusha',
                'currency' => 'TZS',
                'status' => 'active',
            ],
        );

        $this->settings();
        $this->staff();
        [$m1, $m2, $m3] = $this->members();
        $this->shares($m1);
        $this->savings($m1);
        $product = $this->loanProduct();
        $this->loan($m1, $m2, $m3, $product);
        $this->project($m1, $m2);
        $this->insurance($m1);
        $this->payment($m1);
        $this->transaction($m1);
        $this->accounting();
        $this->profit($m1, $m2, $m3);
        $this->notification();
        $this->audit();
    }

    private function settings(): void
    {
        foreach ([
            ['share_price', '10000', 'number'],
            ['minimum_shares', '10', 'number'],
            ['minimum_savings', '100000', 'number'],
            ['loan_interest_rate', '10', 'number'],
            ['penalty_rate', '5', 'number'],
            ['insurance_contribution', '20000', 'number'],
            ['reserve_rate', '20', 'number'],
            ['currency', 'TZS', 'string'],
        ] as [$k, $v, $t]) {
            Setting::updateOrCreate(
                ['organization_id' => $this->org->id, 'key' => $k],
                ['value' => $v, 'type' => $t],
            );
        }
    }

    private function staff(): void
    {
        foreach ([
            ['System Owner', 'super.admin@kikoba.co.tz', '+255755000001', 'super_admin'],
            ['Fatuma Kimaro', 'admin@kikoba.co.tz', '+255755000002', 'admin'],
            ['Tumaini Mushi', 'treasurer@kikoba.co.tz', '+255755000003', 'treasurer'],
            ['Anna Kessy', 'accountant@kikoba.co.tz', '+255755000004', 'accountant'],
            ['Lucas Swai', 'officer@kikoba.co.tz', '+255755000005', 'loan_officer'],
        ] as [$name, $email, $phone, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['organization_id' => $this->org->id, 'name' => $name, 'phone' => $phone,
                    'password' => 'demo1234', 'status' => 'active', 'email_verified_at' => now()],
            );
            $user->syncRoles([$role]);
        }
    }

    /** @return array{0:Member,1:Member,2:Member} */
    private function members(): array
    {
        $rows = [
            ['Emmanuel Urio', 'member@mfano.co.tz', '+255716766926', 'male', '#115e59', 'active'],
            ['Neema Mrema', 'neema.mrema@mfano.co.tz', '+255742118330', 'female', '#2563eb', 'active'],
            ['Juma Shirima', 'juma.shirima@mfano.co.tz', '+255760429653', 'male', '#16a34a', 'active'],
        ];
        $members = [];
        foreach ($rows as $i => [$name, $email, $phone, $gender, $color, $status]) {
            $members[] = Member::create([
                'organization_id' => $this->org->id,
                'member_number' => 'MBR-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'phone' => $phone,
                'email' => $email,
                'gender' => $gender,
                'date_of_birth' => $this->today->copy()->subYears(30 + $i)->toDateString(),
                'address' => 'Njiro, Arusha',
                'next_of_kin' => 'Next of Kin '.($i + 1),
                'next_of_kin_phone' => '+2556140000'.($i + 1),
                'registration_date' => $this->today->copy()->subMonths(8 - $i)->toDateString(),
                'status' => $status,
                'avatar_color' => $color,
            ]);
        }

        $user = User::updateOrCreate(
            ['email' => 'member@mfano.co.tz'],
            ['organization_id' => $this->org->id, 'name' => $members[0]->full_name, 'phone' => $members[0]->phone,
                'password' => 'demo1234', 'status' => 'active', 'email_verified_at' => now()],
        );
        $user->syncRoles(['member']);
        $members[0]->update(['user_id' => $user->id]);

        return $members;
    }

    private function shares(Member $m): void
    {
        Share::create([
            'organization_id' => $this->org->id, 'member_id' => $m->id,
            'quantity' => 25, 'price_per_share' => 10000, 'total_value' => 250000,
            'purchased_at' => $this->today->copy()->subMonths(3)->toDateString(),
            'transaction_reference' => 'TXN-2026-000001', 'status' => 'confirmed',
        ]);
    }

    private function savings(Member $m): void
    {
        $acc = SavingsAccount::create([
            'organization_id' => $this->org->id, 'member_id' => $m->id,
            'account_number' => 'SAV-000001', 'balance' => 0, 'status' => 'active',
            'opened_at' => $m->registration_date,
        ]);
        $bal = 0;
        foreach ([['deposit', 300000], ['deposit', 250000], ['withdrawal', 100000]] as $i => [$type, $amount]) {
            $before = $bal;
            $bal = $type === 'deposit' ? $bal + $amount : $bal - $amount;
            SavingsTransaction::create([
                'organization_id' => $this->org->id, 'savings_account_id' => $acc->id, 'member_id' => $m->id,
                'type' => $type, 'amount' => $amount, 'balance_before' => $before, 'balance_after' => $bal,
                'reference' => 'TXN-2026-0000'.($i + 2),
                'created_at' => $this->today->copy()->subWeeks(6 - $i * 2), 'updated_at' => now(),
            ]);
        }
        $acc->update(['balance' => $bal]);
    }

    private function loanProduct(): LoanProduct
    {
        return LoanProduct::create([
            'organization_id' => $this->org->id,
            'name' => 'Normal Loan', 'description' => 'Standard member loan against savings.',
            'minimum_amount' => 100000, 'maximum_amount' => 5000000, 'interest_rate' => 10,
            'interest_method' => 'reducing', 'repayment_period' => 6, 'repayment_frequency' => 'monthly',
            'processing_fee' => 1, 'insurance_fee' => 1, 'penalty_rate' => 5,
            'minimum_savings' => 100000, 'minimum_shares' => 10, 'required_guarantors' => 2, 'status' => 'active',
        ]);
    }

    private function loan(Member $borrower, Member $g1, Member $g2, LoanProduct $product): void
    {
        $principal = 1000000;
        $interest = 50000;
        $fees = 10000;
        $insurance = 10000;
        $total = $principal + $interest + $fees + $insurance;
        $amountPaid = 360000;

        $loan = Loan::create([
            'organization_id' => $this->org->id, 'member_id' => $borrower->id,
            'loan_product_id' => $product->id, 'loan_number' => 'LN-000001',
            'principal_amount' => $principal, 'interest_amount' => $interest, 'processing_fee' => $fees,
            'insurance_amount' => $insurance, 'penalty_amount' => 0, 'total_amount' => $total,
            'amount_paid' => $amountPaid, 'outstanding_balance' => $total - $amountPaid,
            'status' => 'active', 'purpose' => 'Business stock', 'period' => 6,
            'repayment_frequency' => 'monthly', 'interest_method' => 'reducing',
            'application_date' => $this->today->copy()->subMonths(3)->toDateString(),
            'approval_date' => $this->today->copy()->subMonths(3)->addDays(2)->toDateString(),
            'disbursement_date' => $this->today->copy()->subMonths(3)->addDays(4)->toDateString(),
            'maturity_date' => $this->today->copy()->addMonths(3)->toDateString(),
        ]);

        foreach ([$g1, $g2] as $g) {
            Guarantor::create([
                'organization_id' => $this->org->id, 'loan_id' => $loan->id,
                'member_id' => $borrower->id, 'guarantor_member_id' => $g->id,
                'guaranteed_amount' => $total / 2, 'status' => 'approved', 'approved_at' => now(),
            ]);
        }

        $perInst = (int) round($total / 6);
        $paidLeft = $amountPaid;
        for ($k = 1; $k <= 6; $k++) {
            $totalDue = $k === 6 ? $total - $perInst * 5 : $perInst;
            $pay = min($paidLeft, $totalDue);
            $paidLeft -= $pay;
            $due = $this->today->copy()->subMonths(3)->addMonths($k);
            $status = $pay >= $totalDue ? 'paid' : ($pay > 0 ? 'partial' : ($due->lt($this->today) ? 'overdue' : 'pending'));

            $schedule = LoanRepaymentSchedule::create([
                'loan_id' => $loan->id, 'installment_number' => $k, 'due_date' => $due->toDateString(),
                'principal_due' => (int) round($principal / 6), 'interest_due' => (int) round($interest / 6),
                'fee_due' => (int) round(($fees + $insurance) / 6), 'total_due' => $totalDue,
                'amount_paid' => $pay, 'status' => $status,
                'paid_at' => $pay >= $totalDue ? $due->toDateString() : null,
            ]);

            if ($pay > 0) {
                LoanRepayment::create([
                    'organization_id' => $this->org->id, 'loan_id' => $loan->id, 'member_id' => $borrower->id,
                    'schedule_id' => $schedule->id, 'installment_number' => $k,
                    'principal_paid' => (int) round($pay * $principal / $total),
                    'interest_paid' => (int) round($pay * $interest / $total),
                    'fee_paid' => (int) round($pay * ($fees + $insurance) / $total),
                    'total_paid' => $pay, 'payment_date' => $due->toDateString(),
                    'reference' => 'TXN-2026-0001'.$k, 'method' => 'mobile_money',
                ]);
            }
        }
    }

    private function project(Member $m1, Member $m2): void
    {
        $project = Project::create([
            'organization_id' => $this->org->id, 'name' => 'Poultry Unit',
            'description' => 'Broiler production cycle for local hotels.', 'type' => 'monthly',
            'capital_required' => 6000000, 'capital_raised' => 4000000, 'expected_profit' => 1500000,
            'actual_profit' => 0, 'start_date' => $this->today->copy()->subMonths(1)->toDateString(),
            'end_date' => $this->today->copy()->addMonths(1)->toDateString(), 'status' => 'active',
            'manager' => 'Neema Mrema',
        ]);
        foreach ([$m1, $m2] as $m) {
            ProjectInvestment::create([
                'organization_id' => $this->org->id, 'project_id' => $project->id, 'member_id' => $m->id,
                'amount' => 2000000, 'profit_share' => 0, 'status' => 'active',
                'invested_at' => $this->today->copy()->subWeeks(3)->toDateString(),
            ]);
        }
    }

    private function insurance(Member $m): void
    {
        $acc = InsuranceAccount::create([
            'organization_id' => $this->org->id, 'member_id' => $m->id, 'plan_name' => 'Standard Plan',
            'monthly_contribution' => 20000, 'coverage_amount' => 2000000,
            'start_date' => $this->today->copy()->subMonths(6)->toDateString(),
            'end_date' => $this->today->copy()->addMonths(6)->toDateString(), 'status' => 'active',
        ]);
        for ($k = 0; $k < 3; $k++) {
            $d = $this->today->copy()->subMonths($k)->startOfMonth();
            InsuranceContribution::create([
                'organization_id' => $this->org->id, 'insurance_account_id' => $acc->id, 'member_id' => $m->id,
                'amount' => 20000, 'period' => $d->format('Y-m'), 'paid_on' => $d->toDateString(),
                'reference' => 'TXN-2026-0002'.$k,
            ]);
        }
        InsuranceClaim::create([
            'organization_id' => $this->org->id, 'insurance_account_id' => $acc->id, 'member_id' => $m->id,
            'claim_number' => 'CLM-00001', 'claim_type' => 'Medical', 'description' => 'Hospitalisation costs',
            'amount_requested' => 500000, 'amount_approved' => 0, 'status' => 'submitted', 'submitted_at' => now(),
        ]);
    }

    private function payment(Member $m): void
    {
        Payment::create([
            'organization_id' => $this->org->id, 'member_id' => $m->id, 'provider' => 'M-Pesa',
            'payment_method' => 'mobile_money', 'amount' => 300000, 'external_reference' => 'QGH123456',
            'internal_reference' => 'PMT-2026-000001', 'purpose' => 'Savings deposit', 'status' => 'successful',
            'paid_at' => $this->today->copy()->subDays(2), 'verified_at' => $this->today->copy()->subDays(2),
        ]);
    }

    private function transaction(Member $m): void
    {
        Transaction::create([
            'organization_id' => $this->org->id, 'member_id' => $m->id,
            'transaction_reference' => 'TXN-2026-000030', 'type' => 'SAVINGS_DEPOSIT', 'amount' => 300000,
            'status' => 'successful', 'description' => 'savings deposit', 'created_by' => 'System',
            'created_at' => $this->today->copy()->subDays(2), 'updated_at' => now(),
        ]);
    }

    private function accounting(): void
    {
        $accounts = [
            ['1000', 'Cash', 'asset', 1500000], ['1010', 'Bank', 'asset', 8000000],
            ['1300', 'Loan Receivable', 'asset', 700000], ['1400', 'Project Investments', 'asset', 4000000],
            ['1100', 'Member Savings', 'liability', 450000], ['2100', 'Insurance Fund', 'liability', 60000],
            ['1200', 'Member Shares', 'equity', 250000], ['3000', 'Retained Earnings', 'equity', 0],
            ['4000', 'Interest Income', 'revenue', 50000], ['4100', 'Project Income', 'revenue', 0],
            ['4200', 'Fees Income', 'revenue', 20000], ['5000', 'Insurance Expense', 'expense', 0],
            ['5100', 'Operating Expenses', 'expense', 40000], ['5200', 'Project Expenses', 'expense', 0],
        ];
        $byCode = [];
        foreach ($accounts as [$code, $name, $type, $balance]) {
            $byCode[$code] = Account::create([
                'organization_id' => $this->org->id, 'account_code' => $code,
                'name' => $name, 'account_type' => $type, 'balance' => $balance,
            ]);
        }
        $entry = JournalEntry::create([
            'organization_id' => $this->org->id, 'reference' => 'JE-2026-00001',
            'description' => 'Member savings deposit', 'entry_date' => $this->today->copy()->subDays(2)->toDateString(),
            'posted_by' => 'System',
        ]);
        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $byCode['1000']->id, 'debit' => 300000, 'credit' => 0]);
        JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $byCode['1100']->id, 'debit' => 0, 'credit' => 300000]);
    }

    private function profit(Member $m1, Member $m2, Member $m3): void
    {
        $dist = ProfitDistribution::create([
            'organization_id' => $this->org->id, 'period_start' => '2026-01-01', 'period_end' => '2026-06-30',
            'total_profit' => 900000, 'reserved_amount' => 180000, 'distributable_profit' => 720000,
            'basis' => 'shares', 'status' => 'calculated',
        ]);
        foreach ([$m1, $m2, $m3] as $i => $m) {
            ProfitAllocation::create([
                'profit_distribution_id' => $dist->id, 'member_id' => $m->id, 'basis' => 'shares',
                'percentage' => $i === 0 ? 100 : 0, 'amount' => $i === 0 ? 720000 : 0, 'status' => 'pending',
            ]);
        }
    }

    private function notification(): void
    {
        Notification::create([
            'organization_id' => $this->org->id, 'title' => 'Loan approved',
            'message' => 'Loan LN-000001 has been approved and disbursed.', 'type' => 'loan_approved',
            'channel' => 'in_app', 'created_at' => $this->today->copy()->subDays(1), 'updated_at' => now(),
        ]);
    }

    private function audit(): void
    {
        AuditLog::create([
            'organization_id' => $this->org->id, 'action' => 'DISBURSE_LOAN', 'entity' => 'Loan',
            'entity_id' => 'LN-000001', 'old_values' => ['value' => 'Approved'], 'new_values' => ['value' => 'Disbursed'],
            'ip_address' => '196.0.0.1', 'created_at' => $this->today->copy()->subDays(1),
        ]);
    }
}
