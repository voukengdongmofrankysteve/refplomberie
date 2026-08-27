import { useCallback, useEffect, useRef, useState } from 'react';
import { track } from '@/lib/track';
import type { Story } from '@/types/shop';

/**
 * Fil de statuts de la boutique — arrivages, chantiers, courtes vidéos.
 *
 * Le déroulé est horizontal : les cartes défilent latéralement, et un clic
 * ouvre la visionneuse plein écran avec navigation au clavier.
 */
export default function StoriesRail({ stories }: { stories: Story[] }) {
    const railRef = useRef<HTMLDivElement | null>(null);
    const [openIndex, setOpenIndex] = useState<number | null>(null);

    const close = useCallback(() => setOpenIndex(null), []);

    const go = useCallback(
        (step: number) =>
            setOpenIndex((index) =>
                index === null
                    ? null
                    : (index + step + stories.length) % stories.length,
            ),
        [stories.length],
    );

    useEffect(() => {
        if (openIndex === null) {
            return;
        }

        const onKey = (e: KeyboardEvent) => {
            if (e.key === 'Escape') {
                close();
            }

            if (e.key === 'ArrowRight') {
                go(1);
            }

            if (e.key === 'ArrowLeft') {
                go(-1);
            }
        };

        window.addEventListener('keydown', onKey);

        return () => window.removeEventListener('keydown', onKey);
    }, [openIndex, close, go]);

    if (stories.length === 0) {
        return null;
    }

    const scroll = (direction: -1 | 1) =>
        railRef.current?.scrollBy({
            left: direction * 320,
            behavior: 'smooth',
        });

    const current = openIndex === null ? null : stories[openIndex];

    return (
        <section id="actualites" className="bg-white py-12 md:py-16">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                {/* En-tête */}
                <div className="mb-6 flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <p className="mb-2 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                            En direct du magasin
                        </p>
                        <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                            Arrivages &amp; chantiers
                        </h2>
                    </div>

                    {/* Flèches de défilement, masquées sur mobile où le doigt suffit. */}
                    <div className="hidden gap-2 md:flex">
                        <button
                            onClick={() => scroll(-1)}
                            className="flex h-10 w-10 items-center justify-center rounded-xl border border-[#E9ECEF] text-[#4A4A6A] transition-colors hover:border-[#25D366] hover:text-[#1A1A2E]"
                            aria-label="Faire défiler vers la gauche"
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
                        <button
                            onClick={() => scroll(1)}
                            className="flex h-10 w-10 items-center justify-center rounded-xl border border-[#E9ECEF] text-[#4A4A6A] transition-colors hover:border-[#25D366] hover:text-[#1A1A2E]"
                            aria-label="Faire défiler vers la droite"
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
                    </div>
                </div>

                {/* Rail horizontal */}
                <div
                    ref={railRef}
                    className="stories-rail flex snap-x snap-mandatory gap-4 overflow-x-auto pb-4"
                >
                    {stories.map((story, index) => (
                        <button
                            key={story.id}
                            onClick={() => {
                                setOpenIndex(index);
                                track('story_view', {
                                    subject: 'story',
                                    id: story.id,
                                    label: story.title,
                                });
                            }}
                            className="group relative aspect-[9/16] w-44 shrink-0 snap-start overflow-hidden rounded-2xl border border-[#E9ECEF] bg-[#F8F9FA] text-left transition-transform hover:-translate-y-1 sm:w-52"
                        >
                            {story.thumbnailUrl && (
                                <img
                                    src={story.thumbnailUrl}
                                    alt={story.title}
                                    loading="lazy"
                                    className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                />
                            )}

                            {/* Voile pour garder le titre lisible sur toute photo. */}
                            <div className="absolute inset-0 bg-gradient-to-t from-[#1A1A2E]/85 via-transparent to-transparent" />

                            {story.type === 'video' && (
                                <span className="absolute top-3 right-3 flex h-8 w-8 items-center justify-center rounded-full bg-white/90 text-[#1A1A2E]">
                                    <svg
                                        className="h-4 w-4"
                                        viewBox="0 0 24 24"
                                        fill="currentColor"
                                    >
                                        <path d="M8 5v14l11-7z" />
                                    </svg>
                                </span>
                            )}

                            <div className="absolute inset-x-0 bottom-0 p-3">
                                <p className="line-clamp-2 text-sm font-bold text-white">
                                    {story.title}
                                </p>
                                {story.caption && (
                                    <p className="mt-0.5 line-clamp-1 text-[11px] text-white/70">
                                        {story.caption}
                                    </p>
                                )}
                            </div>
                        </button>
                    ))}
                </div>
            </div>

            {/* Visionneuse plein écran */}
            {current && (
                <>
                    <div
                        className="fixed inset-0 z-[60] bg-black/90 backdrop-blur-sm"
                        onClick={close}
                        aria-hidden="true"
                    />
                    <div
                        className="fixed inset-0 z-[60] flex items-center justify-center p-4"
                        role="dialog"
                        aria-modal="true"
                        aria-label={current.title}
                    >
                        <button
                            onClick={close}
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

                        {stories.length > 1 && (
                            <>
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        go(-1);
                                    }}
                                    className="absolute left-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                                    aria-label="Statut précédent"
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
                                <button
                                    onClick={(e) => {
                                        e.stopPropagation();
                                        go(1);
                                    }}
                                    className="absolute right-4 flex h-11 w-11 items-center justify-center rounded-xl bg-white/10 text-white transition-colors hover:bg-white/20"
                                    aria-label="Statut suivant"
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
                            </>
                        )}

                        <figure
                            className="flex max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-[#1A1A2E]"
                            onClick={(e) => e.stopPropagation()}
                        >
                            {current.type === 'video' ? (
                                <video
                                    key={current.id}
                                    src={current.mediaUrl ?? undefined}
                                    poster={current.thumbnailUrl ?? undefined}
                                    controls
                                    autoPlay
                                    playsInline
                                    className="max-h-[70vh] w-full bg-black object-contain"
                                />
                            ) : (
                                <img
                                    key={current.id}
                                    src={current.mediaUrl ?? undefined}
                                    alt={current.title}
                                    className="max-h-[70vh] w-full object-contain"
                                />
                            )}

                            <figcaption className="p-4">
                                <p className="font-display font-bold text-white">
                                    {current.title}
                                </p>
                                {current.caption && (
                                    <p className="mt-1 text-sm text-white/70">
                                        {current.caption}
                                    </p>
                                )}
                                {current.linkUrl && (
                                    <a
                                        href={current.linkUrl}
                                        className="mt-3 inline-flex rounded-xl bg-[#25D366] px-5 py-2.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                                    >
                                        {current.linkLabel ?? 'Voir le produit'}
                                    </a>
                                )}
                            </figcaption>
                        </figure>
                    </div>
                </>
            )}
        </section>
    );
}
