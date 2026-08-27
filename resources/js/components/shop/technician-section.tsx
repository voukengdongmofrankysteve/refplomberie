import { useState } from 'react';
import type { ReactNode } from 'react';
import BookingModal from '@/components/shop/booking-modal';
import StarRating from '@/components/shop/star-rating';
import type { Technician } from '@/types/shop';

const STATS: { value: string; label: string; icon: ReactNode }[] = [
    {
        value: '50+',
        label: 'Techniciens',
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"
                />
            </svg>
        ),
    },
    {
        value: '24h',
        label: 'Délai max',
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                strokeWidth={2}
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"
                />
            </svg>
        ),
    },
    {
        value: '4.9',
        label: 'Note moyenne',
        icon: (
            <svg
                className="h-5 w-5 text-[#25D366]"
                fill="currentColor"
                viewBox="0 0 20 20"
            >
                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
            </svg>
        ),
    },
];

type TechnicianSectionProps = {
    technicians: Technician[];
    services: string[];
};

export default function TechnicianSection({
    technicians,
    services,
}: TechnicianSectionProps) {
    const [bookingOpen, setBookingOpen] = useState(false);

    return (
        <section id="techniciens" className="bg-white py-16 md:py-24">
            <div className="mx-auto max-w-7xl px-4 md:px-8">
                {/* En-tête */}
                <div className="mb-14 grid items-end gap-8 md:grid-cols-2">
                    <div>
                        <p className="mb-3 text-xs font-bold tracking-widest text-[#25D366] uppercase">
                            Techniciens certifiés
                        </p>
                        <h2 className="font-display text-3xl leading-snug font-bold text-[#1A1A2E] md:text-4xl">
                            Besoin d&apos;un
                            <br />
                            <span className="text-[#25D366]">technicien ?</span>
                        </h2>
                    </div>
                    <div>
                        <p className="leading-relaxed text-[#4A4A6A]">
                            Nos techniciens certifiés interviennent chez vous
                            partout au Cameroun. Fuite, installation, dépannage
                            — réservez en ligne, confirmez par WhatsApp.
                        </p>
                        <button
                            onClick={() => setBookingOpen(true)}
                            className="mt-5 inline-flex items-center gap-2 rounded-xl bg-[#25D366] px-6 py-3.5 text-sm font-bold text-[#1A1A2E] shadow-md transition-all hover:bg-[#1DA851] hover:shadow-lg"
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
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                                />
                            </svg>
                            Réserver une intervention
                        </button>
                    </div>
                </div>

                {/* Visuel + fiches techniciens */}
                <div className="grid items-start gap-8 md:grid-cols-2">
                    <div className="relative aspect-[4/3] overflow-hidden rounded-2xl shadow-xl">
                        <img
                            src="https://images.unsplash.com/photo-1621905251189-08b45d6a269e?q=80&w=800&auto=format&fit=crop"
                            alt="Technicien plombier au travail"
                            className="h-full w-full object-cover"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-[#1A1A2E]/70 to-transparent" />
                        <div className="absolute right-6 bottom-6 left-6">
                            <div className="flex items-center gap-3 rounded-xl bg-white/95 p-4 backdrop-blur-sm">
                                <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-[#25D366]">
                                    <svg
                                        className="h-6 w-6 text-[#1A1A2E]"
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
                                    <p className="text-sm font-bold text-[#1A1A2E]">
                                        Intervention sous 24h
                                    </p>
                                    <p className="text-xs text-[#4A4A6A]">
                                        Techniciens certifiés au Cameroun
                                    </p>
                                </div>
                                <div className="ml-auto flex items-center gap-1">
                                    <div className="h-2 w-2 animate-pulse rounded-full bg-green-500" />
                                    <span className="text-xs font-semibold text-green-600">
                                        Disponible
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-4">
                        {technicians.map((tech) => (
                            <div
                                key={tech.id}
                                className="flex items-center gap-4 rounded-2xl border border-[#E9ECEF] bg-[#F8F9FA] p-4 transition-all hover:border-[#25D366]/50 hover:shadow-md"
                            >
                                <div className="relative flex-shrink-0">
                                    <img
                                        src={tech.img}
                                        alt={tech.name}
                                        className="h-14 w-14 rounded-xl object-cover"
                                    />
                                    <span
                                        className={`absolute -right-1 -bottom-1 h-4 w-4 rounded-full border-2 border-white ${
                                            tech.available
                                                ? 'bg-green-500'
                                                : 'bg-gray-400'
                                        }`}
                                        aria-label={
                                            tech.available
                                                ? 'Disponible'
                                                : 'Occupé'
                                        }
                                    />
                                </div>
                                <div className="min-w-0 flex-1">
                                    <p className="font-display text-sm font-bold text-[#1A1A2E]">
                                        {tech.name}
                                    </p>
                                    <p className="text-xs text-[#4A4A6A]">
                                        {tech.specialty}
                                    </p>
                                    <div className="mt-1 flex items-center gap-2">
                                        <StarRating
                                            rating={tech.rating}
                                            className="h-3.5 w-3.5"
                                        />
                                        <span className="text-[10px] text-[#4A4A6A]">
                                            {tech.rating} · {tech.jobs}{' '}
                                            interventions
                                        </span>
                                    </div>
                                </div>
                                <div className="flex flex-shrink-0 flex-col items-end gap-2">
                                    <span className="text-[10px] font-bold text-[#4A4A6A]">
                                        {tech.experience}
                                    </span>
                                    <button
                                        onClick={() => setBookingOpen(true)}
                                        disabled={!tech.available}
                                        className={`rounded-lg px-3 py-1.5 text-xs font-bold transition-colors ${
                                            tech.available
                                                ? 'bg-[#25D366] text-[#1A1A2E] hover:bg-[#1DA851]'
                                                : 'cursor-not-allowed bg-gray-200 text-gray-400'
                                        }`}
                                    >
                                        {tech.available ? 'Réserver' : 'Occupé'}
                                    </button>
                                </div>
                            </div>
                        ))}

                        {/* Chiffres clés */}
                        <div className="mt-4 grid grid-cols-3 gap-3">
                            {STATS.map((s) => (
                                <div
                                    key={s.label}
                                    className="rounded-xl border border-[#E9ECEF] bg-white p-3 text-center"
                                >
                                    <div className="mb-1 flex justify-center">
                                        {s.icon}
                                    </div>
                                    <p className="font-display text-lg font-bold text-[#1A1A2E]">
                                        {s.value}
                                    </p>
                                    <p className="text-[10px] text-[#4A4A6A]">
                                        {s.label}
                                    </p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </div>

            {bookingOpen && (
                <BookingModal
                    services={services}
                    onClose={() => setBookingOpen(false)}
                />
            )}
        </section>
    );
}
