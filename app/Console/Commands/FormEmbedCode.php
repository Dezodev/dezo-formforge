<?php

namespace App\Console\Commands;

use App\Forms\FormRegistry;
use Illuminate\Console\Command;

class FormEmbedCode extends Command
{
    protected $signature = 'form:embed {site? : Identifiant du site} {slug? : Slug du formulaire}
                            {--bg=ffffff : Couleur de fond (hex sans #)}
                            {--color=1a1a1a : Couleur du texte (hex sans #)}';

    protected $description = 'Affiche le code d\'intégration iframe d\'un formulaire';

    public function handle(): int
    {
        $all = FormRegistry::all();

        if (empty($all)) {
            $this->error('Aucun formulaire enregistré.');
            return self::FAILURE;
        }

        [$site, $slug] = $this->resolveForm($all);

        if ($site === null || $slug === null) {
            return self::FAILURE;
        }

        $form = FormRegistry::resolve($site, $slug);
        $bg = $this->option('bg');
        $color = $this->option('color');

        $url = rtrim(config('app.url'), '/') . "/f/{$site}/{$slug}?bg={$bg}&color={$color}";
        $iframeId = "formforge-{$slug}";

        $this->newLine();
        $this->line("<fg=cyan;options=bold>📋 Code d'intégration — {$form->title}</>");
        $this->newLine();

        $code = <<<HTML
<iframe
  src="{$url}"
  id="{$iframeId}"
  style="width:100%; border:none; min-height:400px;"
  loading="lazy"
></iframe>

<script>
  window.addEventListener('message', function(e) {
    if (e.origin !== '{$this->origin()}') return;
    if (e.data?.type === 'formforge:resize') {
      document.getElementById('{$iframeId}').style.height = e.data.height + 'px';
    }
  });
</script>
HTML;

        $this->line($code);
        $this->newLine();
        $this->line("<fg=green>URL directe :</> {$url}");
        $this->newLine();

        return self::SUCCESS;
    }

    /** @param array<string, array<string, class-string>> $all */
    private function resolveForm(array $all): array
    {
        $site = $this->argument('site');
        $slug = $this->argument('slug');

        if ($site !== null && $slug !== null) {
            if (! FormRegistry::has($site, $slug)) {
                $this->error("Formulaire introuvable : {$site}/{$slug}");
                return [null, null];
            }
            return [$site, $slug];
        }

        $choices = [];
        foreach ($all as $s => $slugs) {
            foreach ($slugs as $sl => $class) {
                $instance = new $class();
                $choices["{$s}/{$sl}"] = $instance->title;
            }
        }

        $options = array_map(
            fn($key) => "{$key} — {$choices[$key]}",
            array_keys($choices)
        );

        $selected = $this->choice('Choisissez un formulaire', $options);
        $key = explode(' — ', $selected)[0];

        return explode('/', $key, 2);
    }

    private function origin(): string
    {
        return rtrim(config('app.url'), '/');
    }
}
