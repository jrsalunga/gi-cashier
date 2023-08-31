<?php namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\KitlogRepository as Kitlogs;
use App\Services\KitlogImporter as Kitlog;
use App\Services\CashAuditImporter as CashAudit;
use App\Services\CvhdrImporter as Cvhdr;
use App\Services\CvinvdtlImporter as Cvinvdtl;
use App\Services\BegBalImporter as BegBal;
use App\Services\ComponentImporter as Component;
use App\Services\ChangeItemImporter as ChangeItem;

class DbfImporter extends Controller {

  protected $kitlog;
  protected $cash_audit;
  protected $cvhdr;
  protected $cvinvdtl;
  protected $begbal;
  protected $component;
  protected $change_item;

  public function __construct(Kitlog $kitlog, CashAudit $cash_audit, Cvhdr $cvhdr, Cvinvdtl $cvinvdtl, BegBal $begbal, Component $component, ChangeItem $change_item) {
    $this->kitlog = $kitlog;
    $this->cash_audit = $cash_audit;
    $this->cvhdr = $cvhdr;
    $this->cvinvdtl = $cvinvdtl;
    $this->begbal = $begbal;
    $this->component = $component;
    $this->change_item = $change_item;
  }

  public function invoke($table) {
    return $this->{$table};
  }
}