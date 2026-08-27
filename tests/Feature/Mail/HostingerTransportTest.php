<?php

namespace Tests\Feature\Mail;

use App\Mail\EmailVerificationCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class HostingerTransportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'mail.default' => 'hostinger',
            'services.hostinger.token' => 'jeton-de-test',
            'services.hostinger.mailbox' => 'referenceplomberie@vyloxi.com',
        ]);
    }

    public function test_an_email_is_posted_to_the_hostinger_api(): void
    {
        $this->fakeApi();

        Mail::to('client@example.com')
            ->send(new EmailVerificationCodeMail('Jean Mbarga', '123456', 15));

        Http::assertSent(function (Request $request): bool {
            if (! str_contains($request->url(), '/send')) {
                return false;
            }

            $body = $request->data();

            return $request->hasHeader('Authorization', 'Bearer jeton-de-test')
                && $body['to'] === ['client@example.com']
                && str_contains($body['subject'], '123456')
                && str_contains($body['html'], '123456');
        });
    }

    public function test_the_mailbox_identifier_is_resolved_from_its_address(): void
    {
        $this->fakeApi();

        Mail::to('client@example.com')
            ->send(new EmailVerificationCodeMail('Jean', '123456', 15));

        // L'identifiant opaque ne figure pas dans la configuration : il est
        // déduit de l'adresse, plus lisible et plus stable.
        Http::assertSent(fn (Request $request): bool => str_contains(
            $request->url(),
            '/api/v1/mailboxes/AC1a2b3c4d5e6f7g/send',
        ));
    }

    public function test_the_identifier_lookup_is_cached_between_sends(): void
    {
        $this->fakeApi();

        foreach (range(1, 3) as $ignored) {
            Mail::to('client@example.com')
                ->send(new EmailVerificationCodeMail('Jean', '123456', 15));
        }

        $lookups = collect(Http::recorded())
            ->filter(fn (array $pair): bool => str_ends_with($pair[0]->url(), '/api/v1/me'))
            ->count();

        $this->assertSame(1, $lookups);
    }

    public function test_a_refused_send_raises_a_readable_error(): void
    {
        Http::fake([
            '*/api/v1/me' => Http::response([
                'data' => [
                    'mailboxes' => [
                        ['resourceId' => 'AC1a2b3c4d5e6f7g', 'address' => 'referenceplomberie@vyloxi.com'],
                    ],
                ],
            ]),
            '*/send' => Http::response(['error' => 'Quota dépassé.'], 422),
        ]);

        $this->expectExceptionMessage('Quota dépassé.');

        Mail::to('client@example.com')
            ->send(new EmailVerificationCodeMail('Jean', '123456', 15));
    }

    public function test_an_unknown_mailbox_is_reported_clearly(): void
    {
        Http::fake([
            '*/api/v1/me' => Http::response([
                'data' => [
                    'mailboxes' => [
                        ['resourceId' => 'AC9999', 'address' => 'autre@vyloxi.com'],
                    ],
                ],
            ]),
        ]);

        $this->expectExceptionMessage("La boîte « referenceplomberie@vyloxi.com » n'est pas accessible");

        Mail::to('client@example.com')
            ->send(new EmailVerificationCodeMail('Jean', '123456', 15));
    }

    private function fakeApi(): void
    {
        Http::fake([
            '*/api/v1/me' => Http::response([
                'data' => [
                    'orderResourceId' => 'OR1a2b3c4d5e6f7g',
                    'mailboxes' => [
                        [
                            'resourceId' => 'AC1a2b3c4d5e6f7g',
                            'address' => 'referenceplomberie@vyloxi.com',
                        ],
                    ],
                ],
            ]),
            '*/send' => Http::response([], 202),
        ]);
    }
}
