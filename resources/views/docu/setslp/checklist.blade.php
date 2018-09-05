@extends('index')

@section('title', '- Card Settlement Checklist')

@section('body-class', 'setslp-checklist')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/setslp/log">Deposit Slip</a></li>
    <li><a href="/{{brcode()}}/setslp/checklist">Checklist</a></li>
    <li class="active">{{ $date->format('M Y') }}</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          
          @include('_partials.menu.logs')

          <div class="btn-group pull-right clearfix" role="group">
          <a href="/{{brcode()}}/setslp/checklist?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->subMonth()->format('Y-m-d') }}">
            <span class="glyphicon glyphicon-chevron-left"></span>
          </a>
          <input type="text" class="btn btn-default" id="dp-date" value="{{ $date->format('m/Y') }}" style="max-width: 110px;" readonly>
          <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
          <a href="/{{brcode()}}/setslp/checklist?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->addMonth()->format('Y-m-d') }}">
            <span class="glyphicon glyphicon-chevron-right"></span>
          </a>
        </div>


        </div>
      </div>
    </nav>

    @include('_partials.alerts')



    <div class="table-responsive">
    <table class="table table-hover table-striped">
      <thead>
        <tr>
          <th>Business Date</th>
          <th class="text-right">POS Total Charge</th>
          <th class="text-right">Settlement Total</th>
          <th class="text-right">Settlement Slips</th>
          <th class="text-right">&nbsp;</th>
        </tr>
      </thead>
      <tbody>
        <?php $tot_pos = $tot_set = 0; ?>
        @foreach($datas as $key => $b) 
        <?php
          $class = c()->format('Y-m-d')==$b['date']->format('Y-m-d') ? 'bg-success':'';
        ?>
        <tr>
          <td class="{{ $class }}">{{ $b['date']->format('M j, D') }}</td>
           
          @if($b['pos_total']>0) 
          <td class="text-right {{ $class }}">{{ number_format($b['pos_total'],2) }}</td>

          <?php $tot_pos += $b['pos_total']; ?>
          @else
            <td class="text-right {{ $class }}">-</td>
          @endif 

          @if($b['count']>0)
            <td class="text-right {{ $class }}">{{ number_format($b['slip_total'],2) }}</td>
            <?php $tot_set += $b['slip_total']; ?>
            <td class="text-right {{ $class }}">
              <span class="badge text-info help" title="" data-toggle="tooltip">{{ $b['count'] }}</span>

              <div class="btn-group">
              <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="true" style="box-shadow: none; cursor: pointer;">
                <span class="caret"></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-right">
                @foreach($b['slips'] as $slip)
                  <li>
                  <a href="/{{brcode()}}/setslp/{{$slip->lid()}}" target="_blank" class="text-right">
                  {{ number_format($slip->amount,2) }}
                  </a>
                </li>
                @endforeach
              </ul>
              </div>

            </td>
          @else
            <td class="text-right {{ $class }}">-</td>
            <td class="{{ $class }}"></td>
          @endif
          
            <td class="text-right {{ $class }}">&nbsp;</td>
        </tr>
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td></td>
          <td class="text-right">{{ number_format($tot_pos,2) }}</td>
          <td class="text-right">{{ number_format($tot_set,2) }}</td>
          <td></td>
          <td></td>
        </tr>
      </tfoot>
    </table>
    </div>
    
   

    
      
  
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
      document.location.href = '/{{brcode()}}/setslp/checklist?date='+e.date.format('YYYY-MM-DD');
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
