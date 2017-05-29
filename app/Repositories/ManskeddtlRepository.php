<?php namespace App\Repositories;

use App\User;
use App\Models\Manskeddtl;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;

class ManskeddtlRepository extends BaseRepository implements CacheableInterface
{

  use CacheableRepository;



  function model() {
    return "App\\Models\\Manskeddtl";
  }

    /**
     * Get all of the tasks for a given user.
     *
     * @param  User  $user
     * @return Collection
     */
    public function branchByDate(User $user, $date)
    {
        return Manskeddtl::with(['employee'=>function($query){
						        	$query->select('lastname', 'firstname', 'id');
						        }])
        						->select('manskeddtl.*')
      							->leftJoin('hr.employee', function($join){
                                  	$join->on('manskeddtl.employeeid', '=', 'employee.id');
                                })
                                ->leftJoin('manskedday', function($join){
                                    $join->on('manskeddtl.mandayid', '=', 'manskedday.id');
                                })
                                ->where('employee.branchid', '=', $user->branchid)
                                ->where('manskedday.date', '=', $date)
                                ->orderBy('employee.lastname', 'ASC')
                                ->orderBy('employee.firstname', 'ASC')->get();
      
    }
}