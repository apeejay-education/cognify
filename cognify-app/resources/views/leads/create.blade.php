@extends('layouts.app')

@section('content')
    <h1 class="text-2xl font-semibold mb-4">Submit a Lead</h1>

    @if(session('status'))
        <div class="mb-4 text-green-700">{{ session('status') }}</div>
    @endif

    @if($errors->any())
        <div class="mb-4 text-red-700">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="/leads">
        @csrf

        <div class="mb-4">
            <label class="block text-sm font-medium">Name</label>
            <input name="name" value="{{ old('name') }}" class="mt-1 block w-full border rounded p-2" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Email</label>
            <input name="email" value="{{ old('email') }}" class="mt-1 block w-full border rounded p-2" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Phone</label>
            <input name="phone" value="{{ old('phone') }}" class="mt-1 block w-full border rounded p-2" />
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium">Course Interest</label>
            <input name="course_interest" value="{{ old('course_interest') }}" class="mt-1 block w-full border rounded p-2" />
        </div>

        <div class="flex justify-end">
            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Submit</button>
        </div>
    </form>

@endsection
