<?php

namespace App\Http\Controllers\Api;

use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Support\ApiResponse;
use App\Support\Audit;
use Illuminate\Http\Request;

class ProjectController extends ApiController
{
    public function index(Request $request)
    {
        $projects = Project::where('organization_id', $this->orgId($request))
            ->withCount('investments as participant_count')
            ->orderByDesc('created_at')->get();

        return $this->items($projects, fn (Project $p) => $this->row($p));
    }

    public function show(Request $request, string $id)
    {
        $p = Project::where('organization_id', $this->orgId($request))
            ->with(['investments.member:id,full_name,member_number,avatar_color'])
            ->withCount('investments as participant_count')
            ->findOrFail($id);

        return $this->item($p, fn ($p) => [
            ...$this->row($p),
            'investments' => $p->investments->map(fn ($i) => [
                'id' => $i->id,
                'member' => $i->member ? ['id' => $i->member->id, 'fullName' => $i->member->full_name, 'memberNumber' => $i->member->member_number, 'avatarColor' => $i->member->avatar_color] : null,
                'amount' => $i->amount,
                'profitShare' => $i->profit_share,
                'status' => $i->status,
                'investedAt' => $i->invested_at?->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string'],
            'description' => ['nullable', 'string'],
            'type' => ['required', 'in:monthly,three_months,long_term,custom'],
            'capital_required' => ['required', 'integer', 'min:0'],
            'expected_profit' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'manager' => ['nullable', 'string'],
        ]);
        $project = Project::create([...$data, 'organization_id' => $this->orgId($request), 'status' => 'planned']);
        Audit::log($request, 'CREATE_PROJECT', 'Project', $project->id);

        return ApiResponse::created($this->row($project), 'Project created');
    }

    public function invest(Request $request, string $id)
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        $project = Project::where('organization_id', $this->orgId($request))->findOrFail($id);
        $investment = ProjectInvestment::create([
            'organization_id' => $project->organization_id,
            'project_id' => $project->id,
            'member_id' => $data['member_id'],
            'amount' => $data['amount'],
            'status' => 'active',
            'invested_at' => now()->toDateString(),
        ]);
        $project->increment('capital_raised', $data['amount']);
        Audit::log($request, 'PROJECT_INVESTMENT', 'ProjectInvestment', $investment->id, null, ['amount' => $data['amount']]);

        return ApiResponse::created((new \App\Http\Resources\GenericResource($investment))->resolve(), 'Investment recorded');
    }

    private function row(Project $p): array
    {
        return [
            'id' => $p->id,
            'name' => $p->name,
            'description' => $p->description,
            'type' => $p->type,
            'capitalRequired' => $p->capital_required,
            'capitalRaised' => $p->capital_raised,
            'expectedProfit' => $p->expected_profit,
            'actualProfit' => $p->actual_profit,
            'startDate' => $p->start_date?->toDateString(),
            'endDate' => $p->end_date?->toDateString(),
            'status' => $p->status,
            'manager' => $p->manager,
            'participantCount' => $p->participant_count ?? $p->investments()->count(),
        ];
    }
}
