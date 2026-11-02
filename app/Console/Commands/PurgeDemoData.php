<?php

namespace App\Console\Commands;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Guarantor;
use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\InsuranceContribution;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Notification;
use App\Models\Payment;
use App\Models\ProfitAllocation;
use App\Models\ProfitDistribution;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Models\SavingsAccount;
use App\Models\SavingsTransaction;
use App\Models\Share;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Removes the sample records DemoSeeder created (three sample members and
 * everything attached to them, the sample project, insurance, profit run,
 * notification, loan product and journal entry), then resets every ledger
 * account balance to what its journal lines add up to.
 *
 * Shows what it would do unless --force is given.
 */
class PurgeDemoData extends Command
{
    protected $signature = 'kikoba:purge-demo {--force : Actually delete (otherwise only report)}';

    protected $description = 'Report, and with --force remove, the DemoSeeder sample records';

    /** @var array<string, string> member number => e-mail, exactly as DemoSeeder created them */
    private const DEMO_MEMBERS = [
        'MBR-000001' => 'member@mfano.co.tz',
        'MBR-000002' => 'neema.mrema@mfano.co.tz',
        'MBR-000003' => 'juma.shirima@mfano.co.tz',
    ];

    public function handle(): int
    {
        $members = Member::withTrashed()
            ->whereIn('member_number', array_keys(self::DEMO_MEMBERS))
            ->get()
            ->filter(fn (Member $m) => self::DEMO_MEMBERS[$m->member_number] === $m->email);

        if ($members->isEmpty()) {
            $this->info('No demo members found. Nothing to do.');

            return self::SUCCESS;
        }
        if ($active = $members->reject->trashed()->pluck('member_number')->all()) {
            $this->error('These demo members are not archived, so they may be in real use: '.implode(', ', $active).'. Archive them first.');

            return self::FAILURE;
        }

        $plan = $this->plan($members->pluck('id'));
        $this->report($members, $plan);

        if (! $this->option('force')) {
            $this->newLine();
            $this->warn('Dry run — nothing was changed. Run again with --force to delete.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($members, $plan) {
            $this->purge($members, $plan);
            $this->resetBalancesFromJournal();
            AuditLog::create([
                'organization_id' => $members->first()->organization_id,
                'action' => 'PURGE_DEMO_DATA',
                'entity' => 'System',
                'new_values' => collect($plan)->map(fn (Collection $c) => $c->count())->all(),
            ]);
        });

        $this->newLine();
        $this->info('Demo records removed and account balances reset to their journal totals.');

        return self::SUCCESS;
    }

    /**
     * @param  Collection<int, string>  $memberIds
     * @return array<string, Collection<int, mixed>>
     */
    private function plan(Collection $memberIds): array
    {
        $loans = Loan::whereIn('member_id', $memberIds)->get();
        $savingsAccounts = SavingsAccount::whereIn('member_id', $memberIds)->get();
        $insuranceAccounts = InsuranceAccount::whereIn('member_id', $memberIds)->get();

        // Shared records go only when every member involved is a demo member.
        $projects = Project::with('investments')->get()
            ->filter(fn (Project $p) => $p->investments->isNotEmpty() && $p->investments->every(fn ($i) => $memberIds->contains($i->member_id)));
        $distributions = ProfitDistribution::with('allocations')->where('status', '!=', 'distributed')->get()
            ->filter(fn (ProfitDistribution $d) => $d->allocations->isNotEmpty() && $d->allocations->every(fn ($a) => $memberIds->contains($a->member_id)));
        $products = LoanProduct::where('name', 'Normal Loan')->get()
            ->filter(fn (LoanProduct $p) => $p->loans()->whereNotIn('id', $loans->pluck('id'))->doesntExist());
        $journal = JournalEntry::with('lines.account')->where('reference', 'JE-2026-00001')
            ->where('description', 'Member savings deposit')->where('posted_by', 'System')->whereNull('transaction_id')->get();
        $notifications = Notification::where(fn ($q) => $q->whereIn('member_id', $memberIds)
            ->orWhere(fn ($w) => $w->where('type', 'loan_approved')->where('message', 'like', '%LN-000001%')))->get();

        return [
            'loans' => $loans,
            'repayment schedule rows' => LoanRepaymentSchedule::whereIn('loan_id', $loans->pluck('id'))->get(),
            'loan repayments' => LoanRepayment::whereIn('loan_id', $loans->pluck('id'))->orWhereIn('member_id', $memberIds)->get(),
            'guarantors' => Guarantor::whereIn('loan_id', $loans->pluck('id'))->orWhereIn('guarantor_member_id', $memberIds)->orWhereIn('member_id', $memberIds)->get(),
            'loan applications' => LoanApplication::whereIn('member_id', $memberIds)->get(),
            'savings accounts' => $savingsAccounts,
            'savings movements' => SavingsTransaction::whereIn('savings_account_id', $savingsAccounts->pluck('id'))->get(),
            'shares' => Share::whereIn('member_id', $memberIds)->get(),
            'insurance accounts' => $insuranceAccounts,
            'insurance contributions' => InsuranceContribution::whereIn('member_id', $memberIds)->get(),
            'insurance claims' => InsuranceClaim::whereIn('member_id', $memberIds)->get(),
            'project investments' => ProjectInvestment::whereIn('member_id', $memberIds)->get(),
            'projects' => $projects->values(),
            'payments' => Payment::whereIn('member_id', $memberIds)->get(),
            'transactions' => Transaction::whereIn('member_id', $memberIds)->get(),
            'profit allocations' => ProfitAllocation::whereIn('member_id', $memberIds)->get(),
            'profit distributions' => $distributions->values(),
            'notifications' => $notifications,
            'loan products' => $products->values(),
            'journal entries' => $journal,
        ];
    }

    /**
     * @param  Collection<int, Member>  $members
     * @param  array<string, Collection<int, mixed>>  $plan
     */
    private function report(Collection $members, array $plan): void
    {
        $this->line('Demo members (archived): '.$members->map(fn ($m) => "{$m->member_number} {$m->full_name}")->implode(', '));
        $this->table(['Records to delete', 'Count', 'Detail'], collect($plan)->map(fn (Collection $rows, string $label) => [
            $label,
            $rows->count(),
            match ($label) {
                'loans' => $rows->map(fn ($l) => "{$l->loan_number} outstanding ".money($l->outstanding_balance))->implode('; '),
                'savings accounts' => $rows->map(fn ($a) => "{$a->account_number} balance ".money($a->balance))->implode('; '),
                'projects' => $rows->map(fn ($p) => "{$p->name} raised ".money($p->capital_raised))->implode('; '),
                'profit distributions' => $rows->map(fn ($d) => "{$d->period_start->toDateString()}..{$d->period_end->toDateString()} ".money($d->total_profit))->implode('; '),
                'loan products' => $rows->pluck('name')->implode('; '),
                'journal entries' => $rows->map(fn ($e) => "{$e->reference} {$e->description}")->implode('; '),
                default => '',
            },
        ])->values()->all());

        $this->line('Ledger balances after removal (stored → from journal lines):');
        $removedEntryIds = $plan['journal entries']->pluck('id');
        $this->table(['Account', 'Stored now', 'After'], Account::orderBy('account_code')->get()->map(fn (Account $a) => [
            "{$a->account_code} {$a->name}",
            money($a->balance, symbol: false),
            money($this->journalBalance($a, $removedEntryIds), symbol: false),
        ])->all());
    }

    /**
     * @param  Collection<int, Member>  $members
     * @param  array<string, Collection<int, mixed>>  $plan
     */
    private function purge(Collection $members, array $plan): void
    {
        $ids = fn (string $key) => $plan[$key]->pluck('id');

        LoanRepayment::whereIn('id', $ids('loan repayments'))->delete();
        LoanRepaymentSchedule::whereIn('id', $ids('repayment schedule rows'))->delete();
        Guarantor::whereIn('id', $ids('guarantors'))->delete();
        Loan::whereIn('id', $ids('loans'))->delete();
        LoanApplication::whereIn('id', $ids('loan applications'))->delete();
        SavingsTransaction::whereIn('id', $ids('savings movements'))->delete();
        SavingsAccount::whereIn('id', $ids('savings accounts'))->delete();
        Share::whereIn('id', $ids('shares'))->delete();
        InsuranceContribution::whereIn('id', $ids('insurance contributions'))->delete();
        InsuranceClaim::whereIn('id', $ids('insurance claims'))->delete();
        InsuranceAccount::whereIn('id', $ids('insurance accounts'))->delete();
        ProjectInvestment::whereIn('id', $ids('project investments'))->delete();
        Project::withTrashed()->whereIn('id', $ids('projects'))->forceDelete();
        Payment::whereIn('id', $ids('payments'))->delete();
        Transaction::whereIn('id', $ids('transactions'))->delete();
        ProfitAllocation::whereIn('id', $ids('profit allocations'))->delete();
        ProfitDistribution::whereIn('id', $ids('profit distributions'))->delete();
        Notification::whereIn('id', $ids('notifications'))->delete();
        LoanProduct::withTrashed()->whereIn('id', $ids('loan products'))->forceDelete();
        JournalEntry::whereIn('id', $ids('journal entries'))->delete();

        Member::withTrashed()->whereIn('id', $members->pluck('id'))->forceDelete();
    }

    private function resetBalancesFromJournal(): void
    {
        Account::all()->each(fn (Account $a) => $a->update(['balance' => $this->journalBalance($a)]));
    }

    /**
     * Balance on the account's normal side from its journal lines, optionally ignoring some entries.
     *
     * @param  Collection<int, string>|null  $exceptEntryIds
     */
    private function journalBalance(Account $account, ?Collection $exceptEntryIds = null): int
    {
        $lines = DB::table('journal_lines')->where('account_id', $account->id)
            ->when($exceptEntryIds?->isNotEmpty(), fn ($q) => $q->whereNotIn('journal_entry_id', $exceptEntryIds))
            ->selectRaw('coalesce(sum(debit), 0) as dr, coalesce(sum(credit), 0) as cr')->first();

        return in_array($account->account_type, ['asset', 'expense'], true)
            ? (int) $lines->dr - (int) $lines->cr
            : (int) $lines->cr - (int) $lines->dr;
    }
}
