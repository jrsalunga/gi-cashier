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
          <div class="btn-group" role="group">
            <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span>
              <span class="hidden-xs hidden-sm">Back</span>
            </a> 
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-archive"></span> 
                <span class="hidden-xs hidden-sm">Filing System</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>
           
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-calendar-check-o"></span>
                <span class="hidden-xs hidden-sm">Checklist</span>
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
                <span class="hidden-xs hidden-sm">Logs</span>
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


          


        </div>
      </div>
    </nav>

  @include('_partials.alerts')

  @if(is_null($date))
    no data
  @else
    <h3>Upload summary of POS backup GC{{ $date->format('mdy') }}.ZIP</h3>
    <p>&nbsp;</p>

    
    <div class="row">
      <div class="col-md-3">
        Backup Date:
        <h3>{{ $date->format('D M d, Y') }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3">
        Gross Sales:
        <h3>{{ number_format($ds->slsmtd_totgrs, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3">
        Net Sales:
        <h3>{{ number_format($ds->sales, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3">
        Total Purchased Cost:
        <h3>{{ number_format($ds->purchcost, 2) }}</h3>
      </div><!-- end: .col-md-3-->
    </div><!-- end: .row-->
    <div class="row">
    <p>&nbsp;</p>
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item"><span class="badge">{{ $ds->crew_din }}</span> Dining Crew</li>
          <li class="list-group-item"><span class="badge">{{ $ds->crew_kit }}</span> Kitchen Crew</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item">Customers <span class="pull-right">{{ number_format($ds->custcount, 0) }}</span></li>
          <li class="list-group-item">Tips <span class="pull-right">{{ number_format($ds->tips, 2) }}</span></li>
        </ul>
      <!--
        <div class="panel panel-success">
          <div class="panel-body">
            <table class='table'>
              <tbody>
                <tr>
                  <td>Gross Sales</td>
                  <td>{{ number_format($ds->slsmtd_totgrs, 2) }}</td>
                </tr>
                <tr>
                  <td>Net Sales</td>
                  <td>{{ number_format($ds->sales, 2) }}</td>
                </tr> 
                <tr>
                  <td>Net Sales</td>
                  <td>{{ number_format($ds->sales, 2) }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      -->
      </div>
    </div><!-- end: .row-->

    <div class="row">
      <div class="col-md-12">
        
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
            <span class="hidden-xs hidden-sm">Back to DropBox</span>
          </a>
        </div>

        
      </div>
    </div>


  @endif
   
  
    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  <script type="text/javascript">
  $(document).ready(function(){

   
  });
  </script>
@endsection
