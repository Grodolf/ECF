<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <!-- Container principal -->
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="padding: 20px 0;">
                <!-- Card email -->
                <table role="presentation" width="600" style="margin: 0 auto; background-color: #ffffff; border-radius: 8px; overflow: hidden;">
                    
                    <!-- Header -->
                    <tr>
                        <td style="background-color: #6B1F3F; padding: 30px; text-align: center;">
                            <h1 style="color: #B8860B; margin: 0; font-size: 28px; font-weight: bold;">Vite & Gourmand</h1>
                            <p style="color: #ffffff; margin: 10px 0 0 0; font-size: 14px;">Traiteur à Bordeaux</p>
                        </td>
                    </tr>
                    
                    <!-- Contenu -->
                    <tr>
                        <td style="padding: 40px 30px; color: #333333; font-size: 16px; line-height: 1.6;">
                            {{content}}
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #2c2c2c; padding: 20px; text-align: center;">
                            <p style="color: #B8860B; margin: 0 0 10px 0; font-size: 16px; font-weight: bold;">
                                Vite & Gourmand
                            </p>
                            <p style="color: #cccccc; margin: 0; font-size: 14px;">
                                Traiteur professionnel à Bordeaux depuis 25 ans
                            </p>
                        </td>
                    </tr>
                    
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
