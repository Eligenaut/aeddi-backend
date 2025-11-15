<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Réinitialisation de votre mot de passe AEDDI</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: #f4f6fb;
            color: #222;
            max-width: 480px;
            margin: 0 auto;
            padding: 0;
        }
        .container {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 16px #667eea15;
            margin: 20px 0;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #00c6fb 100%);
            color: white;
            padding: 24px 20px 16px 20px;
            text-align: center;
        }
        .header img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            box-shadow: 0 2px 8px #0002;
            margin-bottom: 8px;
            background: #fff;
            object-fit: cover;
        }
        .header h1 {
            margin: 0 0 6px 0;
            font-size: 1.6rem;
            letter-spacing: 0.5px;
        }
        .header h2 {
            margin: 0;
            font-size: 1rem;
            font-weight: 400;
            letter-spacing: 0.3px;
        }
        .content {
            background: #f8f9fa;
            padding: 24px 20px;
        }
        .reset-code {
            background: #fff;
            border: 2px solid #667eea;
            border-radius: 8px;
            padding: 18px;
            text-align: center;
            margin: 20px 0 16px 0;
            font-size: 1.6rem;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 4px;
            box-shadow: 0 2px 8px #667eea11;
        }
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 16px;
            margin: 18px 0 14px 0;
            border-radius: 6px;
            color: #1565c0;
            font-size: 0.9rem;
        }
        .cta-btn {
            display: inline-block;
            background: linear-gradient(90deg, #667eea 0%, #00c6fb 100%);
            color: #fff !important;
            text-decoration: none;
            font-weight: 600;
            padding: 12px 24px;
            border-radius: 6px;
            margin: 18px 0 0 0;
            font-size: 1rem;
            box-shadow: 0 2px 8px #667eea22;
            transition: background 0.2s;
        }
        .cta-btn:hover {
            background: linear-gradient(90deg, #00c6fb 0%, #667eea 100%);
        }
        .footer {
            text-align: center;
            margin: 0 0 18px 0;
            color: #888;
            font-size: 12px;
            padding: 0 20px 16px 20px;
        }
        @media (max-width: 480px) {
            .container, .content, .header, .footer { padding-left: 12px; padding-right: 12px; }
            .header { padding: 20px 12px 12px 12px; }
            .content { padding: 20px 12px; }
            .reset-code { font-size: 1.4rem; padding: 16px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="http://localhost:8000/images/aeddi.png" alt="Logo AEDDI">
            <h1>AEDDI</h1>
            <h2>Réinitialisation de mot de passe</h2>
        </div>
        <div class="content">
            @isset($user)
            <p>Bonjour <strong>{{ $user->prenom }} {{ $user->nom }}</strong>,</p>
            @else
            <p>Bonjour,</p>
            @endisset
            <p>Vous avez demandé la réinitialisation de votre mot de passe pour votre compte AEDDI.</p>
            <p>Utilisez le code ci-dessous pour créer un nouveau mot de passe :</p>
            <div class="reset-code">
                {{ $resetCode }}
            </div>
            <div class="info-box">
                <p><strong>⚠️ Important :</strong> Ce code expire dans <strong>15 minutes</strong>. Ne le partagez avec personne.</p>
            </div>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
            <p style="margin-top:20px;">Cordialement,<br><strong>L'équipe AEDDI</strong></p>
        </div>
        <div class="footer">
            <p>Association des Étudiants de Diego Suarez (AEDDI)</p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
