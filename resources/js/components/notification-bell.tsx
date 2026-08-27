import { router } from '@inertiajs/react';
import { useEffect, useRef, useState } from 'react';
import { useNotifications } from '@/hooks/use-notifications';

/** Cloche du centre de notifications, avec pastille des non-lues. */
export default function NotificationBell({
    tone = 'shop',
}: {
    /** `shop` sur la vitrine claire, `dashboard` dans le back-office. */
    tone?: 'shop' | 'dashboard';
}) {
    const {
        items,
        unread,
        markAllRead,
        refresh,
        enablePush,
        pushEnabled,
        pushBlocked,
        pushAvailable,
    } = useNotifications();

    const [open, setOpen] = useState(false);
    const panelRef = useRef<HTMLDivElement | null>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const onClickOutside = (event: MouseEvent) => {
            if (!panelRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        document.addEventListener('mousedown', onClickOutside);

        return () => document.removeEventListener('mousedown', onClickOutside);
    }, [open]);

    const iconColor =
        tone === 'shop' ? 'text-[#4A4A6A]' : 'text-muted-foreground';

    return (
        <div className="relative" ref={panelRef}>
            <button
                type="button"
                onClick={() => {
                    setOpen((value) => !value);

                    if (!open) {
                        void refresh();
                    }
                }}
                className={`relative flex h-10 w-10 items-center justify-center rounded-xl transition-colors hover:bg-gray-100 ${iconColor}`}
                aria-label={
                    unread > 0
                        ? `Notifications, ${unread} non lue${unread > 1 ? 's' : ''}`
                        : 'Notifications'
                }
            >
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
                        d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                    />
                </svg>
                {unread > 0 && (
                    <span className="absolute -top-0.5 -right-0.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-[#25D366] px-1 text-[9px] font-bold text-white">
                        {unread > 9 ? '9+' : unread}
                    </span>
                )}
            </button>

            {open && (
                <div className="absolute right-0 z-50 mt-2 w-80 overflow-hidden rounded-2xl border border-[#E9ECEF] bg-white shadow-xl">
                    <div className="flex items-center justify-between border-b border-[#E9ECEF] px-4 py-3">
                        <p className="text-sm font-bold text-[#1A1A2E]">
                            Notifications
                        </p>
                        {unread > 0 && (
                            <button
                                type="button"
                                onClick={() => void markAllRead()}
                                className="text-xs font-semibold text-[#1DA851] hover:underline"
                            >
                                Tout marquer comme lu
                            </button>
                        )}
                    </div>

                    {/* Invitation au push : uniquement si c'est encore possible. */}
                    {pushAvailable && !pushEnabled && !pushBlocked && (
                        <button
                            type="button"
                            onClick={() => void enablePush()}
                            className="flex w-full items-center gap-2 border-b border-[#E9ECEF] bg-[#E8F5E9] px-4 py-2.5 text-left text-xs text-[#1A1A2E] transition-colors hover:bg-[#d7eeda]"
                        >
                            <span className="font-semibold text-[#1DA851]">
                                Activer les alertes
                            </span>
                            <span className="text-[#4A4A6A]">
                                pour être prévenu même hors du site
                            </span>
                        </button>
                    )}

                    <div className="max-h-96 overflow-y-auto">
                        {items.length === 0 ? (
                            <p className="px-4 py-10 text-center text-sm text-[#4A4A6A]">
                                Aucune notification pour le moment.
                            </p>
                        ) : (
                            <ul className="divide-y divide-[#E9ECEF]">
                                {items.map((item) => (
                                    <li key={item.id}>
                                        <button
                                            type="button"
                                            onClick={() => {
                                                setOpen(false);

                                                if (item.url) {
                                                    router.visit(item.url);
                                                }
                                            }}
                                            className={`w-full px-4 py-3 text-left transition-colors hover:bg-[#F8F9FA] ${
                                                item.read
                                                    ? ''
                                                    : 'bg-[#E8F5E9]/40'
                                            }`}
                                        >
                                            <p className="flex items-center gap-2 text-sm font-semibold text-[#1A1A2E]">
                                                {!item.read && (
                                                    <span className="h-1.5 w-1.5 shrink-0 rounded-full bg-[#25D366]" />
                                                )}
                                                {item.title}
                                            </p>
                                            <p className="mt-0.5 text-xs leading-relaxed text-[#4A4A6A]">
                                                {item.body}
                                            </p>
                                        </button>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
