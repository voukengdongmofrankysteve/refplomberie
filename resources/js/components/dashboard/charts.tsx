/*
 * Graphiques du tableau de bord.
 *
 * En SVG, sans bibliothèque : ce dont la page a besoin — une courbe, des
 * barres, un entonnoir — tient en cent lignes, là où une bibliothèque de
 * graphiques pèserait plus lourd que le reste du back-office réuni.
 */

type Point = {
    label: string;
    value: number;
    secondary?: number;
};

/**
 * Courbe d'évolution, avec une seconde série en surimpression.
 *
 * L'aire est tracée sous la courbe principale : sur un mois de données, une
 * ligne seule se lit mal une fois imprimée ou projetée.
 */
export function TrendChart({
    points,
    label,
    secondaryLabel,
    formatValue = (n) => n.toLocaleString('fr-FR'),
}: {
    points: Point[];
    label: string;
    secondaryLabel?: string;
    formatValue?: (n: number) => string;
}) {
    const width = 900;
    const height = 260;
    const padding = { top: 16, right: 12, bottom: 28, left: 44 };

    const max = Math.max(
        1,
        ...points.map((p) => Math.max(p.value, p.secondary ?? 0)),
    );

    const innerWidth = width - padding.left - padding.right;
    const innerHeight = height - padding.top - padding.bottom;

    const x = (index: number) =>
        padding.left +
        (points.length <= 1
            ? innerWidth / 2
            : (index / (points.length - 1)) * innerWidth);

    const y = (value: number) =>
        padding.top + innerHeight - (value / max) * innerHeight;

    const line = (key: 'value' | 'secondary') =>
        points
            .map(
                (point, index) =>
                    `${index === 0 ? 'M' : 'L'} ${x(index)} ${y(point[key] ?? 0)}`,
            )
            .join(' ');

    const area = `${line('value')} L ${x(points.length - 1)} ${
        padding.top + innerHeight
    } L ${x(0)} ${padding.top + innerHeight} Z`;

    // Quatre repères horizontaux suffisent à situer une valeur sans
    // encombrer le fond du graphique.
    const gridlines = [0, 0.25, 0.5, 0.75, 1];

    // Sur trente jours, écrire toutes les dates les rendrait illisibles.
    const step = Math.max(1, Math.ceil(points.length / 8));

    return (
        <div className="overflow-x-auto">
            <svg
                viewBox={`0 0 ${width} ${height}`}
                className="h-64 w-full min-w-[520px]"
                role="img"
                aria-label={`${label} sur la période`}
            >
                {gridlines.map((ratio) => (
                    <g key={ratio}>
                        <line
                            x1={padding.left}
                            x2={width - padding.right}
                            y1={y(max * ratio)}
                            y2={y(max * ratio)}
                            className="stroke-border"
                            strokeDasharray="3 4"
                        />
                        <text
                            x={padding.left - 8}
                            y={y(max * ratio) + 4}
                            textAnchor="end"
                            className="fill-muted-foreground text-[10px]"
                        >
                            {formatValue(Math.round(max * ratio))}
                        </text>
                    </g>
                ))}

                <path d={area} className="fill-primary/15" />
                <path
                    d={line('value')}
                    className="stroke-primary"
                    strokeWidth={2}
                    fill="none"
                    strokeLinejoin="round"
                />

                {secondaryLabel && (
                    <path
                        d={line('secondary')}
                        className="stroke-muted-foreground/60"
                        strokeWidth={1.5}
                        strokeDasharray="4 4"
                        fill="none"
                    />
                )}

                {points.map((point, index) =>
                    index % step === 0 || index === points.length - 1 ? (
                        <text
                            key={point.label + index}
                            x={x(index)}
                            y={height - 8}
                            textAnchor="middle"
                            className="fill-muted-foreground text-[10px]"
                        >
                            {point.label}
                        </text>
                    ) : null,
                )}

                {points.map((point, index) => (
                    <circle
                        key={`dot-${point.label}-${index}`}
                        cx={x(index)}
                        cy={y(point.value)}
                        r={points.length > 45 ? 0 : 2.5}
                        className="fill-primary"
                    >
                        <title>{`${point.label} — ${formatValue(point.value)} ${label.toLowerCase()}`}</title>
                    </circle>
                ))}
            </svg>

            <div className="flex flex-wrap items-center gap-4 px-2 text-xs text-muted-foreground">
                <span className="flex items-center gap-1.5">
                    <span className="h-0.5 w-4 rounded bg-primary" />
                    {label}
                </span>
                {secondaryLabel && (
                    <span className="flex items-center gap-1.5">
                        <span className="h-0.5 w-4 rounded bg-muted-foreground/60" />
                        {secondaryLabel}
                    </span>
                )}
            </div>
        </div>
    );
}

export type BarRow = {
    name: string;
    code?: string | null;
    value: number;
    hint?: string | null;
};

/**
 * Classement en barres proportionnelles.
 *
 * La barre est un fond derrière le libellé plutôt qu'une colonne à côté : les
 * noms de villes camerounaises sont longs, et une colonne les tronquerait.
 */
export function BarList({
    rows,
    empty = 'Aucune donnée sur cette période.',
    formatValue = (n) => n.toLocaleString('fr-FR'),
}: {
    rows: BarRow[];
    empty?: string;
    formatValue?: (n: number) => string;
}) {
    if (rows.length === 0) {
        return (
            <p className="px-4 py-8 text-center text-sm text-muted-foreground">
                {empty}
            </p>
        );
    }

    const max = Math.max(1, ...rows.map((row) => row.value));

    return (
        <ul className="space-y-1 p-2">
            {rows.map((row) => (
                <li key={`${row.name}-${row.code ?? ''}`} className="relative">
                    <div
                        className="absolute inset-y-0 left-0 rounded-md bg-primary/10"
                        style={{
                            width: `${Math.max(2, (row.value / max) * 100)}%`,
                        }}
                        aria-hidden
                    />
                    <div className="relative flex items-center justify-between gap-3 px-2 py-1.5">
                        <span className="flex min-w-0 items-center gap-2 text-sm">
                            {row.code && <FlagEmoji code={row.code} />}
                            <span className="truncate">{row.name}</span>
                        </span>
                        <span className="shrink-0 text-sm font-semibold tabular-nums">
                            {formatValue(row.value)}
                            {row.hint && (
                                <span className="ml-1.5 text-xs font-normal text-muted-foreground">
                                    {row.hint}
                                </span>
                            )}
                        </span>
                    </div>
                </li>
            ))}
        </ul>
    );
}

/** Histogramme compact — l'affluence heure par heure, par exemple. */
export function MiniBars({
    rows,
    formatValue = (n) => n.toLocaleString('fr-FR'),
}: {
    rows: { label: string; value: number }[];
    formatValue?: (n: number) => string;
}) {
    const max = Math.max(1, ...rows.map((row) => row.value));

    return (
        <div className="flex items-end gap-0.5 overflow-x-auto px-4 pb-2">
            {rows.map((row) => (
                <div
                    key={row.label}
                    className="flex min-w-0 flex-1 flex-col items-center gap-1"
                    title={`${row.label} — ${formatValue(row.value)}`}
                >
                    <div
                        className="w-full rounded-t bg-primary/70"
                        style={{
                            height: `${Math.max(2, (row.value / max) * 96)}px`,
                        }}
                    />
                    <span className="truncate text-[9px] text-muted-foreground">
                        {row.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

/**
 * Drapeau du pays, dérivé de son code à deux lettres.
 *
 * Les indicateurs régionaux Unicode évitent d'embarquer deux cents images
 * pour une colonne de tableau.
 */
function FlagEmoji({ code }: { code: string }) {
    const flag = code
        .toUpperCase()
        .replace(/[^A-Z]/g, '')
        .split('')
        .map((letter) => String.fromCodePoint(127397 + letter.charCodeAt(0)))
        .join('');

    return (
        <span aria-hidden className="shrink-0 text-base leading-none">
            {flag}
        </span>
    );
}
