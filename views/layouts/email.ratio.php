<!DOCTYPE html>
<html lang="<?= getenv('APP_LANG'); ?>">
<head>
  <meta charset="UTF-8">
  <meta name="x-apple-disable-message-reformatting">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>{% yield title %}</title>
  <style>
    /* Mobil-responsiv typografi */
    @media only screen and (max-width: 600px) {
      .container { width: 100% !important; }
      .px { padding-left: 16px !important; padding-right: 16px !important; }
      .hero-title { font-size: 22px !important; }
    }
    /* Mörkt läge */
    @media (prefers-color-scheme: dark) {
      body, .bg-body { background: #0B0B0C !important; color: #ECEDEE !important; }
      .card { background: #141518 !important; border-color: #2A2C2F !important; }
      .muted { color: #B2B3B5 !important; }
      .btn { background: #3B82F6 !important; color: #fff !important; }
      .btn-outline { color: #ECEDEE !important; border-color: #3B82F6 !important; }
      a { color: #7CB3FF !important; }
    }
    /* Fallback för vissa klienter */
    a { text-decoration: none; }
    img { border: 0; line-height: 100%; outline: none; text-decoration: none; }
    table { border-collapse: collapse; }
  </style>
</head>
<body class="bg-body" style="margin:0; padding:0; background:#F3F4F6; font-family: Arial, Helvetica, sans-serif; color:#111827; line-height:1.5;">
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background:#F3F4F6;">
    <tr>
      <td align="center" style="padding: 24px;">
        <!-- Wrapper -->
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" class="container" style="width:600px; max-width:600px;">
          <!-- Header -->
          <tr>
            <td class="px" style="padding: 0 24px 16px 24px;" align="left">
              <table role="presentation" width="100%">
                <tr>
                  <td align="left" valign="middle">
                    <!-- Logotyp (byt URL om du har en) -->
                    <a href="<?= getenv('APP_URL') ?: '#' ?>" style="display:inline-block;">
                      <img src="<?= getenv('APP_URL') ? getenv('APP_URL').'/images/graphics/logo.png' : 'https://dummyimage.com/120x32/111827/ffffff&text=LOGO' ?>" width="auto" height="32" alt="<?= htmlspecialchars(getenv('APP_NAME') ?: 'App') ?>" style="display:block;">
                    </a>
                  </td>
                  <td align="right" valign="middle" style="font-size:12px; color:#6B7280;">
                    <span class="muted" style="color:#6B7280;">{% yield title %}</span>
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Card -->
          <tr>
            <td style="padding: 0 24px 0 24px;">
              <table role="presentation" width="100%" class="card" style="background:#FFFFFF; border:1px solid #E5E7EB; border-radius:8px; overflow:hidden;">
                <!-- Hero -->
                <tr>
                  <td style="padding: 24px 24px 0 24px;">
                    <h1 class="hero-title" style="margin:0 0 8px 0; font-size:24px; line-height:1.25; color:#111827;"><?= htmlspecialchars(getenv('APP_NAME') ?: 'Välkommen') ?></h1>
                    <p style="margin:0; color:#4B5563; font-size:14px;">{% yield title %}</p>
                  </td>
                </tr>
                <!-- Body -->
                <tr>
                  <td class="px" style="padding: 16px 24px 24px 24px; font-size:15px; color:#111827;">
                    {% yield body %}
                  </td>
                </tr>
              </table>
            </td>
          </tr>
          <!-- Footer -->
          <tr>
            <td class="px" style="padding: 16px 24px 0 24px;">
              <p class="muted" style="margin: 0 0 4px 0; font-size:12px; color:#6B7280;">
                Skickat av <?= htmlspecialchars(getenv('APP_NAME') ?: 'Vår tjänst') ?> • <?= htmlspecialchars(getenv('APP_URL') ?: '') ?>
              </p>
              <p class="muted" style="margin: 0; font-size:12px; color:#6B7280;">
                Detta meddelande är avsett för mottagaren. Om du fått det av misstag, vänligen radera det.
              </p>
            </td>
          </tr>
          <tr>
            <td style="height: 24px;">&nbsp;</td>
          </tr>
        </table>
        <!-- /Wrapper -->
      </td>
    </tr>
  </table>

  <!-- Små “komponenter” för knappar och separatorer att använda i body-yield -->
  <!-- Exempel:
    <table role="presentation" cellpadding="0" cellspacing="0" border="0">
      <tr><td style="padding: 12px 0;">
        <a href="#" class="btn" style="display:inline-block; background:#2563EB; color:#fff; font-weight:bold; font-size:14px; padding:12px 18px; border-radius:6px;">Primär knapp</a>
      </td></tr>
    </table>
    <table role="presentation" width="100%" style="margin: 16px 0;"><tr><td style="border-top:1px solid #E5E7EB; height:1px; line-height:1px; font-size:0;">&nbsp;</td></tr></table>
    <p class="muted" style="font-size:12px; color:#6B7280;">Liten fotnot</p>
  -->
</body>
</html>