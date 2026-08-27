import AppLogoIcon from '@/components/app-logo-icon';

/**
 * Bloc de marque de la barre latérale.
 *
 * Reprend exactement le logo de la vitrine : carré vert, « Réf.Plomberie »
 * avec « Plomberie » en vert, et la baseline « Matériaux & Équipements ».
 */
export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded-xl bg-[#25D366] text-[#1A1A2E]">
                <AppLogoIcon className="size-5" />
            </div>
            <div className="ml-1 grid flex-1 text-left">
                <span className="truncate font-display text-sm leading-tight font-bold text-[#1A1A2E]">
                    Réf.<span className="text-[#25D366]">Plomberie</span>
                </span>
                <span className="truncate text-[10px] leading-tight tracking-wide text-[#4A4A6A]">
                    Matériaux &amp; Équipements
                </span>
            </div>
        </>
    );
}
