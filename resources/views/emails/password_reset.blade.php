<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe - AEDDI</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 20px;
            min-height: 100vh;
        }

        .container {
            max-width: 560px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15), 0 0 0 1px rgba(255, 255, 255, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -30%;
            left: -30%;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .logo-icon {
            font-size: 48px;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
            display: inline-block;
            filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
        }

        .header h1 {
            color: white;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .content {
            padding: 40px 35px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 600;
            color: #1a202c;
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .greeting span {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .message {
            color: #4a5568;
            line-height: 1.6;
            margin-bottom: 28px;
            font-size: 16px;
        }

        .btn-container {
            text-align: center;
            margin: 32px 0;
        }

        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white !important;
            padding: 14px 42px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.5);
        }

        .info-card {
            background: #f7fafc;
            border-radius: 16px;
            padding: 20px;
            margin: 28px 0;
            border: 1px solid #e2e8f0;
        }

        .warning {
            background: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 14px 18px;
            margin: 24px 0;
            border-radius: 12px;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .warning-icon {
            font-size: 20px;
            flex-shrink: 0;
        }

        .warning p {
            margin: 0;
            color: #92400e;
            font-size: 14px;
            line-height: 1.5;
        }

        .warning strong {
            font-weight: 700;
        }

        .link-fallback {
            background: #f1f5f9;
            border-radius: 12px;
            padding: 16px;
            margin: 24px 0;
        }

        .link-fallback p {
            color: #475569;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .link-fallback a {
            color: #667eea;
            text-decoration: none;
            word-break: break-all;
            font-size: 13px;
            font-family: monospace;
            display: block;
            background: white;
            padding: 10px 12px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }

        .link-fallback a:hover {
            border-color: #667eea;
            background: #faf5ff;
        }

        .divider {
            margin: 32px 0 24px;
            border: none;
            border-top: 1px solid #e2e8f0;
        }

        .help-text {
            background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%);
            border-radius: 12px;
            padding: 18px;
            text-align: center;
        }

        .help-text p {
            color: #5b21b6;
            font-size: 14px;
            margin: 0;
        }

        .help-text strong {
            font-weight: 700;
            display: block;
            margin-bottom: 6px;
        }

        .footer {
            background: #fafbfc;
            padding: 24px 35px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer p {
            color: #94a3b8;
            font-size: 12px;
            margin: 0;
        }

        @media (max-width: 480px) {
            body {
                padding: 20px 12px;
            }

            .content {
                padding: 30px 24px;
            }

            .greeting {
                font-size: 20px;
            }

            .btn {
                padding: 12px 32px;
                font-size: 14px;
            }

            .header {
                padding: 30px 20px;
            }

            .footer {
                padding: 20px 24px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo-icon">🔐</div>
            <h1>AEDDI</h1>
        </div>

        <div class="content">
            <div class="greeting">
                Bonjour <span>{{ $user->name }}</span> !
            </div>

            <div class="message">
                Nous avons reçu une demande de réinitialisation de votre mot de passe.
                Cliquez sur le bouton ci-dessous pour créer un nouveau mot de passe en toute sécurité.
            </div>

            <div class="btn-container">
                <a href="{{ $webUrl }}" class="btn" target="_blank">
                    🔑 Réinitialiser mon mot de passe
                </a>
            </div>

            <div class="info-card">
                <div class="warning">
                    <div class="warning-icon">⏰</div>
                    <p><strong>Ce lien expire dans 24 heures</strong><br>Pour des raisons de sécurité, vous avez 24 heures pour effectuer cette réinitialisation.</p>
                </div>

                <div class="link-fallback">
                    <p>📋 Lien direct (copiez-le dans votre navigateur) :</p>
                    <a href="{{ $webUrl }}" target="_blank">{{ $webUrl }}</a>
                </div>
            </div>

            <div class="help-text">
                <p>
                    <strong>❓ Vous n'avez pas demandé cette réinitialisation ?</strong>
                    Aucune action n'est requise. Votre mot de passe actuel reste valide et sécurisé.
                </p>
            </div>

            <hr class="divider">

            <div class="help-text" style="background: transparent; padding: 0;">
                <p style="color: #64748b;">
                    <strong>💬 Besoin d'aide ?</strong><br>
                    Notre équipe support est à votre disposition pour vous assister.
                </p>
            </div>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} AEDDI - Tous droits réservés</p>
            <p style="margin-top: 8px; font-size: 11px;">Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
