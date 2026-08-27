import { useCallback, useEffect, useState } from 'react';

type ProductGalleryProps = {
    images: string[];
    name: string;
    badge: string | null;
    discount: number | null;
    fav: boolean;
    onToggleFav: () => void;
};

/** Galerie produit : vue principale, miniatures et visionneuse plein écran. */
export default function ProductGallery({
    images,
    name,
    badge,
    discount,
    fav,
    onToggleFav,
}: ProductGalleryProps) {
    const [active, setActive] = useState(0);
    const [lightbox, setLightbox] = useState(false);

    // Navigation clavier dans la visionneuse.
    const handleKey = useCallback(
        (e: KeyboardEvent) => {
            if (!lightbox) {
                return;
            }

            if (e.key === 'ArrowRight') {
                setActive((i) => (i + 1) % images.length);
            }

            if (e.key === 'ArrowLeft') {
                setActive((i) => (i - 1 + images.length) % images.length);
            }

            if (e.key === 'Escape') {
                setLightbox(false);
            }
        },
        [lightbox, images.length],
    );

    useEffect(() => {
        window.addEventListener('keydown', handleKey);

        return () => window.removeEventListener('keydown', handleKey);
    }, [handleKey]);

    if (images.length === 0) {
        return null;
    }

    return (
        <>
            <div className="flex flex-col gap-3">
                {/* Vue principale */}
                <div
                    className="relative aspect-square cursor-zoom-in overflow-hidden rounded-2xl bg-[#F8F9FA]"
                    onClick={() => setLightbox(true)}
                    role="button"
                    aria-label="Agrandir l'image"
                >
                    <img
                        key={active}
                        src={images[active]}
                        alt={`${name} — vue ${active + 1}`}
                        className="h-full w-full object-cover transition-opacity duration-300"
                    />

                    {/* Badges */}
                    <div className="pointer-events-none absolute top-4 left-4 flex flex-col gap-2">
                        {badge && (
                            <span
                                className={`rounded-full px-3 py-1.5 text-xs font-bold uppercase shadow ${
                                    badge === 'Promo'
                                        ? 'bg-red-500 text-white'
                                        : badge === 'Bestseller'
                                          ? 'bg-[#25D366] text-white'
                                          : badge === 'Nouveau'
                                            ? 'bg-[#1A1A2E] text-white'
                                            : 'bg-[#4A4A6A] text-white'
                                }`}
                            >
                                {badge}
                            </span>
                        )}
                        {discount && (
                            <span className="rounded-full bg-red-500 px-3 py-1.5 text-xs font-bold text-white shadow">
                                -{discount}%
                            </span>
                        )}
                    </div>

                    {/* Favori */}
                    <button
                        onClick={(e) => {
                            e.stopPropagation();
                            onToggleFav();
                        }}
                        className={`absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-xl shadow transition-all ${
                            fav
                                ? 'bg-red-500 text-white'
                                : 'bg-white/90 text-[#4A4A6A] hover:text-red-500'
                        }`}
                        aria-label={
                            fav ? 'Retirer des favoris' : 'Ajouter aux favoris'
                        }
                    >
                        <svg
                            className="h-5 w-5"
                            fill={fav ? 'currentColor' : 'none'}
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12z"
                            />
                        </svg>
                    </button>

                    {/* Indice de zoom */}
                    <div className="pointer-events-none absolute right-3 bottom-3 rounded-lg bg-black/40 px-2 py-1 text-[10px] font-semibold text-white backdrop-blur-sm">
                        <svg
                            className="mr-1 inline h-3.5 w-3.5"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke="currentColor"
                            strokeWidth={2}
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607zM10.5 7.5v6m3-3h-6"
                            />
                        </svg>
                        Agrandir
                    </div>

                    {/* Compteur */}
                    <div className="pointer-events-none absolute bottom-3 left-3 rounded-lg bg-black/40 px-2 py-1 text-[10px] font-semibold text-white backdrop-blur-sm">
                        {active + 1} / {images.length}
                    </div>
                </div>

                {/* Miniatures */}
                <div className="grid grid-cols-4 gap-2">
                    {images.map((src, i) => (
                        <button
                            key={i}
                            onClick={() => setActive(i)}
                            className={`relative aspect-square overflow-hidden rounded-xl border-2 transition-all ${
                                i === active
                                    ? 'scale-105 border-[#25D366] shadow-md'
                                    : 'border-transparent opacity-70 hover:border-[#25D366]/50 hover:opacity-100'
                            }`}
                            aria-label={`Vue ${i + 1}`}
                        >
                            <img
                                src={src}
                                alt={`${name} miniature ${i + 1}`}
                                className="h-full w-full object-cover"
                                loading="lazy"
                            />
                        </button>
                    ))}
                </div>
            </div>

            {/* Visionneuse */}
            {lightbox && (
                <>
                    <div
                        className="fixed inset-0 z-[60] bg-black/90 backdrop-blur-sm"
                        onClick={() => setLightbox(false)}
                        aria-hidden="true"
                    />

                    <div
                        className="fixed inset-0 z-[60] flex items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-label={`Image ${active + 1} sur ${images.length}`}
                    >
                        <button
                            onClick={() => setLightbox(false)}
                            className="absolute top-4 right-4 flex h-10 w-10 items-center justify-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                            aria-label="Fermer"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2.5}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>

                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                setActive(
                                    (i) =>
                                        (i - 1 + images.length) % images.length,
                                );
                            }}
                            className="absolute top-1/2 left-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                            aria-label="Image précédente"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2.5}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M15 19l-7-7 7-7"
                                />
                            </svg>
                        </button>

                        <img
                            src={images[active]}
                            alt={`${name} — vue ${active + 1}`}
                            className="max-h-[85vh] max-w-[90vw] rounded-xl object-contain shadow-2xl"
                            onClick={(e) => e.stopPropagation()}
                        />

                        <button
                            onClick={(e) => {
                                e.stopPropagation();
                                setActive((i) => (i + 1) % images.length);
                            }}
                            className="absolute top-1/2 right-4 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                            aria-label="Image suivante"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2.5}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M9 5l7 7-7 7"
                                />
                            </svg>
                        </button>

                        {/* Pastilles */}
                        <div className="absolute bottom-5 left-1/2 flex -translate-x-1/2 gap-2">
                            {images.map((_, i) => (
                                <button
                                    key={i}
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        setActive(i);
                                    }}
                                    className={`h-2 w-2 rounded-full transition-all ${
                                        i === active
                                            ? 'scale-125 bg-[#25D366]'
                                            : 'bg-white/40 hover:bg-white/70'
                                    }`}
                                    aria-label={`Aller à l'image ${i + 1}`}
                                />
                            ))}
                        </div>
                    </div>
                </>
            )}
        </>
    );
}
