{% extends "layouts/email.ratio.php" %}
{% block title %}Återställ lösenord{% endblock %}

{% block body %}
  <p style="margin:0 0 16px 0; color:#0f172a; font-weight: 700;">Säkerhetsmeddelande: Begäran om lösenordsbyte</p>

  <p style="margin:0 0 12px 0; color:#334155; line-height: 1.6;">
    Hej {{ $firstName ?? '' }} {{ $lastName ?? '' }},
  </p>

  <p style="margin:0 0 24px 0; color:#475569; line-height: 1.6;">
    En förfrågan om att återställa lösenordet för ditt konto i Radix har mottagits. Klicka på knappen nedan för att välja ett nytt lösenord.
  </p>

  <!-- Åtgärdsknapp -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center" style="padding: 16px 0 32px 0;">
        <a href="{{ $url }}" class="btn-primary">
          Välj nytt lösenord
        </a>
      </td>
    </tr>
  </table>

  <!-- Säkerhetsinfo & Fallback -->
  <div style="background-color: #f8fafc; border-radius: 12px; padding: 20px; margin-bottom: 24px;">
    <p style="margin:0 0 8px 0; font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Säkerhetskopia av länk</p>
    <p style="margin:0 0 16px 0; word-break: break-all; color: #2563eb; font-size: 12px; font-family: monospace;">
      {{ $url }}
    </p>

    {% if (isset($expiresAt)) : %}
    <div style="border-top: 1px solid #e2e8f0; padding-top: 12px;">
        <p style="margin:0; font-size: 11px; color: #94a3b8; font-weight: 600;">
            GILTIGHETSTID: Denna länk upphör att fungera {{ $expiresAt }}.
        </p>
    </div>
    {% endif %}
  </div>

  <div style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
    <p style="margin:0; color:#64748b; font-size: 13px; line-height: 1.6; font-style: italic;">
        Om du inte har begärt denna återställning kan du bortse från detta meddelande. Inga ändringar har gjorts på ditt konto.
    </p>
  </div>

  <p style="margin-top: 32px; font-size: 11px; color: #94a3b8; text-align: center; font-style: italic;">
    Genererat av Radix Security Engine: {{ date('Y-m-d H:i') }}
  </p>
{% endblock %}