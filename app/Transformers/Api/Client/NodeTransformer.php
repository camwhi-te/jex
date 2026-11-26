<?php

namespace Everest\Transformers\Api\Client;

use Everest\Models\Node;
use Everest\Transformers\Api\Transformer;

class NodeTransformer extends Transformer
{
    /**
     * Return the resource name for the JSONAPI output.
     */
    public function getResourceName(): string
    {
        return Node::RESOURCE_NAME;
    }

    /**
     * Return a node transformed into a format that can be consumed by the
     * external client API. Only the fields the storefront needs are exposed;
     * the rest of the node record (ports, limits, paths) is not public.
     */
    public function transform(Node $model): array
    {
        return [
            'id' => $model->id,
            'name' => $model->name,
            'fqdn' => $model->fqdn,
            'deployable' => (bool) $model->deployable,
            'deployable_free' => (bool) $model->deployable_free,
            'deployment_fee' => $model->deployment_fee,
        ];
    }
}
