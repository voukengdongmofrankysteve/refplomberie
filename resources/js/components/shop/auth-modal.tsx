import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import GoogleMark from '@/components/google-mark';
import { useShopAuth } from '@/contexts/auth-modal-context';
import type { AuthModalMode } from '@/contexts/auth-modal-context';
import { google } from '@/routes/auth';
import { store as loginStore } from '@/routes/login';
import { store as registerStore } from '@/routes/register';

const FIELD_CLASS =
    'w-full border border-[#E9ECEF] rounded-xl px-4 py-3 text-sm text-[#1A1A2E] bg-[#F8F9FA] focus:bg-white focus:border-[#25D366] focus:ring-2 focus:ring-[#25D366]/20 focus:outline-none transition-all';

const LABEL_CLASS =
    'block text-xs font-semibold text-[#4A4A6A] mb-1.5 uppercase tracking-wide';

/**
 * Modale de connexion / inscription.
 *
 * Les soumissions passent par les routes Fortify (`/login`, `/register`) via
 * Inertia : les erreurs de validation reviennent dans `errors`, et la session
 * authentifiée est ensuite partagée par `HandleInertiaRequests`.
 */
export default function AuthModal() {
    const { googleEnabled } = usePage().props;
    const { authModal, setAuthModal } = useShopAuth();
    const [mode, setMode] = useState<AuthModalMode>(authModal ?? 'login');
    const [openedAs, setOpenedAs] = useState(authModal);

    const { data, setData, post, processing, errors, reset, clearErrors } =
        useForm({
            name: '',
            email: '',
            password: '',
            password_confirmation: '',
        });

    // La modale s'ouvre sur le mode demandé par l'appelant, sans passer par un
    // effet : on aligne l'état pendant le rendu quand la demande change.
    if (openedAs !== authModal) {
        setOpenedAs(authModal);

        if (authModal) {
            setMode(authModal);
        }
    }

    if (!authModal) {
        return null;
    }

    const close = () => {
        setAuthModal(null);
        reset();
        clearErrors();
    };

    const switchMode = (next: AuthModalMode) => {
        setMode(next);
        clearErrors();
        reset('password', 'password_confirmation');
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const target =
            mode === 'login' ? loginStore.url() : registerStore.url();
        post(target, {
            preserveScroll: true,
            onSuccess: () => close(),
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <>
            <div
                className="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm"
                onClick={close}
                aria-hidden="true"
            />

            <div
                className="fixed inset-0 z-50 flex items-center justify-center px-4"
                role="dialog"
                aria-modal="true"
                aria-label={mode === 'login' ? 'Connexion' : 'Inscription'}
            >
                <div className="animate-modal relative w-full max-w-md rounded-2xl bg-white p-8 shadow-2xl">
                    <button
                        onClick={close}
                        className="absolute top-4 right-4 flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 transition-colors hover:bg-gray-200"
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

                    {/* Logo */}
                    <div className="mb-6 flex items-center gap-2">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#25D366]">
                            <svg
                                viewBox="0 0 24 24"
                                fill="none"
                                className="h-6 w-6"
                            >
                                <path
                                    d="M12 3C8.5 3 6 5.5 6 9c0 2.5 1.5 4.5 3.5 5.5V17h5v-2.5C16.5 13.5 18 11.5 18 9c0-3.5-2.5-6-6-6z"
                                    fill="#1A1A2E"
                                />
                                <rect
                                    x="9"
                                    y="17"
                                    width="6"
                                    height="4"
                                    rx="1"
                                    fill="#1A1A2E"
                                    opacity="0.6"
                                />
                            </svg>
                        </div>
                        <span className="font-display font-bold text-[#1A1A2E]">
                            Réf.
                            <span className="text-[#25D366]">Plomberie</span>
                        </span>
                    </div>

                    <h2 className="mb-1 font-display text-2xl font-bold text-[#1A1A2E]">
                        {mode === 'login' ? 'Bienvenue !' : 'Créer un compte'}
                    </h2>
                    <p className="mb-6 text-sm text-[#4A4A6A]">
                        {mode === 'login'
                            ? 'Connectez-vous pour accéder à vos favoris et commandes.'
                            : 'Rejoignez Réf. Plomberie pour une meilleure expérience.'}
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        {mode === 'register' && (
                            <div>
                                <label
                                    htmlFor="auth-name"
                                    className={LABEL_CLASS}
                                >
                                    Nom complet *
                                </label>
                                <input
                                    id="auth-name"
                                    name="name"
                                    type="text"
                                    required
                                    autoComplete="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                    className={FIELD_CLASS}
                                    placeholder="Jean Mbarga"
                                />
                                {errors.name && (
                                    <p className="mt-1.5 text-xs text-red-600">
                                        {errors.name}
                                    </p>
                                )}
                            </div>
                        )}

                        <div>
                            <label htmlFor="auth-email" className={LABEL_CLASS}>
                                Email *
                            </label>
                            <input
                                id="auth-email"
                                name="email"
                                type="email"
                                required
                                autoComplete="email"
                                value={data.email}
                                onChange={(e) =>
                                    setData('email', e.target.value)
                                }
                                className={FIELD_CLASS}
                                placeholder="jean@example.com"
                            />
                            {errors.email && (
                                <p className="mt-1.5 text-xs text-red-600">
                                    {errors.email}
                                </p>
                            )}
                        </div>

                        <div>
                            <label
                                htmlFor="auth-password"
                                className={LABEL_CLASS}
                            >
                                Mot de passe *
                            </label>
                            <input
                                id="auth-password"
                                name="password"
                                type="password"
                                required
                                autoComplete={
                                    mode === 'login'
                                        ? 'current-password'
                                        : 'new-password'
                                }
                                value={data.password}
                                onChange={(e) =>
                                    setData('password', e.target.value)
                                }
                                className={FIELD_CLASS}
                                placeholder="••••••••"
                            />
                            {errors.password && (
                                <p className="mt-1.5 text-xs text-red-600">
                                    {errors.password}
                                </p>
                            )}
                        </div>

                        {mode === 'register' && (
                            <div>
                                <label
                                    htmlFor="auth-password-confirm"
                                    className={LABEL_CLASS}
                                >
                                    Confirmer le mot de passe *
                                </label>
                                <input
                                    id="auth-password-confirm"
                                    name="password_confirmation"
                                    type="password"
                                    required
                                    autoComplete="new-password"
                                    value={data.password_confirmation}
                                    onChange={(e) =>
                                        setData(
                                            'password_confirmation',
                                            e.target.value,
                                        )
                                    }
                                    className={FIELD_CLASS}
                                    placeholder="••••••••"
                                />
                                {errors.password_confirmation && (
                                    <p className="mt-1.5 text-xs text-red-600">
                                        {errors.password_confirmation}
                                    </p>
                                )}
                            </div>
                        )}

                        <button
                            type="submit"
                            disabled={processing}
                            className="w-full rounded-xl bg-[#25D366] py-3.5 text-sm font-bold text-[#1A1A2E] transition-colors hover:bg-[#1DA851] disabled:opacity-60"
                        >
                            {processing
                                ? '...'
                                : mode === 'login'
                                  ? 'Se connecter'
                                  : 'Créer le compte'}
                        </button>
                    </form>

                    {googleEnabled && (
                        <>
                            <div className="relative my-5 text-center">
                                <span className="absolute inset-x-0 top-1/2 h-px bg-[#E9ECEF]" />
                                <span className="relative bg-white px-3 text-xs font-medium text-[#4A4A6A]">
                                    ou
                                </span>
                            </div>

                            <a
                                href={google().url}
                                className="flex h-12 w-full items-center justify-center gap-2.5 rounded-xl border border-[#E9ECEF] bg-white text-sm font-semibold text-[#1A1A2E] transition-colors hover:bg-[#F8F9FA]"
                            >
                                <GoogleMark />
                                {mode === 'login'
                                    ? 'Continuer avec Google'
                                    : 'S’inscrire avec Google'}
                            </a>
                        </>
                    )}

                    <p className="mt-5 text-center text-sm text-[#4A4A6A]">
                        {mode === 'login' ? (
                            <>
                                Pas encore de compte ?{' '}
                                <button
                                    onClick={() => switchMode('register')}
                                    className="font-semibold text-[#25D366] hover:underline"
                                >
                                    S&apos;inscrire
                                </button>
                            </>
                        ) : (
                            <>
                                Déjà un compte ?{' '}
                                <button
                                    onClick={() => switchMode('login')}
                                    className="font-semibold text-[#25D366] hover:underline"
                                >
                                    Se connecter
                                </button>
                            </>
                        )}
                    </p>
                </div>
            </div>
        </>
    );
}
