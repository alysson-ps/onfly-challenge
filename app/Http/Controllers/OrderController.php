<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OrderService;
use Illuminate\Support\Facades\DB;
use App\Events\OrderStatusChanged;
use App\Http\Requests\OrderStoreRequest;
use App\Http\Requests\OrderFiltersRequest;
use App\Exceptions\OrderCancellationException;
use App\Http\Requests\OrderChangeStatusRequest;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class OrderController extends Controller
{
    public function __construct(
        protected readonly OrderService $orderService,
    ) {}

    public function store(OrderStoreRequest $request)
    {
        $validated = $request->validated();

        $user = $request->user();

        $order = DB::transaction(function () use ($user, $validated) {
            return $user->orders()->create([
                'user_name' => $user->name,
                'destination' => $validated['destination'],
                'departure_date' => $validated['departure_date'],
                'return_date' => $validated['return_date'],
            ]);
        });

        return response()->json([
            'success' => true,
            'order' => $order->toResource(),
        ], 201);
    }

    public function show(Request $request, int $id)
    {
        $order = $this->orderService->getOrder($request->user(), $id);

        throw_if(!$order, new ModelNotFoundException(
            sprintf("Order with id %d not found.", $id),
            404
        ));

        return response()->json([
            'success' => true,
            'order' => $order->toResource(),
        ]);
    }

    public function index(OrderFiltersRequest $request)
    {
        $user = $request->user();

        $orders = $user->role === 'admin'
            ? $this->orderService->getAll($request->validated())
            : $this->orderService->getOrdersByUser($user, $request->validated());

        return response()->json([
            'success' => true,
            'orders' => $orders->toResourceCollection(),
        ]);
    }

    public function update(OrderStoreRequest $request, int $id)
    {
        $validated = $request->validated();

        $order = $this->orderService->getOrder($request->user(), $id);

        throw_if(!$order, new ModelNotFoundException(
            sprintf("Order with id %d not found.", $id),
            404
        ));

        DB::transaction(function () use ($order, $validated) {
            $order->update($validated);
        });

        return response()->json([
            'success' => true,
            'order' => $order->toResource(),
        ]);
    }

    public function changeStatus(OrderChangeStatusRequest $request, int $id)
    {
        $validated = $request->validated();

        $order = $this->orderService->getAnyOrder($id);

        throw_if(!$order, new ModelNotFoundException(
            sprintf("Order with id %d not found.", $id),
            404
        ));

        throw_if(
            $validated['status'] === 'cancelled' && $order->status === 'approved',
            new OrderCancellationException(
                'Approved orders cannot be cancelled.',
                400
            )
        );

        DB::transaction(function () use ($order, $validated) {
            $order->status = $validated['status'];
            $order->save();
        });

        OrderStatusChanged::dispatch($order);

        return response()->json([
            'success' => true,
            'order' => $order->toResource(),
        ]);
    }
}
