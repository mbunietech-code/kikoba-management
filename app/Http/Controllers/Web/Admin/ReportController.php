<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InsuranceAccount;
use App\Models\InsuranceClaim;
use App\Models\Loan;
use App\Models\Member;
use App\Models\ProfitDistribution;
use App\Models\Project;
use App\Models\SavingsAccount;
use App\Models\Share;
use App\Support\Audit;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const TYPES = ['membership', 'shares', 'savings', 'loans', 'profit', 'projects', 'insurance', 'financial'];

    public function index()
    {
        return view('admin.reports.index', ['types' => self::TYPES]);
    }

    public function download(Request $request, string $type): StreamedResponse
    {
        abort_unless(in_array($type, self::TYPES, true), 404);

        $org = app('kikoba.org')->id;
        [$headers, $rows] = match ($type) {
            'membership' => $this->membership($org),
            'shares' => $this->shares($org),
            'savings' => $this->savings($org),
            'loans' => $this->loans($org),
            'profit' => $this->profit($org),
            'projects' => $this->projects($org),
            'insurance' => $this->insurance($org),
            'financial' => $this->financial($org),
        };

        Audit::log($request, 'EXPORT_REPORT', 'Report', null, null, ['type' => $type]);

        $filename = sprintf('%s-report-%s.csv', $type, now()->format('Y-m-d'));

        return response()->streamDownload(function () use ($headers, $rows) {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM for Excel
            fputcsv($out, $headers);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* ------------------------------ builders ------------------------------ */

    private function membership(string $org): array
    {
        $rows = Member::where('organization_id', $org)
            ->withSum('shares as sv', 'total_value')
            ->with('savingsAccount:id,member_id,balance')
            ->orderBy('member_number')->get()
            ->map(fn (Member $m) => [
                $m->member_number, $m->full_name, $m->phone, $m->email, $m->gender,
                $m->community_group, $m->status, optional($m->registration_date)->toDateString(),
                (int) $m->sv, (int) ($m->savingsAccount?->balance ?? 0),
            ]);

        return [['Member no.', 'Full name', 'Phone', 'Email', 'Gender', 'Jamii', 'Status', 'Joined', 'Shares value', 'Savings balance'], $rows];
    }

    private function shares(string $org): array
    {
        $rows = Share::where('organization_id', $org)->with('member:id,full_name,member_number')
            ->latest('purchased_at')->get()
            ->map(fn (Share $s) => [
                $s->transaction_reference, $s->member?->member_number, $s->member?->full_name,
                $s->kind, $s->quantity, $s->price_per_share, $s->total_value,
                optional($s->purchased_at)->toDateString(), $s->status,
            ]);

        return [['Reference', 'Member no.', 'Member', 'Kind', 'Quantity', 'Price/share', 'Total value', 'Date', 'Status'], $rows];
    }

    private function savings(string $org): array
    {
        $rows = SavingsAccount::where('organization_id', $org)->with('member:id,full_name,member_number')
            ->orderByDesc('balance')->get()
            ->map(fn (SavingsAccount $a) => [
                $a->account_number, $a->member?->member_number, $a->member?->full_name,
                $a->balance, $a->status, optional($a->opened_at)->toDateString(),
            ]);

        return [['Account no.', 'Member no.', 'Member', 'Balance', 'Status', 'Opened'], $rows];
    }

    private function loans(string $org): array
    {
        $rows = Loan::where('organization_id', $org)->with('member:id,full_name,member_number', 'product:id,name')
            ->latest('application_date')->get()
            ->map(fn (Loan $l) => [
                $l->loan_number, $l->member?->member_number, $l->member?->full_name, $l->product?->name,
                $l->principal_amount, $l->interest_amount, $l->total_amount, $l->amount_paid, $l->outstanding_balance,
                $l->status, optional($l->application_date)->toDateString(), optional($l->disbursement_date)->toDateString(),
                optional($l->maturity_date)->toDateString(),
            ]);

        return [['Loan no.', 'Member no.', 'Member', 'Product', 'Principal', 'Interest', 'Total', 'Paid', 'Outstanding', 'Status', 'Applied', 'Disbursed', 'Maturity'], $rows];
    }

    private function profit(string $org): array
    {
        $rows = ProfitDistribution::where('organization_id', $org)->latest('period_end')->get()
            ->map(fn (ProfitDistribution $p) => [
                optional($p->period_start)->toDateString(), optional($p->period_end)->toDateString(),
                $p->total_profit, $p->reserved_amount, $p->distributable_profit, $p->basis, $p->status,
                optional($p->distribution_date)->toDateString(),
            ]);

        return [['Period start', 'Period end', 'Total profit', 'Reserved', 'Distributable', 'Basis', 'Status', 'Distributed on'], $rows];
    }

    private function projects(string $org): array
    {
        $rows = Project::where('organization_id', $org)->latest('start_date')->get()
            ->map(fn (Project $p) => [
                $p->name, $p->type, $p->status, $p->capital_required, $p->capital_raised,
                $p->expected_profit, $p->actual_profit,
                optional($p->start_date)->toDateString(), optional($p->end_date)->toDateString(), $p->manager,
            ]);

        return [['Project', 'Type', 'Status', 'Capital required', 'Capital raised', 'Expected profit', 'Actual profit', 'Start', 'End', 'Manager'], $rows];
    }

    private function insurance(string $org): array
    {
        $accounts = InsuranceAccount::where('organization_id', $org)->with('member:id,full_name,member_number')->get()
            ->map(fn (InsuranceAccount $a) => [
                'ACCOUNT', $a->member?->member_number, $a->member?->full_name, $a->plan_name,
                $a->monthly_contribution, $a->coverage_amount, (int) $a->contributions()->sum('amount'), $a->status,
            ]);
        $claims = InsuranceClaim::where('organization_id', $org)->with('member:id,full_name,member_number')->get()
            ->map(fn (InsuranceClaim $c) => [
                'CLAIM', $c->member?->member_number, $c->member?->full_name, $c->claim_type,
                $c->amount_requested, $c->amount_approved, '', $c->status,
            ]);

        return [['Row', 'Member no.', 'Member', 'Plan / claim type', 'Monthly / requested', 'Coverage / approved', 'Total contributed', 'Status'], $accounts->concat($claims)];
    }

    private function financial(string $org): array
    {
        $rows = Account::where('organization_id', $org)->orderBy('account_code')->get()
            ->map(fn (Account $a) => [$a->account_code, $a->name, $a->account_type, $a->balance]);

        return [['Code', 'Account', 'Type', 'Balance'], $rows];
    }
}
