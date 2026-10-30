@extends('layouts.app')

@section('title', 'Iniciar sesión')

@section('content')
    <div class="mx-auto max-w-md">
        <x-page-header title="Iniciar sesión"
                       description="Accede para gestionar el catálogo de GameHub." />

        <div class="card fade-up p-6 sm:p-8">
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <x-form-field name="email"
                              label="Correo electrónico"
                              type="email"
                              required
                              autocomplete="username"
                              placeholder="tu@correo.com" />

                <x-form-field name="password"
                              label="Contraseña"
                              type="password"
                              required
                              autocomplete="current-password" />

                <label class="flex items-center gap-2 text-sm text-slate-400">
                    <input type="checkbox"
                           name="remember"
                           class="size-4 rounded border-white/20 bg-white/5 text-brand-500 focus:ring-brand-500">
                    Recordarme
                </label>

                <button type="submit" class="btn btn-primary w-full">Entrar</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                ¿No tienes cuenta?
                <a href="{{ route('register') }}" class="font-semibold text-brand-300 transition hover:text-brand-200">
                    Crear una cuenta
                </a>
            </p>
        </div>
    </div>
@endsection
