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

  @if(is_null($date))
    no data
  @else

    @if($ds->sales!=$ds->chrg_total)
      @if($ds->sales!=($ds->chrg_total-$ds->chrg_othr)
      <div class="alert alert-important alert-warning"><b>Warning:</b> The backup file you uploaded is possibly not the end-of-day backup. Please reupload the actual EoD/EoM backup.<br>You can ignore this if you're not sending EoD/EoM backup like sending Payroll Backup.</div>
      @endif
    @endif

    <h3>Upload summary of POS backup GC{{ $date->format('mdy') }}.ZIP <em>({{ $date->format('D M d, Y') }})</em></h3>
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
        Net Sales:
        <h3 class="text-success">{{ number_format($ds->sales, 2) }}</h3>
      </div><!-- end: .col-md-3-->
      <div class="col-md-3 col-xs-6">
        Food Cost:
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
    <p>&nbsp;</p>
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
          <li class="list-group-item"><span class="pull-right"><strong>{{ $ds->get_mancostpct() }}</strong></span> Man Cost %</li>
        </ul>
      </div>
      <div class="col-md-3">
        <ul class="list-group">
          <li class="list-group-item">Tips <span class="pull-right"><strong>{{ number_format($ds->tips, 2) }}</strong></span></li>
          <li class="list-group-item">Tips % <span class="pull-right"><strong>{{ number_format($ds->get_tipspct(), 2) }}</strong></span></li>
        </ul>
      </div>
      
    </div><!-- end: .row-->


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


