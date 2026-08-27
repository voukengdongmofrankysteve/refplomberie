import { useCart } from '@/contexts/cart-context';

/** Notification « produit ajouté » affichée en bas de l'écran. */
export default function CartToast() {
    const { toast } = useCart();

    if (!toast) {
        return null;
    }

    return (
        <div
            role="status"
            aria-live="polite"
            className="toast fixed bottom-6 left-1/2 z-[60] -translate-x-1/2"
        >
            <div className="flex items-center gap-3 rounded-2xl bg-[#1A1A2E] px-5 py-3.5 text-sm font-medium text-white shadow-2xl">
                <span className="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-[#25D366]">
                    <svg
                        className="h-3.5 w-3.5 text-[#1A1A2E]"
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
                </span>
                {toast}
            </div>
        </div>
    );
}
