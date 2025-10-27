{% extends "layouts/email.ratio.php" %}
{% block title %}Aktivera ditt konto{% endblock %}

{% block body %}
  <style>
    @media only screen and (max-width:600px) {
      .body-text { line-height:1.6 !important; }
      .btn { padding:14px 20px !important; font-size:15px !important; }
      .muted-small { font-size:12px !important; line-height:1.5 !important; }
    }
  </style>

  <p class="body-text" style="margin:0 0 8px 0; color:#111827; line-height:1.45;">Hej,</p>
  <p class="body-text" style="margin:0 0 12px 0; color:#111827; line-height:1.45;">
    Tack för att du registrerat dig. Klicka på knappen nedan för att aktivera ditt konto.
  </p>

  <table role="presentation" cellpadding="0" cellspacing="0" border="0">
    <tr>
      <td style="padding: 12px 0;">
        <a href="{{ $url }}" class="btn" style="display:inline-block; background:#2563EB; color:#ffffff; font-weight:bold; font-size:14px; padding:12px 18px; border-radius:6px;">
          Aktivera konto
        </a>
      </td>
    </tr>
  </table>

  <p class="muted muted-small" style="font-size:12px; color:#6B7280; margin:12px 0 4px 0;">
    Om knappen inte fungerar, kopiera och klistra in länken nedan i din webbläsare:
  </p>
  <p style="word-break: break-all; color:#111827; font-size:12px; margin:0 0 12px 0;">{{ $url }}</p>

  <table role="presentation" width="100%" style="margin: 16px 0;">
    <tr><td style="border-top:1px solid #E5E7EB; height:1px; line-height:1px; font-size:0;">&nbsp;</td></tr>
  </table>

  <p class="body-text" style="margin:0; color:#4B5563; font-size:14px; line-height:1.45;">{{ $body }}</p>
  <p class="muted muted-small" style="margin-top:16px; font-size:12px; color:#6B7280;">Skickat {{ date('Y-m-d H:i') }}</p>
{% endblock %}