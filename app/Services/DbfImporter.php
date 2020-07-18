<?php namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\KitlogRepository as Kitlogs;
use App\Services\KitlogImporter as Kitlog;
use App\Services\CashAuditImporter as CashAudit;
use App\Services\CvhdrImporter as Cvhdr;
use App\Services\CvinvdtlImporter as Cvinvdtl;

class DbfImporter extends Controller {

  protected $kitlog;
  protected $cash_audit;
  protected $cvhdr;
  protected $cvinvdtl;

  public function __construct(Kitlog $kitlog, CashAudit $cash_audit, Cvhdr $cvhdr, Cvinvdtl $cvinvdtl) {
    $this->kitlog = $kitlog;
    $this->cash_audit = $cash_audit;
    $this->cvhdr = $cvhdr;
    $this->cvinvdtl = $cvinvdtl;
  }

  public function invoke($table) {
    return $this->{$table};
  }
}