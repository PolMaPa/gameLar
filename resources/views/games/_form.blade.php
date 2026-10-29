<form action="{{ $action }}" method="POST" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <datalist id="genre-options">
        @foreach (['Action-Adventure', 'Action RPG', 'RPG', 'Metroidvania', 'Roguelike', 'Platformer', 'Puzzle', 'Sandbox', 'Simulation', 'Strategy'] as $genre)
            <option value="{{ $genre }}"></option>
        @endforeach
    </datalist>

    <datalist id="platform-options">
        @foreach (['PC', 'PlayStation 5', 'PlayStation 4', 'Xbox Series X|S', 'Xbox One', 'Nintendo Switch'] as $platform)
            <option value="{{ $platform }}"></option>
        @endforeach
    </datalist>

    <x-form-field name="name"
                  label="Nombre"
                  :value="$game->name"
                  required
                  maxlength="150"
                  placeholder="The Legend of Zelda: Breath of the Wild" />

    <x-form-field name="description"
                  label="Descripción"
                  type="textarea"
                  :value="$game->description"
                  :rows="5"
                  required
                  placeholder="De qué trata el juego…" />

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form-field name="genre"
                      label="Género"
                      :value="$game->genre"
                      required
                      maxlength="50"
                      list="genre-options"
                      placeholder="Action-Adventure" />

        <x-form-field name="platform"
                      label="Plataforma"
                      :value="$game->platform"
                      required
                      maxlength="50"
                      list="platform-options"
                      placeholder="Nintendo Switch" />
    </div>

    <div class="grid gap-6 sm:grid-cols-2">
        <x-form-field name="release_date"
                      label="Fecha de lanzamiento"
                      type="date"
                      :value="$game->release_date?->format('Y-m-d')"
                      help="Opcional. Déjala vacía si no hay fecha confirmada." />

        <x-form-field name="rating"
                      label="Valoración (0–10)"
                      type="number"
                      :value="$game->rating"
                      step="0.1"
                      min="0"
                      max="10"
                      placeholder="8.5"
                      help="Opcional. Un decimal, por ejemplo 8.5." />
    </div>

    <x-form-field name="image_url"
                  label="URL de la imagen"
                  :value="$game->image_url"
                  maxlength="2048"
                  placeholder="/images/games/mi-juego.jpg o https://…"
                  help="Opcional. Ruta local o URL absoluta. Si la imagen no carga, se muestra un marcador." />

    <div class="flex flex-col-reverse gap-3 border-t border-white/5 pt-6 sm:flex-row sm:items-center">
        <a href="{{ route('games.index') }}" class="btn btn-ghost">Cancelar</a>
        <button type="submit" class="btn btn-primary sm:ml-auto">{{ $submitLabel }}</button>
    </div>
</form>
