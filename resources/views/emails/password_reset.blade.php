<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 30px; }
        .header { background: #7c3aed; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .btn { display: inline-block; background: #7c3aed; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .btn-app { display: inline-block; background: #6b7280; color: white; padding: 8px 20px; border-radius: 8px; text-decoration: none; font-size: 13px; }
        .footer { color: #888; font-size: 12px; margin-top: 20px; text-align: center; }
        .warning { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px; }
        .warning p { margin: 0; color: #92400e; font-size: 14px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔐 AEDDI</h1>
        </div>
        <div style="padding: 20px;">
            <h2>Bonjour {{ $user->name }} !</h2>
            <p>Nous avons reçu une demande de réinitialisation de votre mot de passe. Cliquez sur le bouton ci-dessous pour réinitialiser votre mot de passe.</p>

            {{-- Bouton principal → Web --}}
            <div style="text-align: center;">
                <a href="{{ $webUrl }}" class="btn">
                    Réinitialiser mon mot de passe
                </a>
            </div>

            {{-- Lien secondaire → App mobile --}}
            <p style="text-align: center; color: #888; font-size: 13px; margin-top: 0;">
                📱 Vous avez l'application AEDDI ?
                <a href="{{ $appUrl }}" style="color: #7c3aed; font-weight: bold;">Ouvrir dans l'app</a>
            </p>

            <div class="warning">
                <p><strong>⚠️ Attention :</strong> Ce lien expire dans <strong>24 heures</strong>.</p>
            </div>

            <p style="color: #666; font-size: 14px;">
                Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email.
            </p>

            <p style="color: #888; font-size: 12px; margin-top: 20px;">
                <strong>Ou copiez ce lien dans votre navigateur :</strong><br>
                <span style="word-break: break-all;">{{ $webUrl }}</span>
            </p>

            <hr style="margin: 20px 0; border: none; border-top: 1px solid #e5e7eb;">

            <p style="color: #666; font-size: 13px;">
                <strong>Besoin d'aide ?</strong><br>
                Si vous rencontrez des problèmes, veuillez contacter l'équipe AEDDI.
            </p>
        </div>
        <div class="footer">© {{ date('Y') }} AEDDI - Tous droits réservés</div>
    </div>
</body>
</html>
