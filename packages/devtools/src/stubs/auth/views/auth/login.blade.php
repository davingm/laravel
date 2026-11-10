@extends('layouts.app')

@section('title', 'Login')

@section('content')
<main class="mx-auto flex min-h-screen max-w-md flex-col justify-center px-6 py-12">
    <h1 class="text-2xl font-semibold">Login</h1>

    <form method="POST" action="{{ route('login.store') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium">Password</label>
            <input id="password" name="password" type="password" required
                class="mt-1 w-full rounded border border-gray-300 px-3 py-2">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="rounded bg-gray-950 px-4 py-2 text-white">Masuk</button>
    </form>
</main>
@endsection
