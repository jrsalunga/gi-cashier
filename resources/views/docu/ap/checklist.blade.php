@extends('index')

@section('title', '- Backup Checklist')

@section('body-class', 'backup-checklist')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/ap/log">Deposit Slip</a></li>
    <li><a href="/{{brcode()}}/ap/checklist">Checklist</a></li>
    <li class="active">{{ $date->format('M Y') }}</li>
  </ol>

  <div>

    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          @include('_partials.menu.logs')

          <div class="btn-group pull-right clearfix" role="group">
            <a href="/{{brcode()}}/ap/checklist?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->subMonth()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-left"></span>
            </a>
            <input type="text" class="btn btn-default" id="dp-date" value="{{ $date->format('m/Y') }}" style="max-width: 110px;" readonly>
            <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
            <a href="/{{brcode()}}/ap/checklist?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->addMonth()->format('Y-m-d') }}">
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
          <th>Date</th>
          <th>Payables</th>
          <th>File Count</th>
        </tr>
      </thead>
      <tbody>
        @foreach($data as $key => $b) 
        <?php
          $class = c()->format('Y-m-d')==$b['date']->format('Y-m-d') ? 'class=bg-success':'';
        ?>
        <tr>
          <td {{ $class }}>{{ $b['date']->format('M j, D') }}</td>
          @if(!$b['exist'])
            <td {{ $class }}> </td>
            <td {{ $class }}> </td>
          @else
            
            <td {{ $class }}>
              @if($b['exist'])
                <span class="glyphicon glyphicon-ok text-success"></span>
                &nbsp;
                <a href="{{ $b['uri'] }}">
                  View Files
                </a>
              @endif
            </td>
            <td {{ $class }}>{{ $b['file_count'] }}</td>

          @endif
          
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    
   

    
      
  
  </div>
</div><!-- end container-fluid -->
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
      document.location.href = '/{{brcode()}}/ap/checklist?date='+e.date.format('YYYY-MM-DD');
      console.log(date);
    });
  });
  </script>
@endsection
