<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectInvestment;
use App\Support\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::where('organization_id', app('kikoba.org')->id)
            ->withCount('investments as participant_count')->latest()->get();

        return view('admin.projects.index', compact('projects'));
    }

    public function create()
    {
        return view('admin.projects.form', ['project' => new Project(['type' => 'monthly', 'status' => 'planned'])]);
    }

    public function edit(Project $project)
    {
        return view('admin.projects.form', compact('project'));
    }

    public function show(Project $project)
    {
        $project->load('investments.member')->loadCount('investments as participant_count');

        return view('admin.projects.show', compact('project'));
    }

    public function store(Request $request)
    {
        $p = Project::create([...$this->validated($request), 'organization_id' => app('kikoba.org')->id, 'capital_raised' => 0, 'actual_profit' => 0]);
        Audit::log($request, 'CREATE_PROJECT', 'Project', $p->id);

        return redirect()->route('admin.projects.show', $p)->with('toast', t('common.save').' ✓');
    }

    public function update(Request $request, Project $project)
    {
        $project->update($this->validated($request));
        Audit::log($request, 'UPDATE_PROJECT', 'Project', $project->id);

        return redirect()->route('admin.projects.show', $project)->with('toast', t('common.saveChanges').' ✓');
    }

    public function destroy(Request $request, Project $project)
    {
        if ($project->investments()->exists()) {
            $project->update(['status' => 'cancelled']);
            Audit::log($request, 'CANCEL_PROJECT', 'Project', $project->id);

            return redirect()->route('admin.projects.show', $project)->with('toast', t('projects.cancelledInstead'));
        }
        $project->delete();
        Audit::log($request, 'DELETE_PROJECT', 'Project', $project->id);

        return redirect()->route('admin.projects.index')->with('toast', t('common.deleted'));
    }

    public function invest(Request $request, Project $project)
    {
        if (! in_array($project->status, ['planned', 'active'], true)) {
            return back()->with('toast', t('projects.closedForInvestment'))->with('toast_tone', 'error');
        }
        $data = $request->validate([
            'member_id' => ['required', Rule::exists('members', 'id')->where('organization_id', $project->organization_id)],
            'amount' => ['required', 'integer', 'min:1'],
        ]);
        ProjectInvestment::create([
            'organization_id' => $project->organization_id, 'project_id' => $project->id,
            'member_id' => $data['member_id'], 'amount' => $data['amount'], 'status' => 'active',
            'invested_at' => now()->toDateString(),
        ]);
        $project->increment('capital_raised', $data['amount']);
        Audit::log($request, 'PROJECT_INVESTMENT', 'Project', $project->id);

        return back()->with('toast', t('projects.invest').' ✓');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', 'in:monthly,three_months,long_term,custom'],
            'capital_required' => ['required', 'integer', 'min:0'],
            'expected_profit' => ['nullable', 'integer', 'min:0'],
            'actual_profit' => ['nullable', 'integer', 'min:0'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'manager' => ['nullable', 'string', 'max:120'],
            'status' => ['required', 'in:planned,active,completed,cancelled'],
        ]);
    }
}
