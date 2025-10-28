<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Jobs\ProcessIncomingLead;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;

class LeadFormController extends Controller
{
    public function create()
    {
        return view('leads.create');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $lead = Lead::create($request->validated());
        ProcessIncomingLead::dispatch($lead);

        return redirect()->back()->with('status', 'Lead submitted');
    }
}
