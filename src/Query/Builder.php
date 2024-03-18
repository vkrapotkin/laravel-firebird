<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Query;

use Illuminate\Database\Query\Builder as QueryBuilder;

class Builder extends QueryBuilder
{
    /**
     * Determine if any rows exist for the current query.
     */
    public function exists(): bool
    {
        return parent::count() > 0;
    }

    /**
     * Add a from stored procedure clause to the query builder.
     */
    public function fromProcedure(string $procedure, array $values = []): QueryBuilder|static
    {
        $compiledProcedure = $this->grammar->compileProcedure($this, $procedure, $values);

        // Remove any expressions from the values array, as they will have
        // already been evaluated by the grammar's parameterize() function.
        $values = array_filter($values, function ($value) {
            return ! $this->grammar->isExpression($value);
        });

        $this->fromRaw($compiledProcedure, array_values($values));

        return $this;
    }
}
