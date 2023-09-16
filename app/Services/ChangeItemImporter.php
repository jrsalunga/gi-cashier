<?php namespace App\Services;

use Carbon\Carbon;
use App\Repositories\ChangeItemRepository as cItem;
use App\Repositories\SalesmtdRepository as Salesmtd;

class ChangeItemImporter {

  protected $cItem;
  protected $salesmtd;

  public function __construct(cItem $cItem, Salesmtd $salesmtd) {
    $this->cItem = $cItem;
    $this->salesmtd = $salesmtd;
  }

  public function import($branchid, Carbon $date, $cmd=NULL) {


    if (!is_null($cmd)) {
      $cmd->line('ChangeItemImporter');
      $cmd->line('import '.$branchid.' '.$date->format('Y-m-d'));
    }

    $this->cItem->deleteWhere(['branch_id'=>$branchid, 'date'=>$date->format('Y-m-d')]);
    $items = $this->salesmtd
                  ->where('orddate', $date->format('Y-m-d'))
                  ->where('branch_id', $branchid)
                  ->select(['comp4', 'orddate', 'cslipno', 'group', 'branch_id'])
                  ->get();


    if (count($items)>0) {

      foreach($items as $k => $item) {

        /* change item stuff */
        if (!is_null($item->comp4)) {
          $len = explode(' ', $item->comp4);

          if (count($len)>1) {
            $k = 0;
            $ci = [];
            $ci['date']       = $item->orddate;
            $ci['cslipno']    = $item->cslipno;
            $ci['branch_id']  = $item->branch_id;
            $ci['group']      = $item->group;

            // update this if there is prince increase
            $last_price_update = '2023-01-01';
            if (Carbon::now()->gte(Carbon::parse($last_price_update))) {
              foreach ($len as $key => $prod) {
                if (!empty($prod)) {
                  
                  preg_match_all('/(\d+(?:\.\d+)?)([A-Z0-9]+)/m', $item->comp4, $matches, PREG_SET_ORDER, 0);
                  
                  if ($k==0) {
                    $ci['fr_qty']  = $matches[$k][1];
                    $ci['fr_code'] = $matches[$k][2];
                  }

                  if ($k==1) {
                    $ci['to_qty']  = $matches[$k][1];
                    $ci['to_code'] = $matches[$k][2];
                  } 

                  $k++;
                }
              } // end: foreach
            } // end: gte

            try {
              $this->cItem->verifyAndCreate($ci);
            } catch(Exception $e) {
              throw new Exception('ChangeItemImporter::changeItem '.$e->getMessage());
            }
          } // end: if count($len)
        }
      }

    
      return count($items);
    } 
    return 0;
  }
}



