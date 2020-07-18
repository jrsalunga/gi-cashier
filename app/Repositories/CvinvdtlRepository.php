<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\ExpenseRepository as Expense;
use App\Repositories\PsupplierRepository as Psupplier;
use App\Repositories\CvhdrRepository as Cvhdr;


class CvinvdtlRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

  protected $order = ['id'];
  protected $expense;
  protected $psupplier;
  protected $cvhdr;
  
  public function model() {
    return 'App\\Models\\Cvinvdtl';
  }

  public function __construct(Expense $expense, Psupplier $psupplier, Cvhdr $cvhdr) {
    parent::__construct(app());
    $this->expense = $expense;
    $this->psupplier = $psupplier;
    $this->cvhdr = $cvhdr;
  }

  public function verifyAndCreate(array $attributes, $create = true) {

    $expense = $this->expense->findWhere(['code'=>$attributes['expensecode']], ['code', 'descriptor', 'id'])->first();


    if ($create)
      $psupplier = $this->psupplier->verifyAndCreate(array_only($attributes, ['psuppliercode', 'psupplier', 'payee', 'tin', 'address', 'city']));
    else
      $psupplier = $this->psupplier->findWhere(['descriptor'=>$attributes['psupplier'], 'code'=>$attributes['psuppliercode']], ['code', 'descriptor', 'id'])->first();
     
    // 'code'=>'XUN', 'descriptor'=>'UNKNOWN - NOT ON DATABASE', 'id'=>A197E8FFBC7F11E6856EC3CDBB4216A7';

    $expense_id = is_null($expense) ? 'A197E8FFBC7F11E6856EC3CDBB4216A7' : $expense->id;
    $psupplier_id = is_null($psupplier) ? 'A197E8FFBC7F11E6856EC3CDBB4216A7' : $psupplier->id;
    
    $cvhdr = NULL;
    if(!is_null($psupplier)) {
      $cvhdr = $this->cvhdr->findWhere([
          'psupplier_id'=>$psupplier_id,
          'branch_id'=>$attributes['branch_id'],
          ['inv_fr','<=',$attributes['invdate']],
          ['inv_to','>=',$attributes['invdate']]
      ], ['id'])->first();
    }
    $cvhdr_id = is_null($cvhdr) ? 'A197E8FFBC7F11E6856EC3CDBB4216A7' : $cvhdr->id;

    $attr = [
      'invdate'     => $attributes['invdate'],
      'invno'       => $attributes['invno'],
      'invamt'      => $attributes['invamt'],
      'vat'         => $attributes['vat'],
      'cvhdr_id'    => $cvhdr_id,
      'branch_id'   => $attributes['branch_id'],
      'psupplier_id'=> $psupplier_id,
      'expense_id'  => $expense_id,
    ];

    try {
      if ($create)
        return $this->create($attr);
      else {
        return $this->findOrNew($attr, ['code', 'descriptor']);
      }
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }
  }

  public function associateAttributes($r) {
    $row = [];

    $invdate = '';
    if (!empty(trim($r['PODATE'])))
      $invdate = c(trim($r['PODATE']).' 00:00:00')->format('Y-m-d');

    $row = [
      'invdate'      => $invdate,
      'invno'        => trim($r['FILLER1']),
      'invamt'       => trim($r['TCOST']),
      'vat'          => trim($r['VAT']),
      'expensecode'  => substr(trim($r['SUPNO']),0,2),
      'psuppliercode'=>substr(trim($r['SUPNO']),2),
      'psupplier'    => enye($r['SUPNAME']),
      'payee'        => enye($r['SUPNAME']),
      'address'      => enye($r['SUPADDR1']),
      'city'         => enye($r['SUPADDR2']),
      'tin'          => trim($r['SUPTIN']),
    ];

    return $row;
  }
}