@extends('index')

@section('title', '- Backup Checklist')

@section('body-class', 'backup-checklist')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/backups">Backups</a></li>
    <li><a href="/backups/checklist">Checklist</a></li>
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
            <button type="button" class="btn btn-default active">
              <span class="fa fa-calendar-check-o"></span>
              <span class="hidden-xs hidden-sm">Checklist</span>
            </button>
            <a href="/backups/log" class="btn btn-default">
              <span class="glyphicon glyphicon-th-list"></span>
              <span class="hidden-xs hidden-sm">Logs</span>
            </a>
            <!--
            <a href="/backups" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud"></span>
            </a>
            -->
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/backups/upload" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span> 
              <span class="hidden-xs hidden-sm">DropBox</span>
            </a>
          </div><!-- end btn-grp -->


          <div class="btn-group pull-right clearfix" role="group">
          <a href="/backups/checklist?date={{ $date->copy()->subMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->subMonth()->format('Y-m-d') }}">
            <span class="glyphicon glyphicon-chevron-left"></span>
          </a>
          <input type="text" class="btn btn-default" id="dp-date" value="{{ $date->format('m/Y') }}" style="max-width: 110px;" readonly>
          <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
          <a href="/backups/checklist?date={{ $date->copy()->addMonth()->format('Y-m-d') }}" class="btn btn-default" title="{{ $date->copy()->addMonth()->format('Y-m-d') }}">
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
          <th>Backup Date</th>
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
        @foreach($backups as $key => $b) 
        <tr>
          <td>{{ $b['date']->format('M j, D') }}</td>
          @if(is_null($b['backup']))
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
            <td>-</td>
          @else
            <td>
              {{ $b['backup']->filename }}
            </td>
            <td title="Shows only the lastest uploader of the same backup.">
              {{ $b['backup']->cashier }}
            </td>
            <td>
              <small><em>
              {{ $b['backup']->uploaddate->format('Y-m-d h:m:i A') }}
              </em>
              </small>
            </td>
            <td>
              <span class="badge">{{ $b['backup']->count }}</span>
            </td>
            <td>
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
      document.location.href = '/backups/checklist?date='+e.date.format('YYYY-MM-DD');
      console.log(date);
    });
      
  });
  
  </script>
@endsection
