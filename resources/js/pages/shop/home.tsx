import SeoTitle from '@/components/seo-title';
import Contact from '@/components/shop/contact';
import Faq from '@/components/shop/faq';
import FlashSaleSection from '@/components/shop/flash-sale-section';
import Hero from '@/components/shop/hero';
import HowItWorks from '@/components/shop/how-it-works';
import Marquee from '@/components/shop/marquee';
import ProductGrid from '@/components/shop/product-grid';
import Services from '@/components/shop/services';
import { StoreMapSection } from '@/components/shop/store-map';
import StoriesRail from '@/components/shop/stories-rail';
import TechnicianSection from '@/components/shop/technician-section';
import Testimonial from '@/components/shop/testimonial';
import WhyUs from '@/components/shop/why-us';
import type {
    Category,
    Faq as FaqType,
    FlashSale,
    Product,
    Story,
    Technician,
    Testimonial as TestimonialType,
} from '@/types/shop';

type Props = {
    products: Product[];
    categories: Category[];
    technicians: Technician[];
    services: string[];
    stories: Story[];
    faqs: FaqType[];
    flashSale: FlashSale | null;
    testimonials: TestimonialType[];
};

export default function Home({
    products,
    categories,
    technicians,
    services,
    stories,
    faqs,
    flashSale,
    testimonials,
}: Props) {
    return (
        <>
            <SeoTitle />

            <main>
                <Hero services={services} />
                <Marquee />
                <StoriesRail stories={stories} />
                <FlashSaleSection sale={flashSale} />
                <ProductGrid products={products} categories={categories} />
                <TechnicianSection
                    technicians={technicians}
                    services={services}
                />
                <HowItWorks />
                <Services />
                <WhyUs />
                <Testimonial testimonials={testimonials} />
                <Faq faqs={faqs} />
                <Contact />
                <StoreMapSection />
            </main>
        </>
    );
}
