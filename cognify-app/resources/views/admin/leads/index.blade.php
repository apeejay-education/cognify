@extends('layouts.app')

@section('content')
<div class="container mx-auto p-6">
    <h1 class="text-2xl font-bold mb-4">Leads</h1>

    <form class="mb-4" method="get" action="{{ url('/admin/leads') }}">
        <input type="text" name="search" placeholder="Search by name or email" value="{{ request('search') }}" class="border p-2" />
        <button class="bg-blue-500 text-white px-3 py-2">Search</button>
    </form>

    <table class="w-full table-auto">
        <thead>
            <tr class="text-left">
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Score</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($leads as $lead)
            <tr>
                <td>{{ $lead->id }}</td>
                <td>{{ $lead->name }}</td>
                <td>{{ $lead->email }}</td>
                <td>{{ $lead->score }}</td>
                <td>{{ $lead->status }}</td>
                <td><a href="{{ url('/admin/leads/'.$lead->id) }}" class="text-blue-600">View</a></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="mt-6">
        {{ $leads->links() }}
    </div>
</div>
@endsection
