<?php

declare(strict_types=1);

namespace Vkrapotkin\Firebird\Query\Processors;

use Illuminate\Database\Query\Processors\Processor;

class FirebirdProcessor extends Processor
{
    /**
     * Process the results of a column listing query.
     */
    public function processColumnListing($results): array
    {
        return array_map(function ($result) {
            return ((object) $result)->column_name;
        }, $results);
    }
}
