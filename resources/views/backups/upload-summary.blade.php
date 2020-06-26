@extends('index')

@section('title', '- Upload Summary')

@section('body-class', 'upload-summary')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/backups">Upload Summary</a></li>
    @if(isset($date))
    <li class="active">{{ $date->format('M d, Y') }}</li>
    @endif
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          @include('_partials.menu.logs')
        </div>
      </div>
    </nav>

  @include('_partials.alerts')

  @if(is_null($date) || is_null($ds))
    no data
  @else

    @if($ds->sales!=$ds->chrg_total)
      @if($ds->sales!=($ds->chrg_total-$ds->chrg_othr))
      <div class="alert alert-important alert-warning"><b>Warning:</b> The backup file you uploaded is possibly not the end-of-day backup. Please reupload the actual EoD/EoM backup.<br>You can ignore this if you're not sending EoD/EoM backup like sending Payroll Backup.</div>
      @endif
    @endif

    <h4>Upload summary of POS backup GC{{ $date->format('mdy') }}.ZIP <em>({{ $date->format('D M d, Y') }})</em></h4>
    <p>&nbsp;</p>

    
    <div class="row">
      <!--
      <div class="col-md-3">
        Backup Date:
        <h3 class="text-primary">{{ $date->format('D M d, Y') }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3 col-xs-6">
        Gross Sales:
        <h3 class="text-muted">{{ number_format($ds->slsmtd_totgrs, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3 col-xs-6">
        Daily Sales:
        <h3 class="text-success">{{ number_format($ds->sales, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3 col-xs-6">
        Cost fo Goods:
        <h3 class="text-info">{{ number_format($ds->cos, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3 col-xs-6">
        Operational Expense:
        <h3 class="text-warning">{{ number_format($ds->getOpex(), 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <!--
      <div class="col-md-3">
        Total Purchased Cost:
        <h3 class="text-danger">{{ number_format($ds->purchcost, 2) }}</h3>
      </div><!-- end: .col-md-3-->
    </div><!-- end: .row-->
    <div class="row">
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item">Customers <span class="pull-right"><strong>{{ number_format($ds->custcount, 0) }}</strong></span></li>
          <li class="list-group-item">Trans Count <span class="pull-right"><strong>{{ number_format($ds->trans_cnt, 0) }}</strong></span></li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item"><span class="pull-right"><strong>{{ $ds->crew_din }}</strong></span> Dining Crew</li>
          <li class="list-group-item"><span class="pull-right"><strong>{{ $ds->crew_kit }}</strong></span> Kitchen Crew</li>
        </ul>
      </div>
       <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item"><span class="pull-right"><strong>{{ number_format($ds->mancost,2) }}</strong></span> Man Cost</li>
          <li class="list-group-item"><span class="pull-right"><strong>{{ $ds->get_mancostpct() }}%</strong></span> Man Cost %</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item">Tips <span class="pull-right"><strong>{{ number_format($ds->tips, 2) }}</strong></span></li>
          <li class="list-group-item">Tips %<span class="pull-right"><strong>{{ number_format($ds->get_tipspct(), 2) }}%</strong></span></li>
        </ul>
      </div>
      
    </div><!-- end: .row-->

    <div class="row">
      <div class="col-md-6" style="margin-top: 20px;">
        @if(!is_null($cash_audit))
        <div class="panel panel-primary">
          <div class="panel-heading">Actual Cashier Drawer Count</div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-condensed table-striped" style="margin-top: 0;">
                <thead>
                  <tr>
                    <th class="text-right">Denomination</th>
                    <th class="text-right">Pcs.</th>
                    <th class="text-right">Value</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td class="text-right">Fx Curr.</td><td class="text-right"></td><td class="text-right">{{ nf($cash_audit->forex, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">Cheques</td><td class="text-right">{{ $cash_audit->checks_pcs }}</td><td class="text-right">{{ nf($cash_audit->checks, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">P1000</td><td class="text-right">{{ $cash_audit->p1000_pcs }}</td><td class="text-right">{{ nf($cash_audit->p1000_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">500</td><td class="text-right">{{ $cash_audit->p500_pcs }}</td><td class="text-right">{{ nf($cash_audit->p500_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">200</td><td class="text-right">{{ $cash_audit->p200_pcs }}</td><td class="text-right">{{ nf($cash_audit->p200_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">100</td><td class="text-right">{{ $cash_audit->p100_pcs }}</td><td class="text-right">{{ nf($cash_audit->p100_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">50</td><td class="text-right">{{ $cash_audit->p50_pcs }}</td><td class="text-right">{{ nf($cash_audit->p50_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">20</td><td class="text-right">{{ $cash_audit->p20_pcs }}</td><td class="text-right">{{ nf($cash_audit->p20_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">10</td><td class="text-right">{{ $cash_audit->p10_pcs }}</td><td class="text-right">{{ nf($cash_audit->p10_amt, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right">Coins</td><td class="text-right"></td><td class="text-right">{{ nf($cash_audit->coins, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right" colspan="2">Total Cash</td><td class="text-right"><b>{{ nf($cash_audit->p1000_amt+$cash_audit->p500_amt+$cash_audit->p200_amt+$cash_audit->p100_amt+$cash_audit->p50_amt+$cash_audit->p20_amt+$cash_audit->p10_amt+$cash_audit->coins, 2, true) }}</b></td>
                  </tr>
                  <tr>
                    <td class="text-right" colspan="2">Total CSH/CHQ/FX</td><td class="text-right"><b title="{{$cash_audit->csh_cnt}} / {{$cash_audit->checks}} / {{$cash_audit->forex}}">{{ nf($cash_audit->csh_cnt+$cash_audit->checks+$cash_audit->forex, 2, true) }}</b></td>
                  </tr>
                  <tr>
                    <td class="text-right" colspan="2">Unliquidated C/A of {{ $date->format('m/d') }}</td><td class="text-right">{{ nf($cash_audit->ca, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td class="text-right" colspan="2">Cummulative (Short)/Over</td><td class="text-right">{{ nf($cash_audit->shrt_cumm, 2, true) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div><!-- end: .panel-body  -->
        </div><!--end: .panel.panel-default  -->
        @endif
       </div>

      <div class="col-md-6" style="margin-top: 20px;">
        @if(!is_null($cash_audit))
        <div class="panel panel-primary">
          <div class="panel-heading">&nbsp;</div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table table-condensed table-striped" style="margin-top: 0;">
                <thead>
                  <tr>
                    <th>Collection</th>
                    <th class="text-right">Cash</th>
                    <th class="text-right">Cheque</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Backard C.Cards</td>
                    <td class="text-right">{{ nf($cash_audit->col_card, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_cardk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>BDO C.Cards</td>
                    <td class="text-right">{{ nf($cash_audit->col_bdo, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_bdok, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>Other Cards</td>
                    <td class="text-right">{{ nf($cash_audit->col_din, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_dink, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="text-right">{{ nf($cash_audit->cas+$cash_audit->ca, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->cak, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="text-right">{{ nf($cash_audit->col_oths+$cash_audit->col_othr, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_othrk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td class="text-right">{{ nf($cash_audit->coloth2s+$cash_audit->col_oth2, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_oth2k, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>Total Collection</td>
                    <td class="text-right">{{ nf($cash_audit->tot_coll, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->tot_collk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td colspan="2" class="text-right">Grand Total :</td>
                    <td class="text-right">{{ nf($cash_audit->tot_coll+$cash_audit->tot_colk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td>Func/F.O. Coll.</td>
                    <td class="text-right">{{ nf($cash_audit->col_food, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->col_foodk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>Func/F.O. Card</td>
                    <td class="text-right">{{ nf($cash_audit->col_foodc, 2, true) }}</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td>S.Chit - Paid</td>
                    <td class="text-right">{{ nf($cash_audit->sig_salep, 2, true) }}</td>
                    <td></td>
                  </tr>
                  <tr>
                    <td>S.Chit - Unpaid</td>
                    <td class="text-right">{{ nf($cash_audit->sig_saleu, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->sig_salep+$cash_audit->sig_saleu, 2, true) }}</td>
                  </tr>
                  <tr>
                    <tr>
                    <td>&nbsp;</td>
                    <td></td>
                    <td></td>
                  </tr>
                  <tr>
                    <td style="color: #95A5A6; font-weight: bold;">Disbursements</td>
                    <td style="color: #95A5A6; font-weight: bold;" class="text-right">Cash</td>
                    <td style="color: #95A5A6; font-weight: bold;" class="text-right">Cheque</td>
                  </tr>
                  <tr>
                    <td>Deposits</td>
                    <td class="text-right">{{ nf($cash_audit->deposit, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->depositk, 2, true) }}</td>
                  </tr>
                  <tr>
                    <td>CashOut/Ref</td>
                    <td class="text-right">{{ nf($cash_audit->csh_out, 2, true) }}</td>
                    <td class="text-right">{{ nf($cash_audit->csh_outk, 2, true) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
        @endif
      </div> 
    </div>


    <div class="row">
      <div class="col-md-12">
        <p>&nbsp;</p>
        <div class="alert alert-important text-primary" style="margin: -25px 0 -5px 0;">
          <?php
            if(strpos(request()->url(), '.com'))
              $url = 'https://goo.gl/gO9X3G';
            else if(strpos(request()->url(), '.net'))
              $url = 'https://goo.gl/OcbBKs';
            else
              $url = '/backups/checklist';
          ?>
          <b><span class="glyphicon glyphicon-alert"></span> Reminders:</b> Always check if you have complete <b>POS Backup</b> by checking on <a href="{{ $url }}" class="btn btn-default"><span class="fa fa-calendar-check-o"></span> Checklist</a> or 
          <a href="/{{brcode()}}/uploader" class="btn btn-primary">
            <span class="glyphicon glyphicon-cloud-upload"></span>
            <span>Back to DropBox</span>
          </a>
        </div>

        
      </div>
    </div>


  @endif
   
  
    
      
  
  </div>
</div><!-- end container-fluid -->
 @if(app()->environment()==='production')
 <div class="row" style="margin-top: 10px;">
  <div class="col-sm-6">
    <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9897737241100378" data-ad-slot="4574225996" data-ad-format="auto"></ins>
  </div>
 </div>
@endif
@endsection


@section('js-external')
  @parent
  <script type="text/javascript">
  $(document).ready(function(){

   
  });
  </script>
 @if(app()->environment()==='production')
<!-- gi- -->
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
@endif
@endsection


