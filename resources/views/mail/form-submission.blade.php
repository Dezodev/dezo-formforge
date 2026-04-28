<x-mail::message>
# {{ $title }}

Nouvelle soumission reçue le **{{ $date }}**.

<x-mail::table>
| Champ | Valeur |
|:------|:-------|
@foreach ($fields as $key => $value)
| {{ ucfirst(str_replace('_', ' ', $key)) }} | {!! is_array($value) ? e(implode(', ', $value)) : str_replace(["\r\n", "\r", "\n"], '<br>', e($value)) !!} |
@endforeach
</x-mail::table>

—
*Envoyé automatiquement par FormForge*
</x-mail::message>
