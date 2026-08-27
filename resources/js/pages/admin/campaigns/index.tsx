import { Head, router, useForm } from '@inertiajs/react';
import { BellRing, Mail, Megaphone, Send, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import StatusBadge from '@/components/dashboard/status-badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { Paginated } from '@/types/shop';

type AdminCampaign = {
    id: number;
    subject: string;
    body: string;
    promoCode: string | null;
    productIds: number[];
    channels: string[];
    pushedCount: number;
    status: string;
    statusLabel: string;
    recipientsCount: number;
    sentAt: string | null;
    createdAt: string;
};

type Props = {
    campaigns: Paginated<AdminCampaign>;
    audience: number;
    pushAudience: number;
    products: { id: number; name: string; price: number }[];
    promoCodes: string[];
};

const BLANK = {
    subject: '',
    body: '',
    promo_code: '',
    product_ids: [] as number[],
    channels: ['email'] as string[],
};

const SELECT_CLASS =
    'h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:outline-none';

export default function AdminCampaigns({
    campaigns,
    audience,
    pushAudience,
    products,
    promoCodes,
}: Props) {
    // `null` = nouvelle campagne, sinon identifiant de celle qu'on retouche.
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (campaign: AdminCampaign) => {
        setEditing(campaign.id);
        setData({
            subject: campaign.subject,
            body: campaign.body,
            promo_code: campaign.promoCode ?? '',
            product_ids: campaign.productIds,
            channels: campaign.channels,
        });
    };

    const toggleProduct = (id: number) => {
        setData(
            'product_ids',
            data.product_ids.includes(id)
                ? data.product_ids.filter((p) => p !== id)
                : [...data.product_ids, id].slice(0, 6),
        );
    };

    const toggleChannel = (channel: string) => {
        setData(
            'channels',
            data.channels.includes(channel)
                ? data.channels.filter((c) => c !== channel)
                : [...data.channels, channel],
        );
    };

    const handleSubmit = (e: FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => {
                setEditing(null);
                reset();
            },
        };

        if (editing === null) {
            post(admin.campaigns.store().url, options);
        } else {
            put(admin.campaigns.update(editing).url, options);
        }
    };

    const send = (campaign: AdminCampaign) => {
        if (
            window.confirm(
                `Envoyer « ${campaign.subject} » ? Cette action est définitive.`,
            )
        ) {
            router.post(admin.campaigns.send(campaign.id).url, undefined, {
                preserveScroll: true,
            });
        }
    };

    const destroy = (campaign: AdminCampaign) => {
        if (window.confirm(`Supprimer « ${campaign.subject} » ?`)) {
            router.delete(admin.campaigns.destroy(campaign.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Campagnes" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Campagnes promotionnelles
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Annoncez vos offres aux clients qui ont demandé à
                            les recevoir.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <div className="flex items-center gap-2 rounded-xl border border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                            <Mail className="size-4 text-primary" />
                            <div>
                                <p className="text-sm font-semibold">
                                    {audience}
                                </p>
                                <p className="text-[10px] text-muted-foreground">
                                    par email
                                </p>
                            </div>
                        </div>
                        <div className="flex items-center gap-2 rounded-xl border border-sidebar-border/70 px-4 py-2 dark:border-sidebar-border">
                            <BellRing className="size-4 text-primary" />
                            <div>
                                <p className="text-sm font-semibold">
                                    {pushAudience}
                                </p>
                                <p className="text-[10px] text-muted-foreground">
                                    par push
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {audience === 0 && pushAudience === 0 && (
                    <p className="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200">
                        Aucun client n’est encore abonné aux promotions. Ils
                        s’abonnent depuis{' '}
                        <strong>Réglages → Notifications</strong> de leur espace
                        client, après avoir confirmé leur adresse email par
                        code.
                    </p>
                )}

                <div className="grid gap-4 lg:grid-cols-[1fr_420px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {campaigns.data.length === 0 ? (
                            <EmptyState
                                icon={Megaphone}
                                title="Aucune campagne"
                                description="Rédigez votre première annonce dans le panneau de droite."
                            />
                        ) : (
                            <>
                                <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {campaigns.data.map((campaign) => (
                                        <li
                                            key={campaign.id}
                                            className="flex flex-wrap items-start gap-3 p-4"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="text-sm font-semibold">
                                                        {campaign.subject}
                                                    </span>
                                                    <StatusBadge
                                                        status={campaign.status}
                                                        label={
                                                            campaign.statusLabel
                                                        }
                                                    />
                                                </div>
                                                <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                    {campaign.body}
                                                </p>
                                                <div className="mt-1.5 flex flex-wrap gap-1">
                                                    {campaign.channels.map(
                                                        (channel) => (
                                                            <span
                                                                key={channel}
                                                                className="rounded-full bg-muted px-2 py-0.5 text-[10px] font-medium text-muted-foreground"
                                                            >
                                                                {channel ===
                                                                'push'
                                                                    ? 'Push'
                                                                    : 'Email'}
                                                            </span>
                                                        ),
                                                    )}
                                                </div>
                                                <p className="mt-1 text-[11px] text-muted-foreground">
                                                    {campaign.sentAt
                                                        ? `Envoyée le ${campaign.sentAt} — ${campaign.recipientsCount} email(s), ${campaign.pushedCount} push`
                                                        : `Créée le ${campaign.createdAt}`}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 gap-1">
                                                {campaign.status ===
                                                    'draft' && (
                                                    <>
                                                        <Button
                                                            variant="ghost"
                                                            size="sm"
                                                            onClick={() =>
                                                                startEdit(
                                                                    campaign,
                                                                )
                                                            }
                                                        >
                                                            Modifier
                                                        </Button>
                                                        <Button
                                                            variant="secondary"
                                                            size="sm"
                                                            onClick={() =>
                                                                send(campaign)
                                                            }
                                                            disabled={
                                                                audience ===
                                                                    0 &&
                                                                pushAudience ===
                                                                    0
                                                            }
                                                        >
                                                            <Send className="size-4" />
                                                            Envoyer
                                                        </Button>
                                                    </>
                                                )}
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(campaign)
                                                    }
                                                    aria-label={`Supprimer ${campaign.subject}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                <Pagination
                                    links={campaigns.links}
                                    from={campaigns.from}
                                    to={campaigns.to}
                                    total={campaigns.total}
                                />
                            </>
                        )}
                    </section>

                    {/* Rédaction */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouvelle campagne'
                                : 'Modifier la campagne'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div>
                                <Label htmlFor="campaign-subject">
                                    Objet *
                                </Label>
                                <Input
                                    id="campaign-subject"
                                    value={data.subject}
                                    onChange={(e) =>
                                        setData('subject', e.target.value)
                                    }
                                    placeholder="−15 % sur toute la robinetterie"
                                />
                                {errors.subject && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.subject}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="campaign-body">Message *</Label>
                                <textarea
                                    id="campaign-body"
                                    rows={7}
                                    value={data.body}
                                    onChange={(e) =>
                                        setData('body', e.target.value)
                                    }
                                    className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                    placeholder={
                                        'Bonne nouvelle !\n\nJusqu’à dimanche, toute la robinetterie est à −15 %…'
                                    }
                                />
                                <p className="mt-1 text-xs text-muted-foreground">
                                    Texte simple : une ligne vide sépare deux
                                    paragraphes.
                                </p>
                                {errors.body && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.body}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label>Canaux d’envoi *</Label>
                                <div className="mt-1 space-y-2">
                                    {[
                                        {
                                            value: 'email',
                                            label: 'Email',
                                            hint: `${audience} client(s) avec une adresse confirmée`,
                                        },
                                        {
                                            value: 'push',
                                            label: 'Notification push',
                                            hint: `${pushAudience} appareil(s) enregistré(s)`,
                                        },
                                    ].map((channel) => (
                                        <label
                                            key={channel.value}
                                            className="flex cursor-pointer items-start gap-2.5 rounded-lg border border-sidebar-border/70 p-2.5 dark:border-sidebar-border"
                                        >
                                            <Checkbox
                                                checked={data.channels.includes(
                                                    channel.value,
                                                )}
                                                onCheckedChange={() =>
                                                    toggleChannel(channel.value)
                                                }
                                                className="mt-0.5"
                                            />
                                            <span>
                                                <span className="block text-xs font-medium">
                                                    {channel.label}
                                                </span>
                                                <span className="block text-[11px] text-muted-foreground">
                                                    {channel.hint}
                                                </span>
                                            </span>
                                        </label>
                                    ))}
                                </div>
                                <p className="mt-1.5 text-[11px] text-muted-foreground">
                                    La notification dans l’application part
                                    toujours : elle ne se désactive pas.
                                </p>
                                {errors.channels && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.channels}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="campaign-code">
                                    Code promo à annoncer
                                </Label>
                                <select
                                    id="campaign-code"
                                    value={data.promo_code}
                                    onChange={(e) =>
                                        setData('promo_code', e.target.value)
                                    }
                                    className={`${SELECT_CLASS} mt-1`}
                                >
                                    <option value="">Aucun</option>
                                    {promoCodes.map((code) => (
                                        <option key={code} value={code}>
                                            {code}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <div>
                                <Label>
                                    Produits mis en avant (
                                    {data.product_ids.length}
                                    /6)
                                </Label>
                                <div className="mt-1 max-h-56 overflow-y-auto rounded-md border border-input p-1">
                                    {products.map((product) => {
                                        const checked =
                                            data.product_ids.includes(
                                                product.id,
                                            );

                                        return (
                                            <button
                                                key={product.id}
                                                type="button"
                                                onClick={() =>
                                                    toggleProduct(product.id)
                                                }
                                                className={`flex w-full items-center justify-between gap-2 rounded px-2 py-1.5 text-left text-xs transition-colors ${
                                                    checked
                                                        ? 'bg-primary/10 font-medium'
                                                        : 'hover:bg-muted'
                                                }`}
                                            >
                                                <span className="min-w-0 truncate">
                                                    {product.name}
                                                </span>
                                                <span className="shrink-0 text-muted-foreground">
                                                    {formatPrice(product.price)}
                                                </span>
                                            </button>
                                        );
                                    })}
                                </div>
                                {errors.product_ids && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.product_ids}
                                    </p>
                                )}
                            </div>

                            <div className="flex gap-2 pt-1">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enregistrement…'
                                        : editing === null
                                          ? 'Enregistrer le brouillon'
                                          : 'Mettre à jour'}
                                </Button>
                                {editing !== null && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={startCreate}
                                    >
                                        Annuler
                                    </Button>
                                )}
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </>
    );
}

AdminCampaigns.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Campagnes', href: admin.campaigns.index() },
    ],
};
