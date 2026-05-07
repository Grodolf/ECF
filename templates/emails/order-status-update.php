
<div style="background: #6B1F3F; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;">
    <p style="margin: 10px 0 0 0;">Mise à jour de votre commande</p>
</div>
<div style="background: #f9f9f9; padding: 20px; border: 1px solid #ddd; border-top: none; border-radius: 0 0 8px 8px;">
    <p>Bonjour {{prenom}} {{nom}},</p>
    <p>Le statut de votre commande <strong>#{{order_id}}</strong> a été mis à jour.</p>

    <h2 style="color: #6B1F3F; border-bottom: 2px solid #B8860B; padding-bottom: 10px;">Nouveau statut</h2>
    <table style="width: 100%; margin: 20px 0;">
        <tr>
            <td style="padding: 8px; background: white;"><strong>Menu :</strong></td>
            <td style="padding: 8px; background: white;">{{menu_title}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; background: #f5f5f5;"><strong>Date de livraison :</strong></td>
            <td style="padding: 8px; background: #f5f5f5;">{{delivery_date}} à {{delivery_time}}</td>
        </tr>
        <tr>
            <td style="padding: 8px; background: white;"><strong>Statut :</strong></td>
            <td style="padding: 8px; background: white; color: #6B1F3F;"><strong>{{status_name}}</strong></td>
        </tr>
        <tr>
            <td style="padding: 8px; background: #f5f5f5;"><strong>{{comment_label}}</strong></td>
            <td style="padding: 8px; background: #f5f5f5;">{{comment}}</td>
        </tr>
    </table>

    <p>Connectez-vous à votre espace pour suivre l'évolution de votre commande.</p>
    <p style="margin-top: 30px;">Cordialement,<br><strong>L'équipe Vite & Gourmand</strong></p>
</div>
<div style="text-align: center; margin-top: 20px; color: #666; font-size: 0.9em;">
    <p>© 2026 Vite & Gourmand - Tous droits réservés</p>
</div>
