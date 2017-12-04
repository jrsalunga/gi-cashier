@extends('master')

@section('title', ' - Dashboard')

@section('navbar-2')
<ul class="nav navbar-nav navbar-right"> 
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
      <span class="glyphicon glyphicon-menu-hamburger"></span>
      
    </a>
    <ul class="dropdown-menu">
      <li><a href="/tk?{{strtolower(session('user.branchcode'))}}"><span class="glyphicon glyphicon-time"></span> Bundy Clock</a></li>
      {{-- <li><a href="/backups/upload"><span class="glyphicon glyphicon-cloud-upload"></span> Upload Backup</a></li> --}}
    	<li><a href="/settings"><span class="glyphicon glyphicon-cog"></span> Settings</a></li>
      <li><a href="/logout"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>     
    </ul>
  </li>
</ul>
<p class="navbar-text navbar-right">{{ $name }}</p>
@endsection


@section('container-body')
<div class="container-fluid">
	
  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li class="active">Dashboard</li>
  </ol>
  
   @if($inadequates)
    <div class="alert alert-warning alert-important">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
      <strong><span class="glyphicon glyphicon-warning-sign"></span> Warning</strong>: No backup uploaded on the following date(s) below. This may affect the report generation.
      <ul>
      @foreach($inadequates as $d) 
        <li>{{ $d->format('m/d/Y') }} - <b>GC{{ $d->format('mdy') }}.ZIP</b></li>
      @endforeach
      </ul>
    </div>
  @endif
  <!--
  <div class="alert alert-warning alert-important">
    <p><span class="glyphicon glyphicon-warning-sign"></span> Please be reminded that starting Aug 19, the <b>Deposit Slip</b> upload should be <b>one is to one (1:1)</b>. One scanned deposit slip for every upload on DropBox.</p>
  </div>
  -->
  <!--
  <div style="margin-top:50px;" class="hidden-xs"></div>
  <div class="row row-centered">-->
  <div class="row">
    <div class="col-sm-6 col-md-7">
      <div id="panel-tasks" class="panel panel-success">
        <div class="panel-heading">
          <h3 class="panel-title"><span class="fa fa-file-archive-o"></span> Last 7 Days Backup</h3>
        </div>
        <div class="panel-body">
          <div class="btn-toolbar" role="toolbar">
            <div class="btn-group">
              <a href="/{{brcode()}}/timesheet?date={{date('Y-m-d')}}" class="btn btn-default">
                <span class="gly gly-stopwatch"></span> 
                <span class="hidden-xs">Timesheet</span>
              </a>
            </div>
            <div class="btn-group">
              <!--
              <a href="/backups/checklist" class="btn btn-default">
                <span class="fa fa-calendar-check-o"></span> 
                <span class="hidden-xs">Backup Checklist</span>
              </a> 
              -->
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-calendar-check-o"></span>
                <span class="hidden-xs">Checklist</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups/checklist"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp/checklist"><span class="fa fa-bank"></span> Deposit Slip</a></li>
                <li><a href="/{{brcode()}}/ap/checklist"><span class="fa fa-briefcase"></span> Payables</a></li>
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
                <li><a href="/{{brcode()}}/ap/log"><span class="fa fa-briefcase"></span> Payables</a></li>
              </ul>
            </div>
          </div>
          <div class="table-responsive">
          <table class="table table table-hover table-striped">
            <thead>
              <tr>
                <th>Backup Date</th>
                <th>Filename</th>
                <th>Cashier</th>
                <th>Upload Date</th>
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
                @else
                  <td>{{ $b['backup']->filename }}</td>
                  <td>
                    {{ $b['backup']->cashier }}
                  </td>
                  <td style="cursor:help;" title="{{ $b['backup']->uploaddate->format('Y-m-d h:m:i A') }}">
                    <small>
                      <em>
                      {{ diffForHumans($b['backup']->uploaddate) }}      
                      </em>
                    </small>
                  </td>
                @endif
                
              </tr>
              @endforeach
            </tbody>
          </table>
          </div>
        </div>
      </div>
    </div>
    <div class="col-sm-6 col-md-5 col-centered">
      <div id="panel-tasks" class="panel panel-success">
        <div class="panel-heading">
          <h3 class="panel-title"><span class="gly gly-notes-2"></span> Tasks</h3>
        </div>
        <div class="panel-body">
          <div class="list-group">
            {{-- <a href="/backups/upload" class="list-group-item">Backup</a> --}}
            <a href="/{{brcode()}}/uploader" class="list-group-item">DropBox</a> 
            <a href="/timelog/add" class="list-group-item">Timelog Manual Entry</a>
            {{-- <a href="/backups/upload" class="list-group-item">Upload Backup</a> --}}
          </div>
        </div>
      </div>
    </div>
	
  
    



</div>
@endsection














@section('js-external')
  
 	<script src="/js/vendors-common.min.js"></script>

  
@endsection