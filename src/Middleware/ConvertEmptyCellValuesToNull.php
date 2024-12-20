<?php

namespace VioletWaves\Excel\Middleware;

class ConvertEmptyCellValuesToNull extends CellMiddleware
{
    /**
     * @param  mixed  $value
     * @return mixed
     */
    public function __invoke($value, callable $next)
    {
        return $next(
            $value === '' ? null : $value
        );
    }
}
