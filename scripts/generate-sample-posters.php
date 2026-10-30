<?php

/**
 * Genera las portadas de muestra de GameHub como SVG locales.
 *
 * Uso: php scripts/generate-sample-posters.php
 *
 * No depende de servicios externos: las imágenes se versionan en
 * public/images/games/ y el seeder las referencia con rutas relativas.
 */
$games = [
    ['breath-of-the-wild', 'The Legend of Zelda: Breath of the Wild', 'Action-Adventure', 'Nintendo Switch', '#0b3d2e', '#2f9e72'],
    ['red-dead-redemption-2', 'Red Dead Redemption 2', 'Action-Adventure', 'PlayStation 4', '#3b1414', '#a83a24'],
    ['the-witcher-3', 'The Witcher 3: Wild Hunt', 'RPG', 'PC', '#2b1b0e', '#a06a26'],
    ['elden-ring', 'Elden Ring', 'Action RPG', 'PC', '#15151f', '#8a7434'],
    ['hollow-knight', 'Hollow Knight', 'Metroidvania', 'PC', '#0b1a2a', '#2c5b8c'],
    ['hades', 'Hades', 'Roguelike', 'PC', '#2a0b1a', '#c23a58'],
    ['stardew-valley', 'Stardew Valley', 'Simulation', 'PC', '#152a0b', '#7fae44'],
    ['celeste', 'Celeste', 'Platformer', 'PC', '#1a0b2e', '#d24a74'],
    ['god-of-war', 'God of War', 'Action-Adventure', 'PlayStation 4', '#1c1c1c', '#a32222'],
    ['portal-2', 'Portal 2', 'Puzzle', 'PC', '#0b2a33', '#399eb2'],
];

$output = __DIR__.'/../public/images/games';

if (! is_dir($output) && ! mkdir($output, 0775, true) && ! is_dir($output)) {
    fwrite(STDERR, "No se pudo crear {$output}\n");
    exit(1);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function initials(string $name): string
{
    $words = preg_split('/\s+/', trim($name)) ?: [];
    $words = array_values(array_filter($words, fn ($word) => $word !== '' && ! in_array(mb_strtolower($word), ['of', 'the', 'a', 'y'], true)));

    return mb_strtoupper(implode('', array_map(fn ($word) => mb_substr($word, 0, 1), array_slice($words, 0, 2))));
}

function wrapTitle(string $name, int $maxChars = 20): array
{
    $lines = [];
    $current = '';

    foreach (explode(' ', $name) as $word) {
        $candidate = $current === '' ? $word : $current.' '.$word;

        if (mb_strlen($candidate) > $maxChars && $current !== '') {
            $lines[] = $current;
            $current = $word;
        } else {
            $current = $candidate;
        }
    }

    if ($current !== '') {
        $lines[] = $current;
    }

    return array_slice($lines, 0, 3);
}

$count = 0;

foreach ($games as [$slug, $name, $genre, $platform, $from, $to]) {
    $lines = wrapTitle($name);
    $initials = initials($name);
    $lineHeight = 64;
    $firstY = 1000 - 170 - (count($lines) - 1) * $lineHeight;

    $titleSvg = '';
    foreach ($lines as $index => $line) {
        $y = $firstY + $index * $lineHeight;
        $titleSvg .= sprintf(
            '    <text x="64" y="%d" font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="54" font-weight="700" fill="#ffffff">%s</text>'."\n",
            $y,
            e($line)
        );
    }

    $svg = <<<SVG
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 800 1000" role="img" aria-label="Portada de {$name}">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0" stop-color="{$from}"/>
      <stop offset="1" stop-color="{$to}"/>
    </linearGradient>
    <linearGradient id="fade" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#000000" stop-opacity="0"/>
      <stop offset="1" stop-color="#000000" stop-opacity="0.55"/>
    </linearGradient>
  </defs>

  <rect width="800" height="1000" fill="url(#bg)"/>

  <circle cx="650" cy="180" r="230" fill="#ffffff" fill-opacity="0.08"/>
  <circle cx="140" cy="420" r="150" fill="#ffffff" fill-opacity="0.06"/>
  <circle cx="560" cy="620" r="90" fill="#ffffff" fill-opacity="0.05"/>

  <text x="64" y="120" font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="20" letter-spacing="6" fill="#ffffff" fill-opacity="0.7">GAMEHUB</text>

  <text x="400" y="480" text-anchor="middle" font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="230" font-weight="800" fill="#ffffff" fill-opacity="0.16">{$initials}</text>

  <rect y="620" width="800" height="380" fill="url(#fade)"/>

{$titleSvg}
    <text x="64" y="910" font-family="Segoe UI, Roboto, Helvetica, Arial, sans-serif" font-size="24" fill="#ffffff" fill-opacity="0.82">{$genre} · {$platform}</text>
</svg>

SVG;

    file_put_contents("{$output}/{$slug}.svg", $svg);
    $count++;
}

echo "Generadas {$count} portadas en public/images/games/\n";
