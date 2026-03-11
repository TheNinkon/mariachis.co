<!DOCTYPE html>
<html lang="es">
  <head>
    <meta charset="UTF-8" />
    <title>Prueba SMTP</title>
  </head>
  <body style="margin:0;padding:0;background:#f8fafc;font-family:Arial,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f8fafc;padding:32px 12px;">
      <tr>
        <td align="center">
          <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:600px;background:#ffffff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;">
            <tr>
              <td style="padding:28px 28px 18px;">
                <p style="margin:0 0 8px;font-size:12px;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:#00563b;">Configuración del sistema</p>
                <h1 style="margin:0;font-size:28px;line-height:1.1;">Correo de prueba enviado correctamente</h1>
                <p style="margin:16px 0 0;font-size:15px;line-height:1.7;color:#475569;">
                  Este mensaje confirma que el sistema pudo procesar un envío de prueba desde el panel administrativo.
                </p>
              </td>
            </tr>
            <tr>
              <td style="padding:0 28px 28px;">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:separate;border-spacing:0 10px;">
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Destinatario</td>
                    <td style="font-size:14px;color:#0f172a;">{{ $recipient }}</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Mailer activo</td>
                    <td style="font-size:14px;color:#0f172a;">{{ $mailer }}</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">From</td>
                    <td style="font-size:14px;color:#0f172a;">{{ $fromName }} &lt;{{ $fromAddress }}&gt;</td>
                  </tr>
                  <tr>
                    <td style="font-size:13px;font-weight:700;color:#64748b;">Fecha</td>
                    <td style="font-size:14px;color:#0f172a;">{{ $sentAt->format('Y-m-d H:i:s') }}</td>
                  </tr>
                </table>
              </td>
            </tr>
          </table>
        </td>
      </tr>
    </table>
  </body>
</html>
