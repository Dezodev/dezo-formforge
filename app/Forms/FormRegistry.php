<?php

namespace App\Forms;

use InvalidArgumentException;

class FormRegistry
{
    /** @var array<string, class-string<BaseForm>> */
    private static array $forms = [];

    public static function register(string $class): void
    {
        $instance = new $class();
        static::$forms[$instance->slug] = $class;
    }

    public static function resolve(string $slug): BaseForm
    {
        if (! isset(static::$forms[$slug])) {
            throw new InvalidArgumentException("Formulaire introuvable : {$slug}");
        }

        return new static::$forms[$slug]();
    }

    public static function has(string $slug): bool
    {
        return isset(static::$forms[$slug]);
    }
}
