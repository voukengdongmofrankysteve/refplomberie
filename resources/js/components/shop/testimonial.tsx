import type { Testimonial as TestimonialType } from '@/types/shop';

function Stars({ count }: { count: number }) {
    return (
        <div className="flex gap-0.5" aria-label={`${count} étoiles sur 5`}>
            {Array.from({ length: count }).map((_, i) => (
                <svg
                    key={i}
                    className="h-4 w-4 text-[#25D366]"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                    aria-hidden="true"
                >
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

/**
 * Témoignages saisis par l'équipe depuis le back-office.
 *
 * Sans témoignage actif — boutique toute neuve, ou section volontairement
 * désactivée — rien ne s'affiche : une section vide inspirerait moins
 * confiance qu'une section absente.
 */
export default function Testimonial({
    testimonials,
}: {
    testimonials: TestimonialType[];
}) {
    if (testimonials.length === 0) {
        return null;
    }

    const average =
        testimonials.reduce((sum, t) => sum + t.rating, 0) /
        testimonials.length;

    return (
        <section className="bg-[#F8F9FA] py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                <div className="mb-12 text-center">
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Témoignages
                    </p>
                    <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                        Ils nous font confiance
                    </h2>
                    <div className="mt-4 flex items-center justify-center gap-2">
                        <Stars count={Math.round(average)} />
                        <span className="text-sm font-medium text-[#4A4A6A]">
                            {average.toFixed(1)}/5 · {testimonials.length}{' '}
                            avis
                        </span>
                    </div>
                </div>

                <div className="grid gap-5 md:grid-cols-3">
                    {testimonials.map((t) => (
                        <div
                            key={t.id}
                            className="flex flex-col gap-4 rounded-2xl border border-[#E9ECEF] bg-white p-6 transition-all hover:border-[#25D366]/50 hover:shadow-lg"
                        >
                            <svg
                                className="h-8 w-8 text-[#25D366]"
                                fill="currentColor"
                                viewBox="0 0 24 24"
                                aria-hidden="true"
                            >
                                <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                            </svg>

                            <p className="flex-1 text-sm leading-relaxed text-[#4A4A6A] italic">
                                « {t.text} »
                            </p>

                            <div className="flex items-center gap-3 border-t border-[#E9ECEF] pt-2">
                                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#25D366] text-sm font-bold text-[#1A1A2E]">
                                    {t.initials}
                                </div>
                                <div>
                                    <p className="text-sm font-semibold text-[#1A1A2E]">
                                        {t.name}
                                    </p>
                                    {t.role && (
                                        <p className="text-[10px] text-[#4A4A6A]">
                                            {t.role}
                                        </p>
                                    )}
                                </div>
                                <div className="ml-auto">
                                    <Stars count={t.rating} />
                                </div>
                            </div>
                        </div>
                    ))}
                </div>
            </div>
        </section>
    );
}
