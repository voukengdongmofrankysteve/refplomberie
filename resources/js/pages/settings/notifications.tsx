import { Head, router, useForm } from '@inertiajs/react';
import { BadgeCheck, Mail, ShieldCheck } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useNotifications } from '@/hooks/use-notifications';
import notifications from '@/routes/notifications';

type Props = {
    notifications: {
        email: string | null;
        verified: boolean;
        awaitingCode: boolean;
        orderUpdates: boolean;
        promotions: boolean;
        push: boolean;
        devices: number;
        accountEmail: string;
    };
};

export default function NotificationSettings({ notifications: state }: Props) {
    // Trois écrans successifs : saisir l'adresse, entrer le code, gérer les
    // thèmes une fois l'adresse confirmée.
    const [step, setStep] = useState<'address' | 'code'>(
        state.awaitingCode ? 'code' : 'address',
    );

    const addressForm = useForm({
        email: state.email ?? state.accountEmail,
    });

    const codeForm = useForm({ code: '' });

    const prefsForm = useForm({
        notify_order_updates: state.orderUpdates,
        notify_promotions: state.promotions,
        notify_push: state.push,
    });

    const { enablePush, pushEnabled, pushBlocked, pushAvailable } =
        useNotifications();

    const sendCode = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        addressForm.post(notifications.code().url, {
            preserveScroll: true,
            onSuccess: () => setStep('code'),
        });
    };

    const confirmCode = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        codeForm.post(notifications.confirm().url, {
            preserveScroll: true,
            onSuccess: () => codeForm.reset(),
        });
    };

    const savePreferences = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        prefsForm.put(notifications.update().url, { preserveScroll: true });
    };

    const disable = () => {
        if (
            window.confirm(
                'Désactiver les notifications par email ? Vous ne recevrez plus rien.',
            )
        ) {
            router.delete(notifications.destroy().url, {
                preserveScroll: true,
                onSuccess: () => setStep('address'),
            });
        }
    };

    return (
        <>
            <Head title="Notifications" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Notifications par email"
                    description="Recevez le suivi de vos commandes et nos offres, si vous le souhaitez."
                />

                {/* Alertes du navigateur : indépendantes de l'email. */}
                <div className="space-y-4 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div className="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p className="text-sm font-medium">
                                Alertes sur cet appareil
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Être prévenu même quand le site est fermé.
                                {state.devices > 0 &&
                                    ` ${state.devices} appareil(s) enregistré(s).`}
                            </p>
                        </div>
                        {pushBlocked ? (
                            <span className="text-xs text-destructive">
                                Bloquées par le navigateur
                            </span>
                        ) : pushEnabled ? (
                            <span className="text-xs font-medium text-green-600 dark:text-green-400">
                                Activées
                            </span>
                        ) : (
                            <Button
                                type="button"
                                size="sm"
                                disabled={!pushAvailable}
                                onClick={() => void enablePush()}
                            >
                                Activer
                            </Button>
                        )}
                    </div>

                    {!pushAvailable && (
                        <p className="text-xs text-muted-foreground">
                            Les notifications push ne sont pas encore
                            configurées sur ce site.
                        </p>
                    )}
                </div>

                {/* Thèmes : le consentement ne dépend d'aucune adresse.
                    Un client peut vouloir les offres par notification seule. */}
                <form onSubmit={savePreferences} className="space-y-4">
                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <Checkbox
                            checked={prefsForm.data.notify_order_updates}
                            onCheckedChange={(checked) =>
                                prefsForm.setData(
                                    'notify_order_updates',
                                    checked === true,
                                )
                            }
                            className="mt-0.5"
                        />
                        <span>
                            <span className="block text-sm font-medium">
                                Suivi de mes commandes
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                Confirmation, préparation, expédition,
                                livraison.
                            </span>
                        </span>
                    </label>

                    <label className="flex cursor-pointer items-start gap-3 rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                        <Checkbox
                            checked={prefsForm.data.notify_promotions}
                            onCheckedChange={(checked) =>
                                prefsForm.setData(
                                    'notify_promotions',
                                    checked === true,
                                )
                            }
                            className="mt-0.5"
                        />
                        <span>
                            <span className="block text-sm font-medium">
                                Promotions et nouveautés
                            </span>
                            <span className="block text-xs text-muted-foreground">
                                Nos offres, arrivages et codes promo. Rarement,
                                et jamais revendus.
                            </span>
                        </span>
                    </label>

                    <Button type="submit" disabled={prefsForm.processing}>
                        {prefsForm.processing
                            ? 'Enregistrement…'
                            : 'Enregistrer mes préférences'}
                    </Button>
                </form>

                <Heading
                    variant="small"
                    title="Recevoir aussi par email"
                    description="Facultatif : une adresse confirmée permet de recevoir les mêmes annonces par email."
                />

                {state.verified ? (
                    <>
                        {/* Adresse confirmée */}
                        <div className="flex flex-wrap items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-4 py-3 dark:border-green-500/30 dark:bg-green-500/10">
                            <BadgeCheck className="size-5 shrink-0 text-green-600 dark:text-green-400" />
                            <div className="min-w-0 flex-1">
                                <p className="text-sm font-medium text-green-900 dark:text-green-200">
                                    {state.email}
                                </p>
                                <p className="text-xs text-green-800/80 dark:text-green-200/70">
                                    Adresse confirmée
                                </p>
                            </div>
                            <Button
                                variant="ghost"
                                size="sm"
                                onClick={disable}
                                className="shrink-0"
                            >
                                Désactiver
                            </Button>
                        </div>
                    </>
                ) : step === 'code' ? (
                    /* Saisie du code */
                    <form onSubmit={confirmCode} className="space-y-4">
                        <div className="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                            <p className="flex items-center gap-2 text-sm font-medium">
                                <Mail className="size-4 text-primary" />
                                Code envoyé à {addressForm.data.email}
                            </p>
                            <p className="mt-1 text-xs text-muted-foreground">
                                Saisissez les 6 chiffres reçus. Le code expire
                                au bout de 15 minutes.
                            </p>
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="code">Code de confirmation</Label>
                            <Input
                                id="code"
                                inputMode="numeric"
                                autoComplete="one-time-code"
                                maxLength={6}
                                value={codeForm.data.code}
                                onChange={(e) =>
                                    codeForm.setData(
                                        'code',
                                        e.target.value.replace(/\D/g, ''),
                                    )
                                }
                                className="max-w-40 text-center text-lg tracking-[0.4em]"
                                placeholder="000000"
                            />
                            {codeForm.errors.code && (
                                <p className="text-xs text-destructive">
                                    {codeForm.errors.code}
                                </p>
                            )}
                        </div>

                        <div className="flex flex-wrap gap-2">
                            <Button
                                type="submit"
                                disabled={
                                    codeForm.processing ||
                                    codeForm.data.code.length !== 6
                                }
                            >
                                {codeForm.processing
                                    ? 'Vérification…'
                                    : 'Confirmer mon adresse'}
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setStep('address')}
                            >
                                Changer d’adresse
                            </Button>
                        </div>
                    </form>
                ) : (
                    /* Saisie de l'adresse */
                    <form onSubmit={sendCode} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="notification-email">
                                Adresse de réception
                            </Label>
                            <Input
                                id="notification-email"
                                type="email"
                                value={addressForm.data.email}
                                onChange={(e) =>
                                    addressForm.setData('email', e.target.value)
                                }
                                placeholder="vous@example.com"
                            />
                            {addressForm.errors.email && (
                                <p className="text-xs text-destructive">
                                    {addressForm.errors.email}
                                </p>
                            )}
                            <p className="text-xs text-muted-foreground">
                                Nous vous enverrons un code à 6 chiffres pour
                                vérifier que cette adresse est bien la vôtre.
                            </p>
                        </div>

                        <Button type="submit" disabled={addressForm.processing}>
                            {addressForm.processing
                                ? 'Envoi…'
                                : 'Recevoir mon code'}
                        </Button>
                    </form>
                )}

                <p className="flex items-start gap-2 text-xs text-muted-foreground">
                    <ShieldCheck className="mt-0.5 size-4 shrink-0" />
                    Aucun email ne part avant que vous ayez confirmé votre
                    adresse. Vous pouvez tout désactiver à tout moment depuis
                    cette page.
                </p>
            </div>
        </>
    );
}

NotificationSettings.layout = {
    breadcrumbs: [{ title: 'Notifications', href: notifications.edit() }],
};
