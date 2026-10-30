<?php

namespace App\Http\Requests;

use App\Models\Game;
use Illuminate\Foundation\Http\FormRequest;

class GameRequest extends FormRequest
{
    /**
     * Solo los administradores autenticados pueden crear o modificar juegos.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Reglas compartidas por creación y actualización.
     *
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return Game::rules();
    }

    /**
     * Normaliza los campos opcionales: un campo vacío se guarda como NULL.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'release_date' => $this->input('release_date') ?: null,
            'rating' => $this->input('rating') === null || $this->input('rating') === '' ? null : $this->input('rating'),
            'image_url' => $this->input('image_url') ?: null,
        ]);
    }

    /**
     * Mensajes de error en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'max' => 'El campo :attribute no puede superar :max caracteres.',
            'numeric' => 'El campo :attribute debe ser un número.',
            'between' => 'El campo :attribute debe estar entre :min y :max.',
            'date' => 'El campo :attribute debe ser una fecha válida.',
        ];
    }

    /**
     * Nombres legibles de los atributos.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'description' => 'descripción',
            'genre' => 'género',
            'platform' => 'plataforma',
            'release_date' => 'fecha de lanzamiento',
            'rating' => 'valoración',
            'image_url' => 'URL de la imagen',
        ];
    }
}
