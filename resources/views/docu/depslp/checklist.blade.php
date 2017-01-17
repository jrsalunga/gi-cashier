@extends('index')

@section('title', '- Backup Checklist')

@section('body-class', 'backup-checklist')

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
          <div class="btn-group" role="group">
            <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span>
              <span class="hidden-xs hidden-sm">Back</span>
            </a> 
            <a href="/backups" class="btn btn-default">
              <span class="fa fa-archive"></span>
              <span class="hidden-xs hidden-sm">Filing System</span>
            </a>
           <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-calendar-check-o"></span>
                <span class="hidden-xs">Checklist</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups/checklist"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp/checklist"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>

            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-th-list"></span>
                <span class="hidden-xs">Logs</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups/log"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp/log"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>
            <!--
            <a href="/backups" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud"></span>
            </a>
            -->
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/{{brcode()}}/uploader" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span> 
              <span class="hidden-xs hidden-sm">DropBox</span>
            </a>
          </div><!-- end btn-grp -->


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



    <div class="table-responsive">
    <table class="table table-hover table-striped">
      <thead>
        <tr>
          <th>Deposit Date</th>
          <th>Filename</th>
          <th>
            <span style="cursor: help;" title="Shows only the lastest uploader of the same backup.">
              Cashier
            </span>
          </th>
          <th>Upload Date</th>
          <th>Log Count</th>
          <th>
            <span style="cursor: help;" title="Tells whether the actual physical backup file is in the server's file system.">
              File in Server?
            </span>
          </th>
        </tr>
      </thead>
      <tbody>
        @foreach($depslips as $key => $b) 
        <?php
          $class = c()->format('Y-m-d')==$b['date']->format('Y-m-d') ? 'class=bg-success':'';
        ?>
        <tr>
          <td {{ $class }}>{{ $b['date']->format('M j, D') }}</td>
          @if(is_null($b['backup']) || !$b['exist'])
            <td {{ $class }}>-</td>
            <td {{ $class }}>-</td>
            <td {{ $class }}>-</td>
            <td {{ $class }}>-</td>
            <td {{ $class }}>-</td>
          @else
            <td {{ $class }}>
              {{ $b['backup']->filename }}
            </td>
            <td title="Shows only the lastest uploader of the same backup." {{ $class }}>
              {{ $b['backup']->cashier }}
            </td>
            <td {{ $class }}>
              <small>
                <em>
                  <span class="hidden-xs">
                    @if($b['backup']->created_at->format('Y-m-d')==now())
                      {{ $b['backup']->created_at->format('h:i A') }}
                    @else
                      {{ $b['backup']->created_at->format('D M j') }}
                    @endif
                  </span> 
                  <em>
                    <small class="text-muted">
                    {{ diffForHumans($b['backup']->created_at) }}
                    </small>
                  </em>
                </em>
              </small>
            </td>
            <td {{ $class }}>
              <span class="badge">{{ $b['backup']->count }}</span>
            </td>
            <td {{ $class }}>
              @if($b['exist'])
                <span class="glyphicon glyphicon-ok text-success"></span>
              @else
                <span class="glyphicon glyphicon-remove text-danger"></span>
              @endif
            </td>
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
      document.location.href = '/{{brcode()}}/depslp/checklist?date='+e.date.format('YYYY-MM-DD');
      console.log(date);
    });
  });
  </script>
@endsection
