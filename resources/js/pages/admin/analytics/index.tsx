import { Head, Link, router } from '@inertiajs/react';
import {
    Activity,
    Clock,
    Download,
    Eye,
    FileText,
    Globe2,
    MousePointerClick,
    Search,
    ShoppingCart,
    TrendingDown,
    TrendingUp,
    Users,
    Wallet,
} from 'lucide-react';
import { useEffect, useState } from 'react';
import { BarList, MiniBars, TrendChart } from '@/components/dashboard/charts';
import { formatPrice } from '@/lib/shop';
import admin from '@/routes/admin';

type Summary = {
    visitors: number;
    newVisitors: number;
    sessions: number;
    pageViews: number;
    events: number;
    pagesPerSession: number;
    avgDuration: number;
    orders: number;
    revenue: number;
    quotes: number;
    contacts: number;
    conversionRate: number;
};

type Breakdown = {
    name: string;
    code?: string | null;
    sessions: number;
    visitors: number;
    share: number | null;
};

type Props = {
    period: {
        key: string;
        label: string;
        from: string;
        to: string;
        granularity: string;
    };
    periods: { value: string; label: string }[];
    summary: Summary;
    previous: Summary;
    series: {
        bucket: string;
        label: string;
        visitors: number;
        pageViews: number;
        orders: number;
        revenue: number;
    }[];
    topPages: {
        path: string;
        label: string;
        views: number;
        visitors: number;
    }[];
    topProducts: {
        id: number;
        slug: string;
        name: string;
        image: string;
        views: number;
        visitors: number;
        quantity: number;
        revenue: number;
        conversion: number;
    }[];
    topSearches: { term: string; searches: number; empty: number }[];
    countries: Breakdown[];
    cities: Breakdown[];
    continents: Breakdown[];
    devices: Breakdown[];
    platforms: Breakdown[];
    browsers: Breakdown[];
    sources: Breakdown[];
    referrers: Breakdown[];
    hours: { hour: string; views: number }[];
    weekdays: { day: string; views: number }[];
    actions: { type: string; label: string; count: number }[];
    funnel: { step: string; visitors: number; share: number }[];
    live: Live;
    geoDriver: string;
};

type Live = {
    minutes: number;
    visitors: number;
    recent: {
        id: number;
        type: string;
        label: string;
        path: string | null;
        city: string | null;
        country: string | null;
        device: string | null;
        source: string | null;
        at: string;
    }[];
};

export default function AdminAnalytics(props: Props) {
    const {
        period,
        periods,
        summary,
        previous,
        series,
        topPages,
        topProducts,
        topSearches,
        countries,
        cities,
        continents,
        devices,
        platforms,
        browsers,
        sources,
        referrers,
        hours,
        weekdays,
        actions,
        funnel,
        geoDriver,
    } = props;

    const live = useLiveActivity(props.live);

    return (
        <>
            <Head title="Audience" />

            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto p-4">
                <header className="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">Audience</h1>
                        <p className="text-sm text-muted-foreground">
                            Qui visite la boutique, ce qu’ils regardent et ce
                            qu’ils achètent — du {period.from} au {period.to}.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <LiveBadge live={live} />

                        <select
                            value={period.key}
                            onChange={(event) =>
                                router.get(
                                    admin.analytics.index().url,
                                    { periode: event.target.value },
                                    { preserveScroll: true },
                                )
                            }
                            className="h-9 rounded-lg border border-sidebar-border/70 bg-background px-3 text-sm dark:border-sidebar-border"
                            aria-label="Période"
                        >
                            {periods.map((option) => (
                                <option key={option.value} value={option.value}>
                                    {option.label}
                                </option>
                            ))}
                        </select>

                        <a
                            href={
                                admin.analytics.export({
                                    query: { periode: period.key },
                                }).url
                            }
                            className="flex h-9 items-center gap-1.5 rounded-lg border border-sidebar-border/70 px-3 text-sm font-medium hover:bg-accent dark:border-sidebar-border"
                            title="Détail journalier au format CSV, pour vos propres calculs"
                        >
                            <Download className="size-4" />
                            CSV
                        </a>

                        <a
                            href={
                                admin.analytics.pdf({
                                    query: { periode: period.key },
                                }).url
                            }
                            className="flex h-9 items-center gap-1.5 rounded-lg bg-primary px-3 text-sm font-medium text-primary-foreground hover:bg-primary/90"
                            title="Ce tableau de bord complet, prêt à imprimer ou partager"
                        >
                            <FileText className="size-4" />
                            PDF
                        </a>
                    </div>
                </header>

                {/* Chiffres clés, comparés à la période précédente */}
                <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <DeltaCard
                        label="Visiteurs"
                        value={summary.visitors}
                        previous={previous.visitors}
                        hint={`${summary.newVisitors} nouveau${summary.newVisitors > 1 ? 'x' : ''}`}
                        icon={Users}
                    />
                    <DeltaCard
                        label="Visites"
                        value={summary.sessions}
                        previous={previous.sessions}
                        hint={`${summary.pagesPerSession} page(s) par visite`}
                        icon={Activity}
                    />
                    <DeltaCard
                        label="Pages vues"
                        value={summary.pageViews}
                        previous={previous.pageViews}
                        hint={`Durée moyenne ${formatDuration(summary.avgDuration)}`}
                        icon={Eye}
                    />
                    <DeltaCard
                        label="Chiffre d’affaires"
                        value={summary.revenue}
                        previous={previous.revenue}
                        format={formatPrice}
                        hint={`${summary.orders} commande${summary.orders > 1 ? 's' : ''}`}
                        icon={Wallet}
                    />
                </section>

                <section className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <DeltaCard
                        label="Taux de conversion"
                        value={summary.conversionRate}
                        previous={previous.conversionRate}
                        format={(n) => `${n.toLocaleString('fr-FR')} %`}
                        hint="Visites aboutissant à une commande"
                        icon={ShoppingCart}
                    />
                    <DeltaCard
                        label="Devis demandés"
                        value={summary.quotes}
                        previous={previous.quotes}
                        icon={MousePointerClick}
                    />
                    <DeltaCard
                        label="Prises de contact"
                        value={summary.contacts}
                        previous={previous.contacts}
                        hint="Messages, WhatsApp, interventions"
                        icon={Activity}
                    />
                    <DeltaCard
                        label="Actions mesurées"
                        value={summary.events}
                        previous={previous.events}
                        icon={TrendingUp}
                    />
                </section>

                <Panel
                    title="Fréquentation"
                    subtitle={
                        period.granularity === 'hour'
                            ? 'Heure par heure'
                            : period.granularity === 'month'
                              ? 'Mois par mois'
                              : 'Jour par jour'
                    }
                >
                    <div className="p-2">
                        <TrendChart
                            points={series.map((row) => ({
                                label: row.label,
                                value: row.visitors,
                                secondary: row.pageViews,
                            }))}
                            label="Visiteurs"
                            secondaryLabel="Pages vues"
                        />
                    </div>
                </Panel>

                <div className="grid gap-3 lg:grid-cols-2">
                    <Panel title="Pages les plus vues">
                        <BarList
                            rows={topPages.map((page) => ({
                                name: page.label,
                                value: page.views,
                                hint: `${page.visitors} visiteur(s)`,
                            }))}
                        />
                    </Panel>

                    <Panel
                        title="Parcours d’achat"
                        subtitle="Visiteurs distincts à chaque étape"
                    >
                        <ul className="space-y-2 p-4">
                            {funnel.map((step) => (
                                <li key={step.step}>
                                    <div className="mb-1 flex items-baseline justify-between gap-2 text-sm">
                                        <span>{step.step}</span>
                                        <span className="font-semibold tabular-nums">
                                            {step.visitors}
                                            <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                                {step.share} %
                                            </span>
                                        </span>
                                    </div>
                                    <div className="h-2 overflow-hidden rounded-full bg-muted">
                                        <div
                                            className="h-full rounded-full bg-primary"
                                            style={{
                                                width: `${Math.max(1, step.share)}%`,
                                            }}
                                        />
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </Panel>
                </div>

                <Panel
                    title="Produits les plus consultés"
                    subtitle="Et ce que ces consultations ont rapporté"
                >
                    {topProducts.length === 0 ? (
                        <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                            Aucune fiche produit consultée sur cette période.
                        </p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[640px] text-sm">
                                <thead className="border-b border-sidebar-border/70 text-left text-xs text-muted-foreground dark:border-sidebar-border">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">
                                            Produit
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Vues
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Visiteurs
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Vendus
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Recettes
                                        </th>
                                        <th className="px-4 py-2 text-right font-medium">
                                            Vues → vente
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                    {topProducts.map((product) => (
                                        <tr key={product.id}>
                                            <td className="px-4 py-2">
                                                <Link
                                                    href={`/produit/${product.slug}`}
                                                    className="flex items-center gap-2 hover:underline"
                                                >
                                                    <img
                                                        src={product.image}
                                                        alt=""
                                                        className="size-8 shrink-0 rounded object-cover"
                                                    />
                                                    <span className="truncate">
                                                        {product.name}
                                                    </span>
                                                </Link>
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {product.views}
                                            </td>
                                            <td className="px-4 py-2 text-right text-muted-foreground tabular-nums">
                                                {product.visitors}
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {product.quantity}
                                            </td>
                                            <td className="px-4 py-2 text-right tabular-nums">
                                                {formatPrice(product.revenue)}
                                            </td>
                                            <td className="px-4 py-2 text-right text-muted-foreground tabular-nums">
                                                {product.conversion} %
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Panel>

                {/* Provenance géographique */}
                <div className="grid gap-3 lg:grid-cols-3">
                    <Panel title="Pays" icon={Globe2}>
                        <BarList
                            rows={countries.map((row) => ({
                                name: row.name,
                                code: row.code,
                                value: row.sessions,
                                hint: `${row.share} %`,
                            }))}
                            empty={
                                geoDriver === 'http'
                                    ? 'Aucune visite localisée pour l’instant.'
                                    : 'Base de localisation absente.'
                            }
                        />
                    </Panel>

                    <Panel title="Villes">
                        <BarList
                            rows={cities.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                                hint: `${row.share} %`,
                            }))}
                        />
                    </Panel>

                    <Panel title="Continents">
                        <BarList
                            rows={continents.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                                hint: `${row.share} %`,
                            }))}
                        />
                    </Panel>
                </div>

                <div className="grid gap-3 lg:grid-cols-2">
                    <Panel title="Provenance du trafic">
                        <BarList
                            rows={referrers.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                            }))}
                        />
                    </Panel>

                    <Panel title="Recherches" icon={Search}>
                        <BarList
                            rows={topSearches.map((row) => ({
                                name: row.term,
                                value: row.searches,
                                hint:
                                    row.empty > 0
                                        ? `${row.empty} sans résultat`
                                        : null,
                            }))}
                            empty="Personne n’a encore utilisé la recherche."
                        />
                    </Panel>
                </div>

                <div className="grid gap-3 lg:grid-cols-4">
                    <Panel title="Appareils">
                        <BarList
                            rows={devices.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                                hint: `${row.share} %`,
                            }))}
                        />
                    </Panel>
                    <Panel title="Systèmes">
                        <BarList
                            rows={platforms.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                            }))}
                        />
                    </Panel>
                    <Panel title="Navigateurs">
                        <BarList
                            rows={browsers.map((row) => ({
                                name: row.name,
                                value: row.sessions,
                            }))}
                        />
                    </Panel>
                    <Panel title="Site ou application">
                        <BarList
                            rows={sources.map((row) => ({
                                name:
                                    row.name === 'app'
                                        ? 'Application mobile'
                                        : 'Site web',
                                value: row.sessions,
                                hint: `${row.share} %`,
                            }))}
                        />
                    </Panel>
                </div>

                <div className="grid gap-3 lg:grid-cols-2">
                    <Panel
                        title="Affluence par heure"
                        subtitle="Heure locale de Douala"
                        icon={Clock}
                    >
                        <MiniBars
                            rows={hours.map((row) => ({
                                label: row.hour,
                                value: row.views,
                            }))}
                        />
                    </Panel>

                    <Panel title="Affluence par jour">
                        <MiniBars
                            rows={weekdays.map((row) => ({
                                label: row.day.slice(0, 3),
                                value: row.views,
                            }))}
                        />
                    </Panel>
                </div>

                <div className="grid gap-3 lg:grid-cols-2">
                    <Panel
                        title="Toutes les actions"
                        subtitle="Chaque geste mesuré sur la période"
                    >
                        <BarList
                            rows={actions.map((row) => ({
                                name: row.label,
                                value: row.count,
                            }))}
                        />
                    </Panel>

                    <Panel
                        title="En direct"
                        subtitle={`Dernières ${live.minutes} minutes`}
                    >
                        {live.recent.length === 0 ? (
                            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                                Personne sur le site en ce moment.
                            </p>
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 text-sm dark:divide-sidebar-border">
                                {live.recent.map((event) => (
                                    <li
                                        key={event.id}
                                        className="flex items-center justify-between gap-3 px-4 py-2"
                                    >
                                        <span className="min-w-0">
                                            <span className="block truncate">
                                                {event.label}
                                            </span>
                                            <span className="block truncate text-xs text-muted-foreground">
                                                {[
                                                    event.city,
                                                    event.country,
                                                    event.device,
                                                    event.source === 'app'
                                                        ? 'application'
                                                        : null,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ') || event.path}
                                            </span>
                                        </span>
                                        <time
                                            dateTime={event.at}
                                            className="shrink-0 text-xs text-muted-foreground"
                                        >
                                            {new Date(
                                                event.at,
                                            ).toLocaleTimeString('fr-FR', {
                                                hour: '2-digit',
                                                minute: '2-digit',
                                            })}
                                        </time>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Panel>
                </div>

                <p className="px-1 pb-2 text-xs text-muted-foreground">
                    Mesure interne : aucune donnée n’est transmise à un service
                    tiers, et aucun cookie publicitaire n’est déposé. Les
                    adresses IP ne sont jamais conservées en clair.
                    {geoDriver === 'http' &&
                        ' Localisation fournie par ip-api.com.'}
                    {geoDriver === 'maxmind' &&
                        ' Localisation issue de la base GeoLite2 de MaxMind.'}
                </p>
            </div>
        </>
    );
}

/**
 * Rafraîchit l'activité en direct sans recharger la page.
 *
 * Trente secondes : assez pour que le panneau vive, assez peu pour qu'un
 * onglet laissé ouvert ne martèle pas le serveur toute la journée.
 */
function useLiveActivity(initial: Live): Live {
    // Rien tant que le premier rafraîchissement n'a pas eu lieu : la valeur
    // rendue par le serveur fait très bien l'affaire, et la recopier dans un
    // état ne servirait qu'à provoquer un rendu de plus.
    const [fresh, setFresh] = useState<Live | null>(null);

    useEffect(() => {
        const url = admin.analytics.live().url;

        const tick = async () => {
            if (document.hidden) {
                return;
            }

            try {
                const response = await fetch(url, {
                    headers: { Accept: 'application/json' },
                });

                if (response.ok) {
                    setFresh(await response.json());
                }
            } catch {
                // Réseau coupé : le panneau garde son dernier état.
            }
        };

        const timer = window.setInterval(tick, 30_000);

        return () => window.clearInterval(timer);
    }, []);

    return fresh ?? initial;
}

function LiveBadge({ live }: { live: Live }) {
    return (
        <span className="flex h-9 items-center gap-2 rounded-lg border border-sidebar-border/70 px-3 text-sm dark:border-sidebar-border">
            <span className="relative flex size-2">
                <span className="absolute inline-flex size-full animate-ping rounded-full bg-green-500 opacity-60" />
                <span className="relative inline-flex size-2 rounded-full bg-green-500" />
            </span>
            <span className="font-semibold tabular-nums">{live.visitors}</span>
            <span className="text-muted-foreground">en ligne</span>
        </span>
    );
}

function Panel({
    title,
    subtitle,
    icon: Icon,
    children,
}: {
    title: string;
    subtitle?: string;
    icon?: React.ComponentType<{ className?: string }>;
    children: React.ReactNode;
}) {
    return (
        <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
            <header className="flex items-center gap-2 border-b border-sidebar-border/70 px-4 py-3 dark:border-sidebar-border">
                {Icon && <Icon className="size-4 text-muted-foreground" />}
                <div>
                    <h2 className="text-sm font-semibold">{title}</h2>
                    {subtitle && (
                        <p className="text-xs text-muted-foreground">
                            {subtitle}
                        </p>
                    )}
                </div>
            </header>
            {children}
        </section>
    );
}

/** Tuile de chiffre clé avec son évolution par rapport à la période d'avant. */
function DeltaCard({
    label,
    value,
    previous,
    hint,
    icon: Icon,
    format = (n: number) => n.toLocaleString('fr-FR'),
}: {
    label: string;
    value: number;
    previous: number;
    hint?: string;
    icon?: React.ComponentType<{ className?: string }>;
    format?: (n: number) => string;
}) {
    // Partir de zéro n'est pas « + ∞ % » : sans base de comparaison, on
    // n'affiche simplement pas d'évolution.
    const delta =
        previous > 0 ? Math.round(((value - previous) / previous) * 100) : null;

    const Trend = delta !== null && delta < 0 ? TrendingDown : TrendingUp;

    return (
        <div className="rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="truncate text-xs font-medium text-muted-foreground">
                        {label}
                    </p>
                    <p className="mt-1 text-2xl font-bold tracking-tight">
                        {format(value)}
                    </p>
                    <div className="mt-1 flex flex-wrap items-center gap-2 text-xs">
                        {delta !== null && (
                            <span
                                className={
                                    delta < 0
                                        ? 'flex items-center gap-1 font-medium text-destructive'
                                        : 'flex items-center gap-1 font-medium text-green-600 dark:text-green-400'
                                }
                            >
                                <Trend className="size-3" />
                                {delta > 0 ? '+' : ''}
                                {delta} %
                            </span>
                        )}
                        {hint && (
                            <span className="text-muted-foreground">
                                {hint}
                            </span>
                        )}
                    </div>
                </div>
                {Icon && (
                    <span className="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary">
                        <Icon className="size-4" />
                    </span>
                )}
            </div>
        </div>
    );
}

function formatDuration(seconds: number): string {
    if (seconds < 60) {
        return `${seconds} s`;
    }

    const minutes = Math.floor(seconds / 60);

    return `${minutes} min ${String(seconds % 60).padStart(2, '0')} s`;
}

AdminAnalytics.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'Audience', href: admin.analytics.index() },
    ],
};
