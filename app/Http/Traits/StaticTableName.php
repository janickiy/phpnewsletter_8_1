<?php

namespace App\Http\Traits;

trait StaticTableName
{
    /**
     * Return the database table name configured for the model using this trait.
     */
    public static function getTableName()
    {
        return with(new static)->getTable();
    }
}
