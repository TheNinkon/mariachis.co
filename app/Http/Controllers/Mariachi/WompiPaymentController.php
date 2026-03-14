<?php

namespace App\Http\Controllers\Mariachi;

use App\Http\Controllers\Controller;
use App\Services\WompiPaymentFlowService;
use App\Services\WompiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class WompiPaymentController extends Controller
{
    public function __construct(
        private readonly WompiService $wompi,
        private readonly WompiPaymentFlowService $paymentFlows
    ) {
    }

    public function redirect(Request $request, string $type, string $reference): View
    {
        $payment = $this->paymentFlows->findPayment($type, $reference);
        abort_unless($payment, Response::HTTP_NOT_FOUND);

        $transactionId = trim((string) $request->query('id', ''));
        if ($transactionId !== '') {
            $transaction = $this->wompi->findTransaction($transactionId);

            if (is_array($transaction) && (string) ($transaction['reference'] ?? '') === $reference) {
                $this->paymentFlows->syncPaymentFromTransaction($type, $payment, $transaction);
                $payment->refresh();
            }
        }

        [$title, $message, $contextClass, $returnUrl, $returnLabel] = match ($type) {
            WompiPaymentFlowService::TYPE_ACTIVATION => $this->activationResponseMeta($payment),
            WompiPaymentFlowService::TYPE_LISTING => $this->listingResponseMeta($payment),
            WompiPaymentFlowService::TYPE_VERIFICATION => $this->verificationResponseMeta($payment),
            default => ['Pago Wompi', 'No pudimos identificar el flujo del pago.', 'secondary', route('mariachi.login'), 'Ir a login'],
        };

        return view('content.payments.wompi-response', [
            'title' => $title,
            'message' => $message,
            'contextClass' => $contextClass,
            'returnUrl' => $returnUrl,
            'returnLabel' => $returnLabel,
            'transactionId' => $transactionId,
            'reference' => $reference,
            'payment' => $payment,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        if (! is_array($payload) || ! $this->wompi->isValidEventSignature($payload)) {
            return response()->json(['ok' => false, 'message' => 'Invalid Wompi signature.'], Response::HTTP_BAD_REQUEST);
        }

        $event = (string) data_get($payload, 'event', '');
        $transaction = data_get($payload, 'data.transaction');

        if ($event === '' || ! is_array($transaction)) {
            return response()->json(['ok' => true], Response::HTTP_ACCEPTED);
        }

        $reference = trim((string) ($transaction['reference'] ?? ''));
        if ($reference === '') {
            return response()->json(['ok' => true], Response::HTTP_ACCEPTED);
        }

        $resolved = $this->paymentFlows->findPaymentByReference($reference);
        if (! $resolved) {
            return response()->json(['ok' => true], Response::HTTP_ACCEPTED);
        }

        $this->paymentFlows->syncPaymentFromTransaction($resolved['type'], $resolved['payment'], $transaction);

        return response()->json(['ok' => true]);
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function activationResponseMeta($payment): array
    {
        $payment->loadMissing('user');

        if ($payment->status === 'approved') {
            return [
                'Pago confirmado',
                'Tu pago fue aprobado por Wompi y la cuenta ya quedó activa. Ya puedes iniciar sesión como mariachi.',
                'success',
                route('mariachi.login'),
                'Ir a login',
            ];
        }

        if ($payment->status === 'rejected') {
            return [
                'Pago no aprobado',
                $payment->rejection_reason ?: 'Wompi no aprobó el cobro. Puedes volver a intentarlo desde la activación.',
                'danger',
                route('mariachi.activation.show', [
                    'user' => $payment->user_id,
                    'token' => $payment->user?->activation_token,
                ]),
                'Volver a activación',
            ];
        }

        return [
            'Pago en proceso',
            'Wompi aún está confirmando tu pago. Si ya autorizaste la transacción, espera unos segundos y vuelve a cargar esta página.',
            'warning',
            route('mariachi.activation.show', [
                'user' => $payment->user_id,
                'token' => $payment->user?->activation_token,
            ]),
            'Ver activación',
        ];
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function listingResponseMeta($payment): array
    {
        if ($payment->status === 'approved') {
            return [
                'Pago confirmado',
                'Wompi aprobó el pago del anuncio. Ahora ya puedes enviar el anuncio a revisión desde el editor.',
                'success',
                route('mariachi.listings.edit', ['listing' => $payment->mariachi_listing_id]),
                'Volver al editor',
            ];
        }

        if ($payment->status === 'rejected') {
            return [
                'Pago no aprobado',
                $payment->rejection_reason ?: 'Wompi no aprobó el cobro de este anuncio. Puedes intentarlo de nuevo.',
                'danger',
                route('mariachi.listings.edit', ['listing' => $payment->mariachi_listing_id]),
                'Volver al editor',
            ];
        }

        return [
            'Pago en proceso',
            'Wompi aún está confirmando este pago. Si acabas de autorizarlo, espera unos segundos y vuelve a revisar el anuncio.',
            'warning',
            route('mariachi.listings.edit', ['listing' => $payment->mariachi_listing_id]),
            'Volver al editor',
        ];
    }

    /**
     * @return array{0:string,1:string,2:string,3:string,4:string}
     */
    private function verificationResponseMeta($payment): array
    {
        if ($payment->status === 'approved') {
            return [
                'Pago confirmado',
                'Wompi aprobó el pago de la verificación. Tus documentos ya quedaron listos para revisión manual del equipo.',
                'success',
                route('mariachi.verification.edit'),
                'Volver a verificación',
            ];
        }

        if ($payment->status === 'rejected') {
            return [
                'Pago no aprobado',
                $payment->rejection_reason ?: 'Wompi no aprobó el cobro de la verificación. Puedes corregirlo e intentarlo de nuevo.',
                'danger',
                route('mariachi.verification.edit'),
                'Volver a verificación',
            ];
        }

        return [
            'Pago en proceso',
            'Wompi aún está confirmando este pago. Si ya lo autorizaste, espera unos segundos y vuelve a verificación.',
            'warning',
            route('mariachi.verification.edit'),
            'Volver a verificación',
        ];
    }
}
