<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Services\Seo\SeoResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaticPageController extends Controller
{
    public function show(Request $request, string $pageKey, SeoResolver $seoResolver): View
    {
        $content = $this->content($pageKey);
        abort_if($content === null, 404);

        return view('front.static-page', [
            'page' => $content,
            'seo' => $seoResolver->resolve($request, 'static_page', [
                'page_key' => $pageKey,
                'title' => $content['title'],
                'description' => $content['lead'],
                'og_type' => 'website',
            ]),
        ]);
    }

    /**
     * @return array{
     *   eyebrow:string,
     *   title:string,
     *   lead:string,
     *   sections:array<int, array{title:string, body:string}>
     * }|null
     */
    private function content(string $pageKey): ?array
    {
        return match ($pageKey) {
            'terms' => [
                'eyebrow' => 'Legal',
                'title' => 'Terminos y condiciones',
                'lead' => 'Condiciones generales para usar Mariachis.co como cliente o proveedor dentro del marketplace.',
                'sections' => [
                    ['title' => 'Uso de la plataforma', 'body' => 'Mariachis.co conecta clientes con proveedores y permite comparar perfiles, solicitar informacion y gestionar publicaciones dentro del marketplace.'],
                    ['title' => 'Responsabilidades de los usuarios', 'body' => 'Cada usuario es responsable de la veracidad de los datos que publica, del uso adecuado de la plataforma y de respetar las normas internas de moderacion.'],
                    ['title' => 'Disponibilidad y cambios', 'body' => 'Podemos actualizar funcionalidades, flujos y politicas para mantener la calidad del servicio. Los cambios relevantes se publicaran dentro del sitio.'],
                ],
            ],
            'privacy' => [
                'eyebrow' => 'Datos personales',
                'title' => 'Politica de privacidad',
                'lead' => 'Resumen de como tratamos la informacion personal, solicitudes y datos de contacto en Mariachis.co.',
                'sections' => [
                    ['title' => 'Datos que recopilamos', 'body' => 'Procesamos informacion de registro, contacto, actividad de uso y contenido enviado en formularios o solicitudes para operar el marketplace.'],
                    ['title' => 'Finalidad del tratamiento', 'body' => 'Usamos los datos para autenticar cuentas, mostrar contenido relevante, conectar clientes con proveedores y mantener seguridad, soporte y moderacion.'],
                    ['title' => 'Control del usuario', 'body' => 'Las personas pueden solicitar actualizacion o eliminacion de informacion cuando aplique, conforme a la normativa vigente y a la operacion del servicio.'],
                ],
            ],
            'help' => [
                'eyebrow' => 'Soporte',
                'title' => 'Centro de ayuda',
                'lead' => 'Preguntas frecuentes para clientes y mariachis sobre cuentas, anuncios, pagos y solicitudes dentro de Mariachis.co.',
                'sections' => [
                    ['title' => 'Para clientes', 'body' => 'Puedes explorar anuncios, guardar favoritos, revisar vistos recientemente y enviar solicitudes de informacion o presupuesto.'],
                    ['title' => 'Para mariachis', 'body' => 'El partner permite completar el perfil, editar anuncios, elegir planes, enviar comprobantes de pago y gestionar su presencia en el marketplace.'],
                    ['title' => 'Soporte operativo', 'body' => 'Si algo no funciona como esperas, usa los canales de contacto internos o comunicate con el equipo administrador para revision manual.'],
                ],
            ],
            default => null,
        };
    }
}
