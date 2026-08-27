import { formatPrice } from '@/lib/shop';
import type { PriceTier } from '@/types/shop';

type PriceTiersProps = {
    priceTiers: PriceTier[] | undefined;
    activeQty: number;
    /** Version resserrée utilisée dans les cartes produit. */
    compact?: boolean;
};

/** Grille des tarifs dégressifs, palier actif mis en évidence. */
export default function PriceTiers({
    priceTiers,
    activeQty,
    compact = false,
}: PriceTiersProps) {
    if (!priceTiers || priceTiers.length === 0) {
        return null;
    }

    return (
        <div
            className={
                compact
                    ? 'mb-3 overflow-hidden rounded-xl border border-[#E9ECEF] text-xs'
                    : 'mb-6 overflow-hidden rounded-xl border border-[#E9ECEF] text-sm'
            }
        >
            <div
                className={
                    compact
                        ? 'bg-[#F8F9FA] px-3 py-1.5 text-[10px] font-bold tracking-wider text-[#4A4A6A] uppercase'
                        : 'bg-[#F8F9FA] px-4 py-2 text-xs font-bold tracking-wider text-[#4A4A6A] uppercase'
                }
            >
                {compact ? 'Prix dégressifs' : 'Tarifs dégressifs'}
            </div>
            {priceTiers.map((tier, i) => {
                const isActive =
                    activeQty >= tier.minQty &&
                    (tier.maxQty === null || activeQty <= tier.maxQty);
                const label =
                    tier.maxQty === null
                        ? compact
                            ? `≥ ${tier.minQty} pcs`
                            : `À partir de ${tier.minQty} pcs`
                        : tier.minQty === 1
                          ? `1 – ${tier.maxQty} pcs`
                          : `${tier.minQty} – ${tier.maxQty} pcs`;

                return (
                    <div
                        key={i}
                        className={`flex items-center justify-between border-t border-[#E9ECEF] transition-colors ${
                            compact ? 'px-3 py-2' : 'px-4 py-3'
                        } ${
                            isActive
                                ? 'bg-[#E8F5E9] font-bold text-[#1A1A2E]'
                                : 'text-[#4A4A6A]'
                        }`}
                    >
                        <span>{label}</span>
                        <div className="flex items-center gap-2">
                            <span className={isActive ? 'text-[#1DA851]' : ''}>
                                {formatPrice(tier.price)} / pce
                            </span>
                            {isActive && (
                                <span
                                    className={`rounded-full bg-[#25D366] font-bold text-[#1A1A2E] ${
                                        compact
                                            ? 'px-1.5 py-0.5 text-[9px]'
                                            : 'px-2 py-0.5 text-[10px]'
                                    }`}
                                >
                                    Actif
                                </span>
                            )}
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
