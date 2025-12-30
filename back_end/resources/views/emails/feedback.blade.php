<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; background-color: #f9f9f9; }
        .header { background-color: #007bff; color: white; padding: 10px; border-radius: 8px 8px 0 0; text-align: center; }
        .content { padding: 20px; }
        .footer { font-size: 0.8em; color: #777; text-align: center; margin-top: 20px; }
        .rating { font-weight: bold; color: #ffc107; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Nouveau Feedback</h2>
        </div>
        <div class="content">
            <p>Bonjour,</p>
            <p>Vous avez reçu un nouveau feedback de la part de <strong>{{ $data['name'] }}</strong>.</p>
            
            <p><strong>Note :</strong> <span class="rating">{{ $data['rating'] }}/5</span></p>
            
            <p><strong>Commentaire :</strong></p>
            <blockquote style="background: #fff; padding: 15px; border-left: 4px solid #007bff;">
                {{ $data['comment'] }}
            </blockquote>
        </div>
        <div class="footer">
            <p>Ce mail a été envoyé automatiquement depuis votre application.</p>
        </div>
    </div>
</body>
</html>
