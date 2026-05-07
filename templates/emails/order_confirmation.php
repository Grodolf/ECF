    
    <div style="background: #6B1F3F; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
        <p style="margin: 10px 0 0 0;">Confirmation de commande</p>
    </div>
    
    <div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
        
        <p>Bonjour {{prenom}} {{nom}},</p>
        
        <p>Nous avons bien reçu votre commande <strong>#{{order_id}}</strong>.</p>
        
        <h2 style="color: #6B1F3F; border-bottom: 2px solid #B8860B; padding-bottom: 10px;">Détails de votre commande</h2>
        
        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; background: white;"><strong>Menu :</strong></td>
                <td style="padding: 8px; background: white;">{{menu_title}}</td>
            </tr>
            <tr>
                <td style="padding: 8px; background: #f5f5f5;"><strong>Nombre de personnes :</strong></td>
                <td style="padding: 8px; background: #f5f5f5;">{{nb_people}}</td>
            </tr>
            <tr>
                <td style="padding: 8px; background: white;"><strong>Date de livraison :</strong></td>
                <td style="padding: 8px; background: white;">{{delivery_date}} à {{delivery_time}}</td>
            </tr>
            <tr>
                <td style="padding: 8px; background: #f5f5f5;"><strong>Adresse de livraison :</strong></td>
                <td style="padding: 8px; background: #f5f5f5;">{{delivery_address}}, {{delivery_city}}</td>
            </tr>
        </table>
        
        <h2 style="color: #6B1F3F; border-bottom: 2px solid #B8860B; padding-bottom: 10px;">Récapitulatif financier</h2>
        
        <table style="width: 100%; margin: 20px 0;">
            <tr>
                <td style="padding: 8px; background: white;">Prix du menu :</td>
                <td style="padding: 8px; background: white; text-align: right;">{{menu_price}} €</td>
            </tr>
            <tr>
                <td style="padding: 8px; background: #f5f5f5;">Réduction :</td>
                <td style="padding: 8px; background: #f5f5f5; text-align: right; color: #28a745;">- {{reduction}} €</td>
            </tr>
            <tr>
                <td style="padding: 8px; background: white;">Frais de livraison :</td>
                <td style="padding: 8px; background: white; text-align: right;">{{delivery_cost}} €</td>
            </tr>
            <tr style="border-top: 2px solid #6B1F3F;">
                <td style="padding: 12px; background: #6B1F3F; color: white; font-weight: bold;">TOTAL :</td>
                <td style="padding: 12px; background: #6B1F3F; color: white; text-align: right; font-weight: bold; font-size: 1.2em;">{{total_price}} €</td>
            </tr>
        </table>
        
        <div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0; border-radius: 4px;">
            <p style="margin: 0;"><strong>⚠️ Important :</strong> Votre commande sera traitée dans les plus brefs délais. Vous recevrez un email de confirmation dès sa validation par notre équipe.</p>
        </div>
        
        <p>Pour toute question, n'hésitez pas à nous contacter.</p>
        
        <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe Vite & Gourmand</strong></p>
        
    </div>
    
    <div style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9em;">
        <p>© 2026 Vite & Gourmand - Tous droits réservés</p>
    </div>
