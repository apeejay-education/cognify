<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Jobs\ProcessIncomingLead;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = Lead::create($request->validated());
        ProcessIncomingLead::dispatch($lead);

        return response()->json(['id' => $lead->id], 201);
    }

    public function index(): JsonResponse
    {
        $leads = Lead::forCurrentTenant()->latest()->paginate(20);
        return response()->json($leads);
    }

    public function show(Lead $lead): JsonResponse
    {
        return response()->json($lead);
    }
}
