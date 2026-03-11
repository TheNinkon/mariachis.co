<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tu acceso a Mariachis.co</title>
  </head>
  <body style="margin:0;background:#f4f7f5;font-family:'Plus Jakarta Sans',Arial,sans-serif;color:#16312a;">
    <div style="max-width:640px;margin:0 auto;padding:32px 20px;">
      <div style="border:1px solid rgba(0,86,59,0.12);border-radius:24px;background:#ffffff;overflow:hidden;box-shadow:0 24px 48px -34px rgba(15,23,42,0.28);">
        <div style="padding:28px 32px 18px;background:linear-gradient(140deg,#f6fbf9 0%,#ffffff 100%);border-bottom:1px solid rgba(0,86,59,0.08);">
          <p style="margin:0 0 10px;font-size:12px;letter-spacing:0.16em;text-transform:uppercase;font-weight:800;color:#00563b;">Acceso cliente</p>
          <h1 style="margin:0;font-size:30px;line-height:1.08;font-weight:800;color:#0f172a;">Tu enlace para entrar a Mariachis.co</h1>
        </div>

        <div style="padding:28px 32px 32px;">
          <p style="margin:0 0 14px;font-size:16px;line-height:1.65;color:#334155;">
            Hola {{ $user->first_name ?: $user->display_name }},
          </p>
          <p style="margin:0 0 20px;font-size:15px;line-height:1.7;color:#475569;">
            Usa este enlace para acceder a tu cuenta de cliente. Es de un solo uso y caduca en {{ $expiresInMinutes }} minutos.
          </p>

          <p style="margin:0 0 24px;">
            <a href="{{ $magicUrl }}" style="display:inline-block;padding:14px 22px;border-radius:14px;background:#00563b;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;">Entrar a mi cuenta</a>
          </p>

          <p style="margin:0 0 10px;font-size:13px;line-height:1.7;color:#64748b;">
            Si el botón no funciona, copia y pega este enlace en tu navegador:
          </p>
          <p style="margin:0;padding:14px 16px;border-radius:14px;background:#f8fafc;border:1px solid rgba(148,163,184,0.18);font-size:12px;line-height:1.7;color:#334155;word-break:break-all;">
            {{ $magicUrl }}
          </p>
        </div>
      </div>
    </div>
  </body>
</html>
