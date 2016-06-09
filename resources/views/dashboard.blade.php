@extends('master')

@section('title', ' - Dashboard')

@section('navbar-2')
<ul class="nav navbar-nav navbar-right"> 
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
      <span class="glyphicon glyphicon-menu-hamburger"></span>
      
    </a>
    <ul class="dropdown-menu">
      <li><a href="/tk"><span class="glyphicon glyphicon-time"></span> Bundy Clock</a></li>
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


  <div style="margin-top:50px;" class="hidden-xs"></div>
  <!--<div class="row row-centered">-->
  <div class="row">
    <div class="col-sm-6 col-md-7">
      <div id="panel-tasks" class="panel panel-success">
        <div class="panel-heading">
          <h3 class="panel-title"><span class="fa fa-file-archive-o"></span> Last 7 Days Backup</h3>
        </div>
        <div class="panel-body">
          <table class="table">
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
            <td>-</td>
            <td>-</td>
            <td>-</td>
          @endif
          
        </tr>
        @endforeach
      </tbody>
    </table>
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
            <a href="/backups/upload" class="list-group-item">DropBox 
              
            </a> 
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