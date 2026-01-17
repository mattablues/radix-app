{% extends "layouts/email.ratio.php" %}
{% block title %}Systemnotis: Kontaktmeddelande{% endblock %}

{% block body %}
  <p style="margin:0 0 16px 0; color:#0f172a; font-weight: 700;">Systemstatus: Ny inkommande förfrågan</p>
  <p style="margin:0 0 24px 0; color:#475569;">
    Radix Engine har tagit emot ett nytt meddelande via det publika kontaktformuläret. Detaljer följer nedan:
  </p>

  <!-- Informationsblock -->
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color: #f8fafc; border-radius: 12px; margin-bottom: 24px;">
    <tr>
      <td style="padding: 20px;">
        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
          <tr>
            <td style="padding-bottom: 8px; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Avsändare</td>
          </tr>
          <tr>
            <td style="padding-bottom: 16px; color: #0f172a; font-weight: 600;">{{ $name ?? 'Ej angivet' }}</td>
          </tr>
          <tr>
            <td style="padding-bottom: 8px; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">E-postadress</td>
          </tr>
          <tr>
            <td style="color: #2563eb; font-weight: 600;">{{ $email ?? '—' }}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

  <!-- Meddelandeblock -->
  <p style="margin:0 0 8px 0; font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 1px;">Meddelande</p>
  <div style="background-color: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px; color: #334155; line-height: 1.6; white-space: pre-line;">
    {{ $message ?? $body }}
  </div>

  <!-- Åtgärd -->
  {% if (isset($email) && $email) : %}
  <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
    <tr>
      <td align="center" style="padding: 32px 0 8px 0;">
        <a href="mailto:{{ $email }}" class="btn-primary">
          Svara på meddelandet
        </a>
      </td>
    </tr>
  </table>
  {% endif %}

  <p style="margin-top: 24px; font-size: 11px; color: #94a3b8; text-align: center; font-style: italic;">
    Loggat av Radix Core: {{ date('Y-m-d H:i:s') }}
  </p>
{% endblock %}