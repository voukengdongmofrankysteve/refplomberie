export type PriceTier = {
    minQty: number;
    maxQty: number | null;
    price: number;
};

export type Product = {
    id: number;
    slug: string;
    category: string;
    categoryLabel: string;
    name: string;
    desc: string;
    videoUrl: string | null;
    price: number;
    oldPrice: number | null;
    badge: string | null;
    warrantyBadges: { value: string; label: string }[];
    img: string;
    images: string[];
    rating: number;
    reviews: number;
    stock: number;
    priceTiers: PriceTier[];
};

export type Category = {
    id: string;
    label: string;
};

export type StoreInfo = {
    name: string;
    address: string;
    phone: string;
    whatsapp: string;
    email: string;
    hours: string;
    /** Carte intégrée, construite côté serveur d'après les réglages. */
    mapEmbedUrl: string;
    mapLinkUrl: string;
    shippingCost: number;
    freeShippingFrom: number;
};

export type CartItem = Product & { qty: number };

export type ProductReview = {
    id: number;
    author: string;
    avatar: string;
    rating: number;
    text: string;
    verifiedPurchase: boolean;
    date: string;
};

export type Story = {
    id: number;
    title: string;
    caption: string | null;
    type: 'image' | 'video';
    mediaUrl: string | null;
    thumbnailUrl: string | null;
    linkUrl: string | null;
    linkLabel: string | null;
    position: number;
    isActive: boolean;
};

export type Faq = {
    id: number;
    question: string;
    answer: string;
    category: string | null;
};

export type Testimonial = {
    id: number;
    name: string;
    role: string | null;
    text: string;
    rating: number;
    initials: string;
};

export type FlashSaleProduct = {
    id: number;
    slug: string;
    name: string;
    category: string;
    img: string;
    price: number;
    originalPrice: number;
    discount: number;
    stock: number;
    rating: number;
    reviews: number;
};

export type FlashSale = {
    id: number;
    title: string;
    /** « upcoming » avant son début, « running » une fois démarrée. */
    status: 'upcoming' | 'running';
    startsAt: string;
    endsAt: string;
    products: FlashSaleProduct[];
};

export type AdminFaq = Faq & {
    position: number;
    isActive: boolean;
};

export type Technician = {
    id: number;
    name: string;
    specialty: string;
    experience: string;
    rating: number;
    jobs: number;
    img: string;
    available: boolean;
};

export type OrderItem = {
    id: number;
    productName: string;
    unitPrice: number;
    quantity: number;
    lineTotal: number;
};

export type Order = {
    id: number;
    reference: string;
    customerName: string;
    customerPhone: string;
    customerAddress: string | null;
    status: string;
    statusLabel: string;
    subtotal: number;
    shipping: number;
    promoCode: string | null;
    discount: number;
    total: number;
    note: string | null;
    createdAt: string;
    accountEmail?: string | null;
    items?: OrderItem[];
    itemsCount?: number;
    /** Message de suivi pré-rempli, prêt à envoyer au client. */
    whatsAppUrl: string;
    /** Vrai si le client reçoit déjà ce suivi par email. */
    emailNotified?: boolean;
};

export type TechnicianRequest = {
    id: number;
    reference: string;
    customerName: string;
    customerPhone: string;
    address: string;
    service: string;
    preferredDate: string | null;
    preferredTime: string | null;
    description: string;
    status: string;
    statusLabel: string;
    adminNote: string | null;
    technicianId: number | null;
    technicianName?: string | null;
    accountEmail?: string | null;
    createdAt: string;
};

export type ContactMessage = {
    id: number;
    name: string;
    email: string | null;
    phone: string;
    subject: string | null;
    message: string;
    status: string;
    statusLabel: string;
    createdAt: string;
};

export type StatusOption = {
    value: string;
    label: string;
};

/** Lien de pagination rendu par Laravel. */
export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** `paginate()->through()` — la forme « simple », sans enveloppe `data`. */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

/** Collection de ressources paginée : `meta` et `links` sont séparés. */
export type PaginatedResource<T> = {
    data: T[];
    links: {
        first: string | null;
        last: string | null;
        prev: string | null;
        next: string | null;
    };
    meta: {
        current_page: number;
        last_page: number;
        total: number;
        from: number | null;
        to: number | null;
        links: PaginationLink[];
    };
};

/** Résultat d'un code promo vérifié par le serveur. */
export type AppliedPromo = {
    code: string;
    label: string | null;
    advantage: string;
    discount: number;
    shipping: number;
};

/** Réponse de la recherche instantanée du bandeau de navigation. */
export type SearchHit = {
    id: number;
    slug: string;
    name: string;
    category: string;
    price: number;
    img: string;
    stock: number;
};

export type SearchResults = {
    products: SearchHit[];
    categories: Category[];
};

/** Coordonnées saisies pour établir un devis. */
export type QuoteDetails = {
    customer_name: string;
    customer_phone: string;
    customer_email: string;
    customer_company: string;
    customer_address: string;
    note: string;
};

export type QuoteItem = {
    id: number;
    productName: string;
    unitPrice: number;
    quantity: number;
    lineTotal: number;
};

export type Quote = {
    id: number;
    reference: string;
    customerName: string;
    customerPhone: string;
    customerEmail: string | null;
    customerCompany: string | null;
    customerAddress: string | null;
    status: string;
    statusLabel: string;
    subtotal: number;
    shipping: number;
    total: number;
    note: string | null;
    validUntil: string;
    isExpired: boolean;
    createdAt: string;
    accountEmail?: string | null;
    items?: QuoteItem[];
    itemsCount?: number;
    pdfUrl: string;
};

/** Code promo tel qu'il est administré depuis le back-office. */
export type AdminPromoCode = {
    id: number;
    code: string;
    label: string | null;
    type: string;
    typeLabel: string;
    value: number;
    advantage: string;
    minSubtotal: number;
    maxUses: number | null;
    usedCount: number;
    startsAt: string | null;
    endsAt: string | null;
    isActive: boolean;
    isRedeemable: boolean;
};

/** Ligne de l'alerte de réapprovisionnement du tableau de bord. */
export type LowStockProduct = {
    id: number;
    slug: string;
    name: string;
    category: string;
    stock: number;
    threshold: number;
    level: 'out' | 'low' | 'ok';
    image: string;
};
