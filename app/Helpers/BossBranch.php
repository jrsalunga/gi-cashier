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
}