import { Head, router, useForm } from '@inertiajs/react';
import { HelpCircle, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import type { FormEvent } from 'react';
import EmptyState from '@/components/dashboard/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import admin from '@/routes/admin';
import type { AdminFaq } from '@/types/shop';

const BLANK = {
    question: '',
    answer: '',
    category: '',
    position: 0,
    is_active: true,
};

type Props = {
    faqs: AdminFaq[];
};

export default function AdminFaqs({ faqs }: Props) {
    // `null` = création, sinon identifiant de la question éditée.
    const [editing, setEditing] = useState<number | null>(null);
    const { data, setData, post, put, processing, errors, reset } =
        useForm(BLANK);

    const startCreate = () => {
        setEditing(null);
        reset();
    };

    const startEdit = (faq: AdminFaq) => {
        setEditing(faq.id);
        setData({
            question: faq.question,
            answer: faq.answer,
            category: faq.category ?? '',
            position: faq.position,
            is_active: faq.isActive,
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
            post(admin.faqs.store().url, options);
        } else {
            put(admin.faqs.update(editing).url, options);
        }
    };

    const destroy = (faq: AdminFaq) => {
        if (window.confirm(`Supprimer la question « ${faq.question} » ?`)) {
            router.delete(admin.faqs.destroy(faq.id).url, {
                preserveScroll: true,
            });
        }
    };

    return (
        <>
            <Head title="FAQ" />

            <div className="flex h-full flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-lg font-semibold">
                            Foire aux questions
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {faqs.length} question
                            {faqs.length !== 1 ? 's' : ''}. Seules les
                            questions actives paraissent sur la vitrine.
                        </p>
                    </div>
                    <Button variant="secondary" onClick={startCreate}>
                        <Plus className="size-4" />
                        Nouvelle question
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-[1fr_380px]">
                    {/* Liste */}
                    <section className="overflow-hidden rounded-xl border border-sidebar-border/70 bg-card dark:border-sidebar-border">
                        {faqs.length === 0 ? (
                            <EmptyState
                                icon={HelpCircle}
                                title="Aucune question"
                                description="Ajoutez les questions que vos clients posent le plus souvent."
                            />
                        ) : (
                            <ul className="divide-y divide-sidebar-border/70 dark:divide-sidebar-border">
                                {faqs.map((faq) => (
                                    <li
                                        key={faq.id}
                                        className="flex flex-wrap items-start gap-3 p-4"
                                    >
                                        <div className="min-w-0 flex-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-sm font-semibold">
                                                    {faq.question}
                                                </span>
                                                {faq.category && (
                                                    <Badge variant="secondary">
                                                        {faq.category}
                                                    </Badge>
                                                )}
                                                {!faq.isActive && (
                                                    <Badge
                                                        variant="secondary"
                                                        className="bg-muted text-muted-foreground"
                                                    >
                                                        Masquée
                                                    </Badge>
                                                )}
                                            </div>
                                            <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                                                {faq.answer}
                                            </p>
                                        </div>

                                        <div className="flex shrink-0 gap-1">
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => startEdit(faq)}
                                            >
                                                Modifier
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => destroy(faq)}
                                                aria-label={`Supprimer ${faq.question}`}
                                            >
                                                <Trash2 className="size-4 text-destructive" />
                                            </Button>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </section>

                    {/* Formulaire */}
                    <section className="h-fit rounded-xl border border-sidebar-border/70 bg-card p-4 dark:border-sidebar-border">
                        <h2 className="mb-4 text-sm font-semibold">
                            {editing === null
                                ? 'Nouvelle question'
                                : 'Modifier la question'}
                        </h2>

                        <form onSubmit={handleSubmit} className="space-y-3">
                            <div>
                                <Label htmlFor="faq-question">
                                    Question *
                                </Label>
                                <Input
                                    id="faq-question"
                                    value={data.question}
                                    onChange={(e) =>
                                        setData('question', e.target.value)
                                    }
                                    placeholder="Livrez-vous en dehors de Yaoundé ?"
                                />
                                {errors.question && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.question}
                                    </p>
                                )}
                            </div>

                            <div>
                                <Label htmlFor="faq-answer">Réponse *</Label>
                                <textarea
                                    id="faq-answer"
                                    rows={5}
                                    value={data.answer}
                                    onChange={(e) =>
                                        setData('answer', e.target.value)
                                    }
                                    className="mt-1 w-full resize-none rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:outline-none"
                                    placeholder="Oui, partout au Cameroun. Les délais varient selon la ville…"
                                />
                                {errors.answer && (
                                    <p className="mt-1 text-xs text-destructive">
                                        {errors.answer}
                                    </p>
                                )}
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div>
                                    <Label htmlFor="faq-category">
                                        Thème
                                    </Label>
                                    <Input
                                        id="faq-category"
                                        value={data.category}
                                        onChange={(e) =>
                                            setData(
                                                'category',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Livraison"
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="faq-position">
                                        Ordre d’affichage
                                    </Label>
                                    <Input
                                        id="faq-position"
                                        type="number"
                                        min={0}
                                        value={data.position}
                                        onChange={(e) =>
                                            setData(
                                                'position',
                                                Number(e.target.value),
                                            )
                                        }
                                    />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="faq-active"
                                    checked={data.is_active}
                                    onCheckedChange={(checked) =>
                                        setData('is_active', checked === true)
                                    }
                                />
                                <Label
                                    htmlFor="faq-active"
                                    className="font-normal"
                                >
                                    Visible sur la vitrine
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

AdminFaqs.layout = {
    breadcrumbs: [
        { title: 'Administration', href: admin.dashboard() },
        { title: 'FAQ', href: admin.faqs.index() },
    ],
};
