@extends('layouts.app')

@section('title', 'Editar ' . $game->name)

@section('content')
    <div class="mx-auto max-w-3xl">
        <x-page-header :title="'Editar «' . $game->name . '»'"
                       description="Modifica los datos y guarda los cambios. Los campos marcados con * son obligatorios."
                       :back-route="route('games.show', $game)"
                       back-label="Volver al detalle" />

        <div class="card fade-up p-6 sm:p-8">
            @include('games._form', [
                'game' => $game,
                'action' => route('games.update', $game),
                'method' => 'PUT',
                'submitLabel' => 'Guardar cambios',
            ])
        </div>
    </div>
@endsection
