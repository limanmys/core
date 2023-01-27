<?php

namespace App\Support\Database;

use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Cache;

/**
 * Database Builder
 * This class stands for caching duplicate queries.
 *
 * @extends QueryBuilder
 */
class Builder extends QueryBuilder
{
    /**
     * Run the query as a "select" statement against the connection.
     *
     * @return array
     */
    protected function runSelect()
    {
        try {
            return Cache::store('array')->remember($this->getCacheKey(), 1, function () {
                return parent::runSelect();
            });
        } catch (\Throwable) {
            return parent::runSelect();
        }
    }

    /**
     * Returns a Unique String that can identify this Query.
     *
     * @return string|bool
     */
    protected function getCacheKey(): string|bool
    {
        return json_encode([
            $this->toSql() => $this->getBindings(),
        ]);
    }
}
