<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RenderedEmailTemplateMail;
use App\Models\EmailTemplate;
use App\Services\EmailTemplateService;
use App\Services\MailSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class AdminEmailTemplateController extends Controller
{
    public function __construct(
        private readonly EmailTemplateService $templates,
        private readonly MailSettingsService $mailSettings
    ) {
    }

    public function index(): View
    {
        $templates = $this->templates->listForAdmin();

        return view('content.admin.email-templates-index', [
            'templates' => $templates,
            'totalTemplates' => $templates->count(),
            'activeTemplates' => $templates->where('is_active', true)->count(),
            'clientTemplates' => $templates->where('audience', 'client')->count(),
            'adminTemplates' => $templates->where('audience', 'admin')->count(),
            'mariachiTemplates' => $templates->where('audience', 'mariachi')->count(),
        ]);
    }

    public function edit(string $key): View
    {
        $template = $this->templates->findEditable($key);
        abort_if($template === null, 404);

        return view('content.admin.email-templates-edit', [
            'template' => $template,
            'mockVariables' => $this->templates->mockVariables($template->key),
            'mailerName' => $this->mailSettings->publicConfig()['mailer'],
        ]);
    }

    public function preview(Request $request, string $key): JsonResponse
    {
        $template = $this->templates->findEditable($key);
        abort_if($template === null, 404);

        $draft = $this->validateDraftTemplate($request, $template);
        $rendered = $this->templates->renderEditable($draft, $this->templates->mockVariables($template->key));

        return response()->json([
            'subject' => $rendered['subject'],
            'html' => $rendered['html'],
        ]);
    }

    public function update(Request $request, string $key): RedirectResponse
    {
        $template = $this->templates->findEditable($key);
        abort_if($template === null, 404);

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $invalidVariables = $this->templates->invalidVariables(
            $validated['subject'],
            $validated['body_html'],
            $this->templates->allowedVariables($template)
        );

        if ($invalidVariables !== []) {
            return back()
                ->withInput()
                ->withErrors([
                    'body_html' => 'Estas variables no están permitidas en esta plantilla: '.implode(', ', $invalidVariables).'.',
                ]);
        }

        $this->templates->save($key, [
            'subject' => $validated['subject'],
            'body_html' => $validated['body_html'],
            'is_active' => $request->boolean('is_active', true),
        ], $request->user()?->id);

        return redirect()
            ->route('admin.email-templates.edit', $key)
            ->with('status', 'Plantilla actualizada.');
    }

    public function sendTest(Request $request, string $key): RedirectResponse|JsonResponse
    {
        $template = $this->templates->findEditable($key);
        abort_if($template === null, 404);

        $validator = Validator::make($request->all(), [
            'recipient' => ['required', 'email:rfc', 'max:255'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body_html' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->sendTestValidationFailedResponse($request, $validator->errors());
        }

        $validated = $validator->validated();
        $draft = $this->draftTemplateFromRequest($request, $template);
        $invalidVariables = $this->templates->invalidVariables(
            $draft->subject,
            $draft->body_html,
            $this->templates->allowedVariables($template)
        );

        if ($invalidVariables !== []) {
            $message = 'Estas variables no están permitidas en esta plantilla: '.implode(', ', $invalidVariables).'.';

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => $message,
                    'errors' => ['body_html' => [$message]],
                ], 422);
            }

            return back()->withInput()->withErrors(['body_html' => $message]);
        }

        $variables = $this->testVariables($template->key, $validated['recipient']);
        $rendered = $this->templates->renderEditable($draft, $variables);

        try {
            Mail::to($validated['recipient'])->send(
                new RenderedEmailTemplateMail($rendered['subject'], $rendered['html'])
            );
        } catch (Throwable $exception) {
            report($exception);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'No pudimos enviar la prueba con la configuración actual.',
                ], 422);
            }

            return back()
                ->withInput()
                ->withErrors([
                    'recipient' => 'No pudimos enviar la prueba con la configuración actual.',
                ]);
        }

        $message = $this->mailSettings->publicConfig()['mailer'] === MailSettingsService::MAILER_LOG
            ? 'La prueba se procesó con el mailer "log". Revisa el log del sistema si quieres ver el contenido.'
            : 'Correo de prueba enviado correctamente.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
            ]);
        }

        return back()->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function testVariables(string $key, string $recipient): array
    {
        $variables = $this->templates->mockVariables($key);

        if (array_key_exists('recipient', $variables)) {
            $variables['recipient'] = $recipient;
        }

        if (array_key_exists('email', $variables)) {
            $variables['email'] = $recipient;
        }

        if (array_key_exists('user_email', $variables)) {
            $variables['user_email'] = $recipient;
        }

        if (array_key_exists('sentAt', $variables)) {
            $variables['sentAt'] = now()->format('Y-m-d H:i:s');
        }

        return $variables;
    }

    private function validateDraftTemplate(Request $request, EmailTemplate $template): EmailTemplate
    {
        $validator = Validator::make($request->all(), [
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            abort(response()->json([
                'message' => 'Corrige los errores antes de abrir la vista previa.',
                'errors' => $validator->errors(),
            ], 422));
        }

        $draft = $this->draftTemplateFromRequest($request, $template);
        $invalidVariables = $this->templates->invalidVariables(
            $draft->subject,
            $draft->body_html,
            $this->templates->allowedVariables($template)
        );

        if ($invalidVariables !== []) {
            abort(response()->json([
                'message' => 'Estas variables no están permitidas en esta plantilla: '.implode(', ', $invalidVariables).'.',
                'errors' => [
                    'body_html' => ['Estas variables no están permitidas: '.implode(', ', $invalidVariables).'.'],
                ],
            ], 422));
        }

        return $draft;
    }

    private function draftTemplateFromRequest(Request $request, EmailTemplate $template): EmailTemplate
    {
        $draft = clone $template;

        if ($request->filled('subject')) {
            $draft->subject = (string) $request->input('subject');
        }

        if ($request->filled('body_html')) {
            $draft->body_html = (string) $request->input('body_html');
        }

        if ($request->has('is_active')) {
            $draft->is_active = $request->boolean('is_active');
        }

        return $draft;
    }

    private function sendTestValidationFailedResponse(Request $request, \Illuminate\Support\MessageBag $errors): RedirectResponse|JsonResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Corrige los datos antes de enviar la prueba.',
                'errors' => $errors,
            ], 422);
        }

        return back()->withInput()->withErrors($errors);
    }
}
