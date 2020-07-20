<?php namespace App\Repositories;

use Exception;
use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Traits\CacheableRepository;
use Prettus\Repository\Contracts\CacheableInterface;
use App\Traits\Repository as RepoTrait;
use App\Repositories\BankRepository as Bank;
use App\Repositories\PsupplierRepository as Psupplier;
use App\Repositories\CheckRepository as Check;

class CvhdrRepository extends BaseRepository implements CacheableInterface
{
  use CacheableRepository, RepoTrait;

  protected $order = ['refno'];
  protected $bank;
  protected $psupplier;
  protected $check;
  
  public function model() {
    return 'App\\Models\\Cvhdr';
  }

  public function __construct(Bank $bank, Psupplier $psupplier, Check $check) {
    parent::__construct(app());
    $this->bank = $bank;
    $this->psupplier = $psupplier;
    $this->check = $check;
  }

  public function verifyAndCreate(array $attributes, $create = true) {

    if ($create)
      $bank = $this->bank->verifyAndCreate(array_only($attributes, ['bank', 'bankcode']));
    else
      $bank = $this->bank->findWhere(['descriptor'=>$attributes['bank'], 'code'=>$attributes['bankcode']], ['code', 'descriptor', 'id'])->first();


    if ($create)
      $psupplier = $this->psupplier->verifyAndCreate(array_only($attributes, ['psuppliercode', 'psupplier', 'payee', 'tin', 'address', 'city']));
    else
      $psupplier = $this->psupplier->findWhere(['descriptor'=>$attributes['psupplier'], 'code'=>$attributes['psuppliercode']], ['code', 'descriptor', 'id'])->first();
     
    // 'code'=>'XUN', 'descriptor'=>'UNKNOWN - NOT ON DATABASE', 'id'=>A197E8FFBC7F11E6856EC3CDBB4216A7';

    $bank_id = is_null($bank) ? 'A197E8FFBC7F11E6856EC3CDBB4216A7' : $bank->id;
    $psupplier_id = is_null($psupplier) ? 'A197E8FFBC7F11E6856EC3CDBB4216A7' : $psupplier->id;

    $attr = [
      'refno'       => $attributes['refno'],
      'branch_id'   => $attributes['branch_id'],
      'cvno'        => $attributes['cvno'],
      'cvdate'      => $attributes['cvdate'],
      'psupplier_id'=> $psupplier_id,
      'payee'       => $attributes['payee'],
      'checkno'     => $attributes['checkno'],
      'checkdate'   => $attributes['checkdate'],
      'checkamt'    => $attributes['checkamt'],
      'bank_id'     => $bank_id,
      // 'branch_id'   => $attributes['branch_id'],
      'status'      => 1,
      'totcvdtlline'=> $attributes['totcvdtlline'],
      'vat'         => $attributes['vat'],
      'inv_fr'      => $attributes['inv_fr'],
      'inv_to'      => $attributes['inv_to'],
    ];

    try {
      if ($create)
        $r = $this->findWhere(['checkno'=>$attr['checkno']]);
        if (count($r)>0)
          $attr['status'] = (10 + count($r));
        $cvhdr = $this->create($attr);
      else {
        $cvhdr = $this->findOrNew($attr, ['code', 'descriptor']);
      }
    } catch(Exception $e) {
      throw new Exception($e->getMessage());    
    }

    $this->check->create([
      'no'        => $attributes['checkno'],
      'date'      => $attributes['checkdate'],
      'payee'     => strpos(strtolower($attributes['payee']), 'cancel') == false ? $attributes['payee']:'',
      'amount'    => $attributes['checkamt'],
      'status'    => strpos(strtolower($attributes['payee']), 'cancel') == false ? 1:2,
      'cvhdr_id'  => $cvhdr->id,
      'bank_id'   => $bank_id
    ]);

    return $cvhdr;
  }

  public function associateAttributes($r) {
    $row = [];

    $cvdate = '';
    if (!empty(trim($r['CV_DATE'])))
      $cvdate = c(trim($r['CV_DATE']).' 00:00:00')->format('Y-m-d');
    
    $chkdate = '';
    if (!empty(trim($r['CHK_DATE'])))
      $chkdate = c(trim($r['CHK_DATE']).' 00:00:00')->format('Y-m-d');
    
    $fr = '';
    if (!empty(trim($r['INV_LO'])))
      $fr = c(trim($r['INV_LO']).' 00:00:00')->format('Y-m-d');
    
    $to = '';
    if (!empty(trim($r['INV_HI'])))
      $to = c(trim($r['INV_HI']).' 00:00:00')->format('Y-m-d');

    $row = [
      'cvno'        => trim($r['CV_NO']),
      'cvdate'      => $cvdate,
      'psuppliercode'=>substr(trim($r['SUPNO']), 2),
      'psupplier'   => enye($r['SUPNAME']),
      'payee'       => enye($r['SUPNAME']),
      'checkno'     => trim($r['CHK_NO']),
      'checkdate'   => $chkdate,
      'checkamt'    => trim($r['TCOST']),
      'bankcode'    => trim($r['CHK_BANK']),
      'bank'        => enye($r['CHK_BANK']),
      'totcvdtlline'=> trim($r['INV_CNT']),
      'vat'         => trim($r['VAT']),
      'inv_fr'      => $fr,
      'inv_to'      => $to,
      'tin'         => trim($r['SUPTIN']),
      'address'     => enye($r['SUPADDR1']),
      'city'        => enye($r['SUPADDR2']),
    ];

    return $row;
  }
}