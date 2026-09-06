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
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private int $seed = 20260905;
    private Organization $org;
    private Carbon $now;

    private function rnd(): float
    {
        $this->seed = ($this->seed * 1664525 + 1013904223) % 4294967296;

        return $this->seed / 4294967296;
    }

    private function pick(array $a)
    {
        return $a[(int) floor($this->rnd() * count($a))];
    }

    private function int(int $min, int $max): int
    {
        return (int) floor($this->rnd() * ($max - $min + 1)) + $min;
    }

    private function round(float $n, int $to = 1000): int
    {
        return (int) (round($n / $to) * $to);
    }

    private function daysAgo(float $d): Carbon
    {
        return $this->now->copy()->subDays((int) round($d));
    }

    public function run(): void
    {
        $this->now = Carbon::create(2026, 9, 5, 9);

        $this->org = Organization::firstOrCreate(
            ['code' => 'BK'],
            ['name' => 'Benja Kikoba', 'registration_number' => 'TZ-SACCO-2021-0473',
                'phone' => '+255 27 254 0000', 'email' => 'info@benjakikoba.co.tz',
                'address' => 'Njiro, Arusha', 'currency' => 'TZS', 'status' => 'active'],
        );

        $this->seedSettings();
        $this->seedStaff();
        $members = $this->seedMembers();
        $this->seedShares($members);
        $this->seedSavings($members);
        $products = $this->seedLoanProducts();
        $this->seedLoans($members, $products);
        $this->seedProjects($members);
        $this->seedInsurance($members);
        $this->seedPayments($members);
        $this->seedTransactions($members);
        $this->seedAccounting();
        $this->seedProfit($members);
        $this->seedNotifications();
        $this->seedAudit();
    }

    private function seedSettings(): void
    {
        $defaults = [
            ['share_price', '10000', 'number'],
            ['minimum_shares', '10', 'number'],
            ['minimum_savings', '100000', 'number'],
            ['loan_interest_rate', '10', 'number'],
            ['penalty_rate', '5', 'number'],
            ['insurance_contribution', '20000', 'number'],
            ['reserve_rate', '20', 'number'],
            ['currency', 'TZS', 'string'],
        ];
        foreach ($defaults as [$k, $v, $t]) {
            Setting::updateOrCreate(
                ['organization_id' => $this->org->id, 'key' => $k],
                ['value' => $v, 'type' => $t],
            );
        }
    }

    private function seedStaff(): void
    {
        $staff = [
            ['System Owner', 'super.admin@kikoba.co.tz', '+255755000001', 'super_admin'],
            ['Fatuma Kimaro', 'admin@kikoba.co.tz', '+255755000002', 'admin'],
            ['Tumaini Mushi', 'treasurer@kikoba.co.tz', '+255755000003', 'treasurer'],
            ['Anna Kessy', 'accountant@kikoba.co.tz', '+255755000004', 'accountant'],
            ['Lucas Swai', 'officer@kikoba.co.tz', '+255755000005', 'loan_officer'],
        ];
        foreach ($staff as [$name, $email, $phone, $role]) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['organization_id' => $this->org->id, 'name' => $name, 'phone' => $phone,
                    'password' => 'demo1234', 'status' => 'active', 'email_verified_at' => now()],
            );
            $user->syncRoles([$role]);
        }
    }

    /** @return \Illuminate\Support\Collection<int, Member> */
    private function seedMembers()
    {
        $first = ['Amina', 'Baraka', 'Neema', 'Juma', 'Fatuma', 'Hamisi', 'Zainabu', 'Rajabu', 'Grace', 'Emmanuel', 'Mwajuma', 'Said', 'Halima', 'Frank', 'Rehema', 'Deo', 'Anna', 'Kelvin', 'Upendo', 'Ibrahim', 'Joyce', 'Salum', 'Doreen', 'Michael'];
        $last = ['Mushi', 'Kileo', 'Mrema', 'Shirima', 'Massawe', 'Kimaro', 'Lyimo', 'Moshi', 'Temba', 'Swai', 'Nkya', 'Macha', 'Urio', 'Kessy', 'Mollel', 'Sanga', 'Mbwana', 'Chuwa', 'Minja', 'Kweka'];
        $colors = ['#115e59', '#2563eb', '#16a34a', '#0f766e', '#1d4ed8', '#15803d', '#0d9488', '#7c3aed', '#c2410c', '#be123c'];

        $members = collect();
        for ($i = 0; $i < 24; $i++) {
            $name = $this->pick($first).' '.$this->pick($last);
            $status = $i < 19 ? 'active' : $this->pick(['pending', 'suspended', 'inactive', 'deceased']);
            $slug = Str::of($name)->lower()->replaceMatches('/[^a-z]/', '.');

            $member = Member::create([
                'organization_id' => $this->org->id,
                'member_number' => 'MBR-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'full_name' => $name,
                'phone' => '+2557'.$this->int(10, 89).$this->int(1000000, 9999999),
                'email' => "{$slug}@mfano.co.tz",
                'gender' => $this->pick(['male', 'female']),
                'date_of_birth' => $this->daysAgo($this->int(7000, 20000))->toDateString(),
                'address' => $this->pick(['Njiro', 'Kilombero', 'Sakina', 'Moshono', 'Sombetini']).', '.$this->pick(['Arusha', 'Moshi', 'Dar es Salaam']),
                'next_of_kin' => $this->pick($first).' '.$this->pick($last),
                'next_of_kin_phone' => '+2556'.$this->int(10, 89).$this->int(1000000, 9999999),
                'registration_date' => $this->daysAgo($this->int(30, 900))->toDateString(),
                'status' => $status,
                'avatar_color' => $colors[$i % count($colors)],
            ]);
            $members->push($member);
        }

        // give the first active member a login
        $first = $members->firstWhere('status', 'active');
        $user = User::updateOrCreate(
            ['email' => 'member@mfano.co.tz'],
            ['organization_id' => $this->org->id, 'name' => $first->full_name,
                'phone' => $first->phone, 'password' => 'demo1234', 'status' => 'active', 'email_verified_at' => now()],
        );
        $user->syncRoles(['member']);
        $first->update(['user_id' => $user->id, 'email' => 'member@mfano.co.tz']);

        return $members;
    }

    private function seedShares($members): void
    {
        $price = 10000;
        foreach ($members as $m) {
            $buys = $m->status === 'active' ? $this->int(1, 4) : $this->int(0, 1);
            for ($b = 0; $b < $buys; $b++) {
                $qty = $this->int(5, 60);
                Share::create([
                    'organization_id' => $this->org->id,
                    'member_id' => $m->id,
                    'quantity' => $qty,
                    'price_per_share' => $price,
                    'total_value' => $qty * $price,
                    'purchased_at' => $this->daysAgo($this->int(10, 800))->toDateString(),
                    'transaction_reference' => 'TXN-2026-'.str_pad((string) Share::count() + 1, 6, '0', STR_PAD_LEFT),
                    'status' => 'confirmed',
                ]);
            }
        }
    }

    private function seedSavings($members): void
    {
        foreach ($members as $i => $m) {
            $acc = SavingsAccount::create([
                'organization_id' => $this->org->id,
                'member_id' => $m->id,
                'account_number' => 'SAV-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'balance' => 0,
                'status' => $m->status === 'active' ? 'active' : 'dormant',
                'opened_at' => $m->registration_date,
            ]);
            $bal = 0;
            $n = $this->int(6, 16);
            for ($t = 0; $t < $n; $t++) {
                $isW = $this->rnd() < 0.22 && $bal > 100000;
                $amount = $isW ? $this->round($this->int(20, 200) * 1000) : $this->round($this->int(30, 350) * 1000);
                $before = $bal;
                $bal = $isW ? $bal - $amount : $bal + $amount;
                SavingsTransaction::create([
                    'organization_id' => $this->org->id,
                    'savings_account_id' => $acc->id,
                    'member_id' => $m->id,
                    'type' => $isW ? 'withdrawal' : 'deposit',
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $bal,
                    'reference' => 'TXN-2026-'.str_pad((string) (3000 + SavingsTransaction::count()), 6, '0', STR_PAD_LEFT),
                    'created_at' => $this->daysAgo($this->int(1, 500)),
                    'updated_at' => now(),
                ]);
            }
            $acc->update(['balance' => $bal]);
        }
    }

    private function seedLoanProducts()
    {
        $rows = [
            ['Normal Loan', 'Standard member loan against savings.', 100000, 5000000, 10, 'reducing', 6, 1, 1, 5, 100000, 10, 2],
            ['Emergency Loan', 'Fast, short-term loan for urgent needs.', 50000, 1000000, 8, 'flat', 3, 1.5, 1, 5, 50000, 5, 1],
            ['Project Loan', 'Financing for member income-generating projects.', 500000, 10000000, 12, 'reducing', 12, 2, 1.5, 5, 300000, 30, 3],
            ['Large Loan', 'High-value loan with extended repayment.', 5000000, 30000000, 14, 'reducing', 24, 2.5, 2, 6, 1000000, 100, 3],
        ];

        return collect($rows)->map(fn ($r) => LoanProduct::create([
            'organization_id' => $this->org->id,
            'name' => $r[0], 'description' => $r[1], 'minimum_amount' => $r[2], 'maximum_amount' => $r[3],
            'interest_rate' => $r[4], 'interest_method' => $r[5], 'repayment_period' => $r[6],
            'repayment_frequency' => 'monthly', 'processing_fee' => $r[7], 'insurance_fee' => $r[8],
            'penalty_rate' => $r[9], 'minimum_savings' => $r[10], 'minimum_shares' => $r[11],
            'required_guarantors' => $r[12], 'status' => 'active',
        ]));
    }

    private function seedLoans($members, $products): void
    {
        $statuses = ['active', 'active', 'active', 'overdue', 'completed', 'under_review', 'submitted', 'approved', 'disbursed', 'rejected', 'defaulted'];
        $active = $members->where('status', 'active')->values();

        foreach ($active->take(16) as $i => $m) {
            $product = $this->pick($products->all());
            $principal = $this->round($this->int((int) ($product->minimum_amount / 1000), (int) (min($product->maximum_amount, 8000000) / 1000)) * 1000, 50000);
            $interest = (int) round($principal * $product->interest_rate * $product->repayment_period / (100 * 12));
            $fees = (int) round($principal * $product->processing_fee / 100);
            $insurance = (int) round($principal * $product->insurance_fee / 100);
            $status = $statuses[$i % count($statuses)];
            $total = $principal + $interest + $fees + $insurance;
            $runningLike = in_array($status, ['active', 'overdue', 'completed', 'disbursed', 'defaulted']);
            $paidRatio = match ($status) {
                'completed' => 1.0,
                'overdue' => 0.35,
                'defaulted' => 0.2,
                default => $runningLike ? $this->rnd() * 0.7 + 0.1 : 0.0,
            };
            $amountPaid = $this->round($total * $paidRatio, 1000);
            $disbursed = $runningLike || $status === 'approved';

            $loan = Loan::create([
                'organization_id' => $this->org->id,
                'member_id' => $m->id,
                'loan_product_id' => $product->id,
                'loan_number' => 'LN-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'principal_amount' => $principal,
                'interest_amount' => $interest,
                'processing_fee' => $fees,
                'insurance_amount' => $insurance,
                'penalty_amount' => in_array($status, ['overdue', 'defaulted']) ? $this->round($interest * 0.1, 1000) : 0,
                'total_amount' => $total,
                'amount_paid' => $amountPaid,
                'outstanding_balance' => max(0, $total - $amountPaid),
                'status' => $status,
                'purpose' => $this->pick(['Business stock', 'School fees', 'Farm inputs', 'Home improvement', 'Medical', 'Working capital']),
                'period' => $product->repayment_period,
                'repayment_frequency' => 'monthly',
                'interest_method' => $product->interest_method,
                'application_date' => $this->daysAgo($this->int(20, 400))->toDateString(),
                'approval_date' => ($disbursed || $status === 'under_review') ? $this->daysAgo($this->int(15, 380))->toDateString() : null,
                'disbursement_date' => $disbursed ? $this->daysAgo($this->int(10, 360))->toDateString() : null,
                'maturity_date' => $disbursed ? $this->now->copy()->addDays($this->int(-60, 300))->toDateString() : null,
            ]);

            for ($g = 0; $g < $product->required_guarantors; $g++) {
                $guar = $this->pick($active->where('id', '!=', $m->id)->values()->all());
                Guarantor::create([
                    'organization_id' => $this->org->id,
                    'loan_id' => $loan->id,
                    'member_id' => $m->id,
                    'guarantor_member_id' => $guar->id,
                    'guaranteed_amount' => $this->round($total / $product->required_guarantors, 10000),
                    'status' => $runningLike ? 'approved' : $this->pick(['pending', 'approved']),
                    'approved_at' => $i % 2 === 0 ? $this->daysAgo($this->int(10, 300)) : null,
                ]);
            }

            if ($disbursed) {
                $perInst = (int) round($total / $product->repayment_period);
                $paidLeft = $amountPaid;
                for ($k = 1; $k <= $product->repayment_period; $k++) {
                    $totalDue = $k === $product->repayment_period ? $total - $perInst * ($product->repayment_period - 1) : $perInst;
                    $payToThis = min($paidLeft, $totalDue);
                    $paidLeft -= $payToThis;
                    $due = $this->now->copy()->addDays($this->int(-40, 260) + $k * 30 - 120);
                    $rowStatus = $payToThis >= $totalDue ? 'paid' : ($payToThis > 0 ? 'partial' : ($due->lt(Carbon::create(2026, 9, 5)) ? 'overdue' : 'pending'));

                    $schedule = LoanRepaymentSchedule::create([
                        'loan_id' => $loan->id,
                        'installment_number' => $k,
                        'due_date' => $due->toDateString(),
                        'principal_due' => (int) round($principal / $product->repayment_period),
                        'interest_due' => (int) round($interest / $product->repayment_period),
                        'fee_due' => (int) round(($fees + $insurance) / $product->repayment_period),
                        'total_due' => $totalDue,
                        'amount_paid' => $payToThis,
                        'status' => $rowStatus,
                        'paid_at' => $payToThis >= $totalDue ? $due->toDateString() : null,
                    ]);

                    if ($payToThis > 0) {
                        LoanRepayment::create([
                            'organization_id' => $this->org->id,
                            'loan_id' => $loan->id,
                            'member_id' => $m->id,
                            'schedule_id' => $schedule->id,
                            'installment_number' => $k,
                            'principal_paid' => (int) round($payToThis * ($principal / $total)),
                            'interest_paid' => (int) round($payToThis * ($interest / $total)),
                            'fee_paid' => (int) round($payToThis * (($fees + $insurance) / $total)),
                            'total_paid' => $payToThis,
                            'payment_date' => $due->toDateString(),
                            'reference' => 'TXN-2026-'.str_pad((string) (5000 + LoanRepayment::count()), 6, '0', STR_PAD_LEFT),
                            'method' => $this->pick(['mobile_money', 'bank', 'cash']),
                        ]);
                    }
                }
            }
        }
    }

    private function seedProjects($members): void
    {
        $active = $members->where('status', 'active')->values();
        $projects = [
            ['Maize Bulk Trading', 'Buy maize at harvest, store and sell in lean season.', 'three_months', 12000000, 12000000, 3000000, 3450000, -200, -20, 'completed', 'Baraka Kileo', 14],
            ['Poultry Unit', 'Broiler production cycle for local hotels.', 'monthly', 6000000, 4200000, 1500000, 0, -25, 35, 'active', 'Neema Mrema', 9],
            ['Boda Boda Fleet', 'Five motorcycles on daily rental to riders.', 'long_term', 20000000, 8500000, 9000000, 0, 10, 375, 'planned', 'Juma Shirima', 6],
            ['Hardware Shop Stock', 'Restock building materials for peak season.', 'three_months', 15000000, 15000000, 4000000, 0, -40, 50, 'active', 'Grace Massawe', 11],
        ];
        foreach ($projects as $p) {
            $project = Project::create([
                'organization_id' => $this->org->id,
                'name' => $p[0], 'description' => $p[1], 'type' => $p[2],
                'capital_required' => $p[3], 'capital_raised' => $p[4], 'expected_profit' => $p[5], 'actual_profit' => $p[6],
                'start_date' => $this->now->copy()->addDays($p[7])->toDateString(),
                'end_date' => $this->now->copy()->addDays($p[8])->toDateString(),
                'status' => $p[9], 'manager' => $p[10],
            ]);
            foreach ($active->take($p[11]) as $m) {
                $amount = max(50000, $this->round($p[4] / $p[11] + $this->int(-200, 300) * 1000, 50000));
                ProjectInvestment::create([
                    'organization_id' => $this->org->id,
                    'project_id' => $project->id,
                    'member_id' => $m->id,
                    'amount' => $amount,
                    'profit_share' => $p[9] === 'completed' ? (int) round($p[6] / $p[11]) : 0,
                    'status' => $p[9] === 'completed' ? 'completed' : 'active',
                    'invested_at' => $this->daysAgo($this->int(20, 190))->toDateString(),
                ]);
            }
        }
    }

    private function seedInsurance($members): void
    {
        $contrib = 20000;
        $active = $members->where('status', 'active')->values();
        foreach ($active as $i => $m) {
            $months = $this->int(4, 20);
            $acc = InsuranceAccount::create([
                'organization_id' => $this->org->id,
                'member_id' => $m->id,
                'plan_name' => $this->pick(['Basic Protection', 'Family Cover', 'Standard Plan']),
                'monthly_contribution' => $contrib,
                'coverage_amount' => $this->pick([1000000, 2000000, 3000000]),
                'start_date' => $this->daysAgo($months * 30)->toDateString(),
                'end_date' => $this->now->copy()->addDays($this->int(-15, 300))->toDateString(),
                'status' => $i < $active->count() - 3 ? 'active' : $this->pick(['expired', 'suspended']),
            ]);
            for ($k = 0; $k < $months; $k++) {
                $d = Carbon::create(2026, 9, 1)->subMonths($k);
                InsuranceContribution::create([
                    'organization_id' => $this->org->id,
                    'insurance_account_id' => $acc->id,
                    'member_id' => $m->id,
                    'amount' => $contrib,
                    'period' => $d->format('Y-m'),
                    'paid_on' => $d->toDateString(),
                    'reference' => 'TXN-2026-'.str_pad((string) (7000 + InsuranceContribution::count()), 6, '0', STR_PAD_LEFT),
                ]);
            }
        }
        for ($i = 0; $i < 7; $i++) {
            $m = $this->pick($active->all());
            $acc = InsuranceAccount::where('member_id', $m->id)->first();
            $requested = $this->round($this->int(200, 1500) * 1000, 50000);
            $status = $this->pick(['submitted', 'under_review', 'approved', 'rejected', 'paid', 'paid']);
            InsuranceClaim::create([
                'organization_id' => $this->org->id,
                'insurance_account_id' => $acc->id,
                'member_id' => $m->id,
                'claim_number' => 'CLM-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'claim_type' => $this->pick(['Medical', 'Funeral', 'Property loss', 'Disability']),
                'description' => $this->pick(['Hospitalisation costs', 'Bereavement support', 'Fire damage to shop', 'Accident recovery']),
                'amount_requested' => $requested,
                'amount_approved' => in_array($status, ['paid', 'approved']) ? $this->round($requested * ($this->rnd() * 0.4 + 0.6), 10000) : 0,
                'status' => $status,
                'submitted_at' => $this->daysAgo($this->int(5, 120)),
                'paid_at' => $status === 'paid' ? $this->daysAgo($this->int(1, 40)) : null,
            ]);
        }
    }

    private function seedPayments($members): void
    {
        $providers = ['mobile_money' => 'M-Pesa', 'bank' => 'CRDB Bank', 'card' => 'Selcom', 'cash' => 'Cash desk', 'manual' => 'Manual entry'];
        for ($i = 0; $i < 40; $i++) {
            $m = $this->pick($members->all());
            $method = $this->pick(['mobile_money', 'mobile_money', 'bank', 'cash', 'card']);
            $status = $this->pick(['successful', 'successful', 'successful', 'pending', 'failed', 'reversed']);
            Payment::create([
                'organization_id' => $this->org->id,
                'member_id' => $m->id,
                'provider' => $providers[$method],
                'payment_method' => $method,
                'amount' => $this->round($this->int(20, 800) * 1000, 5000),
                'external_reference' => $this->pick(['QGH', 'RTX', 'MPX', 'BNK']).$this->int(100000, 999999),
                'internal_reference' => 'PMT-2026-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'purpose' => $this->pick(['Savings deposit', 'Loan repayment', 'Share purchase', 'Insurance contribution', 'Project investment']),
                'status' => $status,
                'paid_at' => $this->daysAgo($this->int(0, 90)),
                'verified_at' => $status === 'successful' ? $this->daysAgo($this->int(0, 90)) : null,
            ]);
        }
    }

    private function seedTransactions($members): void
    {
        $types = ['SAVINGS_DEPOSIT', 'SAVINGS_WITHDRAWAL', 'SHARE_PURCHASE', 'LOAN_DISBURSEMENT', 'LOAN_REPAYMENT', 'INTEREST_PAYMENT', 'PROJECT_INVESTMENT', 'INSURANCE_PAYMENT', 'FEE', 'PENALTY'];
        for ($i = 0; $i < 60; $i++) {
            $m = $this->pick($members->all());
            $type = $this->pick($types);
            Transaction::create([
                'organization_id' => $this->org->id,
                'member_id' => $m->id,
                'transaction_reference' => 'TXN-2026-'.str_pad((string) ($i + 1), 6, '0', STR_PAD_LEFT),
                'type' => $type,
                'amount' => $this->round($this->int(10, 900) * 1000, 1000),
                'status' => $this->pick(['successful', 'successful', 'successful', 'pending', 'reversed']),
                'description' => Str::of($type)->lower()->replace('_', ' '),
                'created_by' => $this->pick(['T. Mushi', 'A. Kessy', 'System', 'L. Swai']),
                'created_at' => $this->daysAgo($this->int(0, 120)),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAccounting(): void
    {
        $accounts = [
            ['1000', 'Cash', 'asset', 4250000], ['1010', 'Bank', 'asset', 38900000],
            ['1300', 'Loan Receivable', 'asset', 62400000], ['1400', 'Project Investments', 'asset', 39700000],
            ['1100', 'Member Savings', 'liability', 71200000], ['2100', 'Insurance Fund', 'liability', 8600000],
            ['1200', 'Member Shares', 'equity', 24800000], ['3000', 'Retained Earnings', 'equity', 15300000],
            ['4000', 'Interest Income', 'revenue', 9450000], ['4100', 'Project Income', 'revenue', 3450000],
            ['4200', 'Fees Income', 'revenue', 1780000], ['5000', 'Insurance Expense', 'expense', 2100000],
            ['5100', 'Operating Expenses', 'expense', 3640000], ['5200', 'Project Expenses', 'expense', 1200000],
        ];
        $byCode = [];
        foreach ($accounts as [$code, $name, $type, $balance]) {
            $byCode[$code] = Account::create([
                'organization_id' => $this->org->id, 'account_code' => $code,
                'name' => $name, 'account_type' => $type, 'balance' => $balance,
            ]);
        }
        $scenarios = [
            [['1000', 'Cash'], ['1100', 'Member Savings'], 'Member savings deposit'],
            [['1010', 'Bank'], ['1300', 'Loan Receivable'], 'Loan repayment received'],
            [['1300', 'Loan Receivable'], ['4000', 'Interest Income'], 'Interest accrued on loans'],
            [['1000', 'Cash'], ['1200', 'Member Shares'], 'Share purchase'],
            [['5100', 'Operating Expenses'], ['1010', 'Bank'], 'Office running costs'],
        ];
        for ($i = 0; $i < 18; $i++) {
            $s = $this->pick($scenarios);
            $amt = $this->round($this->int(20, 400) * 1000);
            $entry = JournalEntry::create([
                'organization_id' => $this->org->id,
                'reference' => 'JE-2026-'.str_pad((string) ($i + 1), 5, '0', STR_PAD_LEFT),
                'description' => $s[2],
                'entry_date' => $this->daysAgo($this->int(1, 150))->toDateString(),
                'posted_by' => $this->pick(['A. Kessy (Accountant)', 'T. Mushi (Treasurer)', 'System']),
            ]);
            JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $byCode[$s[0][0]]->id, 'debit' => $amt, 'credit' => 0]);
            JournalLine::create(['journal_entry_id' => $entry->id, 'account_id' => $byCode[$s[1][0]]->id, 'debit' => 0, 'credit' => $amt]);
        }
    }

    private function seedProfit($members): void
    {
        $rows = [
            ['2025-01-01', '2025-12-31', 14200000, 2840000, 11360000, 'distributed', '2026-01-20'],
            ['2026-01-01', '2026-06-30', 7680000, 1536000, 6144000, 'calculated', null],
        ];
        foreach ($rows as $r) {
            $dist = ProfitDistribution::create([
                'organization_id' => $this->org->id,
                'period_start' => $r[0], 'period_end' => $r[1], 'total_profit' => $r[2],
                'reserved_amount' => $r[3], 'distributable_profit' => $r[4], 'basis' => 'shares',
                'status' => $r[5], 'distribution_date' => $r[6],
            ]);
            $totalShares = (int) Share::sum('total_value');
            foreach ($members->where('status', 'active')->values() as $m) {
                $v = (int) Share::where('member_id', $m->id)->sum('total_value');
                $pctv = $totalShares > 0 ? $v / $totalShares * 100 : 0;
                ProfitAllocation::create([
                    'profit_distribution_id' => $dist->id,
                    'member_id' => $m->id,
                    'basis' => 'shares',
                    'percentage' => round($pctv, 4),
                    'amount' => (int) round($r[4] * $pctv / 100),
                    'status' => $r[5] === 'distributed' ? 'paid' : 'pending',
                ]);
            }
        }
    }

    private function seedNotifications(): void
    {
        $templates = [
            ['loan_approved', 'Loan approved', 'A loan has been approved and is ready for disbursement.'],
            ['payment_received', 'Payment received', 'A payment has been recorded against a member account.'],
            ['payment_overdue', 'Repayment overdue', 'A loan installment is overdue by 4 days.'],
            ['savings_confirmed', 'Savings confirmed', 'A member deposit has been confirmed.'],
            ['project_completed', 'Project completed', 'Project "Maize Bulk Trading" closed with a profit of TZS 3.45M.'],
            ['profit_distributed', 'Profit distributed', 'The 2025 profit allocation has been credited to members.'],
            ['insurance_expiry', 'Insurance expiring', 'A member insurance cover expires in 12 days.'],
        ];
        for ($i = 0; $i < 14; $i++) {
            $t = $templates[$i % count($templates)];
            Notification::create([
                'organization_id' => $this->org->id,
                'title' => $t[1], 'message' => $t[2], 'type' => $t[0],
                'channel' => $this->pick(['in_app', 'sms', 'email', 'push']),
                'read_at' => $i > 4 ? $this->daysAgo($i) : null,
                'created_at' => $this->daysAgo($i),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedAudit(): void
    {
        $actions = [
            ['APPROVE_LOAN', 'Loan', 'Pending', 'Approved'],
            ['CREATE_MEMBER', 'Member', null, 'Active'],
            ['RECORD_DEPOSIT', 'SavingsTransaction', null, 'Successful'],
            ['UPDATE_SETTINGS', 'Setting', '10%', '12%'],
            ['DISBURSE_LOAN', 'Loan', 'Approved', 'Disbursed'],
            ['REVERSE_TRANSACTION', 'Transaction', 'Successful', 'Reversed'],
            ['VERIFY_PAYMENT', 'Payment', 'Pending', 'Successful'],
        ];
        for ($i = 0; $i < 40; $i++) {
            $a = $actions[$i % count($actions)];
            AuditLog::create([
                'organization_id' => $this->org->id,
                'action' => $a[0], 'entity' => $a[1],
                'entity_id' => strtoupper(substr($a[1], 0, 3)).'-'.str_pad((string) $this->int(1, 300), 6, '0', STR_PAD_LEFT),
                'old_values' => $a[2] ? ['value' => $a[2]] : null,
                'new_values' => ['value' => $a[3]],
                'ip_address' => '196.'.$this->int(0, 255).'.'.$this->int(0, 255).'.'.$this->int(1, 254),
                'created_at' => $this->daysAgo($i * 0.7),
            ]);
        }
    }
}
