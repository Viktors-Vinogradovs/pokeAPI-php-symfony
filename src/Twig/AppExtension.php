<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('pokemon_lv', [$this, 'latvianizePokemonName']),
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
}
