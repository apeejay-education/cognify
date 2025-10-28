@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Lead #{{ $lead->id }} - {{ $lead->name }}</h1>

    <div class="mb-4">
        <p><strong>Email:</strong> {{ $lead->email }}</p>
        <p><strong>Phone:</strong> {{ $lead->phone }}</p>
        <p><strong>Score:</strong> {{ $lead->score }}</p>
        <p><strong>Status:</strong> {{ $lead->status }}</p>
    </div>

    <div class="mb-6">
        <form method="post" action="{{ url('/admin/leads/'.$lead->id.'/assign') }}">
            @csrf
            <label>Assign to counselor</label>
            <select name="counselor_id">
                <option value="">-- Select --</option>
                @foreach($counselors as $c)
                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->email }})</option>
                @endforeach
            </select>
            <button class="bg-green-500 text-white px-3 py-1">Assign</button>
        </form>
    </div>

    <div>
        <h3 class="font-bold mb-2">Activities</h3>
        <ul>
            @foreach($activities as $act)
                <li>{{ $act->created_at }} - <strong>{{ $act->type }}</strong> - {{ $act->notes }}</li>
            @endforeach
        </ul>
    </div>

    <div class="mt-4">
        <form method="post" action="{{ url('/admin/leads/'.$lead->id.'/activities') }}">
            @csrf
            <label>Type</label>
            <input name="type" />
            <label>Notes</label>
            <input name="notes" />
            <button class="bg-blue-500 text-white px-3 py-1">Add Activity</button>
        </form>
    </div>

</div>
@endsection
