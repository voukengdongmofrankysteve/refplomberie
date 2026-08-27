<?php

namespace Tests\Feature\Analytics;

use App\Enums\AnalyticsEvent;
use App\Enums\UserRole;
use App\Models\Analytics\Event;
use App\Models\Analytics\IpLocation;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\Analytics\AnalyticsReport;
use App\Services\Analytics\Period;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Adresse publique : depuis la boucle locale, la localisation est
        // court-circuitée — c'est voulu, mais ça ne testerait rien.
        $this->withServerVariables(['REMOTE_ADDR' => '154.72.166.10']);

        // Aucune requête vers l'extérieur pendant les tests : la localisation
        // est un service tiers, pas une dépendance de la mesure.
        Http::preventStrayRequests();
        Http::fake([
            '*' => Http::response([
                'status' => 'success',
                'continent' => 'Afrique',
                'continentCode' => 'AF',
                'country' => 'Cameroun',
                'countryCode' => 'CM',
                'regionName' => 'Littoral',
                'city' => 'Douala',
                'timezone' => 'Africa/Douala',
            ]),
        ]);
    }

    public function test_a_visit_to_the_shop_is_counted(): void
    {
        $this->get(route('home'))->assertOk();

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Session::count());
        $this->assertDatabaseHas('analytics_events', [
            'type' => AnalyticsEvent::PageView->value,
            'path' => '/',
        ]);
    }

    public function test_the_same_browser_is_not_counted_twice(): void
    {
        $this->get(route('home'));

        // Le cookie posé par la première visite revient avec la seconde.
        $this->followingRedirects()->get(route('home'));

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, Session::count());
        $this->assertSame(2, Event::where('type', AnalyticsEvent::PageView->value)->count());
    }

    public function test_robots_are_not_measured(): void
    {
        $this->withHeader('User-Agent', 'Mozilla/5.0 (compatible; Googlebot/2.1)')
            ->get(route('home'))
            ->assertOk();

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, Event::count());
    }

    public function test_the_back_office_is_not_counted_as_audience(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();

        $this->assertSame(0, Event::where('type', AnalyticsEvent::PageView->value)->count());
    }

    public function test_a_product_page_records_which_product_was_seen(): void
    {
        $product = $this->product();

        $this->get(route('shop.product', $product))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'type' => AnalyticsEvent::ProductView->value,
            'subject_type' => $product->getMorphClass(),
            'subject_id' => $product->id,
        ]);
    }

    public function test_an_order_is_recorded_with_its_amount(): void
    {
        $product = $this->product();

        $this->post(route('orders.store'), [
            'customer_name' => 'Jean Mbarga',
            'customer_phone' => '690000000',
            'items' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertRedirect();

        $event = Event::where('type', AnalyticsEvent::OrderPlaced->value)->sole();

        $this->assertNotNull($event->value);
        $this->assertGreaterThan(0, $event->value);
    }

    public function test_a_search_records_its_term_and_result_count(): void
    {
        $this->product();

        $this->get(route('search', ['q' => 'introuvable']))->assertOk();

        $this->assertDatabaseHas('analytics_events', [
            'type' => AnalyticsEvent::Search->value,
            'label' => 'introuvable',
            'value' => 0,
        ]);
    }

    public function test_the_browser_may_only_declare_whitelisted_events(): void
    {
        $product = $this->product();

        $this->post(route('analytics.record'), [
            'type' => AnalyticsEvent::AddToCart->value,
            'subject' => 'product',
            'id' => $product->id,
        ])->assertNoContent();

        // Une commande annoncée par le navigateur serait un chiffre d'affaires
        // inventé : le serveur refuse.
        $this->post(route('analytics.record'), [
            'type' => AnalyticsEvent::OrderPlaced->value,
            'value' => 5_000_000,
        ])->assertSessionHasErrors('type');

        $this->assertSame(1, Event::where('type', AnalyticsEvent::AddToCart->value)->count());
        $this->assertSame(0, Event::where('type', AnalyticsEvent::OrderPlaced->value)->count());
    }

    public function test_an_address_is_only_located_once(): void
    {
        $this->get(route('home'));
        $this->get(route('shop.product', $this->product()));

        // Une seule interrogation du fournisseur pour deux pages. Le compte
        // est filtré : le rendu serveur d'Inertia passe lui aussi par Http.
        $lookups = collect(Http::recorded())
            ->filter(fn (array $pair): bool => str_contains($pair[0]->url(), 'ip-api.com'))
            ->count();

        $this->assertSame(1, $lookups);
        $this->assertSame(1, IpLocation::count());
        $this->assertSame('Douala', Session::sole()->city);
    }

    public function test_the_report_answers_the_questions_the_dashboard_asks(): void
    {
        $product = $this->product();

        $this->get(route('home'));
        $this->get(route('shop.product', $product));
        $this->get(route('search', ['q' => 'tuyau']));

        $report = new AnalyticsReport(Period::make('30d'));
        $summary = $report->summary();

        $this->assertSame(1, $summary['visitors']);
        $this->assertSame(1, $summary['sessions']);
        $this->assertSame(2, $summary['pageViews']);

        // Une courbe complète, jours creux compris.
        $this->assertCount(30, $report->series());

        $this->assertSame($product->name, $report->topProducts()[0]['name']);
        $this->assertSame('tuyau', $report->topSearches()[0]['term']);
        $this->assertSame('Cameroun', $report->breakdown('country')[0]['name']);
        $this->assertCount(24, $report->byHour());
        $this->assertCount(7, $report->byWeekday());
    }

    public function test_the_dashboard_is_reserved_for_administrators(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.analytics.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('admin.analytics.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/analytics/index')
                ->has('summary')
                ->has('series')
                ->has('countries')
                ->has('funnel'));
    }

    public function test_the_pdf_report_is_reserved_for_administrators(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('admin.analytics.pdf'))
            ->assertForbidden();
    }

    public function test_the_pdf_report_downloads_with_the_period_selected(): void
    {
        $product = $this->product();

        $this->get(route('home'));
        $this->get(route('shop.product', $product));
        $this->get(route('search', ['q' => 'tuyau']));

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $response = $this->actingAs($admin)
            ->get(route('admin.analytics.pdf', ['periode' => '30d']));

        $response->assertOk();
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString(
            'attachment',
            (string) $response->headers->get('Content-Disposition'),
        );
        $this->assertStringContainsString(
            'audience-',
            (string) $response->headers->get('Content-Disposition'),
        );
    }

    public function test_old_measurements_are_pruned(): void
    {
        $this->get(route('home'));

        Event::query()->update(['occurred_at' => now()->subYears(3)]);
        Session::query()->update(['last_activity_at' => now()->subYears(3)]);
        Visitor::query()->update(['last_seen_at' => now()->subYears(3)]);

        $this->artisan('analytics:prune')->assertSuccessful();

        $this->assertSame(0, Event::count());
        $this->assertSame(0, Session::count());
        $this->assertSame(0, Visitor::count());
    }

    private function product(): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'tuyauterie'],
            ['label' => 'Tuyauterie'],
        );

        return Product::firstOrCreate(
            ['slug' => 'tuyau-pvc-100'],
            [
                'category_id' => $category->id,
                'name' => 'Tuyau PVC 100',
                'description' => 'Tuyau d’évacuation en PVC, diamètre 100 mm.',
                'price' => 12000,
                'image' => 'https://example.test/tuyau.jpg',
                'stock' => 40,
                'is_active' => true,
            ],
        );
    }
}
