<?php

namespace App\Models;

use Database\Factories\GameFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

class Game extends Model
{
    /** @use HasFactory<GameFactory> */
    use HasFactory;

    /**
     * Segunda barrera de seguridad: aunque la validación de los formularios
     * es la primera, el modelo también rechaza notas fuera de 0–10.
     */
    protected static function booted(): void
    {
        static::saving(function (Game $game): void {
            if ($game->rating === null) {
                return;
            }

            if (! is_numeric($game->rating) || $game->rating < 0 || $game->rating > 10) {
                throw new InvalidArgumentException('La valoración debe ser un número entre 0 y 10.');
            }
        });
    }

    /**
     * Campos asignables en masa.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'description',
        'genre',
        'platform',
        'release_date',
        'rating',
        'image_url',
    ];

    /**
     * Conversión de tipos al leer de la base de datos.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_date' => 'date:Y-m-d',
            'rating' => 'decimal:1',
        ];
    }

    /**
     * Reglas de validación reutilizables por futuras peticiones (Form Requests).
     *
     * @return array<string, list<string>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['required', 'string'],
            'genre' => ['required', 'string', 'max:50'],
            'platform' => ['required', 'string', 'max:50'],
            'release_date' => ['nullable', 'date'],
            'rating' => ['nullable', 'numeric', 'between:0,10'],
            'image_url' => ['nullable', 'string', 'max:2048'],
        ];
    }
}
