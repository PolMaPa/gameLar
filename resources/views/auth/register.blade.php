@extends('layouts.app')

@section('title', 'Crear cuenta')

@section('content')
    <div class="mx-auto max-w-md">
        <x-page-header title="Crear cuenta"
                       description="Regístrate para añadir y mantener los videojuegos del catálogo." />

        <div class="card fade-up p-6 sm:p-8">
            <form method="POST" action="{{ route('register') }}" class="space-y-6">
                @csrf

                <x-form-field name="name"
                              label="Nombre"
                              required
                              autocomplete="name"
                              placeholder="Tu nombre" />

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
                              autocomplete="new-password"
                              help="Mínimo 8 caracteres. En producción se exigen mayúsculas y minúsculas." />

                <x-form-field name="password_confirmation"
                              label="Confirmar contraseña"
                              type="password"
                              required
                              autocomplete="new-password" />

                <button type="submit" class="btn btn-primary w-full">Crear cuenta</button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-400">
                ¿Ya tienes cuenta?
                <a href="{{ route('login') }}" class="font-semibold text-brand-300 transition hover:text-brand-200">
                    Iniciar sesión
                </a>
            </p>
        </div>
    </div>
@endsection
