<!DOCTYPE html>
<html lang="pl">
<head><meta charset="UTF-8"><title>Nowe zapytanie kontaktowe</title></head>
<body style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; color: #333;">
    <h2 style="color: #1d4ed8; border-bottom: 2px solid #1d4ed8; padding-bottom: 8px;">
        Nowe zapytanie ze strony głównej
    </h2>

    <table style="width: 100%; border-collapse: collapse; margin-top: 16px;">
        <tr>
            <td style="padding: 8px 0; font-weight: bold; width: 120px; color: #555;">Imię i nazwisko:</td>
            <td style="padding: 8px 0;">{{ $inquiry->name }}</td>
        </tr>
        <tr style="background: #f9fafb;">
            <td style="padding: 8px 4px; font-weight: bold; color: #555;">E-mail:</td>
            <td style="padding: 8px 4px;"><a href="mailto:{{ $inquiry->email }}">{{ $inquiry->email }}</a></td>
        </tr>
        @if($inquiry->phone)
        <tr>
            <td style="padding: 8px 0; font-weight: bold; color: #555;">Telefon:</td>
            <td style="padding: 8px 0;">{{ $inquiry->phone }}</td>
        </tr>
        @endif
        @if($inquiry->subject)
        <tr style="background: #f9fafb;">
            <td style="padding: 8px 4px; font-weight: bold; color: #555;">Temat:</td>
            <td style="padding: 8px 4px;">{{ $inquiry->subject }}</td>
        </tr>
        @endif
    </table>

    <div style="margin-top: 20px;">
        <p style="font-weight: bold; color: #555; margin-bottom: 8px;">Wiadomość:</p>
        <div style="background: #f3f4f6; border-left: 4px solid #1d4ed8; padding: 16px; border-radius: 4px; white-space: pre-wrap;">{{ $inquiry->message }}</div>
    </div>

    <p style="margin-top: 24px; color: #6b7280; font-size: 12px; border-top: 1px solid #e5e7eb; padding-top: 12px;">
        Wysłano: {{ $inquiry->created_at->format('d.m.Y H:i') }} &nbsp;·&nbsp;
        <a href="{{ url('/admin/contacts') }}" style="color: #1d4ed8;">Otwórz w panelu admina</a>
    </p>
</body>
</html>
