import { Head, router, useForm } from '@inertiajs/react';
import { Plus, Tag, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import Pagination from '@/components/dashboard/pagination';
import PdfExportButton from '@/components/dashboard/pdf-export-button';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';
import type { AdminPromoCode, Paginated, StatusOption } from '@/types/shop';

const BLANK = {
    code: '',
    label: '',
    type: 'percent',
    value: 10,
    min_subtotal: 0,
    max_uses: '' as number | '',
    starts_at: '',
    ends_at: '',
    is_active: true,
};

type Props = {
    codes: Paginated<AdminPromoCode>;
    types: StatusOption[];
};

export default function AdminPromoCodes({ codes, types }: Props) {
    // `null` = création, sinon identifiant du code édité.
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (code: AdminPromoCode) => {
        setEditing(code.id);
        setData({
            code: code.code,
            label: code.label ?? '',
            type: code.type,
            value: code.value,
            min_subtotal: code.minSubtotal,
            max_uses: code.maxUses ?? '',
            starts_at: code.startsAt ?? '',
            ends_at: code.endsAt ?? '',
            is_active: code.isActive,
        });
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
            post(admin.promoCodes.store().url, options);
        } else {
            put(admin.promoCodes.update(editing).url, options);
        }
    };

    const destroy = (code: AdminPromoCode) => {
        if (window.confirm(`Supprimer le code « ${code.code} » ?`)) {
            router.delete(admin.promoCodes.destroy(code.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="Codes promo" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Codes promo</h1>
                        <p className="text-sm text-muted-foreground">
                            {codes.total} code{codes.total !== 1 ? 's' : ''}{' '}
                            enregistré{codes.total !== 1 ? 's' : ''}. Le client
                            saisit le code dans son panier.
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        <PdfExportButton href={admin.promoCodes.export().url} />
                        <Button variant="secondary" onClick={startCreate}>
                            <Plus className="size-4" />
                            Nouveau code
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {codes.data.length === 0 ? (
                            <EmptyState
                                icon={Tag}
                                title="Aucun code promo"
                                description="Créez un code pour offrir une remise sur le panier."
                            />
                        ) : (
                            <>
                                <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {codes.data.map((code) => (
                                        <li
                                            key={code.id}
                                            className="flex flex-wrap items-center gap-3 p-4"
                                        >
                                            <div className="min-w-0 flex-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-mono text-sm font-semibold">
                                                        {code.code}
                                                    </span>
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-green-100 text-green-800 dark:bg-green-950 dark:text-green-300"
                                                    >
                                                        {code.advantage}
                                                    </Badge>
                                                    {!code.isRedeemable && (
                                                        <Badge
                                                            variant="secondary"
                                                            className="bg-muted text-muted-foreground"
                                                        >
                                                            Inutilisable
                                                        </Badge>
                                                    )}
                                                </div>
                                                <p className="mt-1 truncate text-xs text-muted-foreground">
                                                    {code.label ??
                                                        code.typeLabel}
                                                    {code.minSubtotal > 0 &&
                                                        ` · dès ${formatPrice(code.minSubtotal)}`}
                                                    {code.endsAt &&
                                                        ` · jusqu’au ${code.endsAt}`}
                                                </p>
                                            </div>

                                            <div className="shrink-0 text-right">
                                                <p className="text-sm font-semibold">
                                                    {code.usedCount}
                                                    {code.maxUses !== null &&
                                                        ` / ${code.maxUses}`}
                                                </p>
                                                <p className="text-[10px] text-muted-foreground">
                                                    utilisation
                                                    {code.usedCount !== 1
                                                        ? 's'
                                                        : ''}
                                                </p>
                                            </div>

                                            <div className="flex shrink-0 gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        startEdit(code)
                                                    }
                                                >
                                                    Modifier
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() =>
                                                        destroy(code)
                                                    }
                                                    aria-label={`Supprimer ${code.code}`}
                                                >
                                                    <Trash2 className="size-4 text-destructive" />
                                                </Button>
                                            </div>
                                        </li>
                                    ))}
                                </ul>

                                <Pagination
                                    links={codes.links}
                                    from={codes.from}
                                    to={codes.to}
                                    total={codes.total}
                                />
                            </>
                        )}
                    </section>

                    {/* Formulaire */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouveau code'
                                : 'Modifier le code'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div>
                                <Label htmlFor="promo-code">Code *</Label>
                                <Input
                                    id="promo-code"
                                    value={data.code}
                                    onChange={(e) =>
                                        setData('code', e.target.value)
                                    }
                                    placeholder="BIENVENUE10"
                                    className="font-mono uppercase"
                                    autoComplete="off"
                                />
                                {errors.code && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.code}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="promo-label">
                                    Intitulé interne
                                </Label>
                                <Input
                                    id="promo-label"
                                    value={data.label}
                                    onChange={(e) =>
                                        setData('label', e.target.value)
                                    }
                                    placeholder="Offre de lancement"
                                />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="promo-type">Remise *</Label>
                                    <select
                                        id="promo-type"
                                        value={data.type}
                                        onChange={(e) =>
                                            setData('type', e.target.value)
                                        }
                                        className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none"
                                    >
                                        {types.map((type) => (
                                            <option
                                                key={type.value}
                                                value={type.value}
                                            >
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <Label htmlFor="promo-value">
                                        {data.type === 'percent'
                                            ? 'Pourcentage *'
                                            : 'Montant (FCFA) *'}
                                    </Label>
                                    <Input
                                        id="promo-value"
                                        type="number"
                                        min={1}
                                        value={data.value}
                                        onChange={(e) =>
                                            setData(
                                                'value',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                    {errors.value && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.value}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="promo-min">
                                        Panier minimum
                                    </Label>
                                    <Input
                                        id="promo-min"
                                        type="number"
                                        min={0}
                                        value={data.min_subtotal}
                                        onChange={(e) =>
                                            setData(
                                                'min_subtotal',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="promo-max-uses">
                                        Utilisations max
                                    </Label>
                                    <Input
                                        id="promo-max-uses"
                                        type="number"
                                        min={1}
                                        value={data.max_uses}
                                        onChange={(e) =>
                                            setData(
                                                'max_uses',
                                                e.target.value === ''
                                                    ? ''
                                                    : Number(e.target.value),
                                            )
                                        }
                                        placeholder="illimité"
                                    />
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="promo-start">Début</Label>
                                    <Input
                                        id="promo-start"
                                        type="date"
                                        value={data.starts_at}
                                        onChange={(e) =>
                                            setData('starts_at', e.target.value)
                                        }
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="promo-end">Fin</Label>
                                    <Input
                                        id="promo-end"
                                        type="date"
                                        value={data.ends_at}
                                        onChange={(e) =>
                                            setData('ends_at', e.target.value)
                                        }
                                    />
                                    {errors.ends_at && (
                                        <p className="mt-1 text-xs text-destructive">
                                            {errors.ends_at}
                                        </p>
                                    )}
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="promo-active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="promo-active"
                                    className="font-normal"
                                >
                                    Code actif
                                </Label>
                            </div>

                            <div className="flex gap-2 pt-1">
                                <Button type="submit" disabled={processing}>
                                    {processing
                                        ? 'Enregistrement…'
                                        : editing === null
                                          ? 'Créer'
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

AdminPromoCodes.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Codes promo', href: admin.promoCodes.index() },
    ],
};
