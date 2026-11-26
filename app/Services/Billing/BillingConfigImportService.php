<?php

namespace Everest\Services\Billing;

use Everest\Models\Egg;
use Illuminate\Support\Str;
use Everest\Models\Billing\Product;
use Everest\Models\Billing\Category;

class BillingConfigImportService
{
    /**
     * Process a formatted JSON configuration file. The data is expected to have been
     * validated by ImportBillingConfigRequest before being passed in here.
     */
    public function handle(array $import_data, bool $ignore_duplicates): void
    {
        $old_data = [];

        // Create new categories and map old category UUIDs to new ones
        foreach ($import_data['categories'] as $category) {
            // When ignoring duplicates, reuse an existing category with the same name instead of
            // creating a new one, so that the products from the file are still linked to it.
            if ($ignore_duplicates && ($existing = Category::where('name', $category['name'])->first())) {
                $old_data[$category['uuid']] = $existing->uuid;

                continue;
            }

            $new_uuid = Str::uuid()->toString();

            // An imported egg_id is only trustworthy once we've confirmed it belongs to the
            // imported nest_id; derive nest_id from the egg itself rather than trusting the
            // file's pairing, mirroring how CategoryController resolves this for admin input.
            $egg_id = isset($category['egg_id']) ? (int) $category['egg_id'] : null;
            $nest_id = $egg_id ? Egg::findOrFail($egg_id)->nest_id : (int) $category['nest_id'];

            // Create the new category
            $new_category = Category::create([
                'uuid' => $new_uuid, // don't overlap UUIDs
                'name' => $category['name'],
                'icon' => $category['icon'] ?? null,
                'description' => $category['description'] ?? null,
                'visible' => (bool) ($category['visible'] ?? false),
                'egg_id' => $egg_id,
                'nest_id' => $nest_id,
            ]);

            // Map the old category UUID to the new category UUID
            $old_data[$category['uuid']] = $new_category->uuid;
        }

        // Create products and assign them to the correct new category
        foreach ($import_data['products'] as $product) {
            // Products whose category is not part of the import cannot be linked to anything.
            $category_uuid = $old_data[$product['category_uuid']] ?? null;
            if (!$category_uuid) {
                continue;
            }

            // Skip existing products if ignore_duplicates is true and a product with the same name exists in the same category
            if ($ignore_duplicates && Product::where('name', $product['name'])
                                            ->where('category_uuid', $category_uuid)
                                            ->exists()) {
                continue;
            }

            Product::create([
                'uuid' => Str::uuid()->toString(), // don't overlap UUIDs
                'name' => $product['name'],
                'icon' => $product['icon'] ?? null,
                'price' => (float) $product['price'],
                'description' => $product['description'] ?? null,
                'cpu_limit' => (int) $product['cpu_limit'],
                'memory_limit' => (int) $product['memory_limit'],
                'disk_limit' => (int) $product['disk_limit'],
                'backup_limit' => (int) $product['backup_limit'],
                'database_limit' => (int) $product['database_limit'],
                'allocation_limit' => (int) $product['allocation_limit'],
                'category_uuid' => $category_uuid,
            ]);
        }
    }
}
