<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Lead;
use App\Models\Counselor;
use App\Models\LeadActivity;

class LeadAdminController extends Controller
{
    public function index(Request $request)
    {
        $q = Lead::query();
        if ($request->filled('search')) {
            $term = "%" . $request->input('search') . "%";
            $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
        }

        $leads = $q->latest()->paginate(15);
        return view('admin.leads.index', compact('leads'));
    }

    public function show(Lead $lead)
    {
        $counselors = Counselor::all();
        $activities = $lead->activities()->latest()->get();
        return view('admin.leads.show', compact('lead','counselors','activities'));
    }

    public function assign(Request $request, Lead $lead)
    {
        $request->validate(['counselor_id' => 'required|exists:counselors,id']);
        $lead->assigned_to = $request->counselor_id;
        $lead->status = 'assigned';
        $lead->save();

        LeadActivity::create([
            'lead_id' => $lead->id,
            'counselor_id' => $request->counselor_id,
            'type' => 'assignment',
            'notes' => 'Automatically assigned via admin',
        ]);

        return redirect()->back()->with('status','Assigned');
    }

    public function addActivity(Request $request, Lead $lead)
    {
        $request->validate(['type' => 'required|string','notes'=>'nullable|string','counselor_id'=>'nullable|exists:counselors,id']);
        LeadActivity::create([
            'lead_id' => $lead->id,
            'counselor_id' => $request->counselor_id,
            'type' => $request->type,
            'notes' => $request->notes,
        ]);

        return redirect()->back()->with('status','Activity added');
    }
}
