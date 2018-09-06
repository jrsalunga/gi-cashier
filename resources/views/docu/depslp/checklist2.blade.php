@extends('index')

@section('title', '- Deposit Slip Checklist')

@section('body-class', 'depslp-checklist')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/depslp/log">Deposit Slip</a></li>
    <li><a href="/{{brcode()}}/depslp/checklist">Checklist</a></li>
    <li class="active">{{ $date->format('M Y') }}</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          @include('_partials.menu.logs')

          <div class="btn-group pull-right clearfix" role="group">
          <a href="/{{brcode()}}/depslp/checklist?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->subMonth()->format('Y-m-d') }}">
            <span class="glyphicon glyphicon-chevron-left"></span>
          </a>
          <input type="text" class="btn btn-default" id="dp-date" value="{{ $date->format('m/Y') }}" style="max-width: 110px;" readonly>
          <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
          <a href="/{{brcode()}}/depslp/checklist?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->addMonth()->format('Y-m-d') }}">
            <span class="glyphicon glyphicon-chevron-right"></span>
          </a>
        </div>


        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    @if(is_null($datas))

    @else
    <div class="table-responsive">
    <table class="table table-hover table-striped">
      <thead>
        <tr>
          <th>Deposit Date</th>
          <th class="text-center">
            <span style="cursor: help;" title="Has declared amount of cash deposit on POS" data-toogle="tooltip">
              Cash Deposited
            </span>
          </th>
          <th class="text-center">
            <span style="cursor: help;" title="Has declared amount of cheque deposit on POS" data-toogle="tooltip">
              Cheque Deposited
            </span>
          </th>
          <!--
          <th class="text-right">
            <span style="cursor: help;" title="Total amount of declared deposit on POS" data-toogle="tooltip">
              Total POS
            </span>
          </th>
        -->
          @if(c('2017-09-01')->gt($date))
          <th class="text-right">
            <span style="cursor: help;" title="Combined amount of cash &amp; cheque deposit uploaded the old way" data-toogle="tooltip">
              Cash&amp;Cheque
            </span>
          </th>
          @endif
          <th class="text-right">
            <span style="cursor: help;" title="Combined amount of cash deposit uploaded the new way" data-toogle="tooltip">
              DEPSLP Cash
            </span>
          </th>
          <th class="text-right">
            <span style="cursor: help;" title="Combined amount of cheque deposit uploaded the new way" data-toogle="tooltip">
              DEPSLP Cheque
            </span>
          </th>
          <th class="text-right">
            <span style="cursor: help;" title="Total amount of deposit slip uploaded" data-toogle="tooltip">
              Total Uploaded
            </span>
          </th>
          <!--
          <th class="text-right">
            <span style="cursor: help;" title="Tells whether the total amount declared on POS and total amount uploaded deposit slips are equal"  data-toogle="tooltip">
              Match?
            </span>
          </th>
        -->
        </tr>
      </thead>
      <?php
        $pcash = 0;
        $pcheck = 0;
        $total_pos = 0;

        $gtp_cash = 0;
        $gtp_check = 0;
        $gtp_total = 0;
        $gtu_cash = 0;
        $gtu_check = 0;
        $gtu_total = 0;

      ?>
      <tbody>
        @foreach($datas as $key => $b) 
        <?php
          $bg = c()->format('Y-m-d')==$b['date']->format('Y-m-d') ? 'bg-success':($b['date']->isSunday() ? 'bg-warning' : '');
        ?>
        <tr>
          <td class="{{ $bg }}">{{ $b['date']->format('M j, D') }}</td>
                      
          @foreach($b['pos'] as $kp => $pos)
          <td class="{{ $bg }} text-center" style="color: #909090">
            @if(!$pos['amount'])
              
            @else
              <!--
              {{ number_format($pos['amount'],2) }}
            -->
            <span class="glyphicon glyphicon-ok text-success"></span>
              <?php
              if ($kp==0)
                $gtp_cash += $pos['amount'];
              else
                $gtp_check += $pos['amount'];

              $gtp_total += $pos['amount'];
              ?>
            @endif
          </td>
          @endforeach
          <!--
          <td class="{{ $bg }} text-right">
            @if($b['pos_totamt']>0)
              <b>
              {{ number_format($b['pos_totamt'],2) }}
              </b>
            @else
              
            @endif
          </td>
          -->
          @foreach($b['depo_type'] as $k => $type)
            @if(c('2017-09-01')->gt($date) || $k=='1'|| $k=='2')
            <td class="{{ $bg }} text-right">
            @if(!$type['slips'])
              
            @else
              <?php
              if ($k==1)
                $gtu_cash += $type['amount'];
              else
                $gtu_check += $type['amount'];

              $gtu_total += $type['amount'];
              ?>
              {{number_format($type['amount'],2)}}<div class="btn-group">
              <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="box-shadow: none; cursor: pointer;">
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-right">
                @foreach($type['slips'] as $slip)
                <li>
                  <a href="/{{brcode()}}/depslp/{{$slip->lid()}}" target="_blank" class="text-right">
                    @if($slip->verified)
                      <span class="gly gly-ok pull-left" data-toogle="tooltip" title="Verified"></span>
                    @endif
                    {{ number_format($slip->amount,2) }}
                  </a>
                </li>
                @endforeach
              </ul>
              </div>
            @endif
            </td>
            @endif
          @endforeach
          <td class="{{ $bg }} text-right">
            @if($b['depo_totcnt']>0)
              {{ number_format($b['depo_totamt'],2) }}
            @else
              
            @endif
          </td>
          <!--
          <td class="{{ $bg }} text-center">
            @if($b['depo_totamt']>0 || $b['pos_totamt']>0)
              @if(number_format($b['depo_totamt'],2) == number_format($b['pos_totamt'],2))
                <span class="glyphicon glyphicon-ok text-success"></span>
              @else
                <span class="glyphicon glyphicon-remove text-danger"></span>
              @endif
            @endif
          </td>
        -->
          
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td></td>
          <td></td>
          <!--
          <td><strong class="pull-right">{{ number_format($gtp_cash,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtp_check,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtp_total,2) }}</strong></td>
        -->
          <td><strong class="pull-right">{{ number_format($gtu_cash,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtu_check,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtu_total,2) }}</strong></td>
        </tr>
        <!--
        <tr class="bg-warning">
          <td><strong>Short/Over</strong></td>
          <td><strong class="pull-right">{{ number_format($gtp_cash-$gtu_cash,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtp_check-$gtu_check,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtp_total-$gtu_total,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtu_cash-$gtp_cash,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtu_check-$gtp_check,2) }}</strong></td>
          <td><strong class="pull-right">{{ number_format($gtu_total-$gtp_total,2) }}</strong></td>
          <td></td>
        </tr>
      -->
      </tfoot>
    </table>
    </div>
    <h1>&nbsp;</h1>
    @endif
    
    
    
   

    
      
  
  </div>
</div><!-- end container-fluid -->

@if(app()->environment()==='production')
<div class="row">
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

    $('#dp-date').datetimepicker({
      //defaultDate: "2016-06-01",
      format: 'MM/YYYY',
      showTodayButton: true,
      ignoreReadonly: true,
      viewMode: 'months'
    }).on('dp.change', function(e){
      var date = e.date.format('YYYY-MM-DD');
      document.location.href = '/{{brcode()}}/depslp/checklist?date='+e.date.format('YYYY-MM-DD');
      console.log(date);
    });
  });
  </script>

  <script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- gi- -->

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
@endsection
