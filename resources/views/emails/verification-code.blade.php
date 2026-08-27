<x-mail-layout :title="'Code de confirmation'">
    <p style="margin:0 0 14px; font-size:15px;">Bonjour {{ $name }},</p>

    <p style="margin:0 0 18px; font-size:14px; line-height:1.6; color:#4A4A6A;">
        Voici votre code pour activer les notifications par email sur votre
        espace client. Il est valable {{ $minutes }} minutes.
    </p>

    <div style="text-align:center; margin:22px 0;">
        <div style="display:inline-block; background:#E8F5E9; border:1px solid #25D366; border-radius:12px; padding:16px 28px; font-size:30px; font-weight:bold; letter-spacing:8px; color:#1A1A2E;">
            {{ $code }}
        </div>
    </div>

    <p style="margin:0; font-size:12px; line-height:1.6; color:#4A4A6A;">
        Si vous n’êtes pas à l’origine de cette demande, ignorez simplement ce
        message : aucune notification ne sera activée sans ce code.
    </p>
</x-mail-layout>
