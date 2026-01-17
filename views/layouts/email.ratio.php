<!DOCTYPE html>
<html lang="{{ getenv('APP_LANG') }}">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="format-detection" content="telephone=no, date=no, address=no, email=no">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1">
  <title>{% yield title %}</title>
  <style>
    html, body { width:100% !important; height:100% !important; margin:0; padding:0; }
    body { -webkit-text-size-adjust:100%; -ms-text-size-adjust:100%; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f8fafc; }
    table { border-collapse: collapse; mso-table-lspace:0pt; mso-table-rspace:0pt; }
    img { border:0; line-height:100%; outline:none; text-decoration:none; }

    /* Radix Brand Styles */
    .btn-primary { background-color: #2563eb !important; border-radius: 12px !important; color: #ffffff !important; display: inline-block; font-size: 14px; font-weight: bold; padding: 14px 28px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; }
    .text-main { color: #0f172a; }
    .text-muted { color: #64748b; }

    @media only screen and (max-width: 600px) {
      .container { width:100% !important; padding: 10px !important; }
      .card { border-radius: 16px !important; }
    }
  </style>
</head>
<body style="background-color: #f8fafc; padding: 20px 0;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
      <td align="center">
        <table class="container" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px;">

          <!-- Header: Radix Logo -->
          <tr>
            <td align="center" style="padding: 40px 0 20px 0;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <!-- Denna tabell simulerar kub-loggan för e-post -->
                  <td style="padding-right: 12px;">
                    <table role="presentation" cellspacing="1" cellpadding="0" border="0" width="32" height="32">
                      <tr>
                        <td bgcolor="#2563eb" width="15" height="15" style="border-top-left-radius: 4px;"></td>
                        <td bgcolor="#e2e8f0" width="15" height="15" style="border-top-right-radius: 4px;"></td>
                      </tr>
                      <tr>
                        <td bgcolor="#cbd5e1" width="15" height="15" style="border-bottom-left-radius: 4px;"></td>
                        <td bgcolor="#0f172a" width="15" height="15" style="border-bottom-right-radius: 4px;"></td>
                      </tr>
                    </table>
                  </td>
                  <td style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 900; color: #0f172a; font-style: italic; letter-spacing: -1px;">
                    Radix
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Main Content Card -->
          <tr>
            <td style="padding: 0 20px;">
              <table class="card" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
                <tr>
                  <td style="padding: 40px;">
                    <h1 style="margin: 0 0 16px 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 20px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 1px;">
                      {% yield title %}
                    </h1>
                    <div style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.6; color: #334155;">
                      {% yield body %}
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding: 30px 20px;">
              <p style="margin: 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; letter-spacing: 2px;">
                &copy; {{ date('Y') }} Radix Engine • Systemautomatik
              </p>
              <p style="margin: 8px 0 0 0; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #cbd5e1;">
                Detta är ett automatiskt systemmeddelande som inte kan besvaras.
              </p>
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>