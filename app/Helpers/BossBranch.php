<?php namespace App\Helpers;

use DB;
use Carbon\Carbon;

class BossBranch {

  
  public function getFirstUser(array $admin = ['3'], array $ordinal = ['12', '16']) {

    //$email_add = !is_null(request()->user->email) ? request()->user->email : 'jefferson.salunga@yahoo.com';

    $bb = \App\Models\BossBranch::where('branchid', request()->user()->branchid)->first();
    $am_email = $am = NULL;
    
    if (!is_null($bb)) {
    
      $am = DB::table('user')
                ->join('bossbranch', 'user.id', '=', 'bossbranch.bossid')
                ->where('bossbranch.branchid', request()->user()->branchid)
                ->whereIn('user.admin', $admin)
                ->whereIn('user.ordinal', $ordinal)
                ->orderBy('user.admin')
                ->orderBy('user.ordinal')
                ->first();
    }

    if (!is_null($am))
      if (!empty($am->email))
        $am_email = $am->email;

    return $am_email;
  }


  public function getUsers(array $ordinal = ['12', '20'], array $admin = ['3']) {

    $bb = \App\Models\BossBranch::where('branchid', request()->user()->branchid)->get();
    
    if (is_null($bb))
      return NULL;
    
      return  DB::table('user')
                ->join('bossbranch', 'user.id', '=', 'bossbranch.bossid')
                ->select(['user.name', 'user.email', 'user.id'])
                ->where('bossbranch.branchid', request()->user()->branchid)
                ->whereIn('user.admin', $admin)
                ->whereIn('user.ordinal', $ordinal)
                ->orderBy('user.admin')
                ->orderBy('user.ordinal')
                ->get();
  }


  public function getFirstUserByBranchid($branchid=NULL, array $admin = ['3'], array $ordinal = ['12', '16']) {

    if (is_null($branchid) ||  !is_uuid($branchid))
      return NULL;

    $bb = \App\Models\BossBranch::where('branchid', $branchid)->first();
    $am_email = $am = NULL;
    
    if (!is_null($bb)) {
    
      $am = DB::table('user')
                ->join('bossbranch', 'user.id', '=', 'bossbranch.bossid')
                ->where('bossbranch.branchid', $branchid)
                ->whereIn('user.admin', $admin)
                ->whereIn('user.ordinal', $ordinal)
                ->orderBy('user.admin')
                ->orderBy('user.ordinal')
                ->first();
    }

    if (!is_null($am))
      if (!empty($am->email))
        $am_email = $am->email;

    return $am_email;
  }


  public function getUsersByBranchid($branchid=NULL, array $ordinal = ['12', '20'], array $admin = ['3']) {

    if (is_null($branchid) ||  !is_uuid($branchid))
      return NULL;

    $bb = \App\Models\BossBranch::where('branchid', $branchid)->get();
    
    if (is_null($bb))
      return NULL;
    
      return  DB::table('user')
                ->join('bossbranch', 'user.id', '=', 'bossbranch.bossid')
                ->select(['user.name', 'user.email', 'user.id'])
                ->where('bossbranch.branchid', $branchid)
                ->whereIn('user.admin', $admin)
                ->whereIn('user.ordinal', $ordinal)
                ->orderBy('user.admin')
                ->orderBy('user.ordinal')
                ->get();
  }
}