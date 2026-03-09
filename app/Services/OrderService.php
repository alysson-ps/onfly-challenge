<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class OrderService
{
    public function __construct(
        protected readonly Order $order,
    ) {}

    public function getAll(array $filters = []): Collection
    {
        $query = $this->order->newQuery();

        return $this->applyFilters($query, $filters)->get();
    }

    public function getOrdersByUser(User $user, array $filters = []): Collection
    {
        $query = $this->order->newQuery()->where('user_id', $user->id);

        return $this->applyFilters($query, $filters)->get();
    }

    private function applyFilters($query, array $filters = [])
    {
        return $query->when($filters['destination'] ?? null, function ($query, $destination) {
            $query->where('destination', 'like', "%$destination%");
        })->when($filters['status'] ?? null, function ($query, $status) {
            $query->where('status', $status);
        })->when($filters['departure_date'] ?? null, function ($query, $departureDate) {
            $query->whereBetween('departure_date', [$departureDate['from'], $departureDate['to']]);
        })->when($filters['return_date'] ?? null, function ($query, $returnDate) {
            $query->whereBetween('return_date', [$returnDate['from'], $returnDate['to']]);
        });
    }

    public function getAnyOrder(int $id): ?Order
    {
        return $this->order->newQuery()->find($id);
    }

    public function getOrder(User $user, int $id): ?Order
    {
        return $this->order->newQuery()->where('user_id', $user->id)->find($id);
    }
}
