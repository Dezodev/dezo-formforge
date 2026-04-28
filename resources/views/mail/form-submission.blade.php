<x-mail::message>
# {{ $title }}

Nouvelle soumission reçue le **{{ $date }}**.

<x-mail::table>
| Champ | Valeur |
|:------|:-------|
@foreach ($fields as $key => $value)
| {{ ucfirst(str_replace('_', ' ', $key)) }} | {{ is_array($value) ? implode(', ', $value) : $value }} |
@endforeach
</x-mail::table>

—
*Envoyé automatiquement par FormForge*
</x-mail::message>
