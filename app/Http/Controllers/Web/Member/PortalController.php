<?php

namespace App\Http\Controllers\Web\Member;

use App\Http\Controllers\Controller;
use App\Models\CommunityContribution;
use App\Models\Loan;
use App\Models\LoanApplication;
use App\Models\LoanProduct;
use App\Models\LoanRepayment;
use App\Models\LoanRepaymentSchedule;
use App\Models\Member;
use App\Models\Notification;
use App\Models\ProfitAllocation;
use App\Models\Project;
use App\Services\CommunityContributionService;
use App\Services\LoanService;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalController extends Controller
{
    private function member(Request $request): Member
    {
        $m = $request->user()->member;
        abort_unless($m, 403, 'This account is not linked to a member.');

        return $m->load('savingsAccount', 'insuranceAccount');
    }

    private function position(Member $m): array
    {
        $shareValue = (int) $m->shares()->sum('total_value');
        $shareQty = (int) $m->shares()->sum('quantity');
        $loans = $m->loans()->with('product')->get();
        $active = $loans->first(fn ($l) => in_array($l->status, ['active', 'overdue', 'disbursed']));
        $inv = $m->projectInvestments()->get();

        return [
            'shareValue' => $shareValue,
            'shareQty' => $shareQty,
            'savingsBalance' => (int) ($m->savingsAccount?->balance ?? 0),
            'loanOutstanding' => (int) $loans->sum('outstanding_balance'),
            'activeLoan' => $active,
            'loans' => $loans,
            'projectInvestment' => (int) $inv->sum('amount'),
            // Real money only: project profit shares plus approved / paid dividend allocations.
            'profit' => (int) $inv->sum('profit_share') + (int) ProfitAllocation::where('member_id', $m->id)
                ->where('status', 'paid')->sum('amount'),
            'insurance' => $m->insuranceAccount,
        ];
    }

    public function home(Request $request)
    {
        $member = $this->member($request);
        app(LoanService::class)->markOverdue($member->organization_id);
        $pos = $this->position($member);
        $txns = $member->transactions()->latest('created_at')->limit(6)->get();
        $nextRepay = $pos['activeLoan']
            ? LoanRepaymentSchedule::where('loan_id', $pos['activeLoan']->id)
                ->whereIn('status', ['pending', 'partial', 'overdue'])->orderBy('due_date')->first()
            : null;
        $savingsTrend = $this->savingsTrend($member);
        $community = null;
        if (app('kikoba.features')['community'] ?? true) {
            $thisMonth = CommunityContribution::where('member_id', $member->id)->counted()->where('period', now()->format('Y-m'))
                ->selectRaw('fund, sum(amount) as total')->groupBy('fund')->pluck('total', 'fund');
            $community = [
                'rates' => app(CommunityContributionService::class)->rates($member->organization_id),
                'paid' => ['jamii' => (int) ($thisMonth['jamii'] ?? 0), 'dharura' => (int) ($thisMonth['dharura'] ?? 0)],
            ];
        }

        return view('member.home', compact('member', 'pos', 'txns', 'nextRepay', 'savingsTrend', 'community'));
    }

    /**
     * Savings balance at the end of each of the last six months, from the member's own movements.
     *
     * @return array{labels: array<int, string>, balances: array<int, int>, period: string}
     */
    private function savingsTrend(Member $member): array
    {
        $movements = $member->savingsAccount
            ? $member->savingsAccount->transactions()->orderBy('created_at')->get(['created_at', 'balance_after'])
            : collect();
        $months = collect(range(5, 0))->map(fn (int $back) => now()->startOfMonth()->subMonths($back));

        return [
            'labels' => $months->map(fn ($m) => $m->translatedFormat('M'))->all(),
            'balances' => $months->map(fn ($m) => (int) ($movements->filter(fn ($t) => $t->created_at <= $m->copy()->endOfMonth())->last()?->balance_after ?? 0))->all(),
            'period' => $months->first()->translatedFormat('M').' – '.$months->last()->translatedFormat('M Y'),
        ];
    }

    public function shares(Request $request)
    {
        $member = $this->member($request);
        $shares = $member->shares()->latest('purchased_at')->get();

        return view('member.shares', compact('member', 'shares'));
    }

    public function savings(Request $request)
    {
        $member = $this->member($request);
        $account = $member->savingsAccount;
        $txns = $account ? $account->transactions()->orderBy('created_at')->get() : collect();

        return view('member.savings', compact('member', 'account', 'txns'));
    }

    public function loans(Request $request)
    {
        $member = $this->member($request);
        $loans = $member->loans()->with('product')->latest('application_date')->get();

        return view('member.loans', compact('member', 'loans'));
    }

    public function loan(Request $request, Loan $loan)
    {
        abort_unless($loan->member_id === $this->member($request)->id, 404);
        $loan->load('product', 'schedule', 'repayments', 'guarantors.guarantorMember');
        $tab = $request->get('tab', 'schedule');

        return view('member.loan-show', compact('loan', 'tab'));
    }

    public function applyForm(Request $request)
    {
        $member = $this->member($request);
        $products = LoanProduct::where('organization_id', $member->organization_id)->where('status', 'active')->get();
        $others = Member::where('organization_id', $member->organization_id)->where('status', 'active')
            ->whereNot('id', $member->id)->orderBy('full_name')->get(['id', 'full_name', 'member_number']);

        return view('member.loan-apply', compact('member', 'products', 'others'));
    }

    public function apply(Request $request, LoanService $loans)
    {
        $member = $this->member($request);
        $product = LoanProduct::where('organization_id', $member->organization_id)->where('status', 'active')
            ->find($request->input('loan_product_id'));
        $data = $request->validate([
            'loan_product_id' => ['required', Rule::in([$product?->id])],
            'amount' => ['required', 'integer', 'min:'.($product->minimum_amount ?? 1), 'max:'.($product->maximum_amount ?? 0)],
            'period' => ['required', 'integer', 'min:1', 'max:'.($product->repayment_period ?? 1)],
            'purpose' => ['required', 'string', 'max:255'],
        ]);
        if ($member->loans()->whereIn('status', ['draft', 'submitted', 'under_review'])->exists()) {
            return back()->withInput()->with('toast', t('member.applicationPending'))->with('toast_tone', 'error');
        }
        $application = LoanApplication::create([
            'organization_id' => $member->organization_id,
            'member_id' => $member->id,
            'loan_product_id' => $data['loan_product_id'],
            'requested_amount' => $data['amount'],
            'requested_period' => $data['period'],
            'purpose' => $data['purpose'] ?? null,
            'repayment_frequency' => 'monthly',
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);
        $loans->submitApplication($request, $application);

        return redirect()->route('member.loans')->with('toast', t('member.applicationSent'));
    }

    public function repayments(Request $request)
    {
        $member = $this->member($request);
        $loanIds = $member->loans()->pluck('id');
        $history = LoanRepayment::whereIn('loan_id', $loanIds)->with('loan')->latest('payment_date')->get();
        $upcoming = LoanRepaymentSchedule::whereIn('loan_id', $loanIds)
            ->whereIn('status', ['pending', 'partial', 'overdue'])->with('loan')->orderBy('due_date')->get();

        return view('member.repayments', compact('member', 'history', 'upcoming'));
    }

    public function projects(Request $request)
    {
        $member = $this->member($request);
        $investments = $member->projectInvestments()->with('project')->get();

        return view('member.projects', compact('member', 'investments'));
    }

    public function project(Request $request, Project $project)
    {
        $member = $this->member($request);
        $mine = $project->investments()->where('member_id', $member->id)->first();
        $project->loadCount('investments as participant_count');

        return view('member.project-show', compact('project', 'mine'));
    }

    public function insurance(Request $request)
    {
        $member = $this->member($request);
        $account = $member->insuranceAccount;
        $contributions = $account ? $account->contributions()->latest('paid_on')->get() : collect();
        $claims = $member->insuranceClaims()->latest('submitted_at')->get();

        return view('member.insurance', compact('member', 'account', 'contributions', 'claims'));
    }

    public function transactions(Request $request)
    {
        $member = $this->member($request);
        $transactions = $member->transactions()->latest('created_at')->paginate(20);
        $types = $member->transactions()->distinct()->pluck('type');

        return view('member.transactions', compact('member', 'transactions', 'types'));
    }

    public function statements(Request $request)
    {
        $member = $this->member($request);
        $pos = $this->position($member);

        return view('member.statements', compact('member', 'pos'));
    }

    /**
     * The member's own statement as CSV: savings movements, share purchases and
     * loan repayments in the chosen period, with opening and closing savings balance.
     */
    public function downloadStatement(Request $request): StreamedResponse
    {
        $member = $this->member($request);
        $period = in_array($request->get('period'), ['month', 'year', 'all'], true) ? $request->get('period') : 'month';
        $from = match ($period) {
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => null,
        };
        $to = now();

        $savings = $member->savingsAccount
            ? $member->savingsAccount->transactions()->orderBy('created_at')->get()
            : collect();
        $opening = $from ? (int) ($savings->filter(fn ($t) => $t->created_at < $from)->last()?->balance_after ?? 0) : 0;
        $closing = (int) ($savings->last()?->balance_after ?? 0);

        $rows = collect();
        foreach ($savings->filter(fn ($t) => ! $from || $t->created_at >= $from) as $t) {
            $rows->push([$t->created_at, t('nav.savings'), t('savings.txnType.'.$t->type), $t->reference, $t->balance_after - $t->balance_before, $t->balance_after]);
        }
        foreach ($member->shares()->when($from, fn ($q) => $q->where('purchased_at', '>=', $from->toDateString()))->get() as $s) {
            $rows->push([$s->purchased_at, t('nav.shares'), num($s->quantity).' × '.money($s->price_per_share), $s->transaction_reference, $s->total_value, '']);
        }
        $repayments = LoanRepayment::where('member_id', $member->id)->with('loan:id,loan_number')
            ->when($from, fn ($q) => $q->where('payment_date', '>=', $from->toDateString()))->get();
        $community = CommunityContribution::where('member_id', $member->id)->counted()
            ->when($from, fn ($q) => $q->where('paid_on', '>=', $from->toDateString()))->get();
        foreach ($community as $c) {
            $rows->push([$c->paid_on, t('community.title'), t('community.'.$c->fund).' '.$c->period, $c->reference, $c->amount, '']);
        }
        foreach ($repayments as $r) {
            $rows->push([$r->payment_date, t('nav.loans'), t('loans.recordRepayment').' '.$r->loan?->loan_number, $r->reference, $r->total_paid, '']);
        }
        $rows = $rows->sortBy(fn ($row) => Carbon::parse($row[0])->timestamp)->values();

        Audit::log($request, 'DOWNLOAD_STATEMENT', 'Member', $member->id, null, ['period' => $period]);
        $filename = sprintf('statement-%s-%s.csv', $member->member_number, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($member, $from, $to, $opening, $closing, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, [$member->full_name, $member->member_number]);
            fputcsv($out, [t('statements.period'), ($from ? fdate($from) : '—').' – '.fdate($to)]);
            fputcsv($out, [t('statements.opening').' ('.t('nav.savings').')', $opening]);
            fputcsv($out, []);
            fputcsv($out, [t('common.date'), t('statements.section'), t('common.description'), t('common.reference'), t('common.amount'), t('savings.balanceAfter')]);
            foreach ($rows as $row) {
                fputcsv($out, [fdate($row[0]), $row[1], $row[2], $row[3], $row[4], $row[5]]);
            }
            fputcsv($out, []);
            fputcsv($out, [t('statements.closing').' ('.t('nav.savings').')', $closing]);
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function notifications(Request $request)
    {
        $notifications = Notification::visibleTo($request->user(), $this->member($request)->organization_id)
            ->latest('created_at')->paginate(30);

        return view('member.notifications', compact('notifications'));
    }

    public function profile(Request $request)
    {
        $member = $this->member($request);

        return view('member.profile', compact('member'));
    }

    public function updateProfile(Request $request)
    {
        $member = $this->member($request);
        $member->update($request->validate([
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'next_of_kin' => ['nullable', 'string', 'max:120'],
            'next_of_kin_phone' => ['nullable', 'string', 'max:20'],
        ]));
        Audit::log($request, 'UPDATE_OWN_PROFILE', 'Member', $member->id);

        return back()->with('toast', t('common.saveChanges').' ✓');
    }
}
