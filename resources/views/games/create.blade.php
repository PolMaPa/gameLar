@extends('layouts.app')

@section('title', 'Nuevo juego')

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header title="Añadir un videojuego"
                       description="Completa la ficha del juego. Los campos marcados con * son obligatorios."
                       :back-route="route('games.index')"
                       back-label="Volver al catálogo" />

        <div class="card fade-up p-6 sm:p-8">
            @include('games._form', [
                'game' => $game,
                'action' => route('games.store'),
                'method' => 'POST',
                'submitLabel' => 'Crear juego',
            ])
        </div>
    </div>
@endsection
