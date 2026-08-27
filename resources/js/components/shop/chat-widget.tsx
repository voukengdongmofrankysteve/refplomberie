import { useForm } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { useStoreInfo } from '@/hooks/use-store-info';
import { openWhatsAppConversation, prepareWhatsAppTarget } from '@/lib/shop';
import { track } from '@/lib/track';
import { home } from '@/routes';
import { store as storeMessage } from '@/routes/contact-messages';
import type { Faq } from '@/types/shop';

type Message = {
    id: number;
    from: 'bot' | 'user';
    text: string;
};

/**
 * Correspond une question libre à la FAQ la plus proche.
 *
 * Score grossier — le nombre de mots de la question qui apparaissent dans
 * l'entrée — mais suffisant pour un assistant qui n'a pas la prétention de
 * comprendre le langage naturel : il reconnaît des mots-clés, rien de plus.
 */
function bestFaqMatch(query: string, faqs: Faq[]): Faq | null {
    const words = query
        .toLowerCase()
        .split(/[^a-zà-ÿ0-9]+/)
        .filter((word) => word.length > 2);

    if (words.length === 0) {
        return null;
    }

    let best: { faq: Faq; score: number } | null = null;

    for (const faq of faqs) {
        const haystack =
            `${faq.question} ${faq.answer} ${faq.category ?? ''}`.toLowerCase();
        const score = words.filter((word) => haystack.includes(word)).length;

        if (score > 0 && (best === null || score > best.score)) {
            best = { faq, score };
        }
    }

    return best?.faq ?? null;
}

/** Petit assistant scripté : FAQ, horaires, et relais vers WhatsApp ou le formulaire de contact. */
export default function ChatWidget() {
    const store = useStoreInfo();
    const [open, setOpen] = useState(false);
    const [faqs, setFaqs] = useState<Faq[] | null>(null);
    const [escalate, setEscalate] = useState(false);
    const [input, setInput] = useState('');
    const [messages, setMessages] = useState<Message[]>([
        {
            id: 0,
            from: 'bot',
            text: `Bonjour ! Je suis l’assistant de ${store.name}. Posez-moi une question, ou choisissez une option ci-dessous.`,
        },
    ]);
    const nextId = useRef(1);
    const scrollRef = useRef<HTMLDivElement>(null);

    const contactForm = useForm({
        name: '',
        phone: '',
        message: '',
    });

    useEffect(() => {
        scrollRef.current?.scrollTo({ top: scrollRef.current.scrollHeight });
    }, [messages, escalate]);

    // La FAQ n'est chargée qu'à la première ouverture : la plupart des
    // visiteurs ne parlent jamais à l'assistant, inutile de l'envoyer avec
    // chaque page.
    useEffect(() => {
        if (!open || faqs !== null) {
            return;
        }

        fetch('/api/v1/faq')
            .then((response) => response.json())
            .then((payload: { data: Faq[] }) => setFaqs(payload.data))
            .catch(() => setFaqs([]));
    }, [open, faqs]);

    const addMessage = (from: Message['from'], text: string) => {
        setMessages((prev) => [...prev, { id: nextId.current++, from, text }]);
    };

    const askHuman = () => {
        track('whatsapp_click', { label: 'Assistant du site' });
        const target = prepareWhatsAppTarget();
        openWhatsAppConversation(
            store.whatsapp,
            'Bonjour, j’ai une question pour vous.',
            target,
        );
    };

    const handleQuickReply = (key: 'hours' | 'order' | 'human') => {
        if (key === 'hours') {
            addMessage(
                'bot',
                `Nos horaires : ${store.hours}\nAdresse : ${store.address}\nTéléphone : ${store.phone}`,
            );

            return;
        }

        if (key === 'order') {
            addMessage(
                'bot',
                'Retrouvez le suivi de vos commandes dans votre espace client, rubrique « Mes commandes ».',
            );

            return;
        }

        askHuman();
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const question = input.trim();

        if (question === '') {
            return;
        }

        addMessage('user', question);
        setInput('');

        const match = faqs ? bestFaqMatch(question, faqs) : null;

        if (match) {
            addMessage('bot', match.answer);
        } else {
            addMessage(
                'bot',
                'Je n’ai pas de réponse toute prête pour ça. Laissez-moi vos coordonnées et nous vous répondrons rapidement — ou contactez-nous directement sur WhatsApp.',
            );
            contactForm.setData('message', question);
            setEscalate(true);
        }
    };

    const submitEscalation = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        contactForm.post(storeMessage.url(), {
            preserveScroll: true,
            onSuccess: () => {
                addMessage(
                    'bot',
                    'Merci ! Votre message a été transmis, nous vous recontactons sous 24h.',
                );
                setEscalate(false);
                contactForm.reset();
            },
        });
    };

    return (
        <div className="fixed right-4 bottom-4 z-50 md:right-6 md:bottom-6">
            {open && (
                <div className="mb-3 flex h-[28rem] w-[22rem] max-w-[calc(100vw-2rem)] flex-col overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-xl">
                    <div className="flex items-center justify-between bg-[#1A1A2E] px-4 py-3">
                        <p className="text-sm font-bold text-white">
                            Assistant {store.name}
                        </p>
                        <button
                            onClick={() => setOpen(false)}
                            aria-label="Fermer l’assistant"
                            className="text-white/70 transition-colors hover:text-white"
                        >
                            <svg
                                className="h-5 w-5"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>

                    <div
                        ref={scrollRef}
                        className="flex-1 space-y-3 overflow-y-auto bg-[#F8F9FA] px-3 py-4"
                    >
                        {messages.map((message) => (
                            <div
                                key={message.id}
                                className={
                                    message.from === 'user'
                                        ? 'ml-auto max-w-[85%] rounded-2xl rounded-br-sm bg-[#25D366] px-3 py-2 text-sm text-[#1A1A2E]'
                                        : 'mr-auto max-w-[85%] rounded-2xl rounded-bl-sm bg-white px-3 py-2 text-sm whitespace-pre-line text-[#1A1A2E] shadow-sm'
                                }
                            >
                                {message.text}
                            </div>
                        ))}

                        {messages.length === 1 && (
                            <div className="flex flex-wrap gap-2">
                                <button
                                    onClick={() => handleQuickReply('hours')}
                                    className="rounded-full border border-[#25D366] px-3 py-1.5 text-xs font-semibold text-[#1A1A2E] transition-colors hover:bg-[#E8F5E9]"
                                >
                                    Horaires &amp; contact
                                </button>
                                <button
                                    onClick={() => handleQuickReply('order')}
                                    className="rounded-full border border-[#25D366] px-3 py-1.5 text-xs font-semibold text-[#1A1A2E] transition-colors hover:bg-[#E8F5E9]"
                                >
                                    Suivre ma commande
                                </button>
                                <a
                                    href={`${home().url}#faq`}
                                    className="rounded-full border border-[#25D366] px-3 py-1.5 text-xs font-semibold text-[#1A1A2E] transition-colors hover:bg-[#E8F5E9]"
                                >
                                    Voir la FAQ
                                </a>
                                <button
                                    onClick={() => handleQuickReply('human')}
                                    className="flex items-center gap-1.5 rounded-full bg-[#25D366] px-3 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#20BB5A]"
                                >
                                    <WhatsAppIcon className="h-3.5 w-3.5 fill-white" />
                                    Parler à un humain
                                </button>
                            </div>
                        )}

                        {escalate && (
                            <form
                                onSubmit={submitEscalation}
                                className="space-y-2 rounded-2xl border border-[#E9ECEF] bg-white p-3"
                            >
                                <input
                                    type="text"
                                    required
                                    placeholder="Votre nom"
                                    value={contactForm.data.name}
                                    onChange={(e) =>
                                        contactForm.setData(
                                            'name',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border border-[#E9ECEF] px-3 py-2 text-xs focus:border-[#25D366] focus:outline-none"
                                />
                                <input
                                    type="tel"
                                    required
                                    placeholder="Votre téléphone"
                                    value={contactForm.data.phone}
                                    onChange={(e) =>
                                        contactForm.setData(
                                            'phone',
                                            e.target.value,
                                        )
                                    }
                                    className="w-full rounded-lg border border-[#E9ECEF] px-3 py-2 text-xs focus:border-[#25D366] focus:outline-none"
                                />
                                <button
                                    type="submit"
                                    disabled={contactForm.processing}
                                    className="w-full rounded-lg bg-[#25D366] py-2 text-xs font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:opacity-60"
                                >
                                    {contactForm.processing
                                        ? 'Envoi…'
                                        : 'Envoyer ma question'}
                                </button>
                            </form>
                        )}
                    </div>

                    <form
                        onSubmit={handleSubmit}
                        className="flex items-center gap-2 border-t border-[#E9ECEF] p-2"
                    >
                        <input
                            type="text"
                            value={input}
                            onChange={(e) => setInput(e.target.value)}
                            placeholder="Écrivez votre question…"
                            className="flex-1 rounded-full border border-[#E9ECEF] px-3 py-2 text-sm focus:border-[#25D366] focus:outline-none"
                        />
                        <button
                            type="submit"
                            aria-label="Envoyer"
                            className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-[#25D366] text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                        >
                            <svg
                                className="h-4 w-4"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke="currentColor"
                                strokeWidth={2}
                            >
                                <path
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5"
                                />
                            </svg>
                        </button>
                    </form>
                </div>
            )}

            <button
                onClick={() => setOpen((value) => !value)}
                aria-label={
                    open ? 'Fermer l’assistant' : 'Ouvrir l’assistant du site'
                }
                className="flex h-14 w-14 items-center justify-center rounded-full bg-[#1A1A2E] text-white shadow-lg transition-transform hover:scale-105"
            >
                {open ? (
                    <svg
                        className="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M6 18L18 6M6 6l12 12"
                        />
                    </svg>
                ) : (
                    <svg
                        className="h-6 w-6"
                        fill="none"
                        viewBox="0 0 24 24"
                        stroke="currentColor"
                        strokeWidth={2}
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm3.75 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a5.969 5.969 0 01-.474-.065 4.48 4.48 0 00.978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z"
                        />
                    </svg>
                )}
            </button>
        </div>
    );
}
