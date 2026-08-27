import { useForm, usePage } from '@inertiajs/react';
import type { FormEvent } from 'react';
import WhatsAppIcon from '@/components/shop/whatsapp-icon';
import { useShopAuth } from '@/contexts/auth-modal-context';
import { openWhatsAppConversation, prepareWhatsAppTarget } from '@/lib/shop';
import { store as storeRequest } from '@/routes/technician-requests';

const HOURS = [
    '07:00',
    '08:00',
    '09:00',
    '10:00',
    '11:00',
    '12:00',
    '13:00',
    '14:00',
    '15:00',
    '16:00',
    '17:00',
];

const FIELD_CLASS =
    'w-full rounded-xl border border-[#E9ECEF] bg-[#F8F9FA] px-4 py-3 text-sm text-[#1A1A2E] transition-all focus:border-[#25D366] focus:bg-white focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none';

const LABEL_CLASS =
    'mb-1.5 block text-xs font-semibold tracking-wide text-[#4A4A6A] uppercase';

type BookingModalProps = {
    onClose: () => void;
    /** Services proposés, servis depuis `config/shop.php`. */
    services: string[];
};

/**
 * Formulaire de demande d'intervention.
 *
 * La demande est enregistrée dans `technician_requests` puis résumée sur
 * WhatsApp — l'administrateur la retrouve dans son back-office.
 */
export default function BookingModal({ onClose, services }: BookingModalProps) {
    const { store } = usePage().props;
    const { user } = useShopAuth();

    const { data, setData, post, processing, errors, wasSuccessful } = useForm({
        customer_name: user?.name ?? '',
        customer_phone: user?.phone ?? '',
        address: user?.address ?? '',
        service: '',
        preferred_date: '',
        preferred_time: '',
        description: '',
    });

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        // Cible réservée pendant le clic : depuis la réponse asynchrone, le
        // navigateur y verrait une pop-up et la bloquerait.
        const target = prepareWhatsAppTarget();

        post(storeRequest.url(), {
            preserveScroll: true,
            onError: () => target?.close(),
            onSuccess: () => {
                const message =
                    `🔧 *Demande d'intervention — Réf. Plomberie*\n\n` +
                    `Nom : ${data.customer_name}\n` +
                    `Téléphone : ${data.customer_phone}\n` +
                    `Adresse : ${data.address}\n` +
                    `Service : ${data.service}\n` +
                    `Date souhaitée : ${data.preferred_date} à ${data.preferred_time}\n\n` +
                    `Description :\n${data.description}\n\n` +
                    `🔗 ${window.location.origin}`;

                openWhatsAppConversation(store.whatsapp, message, target);
            },
        });
    };

    return (
        <>
            <div
                className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
                onClick={onClose}
                aria-hidden="true"
            />
            <div
                className="fixed inset-0 z-50 flex items-center justify-center px-4 py-8"
                role="dialog"
                aria-modal="true"
                aria-label="Réserver un technicien"
            >
                <div className="animate-modal max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white shadow-2xl">
                    {/* En-tête */}
                    <div className="sticky top-0 flex items-center justify-between rounded-t-2xl border-b border-[#E9ECEF] bg-white px-6 py-4">
                        <div className="flex items-center gap-3">
                            <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#25D366]">
                                <svg
                                    className="h-5 w-5 text-[#1A1A2E]"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke="currentColor"
                                    strokeWidth={2}
                                >
                                    <path
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"
                                    />
                                </svg>
                            </div>
                            <div>
                                <h2 className="font-display font-bold text-[#1A1A2E]">
                                    Réserver un technicien
                                </h2>
                                <p className="text-xs text-[#4A4A6A]">
                                    Intervention à domicile au Cameroun
                                </p>
                            </div>
                        </div>
                        <button
                            onClick={onClose}
                            className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 transition-colors hover:bg-gray-200"
                            aria-label="Fermer"
                        >
                            <svg
                                className="h-4 w-4 text-[#4A4A6A]"
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
                    </div>

                    <div className="px-6 py-6">
                        {wasSuccessful ? (
                            <div className="py-8 text-center">
                                <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-green-100">
                                    <svg
                                        className="h-8 w-8 text-green-600"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke="currentColor"
                                        strokeWidth={2}
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            d="M5 13l4 4L19 7"
                                        />
                                    </svg>
                                </div>
                                <h3 className="mb-2 font-display text-xl font-bold text-[#1A1A2E]">
                                    Demande enregistrée !
                                </h3>
                                <p className="mb-6 text-sm text-[#4A4A6A]">
                                    Votre demande a été transmise à notre
                                    équipe. Vous la retrouvez dans votre espace
                                    client.
                                </p>
                                <button
                                    onClick={onClose}
                                    className="rounded-xl bg-[#25D366] px-8 py-3 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851]"
                                >
                                    Fermer
                                </button>
                            </div>
                        ) : (
                            <form onSubmit={handleSubmit} className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            htmlFor="booking-name"
                                            className={LABEL_CLASS}
                                        >
                                            Nom complet *
                                        </label>
                                        <input
                                            id="booking-name"
                                            type="text"
                                            required
                                            value={data.customer_name}
                                            onChange={(e) =>
                                                setData(
                                                    'customer_name',
                                                    e.target.value,
                                                )
                                            }
                                            className={FIELD_CLASS}
                                            placeholder="Jean Mbarga"
                                        />
                                        {errors.customer_name && (
                                            <p className="mt-1.5 text-xs text-red-600">
                                                {errors.customer_name}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label
                                            htmlFor="booking-phone"
                                            className={LABEL_CLASS}
                                        >
                                            Téléphone *
                                        </label>
                                        <input
                                            id="booking-phone"
                                            type="tel"
                                            required
                                            value={data.customer_phone}
                                            onChange={(e) =>
                                                setData(
                                                    'customer_phone',
                                                    e.target.value,
                                                )
                                            }
                                            className={FIELD_CLASS}
                                            placeholder="+237 6 00 00 00 00"
                                        />
                                        {errors.customer_phone && (
                                            <p className="mt-1.5 text-xs text-red-600">
                                                {errors.customer_phone}
                                            </p>
                                        )}
                                    </div>
                                </div>

                                <div>
                                    <label
                                        htmlFor="booking-address"
                                        className={LABEL_CLASS}
                                    >
                                        Adresse d&apos;intervention *
                                    </label>
                                    <input
                                        id="booking-address"
                                        type="text"
                                        required
                                        value={data.address}
                                        onChange={(e) =>
                                            setData('address', e.target.value)
                                        }
                                        className={FIELD_CLASS}
                                        placeholder="Quartier, ville, Cameroun"
                                    />
                                    {errors.address && (
                                        <p className="mt-1.5 text-xs text-red-600">
                                            {errors.address}
                                        </p>
                                    )}
                                </div>

                                <div>
                                    <label
                                        htmlFor="booking-service"
                                        className={LABEL_CLASS}
                                    >
                                        Type de service *
                                    </label>
                                    <select
                                        id="booking-service"
                                        required
                                        value={data.service}
                                        onChange={(e) =>
                                            setData('service', e.target.value)
                                        }
                                        className={`${FIELD_CLASS} cursor-pointer`}
                                    >
                                        <option value="">
                                            Sélectionner un service
                                        </option>
                                        {services.map((s) => (
                                            <option key={s} value={s}>
                                                {s}
                                            </option>
                                        ))}
                                    </select>
                                    {errors.service && (
                                        <p className="mt-1.5 text-xs text-red-600">
                                            {errors.service}
                                        </p>
                                    )}
                                </div>

                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label
                                            htmlFor="booking-date"
                                            className={LABEL_CLASS}
                                        >
                                            Date souhaitée
                                        </label>
                                        <input
                                            id="booking-date"
                                            type="date"
                                            value={data.preferred_date}
                                            onChange={(e) =>
                                                setData(
                                                    'preferred_date',
                                                    e.target.value,
                                                )
                                            }
                                            min={
                                                new Date()
                                                    .toISOString()
                                                    .split('T')[0]
                                            }
                                            className={FIELD_CLASS}
                                        />
                                        {errors.preferred_date && (
                                            <p className="mt-1.5 text-xs text-red-600">
                                                {errors.preferred_date}
                                            </p>
                                        )}
                                    </div>
                                    <div>
                                        <label
                                            htmlFor="booking-time"
                                            className={LABEL_CLASS}
                                        >
                                            Heure
                                        </label>
                                        <select
                                            id="booking-time"
                                            value={data.preferred_time}
                                            onChange={(e) =>
                                                setData(
                                                    'preferred_time',
                                                    e.target.value,
                                                )
                                            }
                                            className={`${FIELD_CLASS} cursor-pointer`}
                                        >
                                            <option value="">Choisir</option>
                                            {HOURS.map((t) => (
                                                <option key={t} value={t}>
                                                    {t}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div>
                                    <label
                                        htmlFor="booking-description"
                                        className={LABEL_CLASS}
                                    >
                                        Description du problème *
                                    </label>
                                    <textarea
                                        id="booking-description"
                                        rows={3}
                                        required
                                        value={data.description}
                                        onChange={(e) =>
                                            setData(
                                                'description',
                                                e.target.value,
                                            )
                                        }
                                        className={`${FIELD_CLASS} resize-none`}
                                        placeholder="Décrivez le problème ou les travaux à effectuer..."
                                    />
                                    {errors.description && (
                                        <p className="mt-1.5 text-xs text-red-600">
                                            {errors.description}
                                        </p>
                                    )}
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="flex w-full items-center justify-center gap-2 rounded-xl bg-[#25D366] py-4 text-sm font-bold text-white transition-colors hover:bg-[#20BB5A] disabled:opacity-60"
                                >
                                    <WhatsAppIcon />
                                    {processing
                                        ? 'Envoi…'
                                        : 'Envoyer la demande'}
                                </button>
                                <p className="text-center text-[10px] text-[#4A4A6A]">
                                    Votre demande est enregistrée puis transmise
                                    sur WhatsApp
                                </p>
                            </form>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
