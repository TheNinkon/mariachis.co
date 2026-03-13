<?php

namespace App\Services\Seo;

use App\Models\SeoPage;
use Illuminate\Support\Collection;

class SeoPageCatalog
{
    /**
     * @return array<string, array{
     *   label:string,
     *   path:string|null,
     *   robots:string,
     *   title:string|null,
     *   meta_description:string|null
     * }>
     */
    public function definitions(): array
    {
        return [
            'home' => [
                'label' => 'Home',
                'path' => '/',
                'robots' => 'index,follow',
                'title' => 'Mariachis.co',
                'meta_description' => 'Encuentra mariachis por ciudad, compara perfiles y contacta por WhatsApp o llamada.',
            ],
            'blog_index' => [
                'label' => 'Blog',
                'path' => '/blog',
                'robots' => 'index,follow',
                'title' => 'Blog y recursos para contratar mariachis',
                'meta_description' => 'Consejos, guias y recursos locales para encontrar mariachis por ciudad, zona y tipo de evento en Colombia.',
            ],
            'terms' => [
                'label' => 'Terminos y condiciones',
                'path' => '/terminos',
                'robots' => 'index,follow',
                'title' => 'Terminos y condiciones',
                'meta_description' => 'Consulta los terminos y condiciones del marketplace Mariachis.co.',
            ],
            'privacy' => [
                'label' => 'Politica de privacidad',
                'path' => '/privacidad',
                'robots' => 'index,follow',
                'title' => 'Politica de privacidad',
                'meta_description' => 'Revisa como Mariachis.co trata y protege tus datos personales.',
            ],
            'help' => [
                'label' => 'Centro de ayuda',
                'path' => '/ayuda',
                'robots' => 'index,follow',
                'title' => 'Centro de ayuda',
                'meta_description' => 'Preguntas frecuentes y ayuda para clientes y mariachis en Mariachis.co.',
            ],
            '404' => [
                'label' => 'Error 404',
                'path' => null,
                'robots' => 'noindex,follow',
                'title' => 'Pagina no encontrada',
                'meta_description' => 'La pagina que buscas no existe o fue movida dentro de Mariachis.co.',
            ],
        ];
    }

    public function syncDefaults(): void
    {
        foreach ($this->definitions() as $key => $definition) {
            SeoPage::query()->firstOrCreate(
                ['key' => $key],
                [
                    'path' => $definition['path'],
                    'title' => null,
                    'meta_description' => null,
                    'robots' => $definition['robots'],
                    'canonical_override' => null,
                    'jsonld' => null,
                ]
            );
        }
    }

    /**
     * @return Collection<int, SeoPage>
     */
    public function pages(): Collection
    {
        $this->syncDefaults();

        return SeoPage::query()->orderBy('key')->get();
    }

    /**
     * @return array{
     *   label:string,
     *   path:string|null,
     *   robots:string,
     *   title:string|null,
     *   meta_description:string|null
     * }|null
     */
    public function definition(string $key): ?array
    {
        return $this->definitions()[$key] ?? null;
    }
}
