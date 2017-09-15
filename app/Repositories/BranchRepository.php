<?php namespace App\Repositories;

use Carbon\Carbon;
use App\Repositories\Repository;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;


class BranchRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

  protected $order = ['code'];

	public function __construct() {
    parent::__construct(app());
  }


	public function model() {
    return 'App\Models\Branch';
  }

  

}