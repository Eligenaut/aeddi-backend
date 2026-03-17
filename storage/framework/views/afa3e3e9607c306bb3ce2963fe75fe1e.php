<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; padding: 20px; }
        .container { max-width: 600px; margin: auto; background: white; border-radius: 10px; padding: 30px; }
        .header { background: #2563eb; color: white; padding: 20px; border-radius: 8px 8px 0 0; text-align: center; }
        .btn { display: inline-block; background: #2563eb; color: white; padding: 12px 30px; border-radius: 8px; text-decoration: none; font-weight: bold; margin: 20px 0; }
        .footer { color: #888; font-size: 12px; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 AEDDI</h1>
        </div>
        <div style="padding: 20px;">
            <h2>Bonjour <?php echo e($user->name); ?> !</h2>
            <p>Votre inscription a été validée. Cliquez sur le bouton ci-dessous pour créer votre mot de passe.</p>
            <div style="text-align: center;">
                <a href="<?php echo e($resetUrl); ?>" class="btn">Créer mon mot de passe</a>
            </div>
            <p style="color: #888; font-size: 13px;">
                Ce lien est valable <strong>24 heures</strong>.<br>
                Si vous n'avez pas demandé cette inscription, ignorez cet email.
            </p>
            <p style="color: #888; font-size: 12px;">Ou copiez ce lien : <?php echo e($resetUrl); ?></p>
        </div>
        <div class="footer">© <?php echo e(date('Y')); ?> AEDDI - Tous droits réservés</div>
    </div>
</body>
</html><?php /**PATH C:\wamp64\www\aeddi\aeddiBack\resources\views/emails/create_password.blade.php ENDPATH**/ ?>