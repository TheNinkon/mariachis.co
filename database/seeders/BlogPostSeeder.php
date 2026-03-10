<?php

namespace Database\Seeders;

use App\Models\BlogCity;
use App\Models\BlogPost;
use App\Models\BlogZone;
use App\Models\EventType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogPostSeeder extends Seeder
{
    public function run(): void
    {
        $author = User::query()->where('role', User::ROLE_ADMIN)->first();
        $eventType = EventType::query()->where('name', 'Bodas')->first();

        if (! $author) {
            return;
        }

        $bogotaCity = BlogCity::query()->firstOrCreate(
            ['slug' => Str::slug('Bogota')],
            ['name' => 'Bogota']
        );

        $chapineroZone = BlogZone::query()->firstOrCreate(
            [
                'blog_city_id' => $bogotaCity->id,
                'slug' => Str::slug('Chapinero'),
            ],
            ['name' => 'Chapinero']
        );

        $posts = [
            [
                'title' => 'Como elegir mariachis para una boda en Bogota',
                'excerpt' => 'Guia practica para elegir repertorio, tamano de grupo y tiempos de presentacion para bodas en Bogota.',
                'content' => '<p>Antes de contratar, valida repertorio, tiempos de llegada y cobertura real por zona.</p><p>Compara minimo tres perfiles y revisa su experiencia en bodas.</p>',
                'status' => BlogPost::STATUS_PUBLISHED,
                'city_ids' => [$bogotaCity->id],
                'zone_ids' => [$chapineroZone->id],
                'event_type_ids' => $eventType ? [$eventType->id] : [],
            ],
            [
                'title' => 'Checklist para serenatas sorpresa sin contratiempos',
                'excerpt' => 'Puntos clave para coordinar una serenata sorpresa y evitar errores de logistica.',
                'content' => '<p>Confirma direccion exacta, horario de ingreso y numero de contacto del anfitrion.</p><ul><li>Ubicacion</li><li>Horario</li><li>Canciones clave</li></ul>',
                'status' => BlogPost::STATUS_DRAFT,
                'city_ids' => [],
                'zone_ids' => [],
                'event_type_ids' => [],
            ],
        ];

        foreach ($posts as $item) {
            $slug = Str::slug($item['title']);

            $primaryCity = collect($item['city_ids'])->first();
            $primaryZone = collect($item['zone_ids'])->first();
            $primaryEventType = collect($item['event_type_ids'])->first();

            $post = BlogPost::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'author_id' => $author->id,
                    'event_type_id' => $primaryEventType,
                    'title' => $item['title'],
                    'slug' => $slug,
                    'excerpt' => $item['excerpt'],
                    'content' => $item['content'],
                    'status' => $item['status'],
                    'city_name' => $primaryCity
                        ? BlogCity::query()->where('id', $primaryCity)->value('name')
                        : null,
                    'zone_name' => $primaryZone
                        ? BlogZone::query()->where('id', $primaryZone)->value('name')
                        : null,
                    'published_at' => $item['status'] === BlogPost::STATUS_PUBLISHED ? now() : null,
                ]
            );

            $post->cities()->sync($item['city_ids']);
            $post->zones()->sync($item['zone_ids']);
            $post->eventTypes()->sync($item['event_type_ids']);
        }
    }
}
