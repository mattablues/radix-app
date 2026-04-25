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

    .btn-primary { background-color: #2563eb !important; border-radius: 12px !important; color: #ffffff !important; display: inline-block; font-size: 14px; font-weight: bold; padding: 14px 28px; text-decoration: none; text-transform: uppercase; letter-spacing: 1px; }
    .text-main { color: #0f172a; }
    .text-muted { color: #64748b; }

    @media only screen and (max-width: 600px) {
      .container { width:100% !important; padding: 10px !important; }
      .card { border-radius: 16px !important; }
      .brand-stack { display:block !important; width:100% !important; text-align:center !important; }
      .brand-icon-wrap { display:block !important; padding:0 0 12px 0 !important; text-align:center !important; }
      .brand-text-wrap { display:block !important; text-align:center !important; }
    }
  </style>
</head>
<body style="background-color: #f8fafc; padding: 20px 0;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
    <tr>
      <td align="center">
        <table class="container" role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="max-width: 600px;">

          <!-- Header -->
          <tr>
            <td align="center" style="padding: 40px 0 20px 0;">
              <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                <tr>
                  <td style="padding-right: 16px; vertical-align: middle;">
                    <img
                      src="{{ getenv('APP_URL') }}/images/graphics/logo-email.png"
                      alt="Radix"
                      width="34"
                      height="34"
                      style="display: block; width: 34px; height: 34px; border: 0;"
                    >
                  </td>
                  <td style="vertical-align: middle; text-align: left;">
                    <div style="font-family: Helvetica, Arial, sans-serif; font-size: 24px; font-weight: 900; color: #0f172a; font-style: italic; letter-spacing: -1px; line-height: 1.05;">
                      Radix
                    </div>
                    <div style="margin-top: 4px; font-family: Helvetica, Arial, sans-serif; font-size: 10px; font-weight: 800; color: #64748b; text-transform: uppercase; letter-spacing: 2px; line-height: 1.2;">
                      Systemautomatik
                    </div>
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
                &copy; {{ date('Y') }} Radix • Systemautomatik
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
