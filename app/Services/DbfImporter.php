<?php namespace App\Services;

use App\Http\Controllers\Controller;
use App\Repositories\KitlogRepository as Kitlogs;
use App\Services\KitlogImporter as Kitlog;
use App\Services\CashAuditImporter as CashAudit;

class DbfImporter extends Controller {

  protected $kitlog;
  protected $cash_audit;

  public function __construct(Kitlog $kitlog, CashAudit $cash_audit) {
    $this->kitlog = $kitlog;
    $this->cash_audit = $cash_audit;
  }

  public function new($table) {
    return $this->{$table};
  } 

}