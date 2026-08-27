<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ListPdfService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    /**
     * Annuaire des comptes, avec volume de commandes et de favoris.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->trim()->value();

        $users = User::query()
            ->withCount(['orders', 'favorites', 'technicianRequests'])
            ->withSum(
                ['orders as spent' => fn ($query) => $query->whereNot(
                    'status',
                    OrderStatus::Cancelled->value,
                )],
                'total',
            )
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role->value,
                'roleLabel' => $user->role->label(),
                'ordersCount' => $user->orders_count,
                'favoritesCount' => $user->favorites_count,
                'requestsCount' => $user->technician_requests_count,
                'spent' => (int) ($user->spent ?? 0),
                'createdAt' => $user->created_at?->format('d/m/Y') ?? '',
            ]);

        return Inertia::render('admin/customers/index', [
            'customers' => $users,
            'roles' => array_map(
                fn (UserRole $role): array => [
                    'value' => $role->value,
                    'label' => $role->label(),
                ],
                UserRole::cases(),
            ),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * Change le rôle d'un compte (client ↔ administrateur).
     */
    /**
     * L'annuaire des comptes filtré, en PDF — mêmes filtres que l'écran.
     */
    public function exportPdf(Request $request, ListPdfService $pdf): HttpResponse
    {
        $search = $request->string('search')->trim()->value();

        $users = User::query()
            ->withCount(['orders', 'favorites', 'technicianRequests'])
            ->withSum(
                ['orders as spent' => fn ($query) => $query->whereNot(
                    'status',
                    OrderStatus::Cancelled->value,
                )],
                'total',
            )
            ->when($search !== '', fn ($query) => $query->where(
                fn ($sub) => $sub->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"),
            ))
            ->orderBy('name')
            ->get();

        $document = $pdf->render(
            title: 'Comptes',
            subtitle: $users->count().' compte'.($users->count() > 1 ? 's' : ''),
            columns: [
                ['label' => 'Nom'],
                ['label' => 'Email'],
                ['label' => 'Téléphone'],
                ['label' => 'Rôle'],
                ['label' => 'Commandes', 'align' => 'right'],
                ['label' => 'Dépensé', 'align' => 'right'],
                ['label' => 'Inscrit le'],
            ],
            rows: $users->map(fn (User $user): array => [
                $user->name,
                $user->email,
                $user->phone ?? '',
                $user->role->label(),
                (string) $user->orders_count,
                number_format((int) ($user->spent ?? 0), 0, ',', ' ').' FCFA',
                $user->created_at?->format('d/m/Y') ?? '',
            ])->all(),
            orientation: 'landscape',
        );

        return $document->download(ListPdfService::filename('comptes'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'role' => ['required', Rule::enum(UserRole::class)],
        ], attributes: ['role' => 'rôle']);

        if ($user->is($request->user()) && $data['role'] !== UserRole::Admin->value) {
            return back()->with('error', 'Vous ne pouvez pas retirer votre propre accès administrateur.');
        }

        $user->update($data);

        return back()->with('success', "Rôle de {$user->name} mis à jour.");
    }
}
