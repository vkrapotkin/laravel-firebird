<?php

declare(strict_types=1);

namespace Danidoble\Firebird\Query\Grammars;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Support\Str;

class FirebirdGrammar extends Grammar
{
    /**
     * The components that make up a select clause.
     *
     * @var string[]
     */
    protected $selectComponents = [
        'aggregate',
        'columns',
        'from',
        'joins',
        'wheres',
        'groups',
        'havings',
        'orders',
        // 'limit', - Handled in the compileColumns() method.
        // 'offset', - Handled in the compileColumns() method.
        'lock',
    ];

    /**
     * All the available clause operators.
     *
     * @var array
     *
     * @link https://ib-aid.com/download/docs/firebird-language-reference-2.5/fblangref25-commons-predicates.html
     */
    protected $operators = [
        '=', '<', '>', '<=', '>=', '<>', '!=',
        'like', 'not like', 'between', 'not between',
        'containing', 'not containing', 'starting with', 'not starting with',
        'similar to', 'not similar to', 'is distinct from', 'is not distinct from',
    ];

    /**
     * @param  array  $columns
     * @return string|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        // See superclass.
        if (! is_null($query->aggregate)) {
            return null;
        }

        // In Firebird, the correct syntax for limiting and offsetting rows is
        // "select first [num_rows] skip [start_row] * from table". Laravel does
        // not support adding components between the "select" keyword and the
        // column names, so compile the limit and offset components here. Note
        // that they are commented out in the $selectComponents class variable.
        // Reference: http://mc-computing.com/Databases/Firebird/SQL.html

        $select = 'select ';

        if ($query->limit) {
            $select .= $this->compileLimit($query, $query->limit).' ';
        }

        if ($query->offset) {
            $select .= $this->compileOffset($query, $query->offset).' ';
        }

        if ($query->distinct) {
            $select .= 'distinct ';
        }

        return $select.$this->columnize($columns);
    }

    /**
     * Compile the "limit" portions of the query.
     *
     * @param  int  $limit
     */
    protected function compileLimit(Builder $query, $limit): string
    {
        return 'first '.(int) $limit;
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param  int  $offset
     */
    protected function compileOffset(Builder $query, $offset): string
    {
        return 'skip '.(int) $offset;
    }

    /**
     * Compile the random statement into SQL.
     *
     * @param  string  $seed
     */
    public function compileRandom($seed): string
    {
        return 'RAND()';
    }

    /**
     * Wrap a union sub query in parentheses.
     *
     * @param  string  $sql
     */
    protected function wrapUnion($sql): string
    {
        return $sql;
    }

    /**
     * Compile a date based where clause.
     *
     * @param  string  $type
     * @param  array  $where
     */
    protected function dateBasedWhere($type, Builder $query, $where): string
    {
        $value = $this->parameter($where['value']);

        return 'EXTRACT('.$type.' FROM '.$this->wrap($where['column']).') '.$where['operator'].' '.$value;
    }

    /**
     * Compile SQL statement for a stored procedure.
     */
    public function compileProcedure(Builder $query, string $procedure, ?array $values = null): string
    {
        $procedure = $this->wrap($procedure);

        return $procedure.' ('.$this->parameterize($values).')';
    }

    /**
     * Compile an aggregated select clause.
     *
     * @param  array  $aggregate
     */
    protected function compileAggregate(Builder $query, $aggregate): string
    {
        // Wrap `aggregate` in double quotes to ensure the result set returns the
        // column name as a lowercase string. This resolves compatibility with
        // the framework's paginator.
        return Str::replaceLast(
            'as aggregate', 'as "aggregate"', parent::compileAggregate($query, $aggregate)
        );
    }

    public function whereDate(Builder $query, $where)
    {
        return $this->dateBasedWhere('YEAR', $query, $where);
    }

    public function whereTime(Builder $query, $where)
    {
        return $this->dateBasedWhere('HOUR', $query, $where);
    }
}
