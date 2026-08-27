import { useMemo, useState } from 'react';
import type { Faq as FaqType } from '@/types/shop';

/**
 * Foire aux questions, avec recherche.
 *
 * Filtrée dans le navigateur plutôt qu'en base : une boutique compte au plus
 * quelques dizaines de questions, largement sous le seuil où un aller-retour
 * serveur se justifierait.
 */
export default function Faq({ faqs }: { faqs: FaqType[] }) {
    const [query, setQuery] = useState('');
    const [open, setOpen] = useState<Set<number>>(new Set());

    const filtered = useMemo(() => {
        const term = query.trim().toLowerCase();

        if (term === '') {
            return faqs;
        }

        return faqs.filter(
            (faq) =>
                faq.question.toLowerCase().includes(term) ||
                faq.answer.toLowerCase().includes(term) ||
                faq.category?.toLowerCase().includes(term),
        );
    }, [faqs, query]);

    if (faqs.length === 0) {
        return null;
    }

    const toggle = (id: number) => {
        setOpen((prev) => {
            const next = new Set(prev);

            if (next.has(id)) {
                next.delete(id);
            } else {
                next.add(id);
            }

            return next;
        });
    };

    return (
        <section id="faq" className="bg-white py-16 md:py-24">
            <div className="mx-auto max-w-3xl px-4 md:px-8">
                <div className="mb-10 text-center">
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Questions fréquentes
                    </p>
                    <h2 className="font-display text-3xl font-bold text-[#1A1A2E] md:text-4xl">
                        Une question ?
                    </h2>
                    <p className="mx-auto mt-4 max-w-md text-[#4A4A6A]">
                        Les réponses aux questions qu’on nous pose le plus
                        souvent. Toujours rien trouvé ? Écrivez-nous.
                    </p>
                </div>

                <div className="relative mb-8">
                    <svg
                        className="pointer-events-none absolute top-1/2 left-4 h-5 w-5 -translate-y-1/2 text-[#4A4A6A]"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"
                        />
                    </svg>
                    <input
                        type="search"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        placeholder="Rechercher une question…"
                        aria-label="Rechercher dans la foire aux questions"
                        className="w-full rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] py-3.5 pr-4 pl-12 text-sm text-[#1A1A2E] transition-all focus:border-[#25D366] focus:bg-white focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none"
                    />
                </div>

                {filtered.length === 0 ? (
                    <p className="py-10 text-center text-sm text-[#4A4A6A]">
                        Aucune question ne correspond à «&nbsp;{query}
                        &nbsp;». Essayez un autre mot, ou{' '}
                        <a
                            href="/#contact"
                            className="font-semibold text-[#25D366] hover:underline"
                        >
                            contactez-nous directement
                        </a>
                        .
                    </p>
                ) : (
                    <div className="space-y-3">
                        {filtered.map((faq) => {
                            const expanded = open.has(faq.id);

                            return (
                                <div
                                    key={faq.id}
                                    className="overflow-hidden rounded-xl border border-[#E9ECEF] bg-white"
                                >
                                    <button
                                        type="button"
                                        onClick={() => toggle(faq.id)}
                                        aria-expanded={expanded}
                                        className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left"
                                    >
                                        <span className="text-sm font-semibold text-[#1A1A2E]">
                                            {faq.question}
                                        </span>
                                        <svg
                                            className={`h-5 w-5 shrink-0 text-[#4A4A6A] transition-transform ${expanded ? 'rotate-180' : ''}`}
                                            fill="none"
                                            viewBox="0 0 24 24"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                            aria-hidden="true"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                d="M19 9l-7 7-7-7"
                                            />
                                        </svg>
                                    </button>
                                    {expanded && (
                                        <p className="px-5 pb-4 text-sm leading-relaxed whitespace-pre-line text-[#4A4A6A]">
                                            {faq.answer}
                                        </p>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>
        </section>
    );
}
