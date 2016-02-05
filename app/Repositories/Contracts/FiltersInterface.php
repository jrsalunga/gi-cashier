<?php namespace App\Repositories\Contracts;

use App\Repositories\Filters\Filters;
use Illuminate\Http\Request;

/**
 * Interface FilterInterface
 * @package App\Repositories\Contracts
 */
interface FiltersInterface {

    /**
     * @param bool $status
     * @return $this
     */
    public function skipFilters($status = true);

    /**
     * @return mixed
     */
    public function getFilters();

    /**
     * @param Filter $Filter
     * @return $this
     */
    public function getByFilters(Filters $filter);

    /**
     * @param Filter $Filter
     * @return $this
     */
    public function pushFilters(Filters $filter);

    /**
     * @return $this
     */
    public function  applyFilters();
}