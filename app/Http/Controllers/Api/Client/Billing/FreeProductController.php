<?php

namespace Everest\Http\Controllers\Api\Client\Billing;

use Everest\Models\Egg;
use Everest\Models\Node;
use Everest\Models\User;
use Everest\Models\Billing\Order;
use Illuminate\Support\Facades\DB;
use Everest\Models\Billing\Product;
use Everest\Exceptions\DisplayException;
use Everest\Models\Billing\BillingException;
use Everest\Services\Billing\CreateOrderService;
use Everest\Services\Billing\ServerRenewalService;
use Everest\Transformers\Api\Client\ServerTransformer;
use Everest\Services\Billing\FreeServerDeploymentService;
use Everest\Http\Controllers\Api\Client\ClientApiController;
use Everest\Http\Requests\Api\Client\Billing\ProcessFreeServerRequest;

class FreeProductController extends ClientApiController
{
    public function __construct(
        private CreateOrderService $orderService,
        private ServerRenewalService $renewalService,
        private FreeServerDeploymentService $freeDeploymentService,
    ) {
        parent::__construct();
    }

    /**
     * Process and validate the creation of a server
     * based off of a free product in the billing portal.
     */
    public function process(ProcessFreeServerRequest $request): array
    {
        $user = $request->user();
        $is_new_order = !$request->filled('server_id');
        $node = Node::find($request->input('node_id'));
        $product = Product::findOrFail($request->input('product_id'));

        if ($is_new_order) {
            if (!$node) {
                throw new DisplayException('A valid node must be assigned for deployment.');
            }

            $egg_id = $this->resolveEggSelection($product, $request->input('egg_id'));

            $order = DB::transaction(function () use ($user, $product, $node) {
                User::whereKey($user->getKey())->lockForUpdate()->firstOrFail();

                $this->freeDeploymentService->validate($product, $user, $node, true);

                return $this->orderService->create(
                    null,
                    $user,
                    $product,
                    Order::STATUS_PENDING,
                    Order::TYPE_NEW,
                );
            });

            try {
                $server = $this->freeDeploymentService->handleFree(
                    $user,
                    $product,
                    $node,
                    $order,
                    $request->input('variables') ?? [],
                    $egg_id,
                );

                $order->assignServer($server);
            } catch (\Throwable $exception) {
                // Mark the order failed on any error: a pending "new" order counts as owning
                // the product and would otherwise block every retry.
                $order->setStatus(Order::STATUS_FAILED);

                BillingException::create([
                    'order_id' => $order->id,
                    'exception_type' => BillingException::TYPE_DEPLOYMENT,
                    'title' => 'Deployment of free server failed',
                    'description' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        } else {
            $this->freeDeploymentService->validate($product, $user, $node, false);

            // Run every lookup and check before creating the order, so a rejected
            // renewal never leaves a stray pending order behind.
            $server = $user->servers()
                ->where('id', $request->input('server_id'))
                ->firstOrFail();

            if ((int) $server->billing_product_id !== $product->id) {
                throw new DisplayException('A server can only be renewed using the product it is billed under.');
            }

            if ($server->renewal_date && $server->renewal_date->isAfter(now()->addDays(7))) {
                throw new DisplayException('You cannot renew a free server more than 7 days in advance.');
            }

            $order = $this->orderService->create(
                null,
                $user,
                $product,
                Order::STATUS_PENDING,
                Order::TYPE_RENEWAL,
            );
            $order->assignServer($server);

            $this->renewalService->handle($server);
        }

        $order->update(['status' => Order::STATUS_PROCESSED]);

        return $this->transform($server, ServerTransformer::class);
    }

    /**
     * Validate the egg a customer selected for a product whose category doesn't pin one.
     * Categories with a fixed egg ignore any submitted selection. Returns the egg id to
     * carry through deployment, or null when the category already has a fixed egg.
     */
    private function resolveEggSelection(Product $product, mixed $submittedEggId): ?int
    {
        if ($product->category->egg_id) {
            return null;
        }

        if (!$submittedEggId) {
            throw new DisplayException('An egg must be selected to deploy this product.');
        }

        $egg = Egg::findOrFail($submittedEggId);

        if ((int) $egg->nest_id !== (int) $product->category->nest_id) {
            throw new DisplayException('The selected egg does not belong to this product\'s nest.');
        }

        return $egg->id;
    }
}
