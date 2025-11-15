<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de validation AEDDI</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: #ffffff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .title {
            font-size: 24px;
            color: #2d3748;
            margin-bottom: 20px;
        }
        .code-container {
            background-color: #f7fafc;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .code {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
            letter-spacing: 8px;
            font-family: 'Courier New', monospace;
            margin: 10px 0;
        }
        .instructions {
            background-color: #edf2f7;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .instructions h3 {
            color: #2d3748;
            margin-top: 0;
        }
        .instructions ol {
            margin: 10px 0;
            padding-left: 20px;
        }
        .instructions li {
            margin: 8px 0;
        }
        .warning {
            background-color: #fef5e7;
            border-left: 4px solid #f6ad55;
            padding: 15px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #718096;
            font-size: 14px;
        }
        .contact {
            background-color: #e6fffa;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .contact h4 {
            color: #234e52;
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">AEDDI</div>
            <h1 class="title">Code de validation</h1>
        </div>

        <p>Bonjour,</p>
        
        <p>Vous avez demandé à vous inscrire sur la plateforme AEDDI. Voici votre code de validation :</p>

        <div class="code-container">
            <p><strong>Votre code de validation :</strong></p>
            <div class="code">{{ $code }}</div>
            <p><small>Ce code expire dans 24 heures</small></p>
        </div>

        <div class="instructions">
            <h3>📋 Instructions :</h3>
            <ol>
                <li>Retournez sur la page d'inscription</li>
                <li>Entrez votre email : <strong>{{ $email }}</strong></li>
                <li>Saisissez le code ci-dessus</li>
                <li>Complétez votre formulaire d'inscription</li>
            </ol>
        </div>

        <div class="warning">
            <strong>⚠️ Important :</strong>
            <ul>
                <li>Ce code est unique et ne peut être utilisé qu'une seule fois</li>
                <li>Ne partagez jamais ce code avec d'autres personnes</li>
                <li>Si vous n'avez pas demandé cette inscription, ignorez cet email</li>
            </ul>
        </div>

        <div class="contact">
            <h4>📞 Besoin d'aide ?</h4>
            <p>Si vous rencontrez des difficultés ou si vous n'avez pas demandé cette inscription, contactez le trésorier de l'AEDDI.</p>
        </div>

        <div class="footer">
            <p><strong>Association des Étudiants en Développement Durable et Innovation (AEDDI)</strong></p>
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>
