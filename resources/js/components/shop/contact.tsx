import { useForm } from '@inertiajs/react';
import type { ChangeEvent, FormEvent, ReactNode } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { useStoreInfo } from '@/hooks/use-store-info';
import { openWhatsAppConversation } from '@/lib/shop';
import { track } from '@/lib/track';
import { store as storeMessage } from '@/routes/contact-messages';

const FIELD_CLASS =
    'w-full border border-[#E9ECEF] rounded-xl px-4 py-3 text-sm text-[#1A1A2E] bg-[#F8F9FA] focus:bg-white focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none transition-all';

const LABEL_CLASS =
    'block text-xs font-semibold text-[#4A4A6A] mb-1.5 uppercase tracking-wide';

const PHONE_ICON: ReactNode = (
    <svg
        className="h-5 w-5"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        strokeWidth={2}
        aria-hidden="true"
    >
        <path
            strokeLinecap="round"
            strokeLinejoin="round"
            d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 002.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 01-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 00-1.091-.852H4.5A2.25 2.25 0 002.25 4.5v2.25z"
        />
    </svg>
);

export default function Contact() {
    const store = useStoreInfo();

    // La demande de devis est enregistrée en base (table `contact_messages`)
    // et consultable depuis le back-office.
    const { data, setData, post, processing, errors, reset, wasSuccessful } =
        useForm({
            name: '',
            email: '',
            phone: '',
            subject: '',
            message: '',
        });

    const form = data;
    const submitted = wasSuccessful;

    const handleChange = (
        e: ChangeEvent<
            HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement
        >,
    ) => setData(e.target.name as keyof typeof data, e.target.value as never);

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(storeMessage.url(), { preserveScroll: true });
    };

    const handleWhatsApp = () => {
        track('whatsapp_click', { label: 'Formulaire de contact' });

        const msg =
            `Bonjour Réf. Plomberie,\n\nJe souhaite obtenir un devis.\n\n` +
            `Nom: ${form.name}\nTéléphone: ${form.phone}\n\n${form.message}`;
        openWhatsAppConversation(store.whatsapp, msg);
    };

    const contactCards = [
        {
            icon: PHONE_ICON,
            label: 'Téléphone',
            value: store.phone,
            sub: 'Lun–Sam, 7h–18h',
        },
        {
            icon: (
                <svg
                    className="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                    aria-hidden="true"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"
                    />
                </svg>
            ),
            label: 'Email',
            value: store.email,
            sub: 'Réponse sous 24h',
        },
        {
            icon: (
                <svg
                    className="h-5 w-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    strokeWidth={2}
                    aria-hidden="true"
                >
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M15 10.5a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1115 0z"
                    />
                </svg>
            ),
            label: 'Localisation',
            value: 'Cameroun',
            sub: 'Livraison dans tout le pays',
        },
        {
            icon: <WhatsAppIcon className="h-5 w-5 fill-[#25D366]" />,
            label: 'WhatsApp',
            value: store.phone,
            sub: 'Réponse immédiate',
        },
    ];

    return (
        <section id="contact" className="bg-[#F8F9FA] py-16 md:py-24">
            <div className="mx-auto grid max-w-7xl items-start gap-12 px-4 md:grid-cols-2 md:px-8">
                {/* Colonne gauche */}
                <div>
                    <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                        Contact &amp; Devis
                    </p>
                    <h2 className="mb-5 font-display text-3xl leading-snug font-bold text-[#1A1A2E] md:text-4xl">
                        Parlons de votre
                        <br />
                        <span className="text-[#25D366]">projet</span>
                    </h2>
                    <p className="mb-8 max-w-sm leading-relaxed text-[#4A4A6A]">
                        Devis gratuit sous 24h. Nos experts plombiers sont
                        disponibles pour répondre à toutes vos questions
                        techniques.
                    </p>

                    <div className="space-y-4">
                        {contactCards.map((c) => (
                            <div
                                key={c.label}
                                className="flex items-center gap-4 rounded-xl border border-[#E9ECEF] bg-white p-4"
                            >
                                <div className="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-xl bg-[#E8F5E9] text-[#25D366]">
                                    {c.icon}
                                </div>
                                <div>
                                    <p className="text-xs font-medium text-[#4A4A6A]">
                                        {c.label}
                                    </p>
                                    <p className="text-sm font-semibold text-[#1A1A2E]">
                                        {c.value}
                                    </p>
                                    <p className="text-[10px] text-[#4A4A6A]">
                                        {c.sub}
                                    </p>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Colonne droite — formulaire */}
                <div className="rounded-2xl border border-[#E9ECEF] bg-white p-6 shadow-sm md:p-8">
                    {submitted ? (
                        <div className="py-12 text-center">
                            <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-[#E8F5E9]">
                                <svg
                                    className="h-8 w-8 text-[#25D366]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2}
                                    aria-hidden="true"
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M5 13l4 4L19 7"
                                    />
                                </svg>
                            </div>
                            <h3 className="mb-2 font-display text-xl font-bold text-[#1A1A2E]">
                                Message envoyé !
                            </h3>
                            <p className="text-sm text-[#4A4A6A]">
                                Nous vous recontacterons sous 24h.
                            </p>
                            <button
                                onClick={() => reset()}
                                className="mt-6 text-sm font-semibold text-[#25D366] underline underline-offset-4"
                            >
                                Envoyer un autre message
                            </button>
                        </div>
                    ) : (
                        <form onSubmit={handleSubmit} className="space-y-5">
                            <h3 className="mb-1 font-display text-xl font-bold text-[#1A1A2E]">
                                Demande de devis gratuit
                            </h3>
                            <p className="mb-4 text-xs text-[#4A4A6A]">
                                Réponse garantie sous 24h ouvrées
                            </p>

                            {Object.values(errors).length > 0 && (
                                <ul className="space-y-1 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs text-red-600">
                                    {Object.entries(errors).map(
                                        ([field, message]) => (
                                            <li key={field}>{message}</li>
                                        ),
                                    )}
                                </ul>
                            )}

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label
                                        htmlFor="contact-name"
                                        className={LABEL_CLASS}
                                    >
                                        Nom complet *
                                    </label>
                                    <input
                                        id="contact-name"
                                        name="name"
                                        type="text"
                                        required
                                        value={form.name}
                                        onChange={handleChange}
                                        className={FIELD_CLASS}
                                        placeholder="Jean Mbarga"
                                    />
                                </div>
                                <div>
                                    <label
                                        htmlFor="contact-phone"
                                        className={LABEL_CLASS}
                                    >
                                        Téléphone *
                                    </label>
                                    <input
                                        id="contact-phone"
                                        name="phone"
                                        type="tel"
                                        required
                                        value={form.phone}
                                        onChange={handleChange}
                                        className={FIELD_CLASS}
                                        placeholder="+237 6 00 00 00 00"
                                    />
                                </div>
                            </div>

                            <div>
                                <label
                                    htmlFor="contact-email"
                                    className={LABEL_CLASS}
                                >
                                    Email
                                </label>
                                <input
                                    id="contact-email"
                                    name="email"
                                    type="email"
                                    value={form.email}
                                    onChange={handleChange}
                                    className={FIELD_CLASS}
                                    placeholder="jean@example.com"
                                />
                            </div>

                            <div>
                                <label
                                    htmlFor="contact-subject"
                                    className={LABEL_CLASS}
                                >
                                    Sujet
                                </label>
                                <select
                                    id="contact-subject"
                                    name="subject"
                                    value={form.subject}
                                    onChange={handleChange}
                                    className={`${FIELD_CLASS} cursor-pointer`}
                                >
                                    <option value="">Choisir un sujet</option>
                                    <option value="devis">
                                        Demande de devis
                                    </option>
                                    <option value="commande">
                                        Question sur une commande
                                    </option>
                                    <option value="produit">
                                        Renseignement produit
                                    </option>
                                    <option value="urgence">
                                        Urgence plomberie
                                    </option>
                                    <option value="autre">Autre</option>
                                </select>
                            </div>

                            <div>
                                <label
                                    htmlFor="contact-message"
                                    className={LABEL_CLASS}
                                >
                                    Votre message *
                                </label>
                                <textarea
                                    id="contact-message"
                                    name="message"
                                    rows={4}
                                    required
                                    value={form.message}
                                    onChange={handleChange}
                                    className={`${FIELD_CLASS} resize-none`}
                                    placeholder="Décrivez votre projet ou votre besoin..."
                                />
                            </div>

                            <div className="flex flex-col gap-3 sm:flex-row">
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex-1 rounded-xl bg-[#25D366] py-3.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:opacity-60"
                                >
                                    {processing
                                        ? 'Envoi…'
                                        : 'Envoyer le message'}
                                </button>
                                <button
                                    type="button"
                                    onClick={handleWhatsApp}
                                    className="flex items-center justify-center gap-2 rounded-xl bg-[#25D366] px-5 py-3.5 text-sm font-bold text-white transition-colors hover:bg-[#20BB5A]"
                                >
                                    <WhatsAppIcon />
                                    WhatsApp
                                </button>
                            </div>
                        </form>
                    )}
                </div>
            </div>
        </section>
    );
}
