<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Vendor = 'vendor';
    case StockManager = 'stock_manager';
    case Admin = 'admin';

    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Client',
            self::Vendor => 'Vendeur',
            self::StockManager => 'Gestionnaire de stock',
            self::Admin => 'Administrateur',
        };
    }

    /** Un compte du personnel, par opposition à un client. */
    public function isStaff(): bool
    {
        return $this !== self::Customer;
    }

    /**
     * Zones du back-office ouvertes à ce rôle.
     *
     * L'administrateur a toujours accès à tout : plutôt que de recopier la
     * liste, `Accounts`/`Settings`/`AuditLog`/`Analytics` restent en plus des
     * permissions du vendeur et du gestionnaire de stock, réservées à lui
     * seul — notamment `Accounts`, qui autorise le changement de rôle d'un
     * compte : l'ouvrir au vendeur ou au gestionnaire de stock leur
     * permettrait de se promouvoir eux-mêmes administrateur.
     *
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Customer => [],
            self::Vendor => [
                Permission::Orders,
                Permission::Quotes,
                Permission::TechnicianRequests,
                Permission::Messages,
                Permission::Technicians,
                Permission::PromoCodes,
                Permission::Campaigns,
            ],
            self::StockManager => [
                Permission::Products,
                Permission::Catalog,
                Permission::FlashSales,
                Permission::Faqs,
                Permission::Stories,
                Permission::Testimonials,
                Permission::Suppliers,
            ],
            self::Admin => Permission::cases(),
        };
    }

    public function can(Permission $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }
}
