<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private const TYPE_LV = [
        'normal' => 'Normāls',
        'fighting' => 'Cīņas',
        'flying' => 'Lidojošs',
        'poison' => 'Indes',
        'ground' => 'Zemes',
        'rock' => 'Akmens',
        'bug' => 'Kukaiņu',
        'ghost' => 'Spoku',
        'steel' => 'Tērauda',
        'fire' => 'Uguns',
        'water' => 'Ūdens',
        'grass' => 'Zāles',
        'electric' => 'Elektriskais',
        'psychic' => 'Psihiskais',
        'ice' => 'Ledus',
        'dragon' => 'Pūķa',
        'dark' => 'Tumšais',
        'fairy' => 'Feju',
        'stellar' => 'Zvaigžņu',
        'unknown' => 'Nezināms',
    ];

    public function getFilters(): array
    {
        return [
            new TwigFilter('pokemon_lv', [$this, 'latvianizePokemonName']),
            new TwigFilter('type_lv', [$this, 'translateType']),
        ];
    }

    public function latvianizePokemonName(string $name): string
    {
        $name = trim(strtolower($name));

        if (str_ends_with($name, 's')) {
            return $name;
        }

        return $name . 's';
    }

    public function translateType(string $type): string
    {
        $key = trim(strtolower($type));

        return self::TYPE_LV[$key] ?? ucfirst($type);
    }
}
