<?php

namespace App\Forms;

use InvalidArgumentException;

class FormRegistry
{
    /** @var array<string, array<string, class-string<BaseForm>>> */
    private static array $forms = [];

    public static function register(string $site, string $class): void
    {
        $instance = new $class();
        static::$forms[$site][$instance->slug] = $class;
    }

    public static function resolve(string $site, string $slug): BaseForm
    {
        if (! static::has($site, $slug)) {
            throw new InvalidArgumentException("Formulaire introuvable : {$site}/{$slug}");
        }

        return new static::$forms[$site][$slug]();
    }

    public static function has(string $site, string $slug): bool
    {
        return isset(static::$forms[$site][$slug]);
    }

    /** @return array<string, array<string, class-string<BaseForm>>> */
    public static function all(): array
    {
        return static::$forms;
    }
}
