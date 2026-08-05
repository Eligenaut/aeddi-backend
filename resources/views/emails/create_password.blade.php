<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 0; }
        .header { background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 30px; }
        .code-box { background: #f0f4ff; border: 2px dashed #2563eb; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; }
        .code-box .code { font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #2563eb; font-family: 'Courier New', monospace; }
        .footer { color: #888; font-size: 12px; margin-top: 20px; text-align: center; padding: 20px; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning p { margin: 0; color: #92400e; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 AEDDI</h1>
        </div>
        <div class="content">
            <h2>Bonjour {{ $user->name }} !</h2>
            <p>Votre inscription a été validée. Utilisez le code ci-dessous pour créer votre mot de passe.</p>

            <div class="code-box">
                <p style="margin: 0 0 10px 0; color: #666; font-size: 14px;">Votre code de vérification</p>
                <div class="code">{{ $code }}</div>
            </div>

            <div class="warning">
                <p><strong>⚠️ Attention :</strong> Ce code expire dans <strong>24 heures</strong>.</p>
            </div>

            <p style="color: #666; font-size: 14px;">
                Si vous n'avez pas demandé cette inscription, veuillez ignorer cet email.
            </p>

            <p style="color: #666; font-size: 13px; margin-top: 20px; text-align: center;">
                Retournez sur l'application et saisissez ce code pour finaliser votre inscription.
            </p>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">

            <p style="color: #666; font-size: 13px;">
                <strong>Besoin d'aide ?</strong><br>
                Contactez l'équipe AEDDI.
            </p>
        </div>
        <div class="footer">© {{ date('Y') }} AEDDI - Tous droits réservés</div>
    </div>
</body>
</html>
