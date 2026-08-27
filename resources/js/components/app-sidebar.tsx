import { Link, usePage } from '@inertiajs/react';
import {
    Calculator,
    ChartNoAxesCombined,
    ClipboardList,
    Clapperboard,
    FileSpreadsheet,
    FileText,
    Heart,
    HelpCircle,
    History,
    Megaphone,
    LayoutGrid,
    MessageSquare,
    MessagesSquare,
    Package,
    ReceiptText,
    Settings,
    Store,
    Tag,
    Timer,
    Truck,
    Users,
    Wrench,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard, home } from '@/routes';
import * as account from '@/routes/account';
import admin from '@/routes/admin';
import type { NavItem } from '@/types';

/** Item de nav du back-office, réservé à un rôle si `permission` est renseigné. */
type AdminNavItem = NavItem & { permission?: string };

/** Navigation de l'espace client. */
const customerNavItems: NavItem[] = [
    { title: 'Tableau de bord', href: dashboard(), icon: LayoutGrid },
    { title: 'Mes favoris', href: account.favorites(), icon: Heart },
    { title: 'Mes commandes', href: account.orders(), icon: ReceiptText },
    {
        title: 'Mes interventions',
        href: account.technicianRequests(),
        icon: Wrench,
    },
];

/**
 * Navigation du back-office. Le tableau de bord reste commun à tout le
 * personnel ; chaque autre item porte la permission qui protège sa route
 * côté serveur, et n'apparaît que si le compte connecté la possède.
 */
const adminNavItems: AdminNavItem[] = [
    { title: 'Vue d’ensemble', href: admin.dashboard(), icon: LayoutGrid },
    {
        title: 'Audience',
        href: admin.analytics.index(),
        icon: ChartNoAxesCombined,
        permission: 'analytics',
    },
    {
        title: 'Produits',
        href: admin.products.index(),
        icon: Package,
        permission: 'products',
    },
    {
        title: 'Commandes',
        href: admin.orders.index(),
        icon: ReceiptText,
        permission: 'orders',
    },
    {
        title: 'Devis',
        href: admin.quotes.index(),
        icon: FileText,
        permission: 'quotes',
    },
    {
        title: 'Codes promo',
        href: admin.promoCodes.index(),
        icon: Tag,
        permission: 'promo-codes',
    },
    {
        title: 'Ventes flash',
        href: admin.flashSales.index(),
        icon: Timer,
        permission: 'flash-sales',
    },
    {
        title: 'Campagnes',
        href: admin.campaigns.index(),
        icon: Megaphone,
        permission: 'campaigns',
    },
    {
        title: 'Interventions',
        href: admin.technicianRequests.index(),
        icon: ClipboardList,
        permission: 'technician-requests',
    },
    {
        title: 'Techniciens',
        href: admin.technicians.index(),
        icon: Wrench,
        permission: 'technicians',
    },
    {
        title: 'Statuts',
        href: admin.stories.index(),
        icon: Clapperboard,
        permission: 'stories',
    },
    {
        title: 'Témoignages',
        href: admin.testimonials.index(),
        icon: MessagesSquare,
        permission: 'testimonials',
    },
    {
        title: 'Messages',
        href: admin.messages.index(),
        icon: MessageSquare,
        permission: 'messages',
    },
    {
        title: 'FAQ',
        href: admin.faqs.index(),
        icon: HelpCircle,
        permission: 'faqs',
    },
    {
        title: 'Comptes',
        href: admin.customers.index(),
        icon: Users,
        permission: 'accounts',
    },
    {
        title: 'Import / export',
        href: admin.catalog.index(),
        icon: FileSpreadsheet,
        permission: 'catalog',
    },
    {
        title: 'Fournisseurs',
        href: admin.suppliers.index(),
        icon: Truck,
        permission: 'suppliers',
    },
    {
        title: 'Bons de commande',
        href: admin.purchaseOrders.index(),
        icon: ClipboardList,
        permission: 'suppliers',
    },
    {
        title: 'Comptabilité',
        href: admin.accounting.index(),
        icon: Calculator,
        permission: 'accounting',
    },
    {
        title: 'Journal d’audit',
        href: admin.auditLog.index(),
        icon: History,
        permission: 'audit-log',
    },
    {
        title: 'Réglages',
        href: admin.settings.edit(),
        icon: Settings,
        permission: 'settings',
    },
];

const footerNavItems: NavItem[] = [
    { title: 'Voir la boutique', href: home(), icon: Store },
];

export function AppSidebar() {
    const { auth } = usePage().props;
    const isStaff = auth.isStaff;

    const items = isStaff
        ? adminNavItems.filter(
              (item) =>
                  !item.permission ||
                  auth.permissions.includes(item.permission),
          )
        : customerNavItems;
    const homeHref = isStaff ? admin.dashboard() : dashboard();

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={homeHref} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain
                    items={items}
                    label={isStaff ? 'Administration' : 'Mon espace'}
                />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
