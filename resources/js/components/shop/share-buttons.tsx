import { useState } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { formatPrice, openWhatsAppConversation, whatsAppUrl } from '@/lib/shop';
import type { Product } from '@/types/shop';

/**
 * Partage d'une fiche produit, image comprise.
 *
 * L'aperçu visuel ne s'obtient jamais en collant une image dans un lien : ce
 * sont les balises Open Graph de la page produit, rendues côté serveur, que
 * WhatsApp et Facebook vont chercher. On partage donc l'URL — la vignette, le
 * titre et le prix suivent.
 *
 * Sur mobile, l'API de partage native fait mieux : elle joint le fichier
 * image lui-même et laisse choisir l'application.
 */
type ShareButtonsProps = {
    product: Product;
    /** URL absolue de la fiche, telle qu'elle sera lue par les robots. */
    url: string;
};

export default function ShareButtons({ product, url }: ShareButtonsProps) {
    const [copied, setCopied] = useState(false);

    const message =
        `*${product.name}*\n` +
        `${formatPrice(product.price)}\n\n` +
        `${product.desc.slice(0, 140)}${product.desc.length > 140 ? '…' : ''}\n\n` +
        `👉 ${url}`;

    /**
     * Partage natif avec le fichier image quand le navigateur le permet,
     * sinon WhatsApp directement.
     */
    const shareNatively = async () => {
        if (typeof navigator.share !== 'function') {
            openWhatsAppConversation('', message);

            return;
        }

        try {
            const response = await fetch(product.img);
            const blob = await response.blob();
            const file = new File([blob], `${product.slug}.webp`, {
                type: blob.type,
            });

            if (navigator.canShare?.({ files: [file] })) {
                await navigator.share({
                    title: product.name,
                    text: `${product.name} — ${formatPrice(product.price)}`,
                    url,
                    files: [file],
                });

                return;
            }

            await navigator.share({ title: product.name, text: message, url });
        } catch {
            // Partage refusé ou image inaccessible : on retombe sur le lien.
            window.open(whatsAppUrl('', message), '_blank');
        }
    };

    const copyLink = async () => {
        try {
            await navigator.clipboard.writeText(url);
            setCopied(true);
            window.setTimeout(() => setCopied(false), 2000);
        } catch {
            setCopied(false);
        }
    };

    return (
        <div className="flex flex-wrap items-center gap-2">
            <span className="mr-1 text-xs font-semibold tracking-wide text-[#4A4A6A] uppercase">
                Partager
            </span>

            {/* WhatsApp : `text` porte le lien, dont l'aperçu se déplie seul. */}
            <a
                href={whatsAppUrl('', message)}
                target="_blank"
                rel="noopener noreferrer"
                onClick={(e) => {
                    // Sur mobile on préfère le partage natif, qui joint l'image.
                    if (typeof navigator.share === 'function') {
                        e.preventDefault();
                        void shareNatively();
                    }
                }}
                className="flex items-center gap-2 rounded-xl bg-[#25D366] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#20BB5A]"
                aria-label={`Partager ${product.name} sur WhatsApp`}
            >
                <WhatsAppIcon className="h-4 w-4 fill-white" />
                WhatsApp
            </a>

            {/* Facebook lit l'Open Graph de l'URL : image, titre et description. */}
            <a
                href={`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-2 rounded-xl bg-[#1877F2] px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-[#0F63D2]"
                aria-label={`Partager ${product.name} sur Facebook`}
            >
                <svg
                    className="h-4 w-4 fill-white"
                    viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path d="M24 12.07C24 5.4 18.63 0 12 0S0 5.4 0 12.07C0 18.1 4.39 23.1 10.13 24v-8.44H7.08v-3.49h3.05V9.41c0-3.02 1.79-4.69 4.53-4.69 1.31 0 2.68.24 2.68.24v2.97h-1.51c-1.49 0-1.96.93-1.96 1.89v2.25h3.33l-.53 3.49h-2.8V24C19.61 23.1 24 18.1 24 12.07z" />
                </svg>
                Facebook
            </a>

            <button
                type="button"
                onClick={copyLink}
                className="flex items-center gap-2 rounded-xl border border-[#E9ECEF] bg-white px-4 py-2.5 text-sm font-semibold text-[#1A1A2E] transition-colors hover:border-[#25D366] hover:bg-[#F8F9FA]"
                aria-label="Copier le lien de la fiche produit"
            >
                {copied ? (
                    <svg
                        className="h-4 w-4 text-[#25D366]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2.5}
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M5 13l4 4L19 7"
                        />
                    </svg>
                ) : (
                    <svg
                        className="h-4 w-4"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M13.828 10.172a4 4 0 010 5.656l-3 3a4 4 0 01-5.656-5.656l1.5-1.5m6.5-6.5l1.5-1.5a4 4 0 115.656 5.656l-3 3a4 4 0 01-5.656 0"
                        />
                    </svg>
                )}
                {copied ? 'Lien copié' : 'Copier le lien'}
            </button>
        </div>
    );
}
