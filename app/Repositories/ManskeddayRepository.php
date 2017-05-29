<?php namespace App\Repositories;

use App\User;
use App\Models\Manskedday;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;

class ManskeddayRepository extends BaseRepository implements CacheableInterface
{

  use CacheableRepository;


  function model() {
    return "App\\Models\\Manskedday";
  }

    /**
     * Get all of the tasks for a given user.
     *
     * @param  User  $user
     * @return Collection
     */
    public function countBranchMandtlByDate(User $user, $date)
    {
        return Manskedday::with('countMandtls')
	                    ->select('manskedday.*')
                        ->leftJoin('manskedhdr', function($join){
                            $join->on('manskedday.manskedid', '=', 'manskedhdr.id');
                        })
                        ->where('manskedhdr.branchid', '=', $user->branchid)
                        ->where('manskedday.date', '=', $date);
    }
}