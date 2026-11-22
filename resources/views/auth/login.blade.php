@extends('nyumbani::layouts.auth')
@section('title', __('Login to continue'))
@section('heading', __('Login to continue'))
@section('description', __('Please enter your login credentials to continue'))

@section('content')
    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="mb-5">
            <label for="{{ config('fortify.username', 'email') }}">{{ prettify(config('fortify.username', 'email')) }}</label>
            <div class="relative">
                <span data-icon class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg></span>
                <input aria-invalid="{{ $errors->has(config('fortify.username', 'email')) ? 'true' : 'false' }}" id="{{ config('fortify.username', 'email') }}" name="{{ config('fortify.username', 'email') }}" type="{{ config('fortify.username', 'email') === 'email' ? 'email' : 'text' }}" value="{{ old(config('fortify.username', 'email')) }}" placeholder="{{ __('Enter your :field', ['field' => strtolower(prettify(config('fortify.username', 'email')))]) }}" autocomplete="username" required autofocus>
            </div>
            @error(config('fortify.username', 'email'))
                <span class="mt-1 block text-xs text-red-600" role="alert">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-5">
            <x-nyumbani::password-input name="password" label="{{ __('Password') }}" placeholder="{{ __('Enter your password') }}" autocomplete="current-password" icon required />
        </div>

        <div class="mb-7 flex items-center justify-between">
            <label class="mb-0 inline-flex cursor-pointer items-center" for="remember">
                <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember'))>
                <span class="ml-2 text-sm font-medium text-gray-700">{{ __('Remember me') }}</span>
            </label>
            <a href="{{ route('password.request') }}" class="text-sm font-medium text-primary-dark underline-offset-2 hover:text-primary hover:underline">{{ __('Forgot password?') }}</a>
        </div>

        <button type="submit" class="btn btn-primary btn-lg w-full uppercase tracking-wide">{{ __('Sign In') }}</button>
    </form>
@endsection
