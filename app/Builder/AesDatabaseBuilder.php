<?php

namespace App\Builder;

use Closure;
use Illuminate\Database\Eloquent\Builder as BaseBuilder;

class AesDatabaseBuilder extends BaseBuilder
{
    protected $aesKeys = [];

    public function __construct($query)
    {
        parent::__construct($query);
        $this->aesKeys = [
            'id_user' => env('MYSQL_AES_KEY_IDUSER'),
            'password' => env('MYSQL_AES_KEY_PASSWORD'),
        ];
    }

    /**
     * Tambahkan where clause dengan AES_DECRYPT otomatis untuk kolom terenkripsi.
     *
     * @param  (\Closure(static): mixed)|string|array|\Illuminate\Contracts\Database\Query\Expression  $column
     * @param  mixed  $operator
     * @param  mixed  $value
     * @param  string  $boolean
     * @return $this
     */
    public function where($column, $operator = null, $value = null, $boolean = 'and')
    {
        // Handle closure untuk nested
        if ($column instanceof Closure && is_null($operator)) {
            $column($query = $this->model->newQueryWithoutRelationships());
            $this->eagerLoad = array_merge($this->eagerLoad, $query->getEagerLoads());
            $this->query->addNestedWhereQuery($query->getQuery(), $boolean);
            return $this;
        }

        // Tangani jika array input
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                $this->where($key, '=', $val, $boolean);
            }
            return $this;
        }

        // Ambil nama kolom tanpa prefix tabel
        $columnParts = explode('.', $column);
        $pureColumn = end($columnParts);

        if (isset($this->aesKeys[$pureColumn])) {
            $aesKey = $this->aesKeys[$pureColumn];

            [$value, $operator] = $this->query->prepareValueAndOperator($value, $operator, func_num_args() === 2);
            $sql = "$pureColumn $operator AES_ENCRYPT(?, ?)";

            return $this->whereRaw($sql, [$value, $aesKey], $boolean);
        }

        return parent::where($column, $operator, $value, $boolean);
    }

    /**
     * Helper untuk parsing argumen where.
     */
    protected function parseWhereArgs($column, $operator = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return [$column, $operator, $value];
    }
}
