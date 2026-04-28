<?php

namespace App\Http\Controllers;

use App\Forms\FormRegistry;
use Illuminate\Http\Request;

class FormController extends Controller
{
    public function show(Request $request, string $site, string $slug)
    {
        abort_unless(FormRegistry::has($site, $slug), 404);

        $form = FormRegistry::resolve($site, $slug);

        $bg    = $this->sanitizeHex($request->query('bg', 'ffffff'));
        $color = $this->sanitizeHex($request->query('color', '1a1a1a'));

        return view('form.show', compact('form', 'bg', 'color'));
    }

    private function sanitizeHex(string $value): string
    {
        $value = ltrim($value, '#');

        return preg_match('/^[0-9a-fA-F]{3,6}$/', $value) ? $value : 'ffffff';
    }
}
