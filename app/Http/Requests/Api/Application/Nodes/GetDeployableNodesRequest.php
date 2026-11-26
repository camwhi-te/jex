<?php

namespace Everest\Http\Requests\Api\Application\Nodes;

use Everest\Models\AdminRole;

class GetDeployableNodesRequest extends GetNodesRequest
{
    public function rules(): array
    {
        return [
            'page' => 'integer|min:1',
            'per_page' => 'integer|between:1,100',
            'memory' => 'required|integer|min:0',
            'disk' => 'required|integer|min:0',
        ];
    }

    public function permission(): string
    {
        return AdminRole::NODES_READ;
    }
}
