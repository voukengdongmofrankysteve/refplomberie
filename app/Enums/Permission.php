<?php

namespace App\Enums;

/**
 * Zones du back-office qu'un rôle peut ouvrir. Chaque route qui n'est pas
 * ouverte à tout le personnel porte le middleware `permission:xxx`
 * correspondant à l'un de ces cas.
 */
enum Permission: string
{
    case Orders = 'orders';
    case Quotes = 'quotes';
    case TechnicianRequests = 'technician-requests';
    case Messages = 'messages';
    case Technicians = 'technicians';
    case PromoCodes = 'promo-codes';
    case Campaigns = 'campaigns';

    case Products = 'products';
    case Catalog = 'catalog';
    case FlashSales = 'flash-sales';
    case Faqs = 'faqs';
    case Stories = 'stories';
    case Testimonials = 'testimonials';
    case Suppliers = 'suppliers';

    case Accounts = 'accounts';
    case Settings = 'settings';
    case AuditLog = 'audit-log';
    case Analytics = 'analytics';
    case Accounting = 'accounting';
}
