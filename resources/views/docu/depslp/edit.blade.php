@extends('index')

@section('title', '- Edit Deposit Slip')

@section('body-class', 'depslp-edit')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/depslp/log">Deposit Slip</a></li>
    <li><a href="/{{brcode()}}/depslp/{{$depslp->lid()}}">{{ $depslp->fileUpload->filename }}</a></li>
    <li class="active">Edit</li>
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

    <div class="row">
      <div class="col-md-6">
        {!! Form::open(['method'=>'PUT', 'url'=>'put/depslp', 'id'=>'form-file', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
        <div class="panel panel-success">
          <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-cloud-uploads"></span> 
              Deposit Slip Information
              @if($depslp->verified || $depslp->matched)
                <span class="fa fa-lock"></span>
              @endif

            </h3>
          </div>
          <div class="panel-body">
            <h4>
              <span class="gly gly-file"></span> 
              {{ $depslp->fileUpload->filename }} 
              @if($depslp->verified and $depslp->matched)
                <span class="glyphicon glyphicon-ok-sign text-success" data-toggle="tooltip" title="Matched and verified by {{ $depslp->user->name }}"></span>
              @elseif($depslp->verified and !$depslp->matched)
                <span class="gly gly-ok" data-toggle="tooltip" title="Verified by {{ $depslp->user->name }}"></span>
              @else

              @endif
              <small><small>(uploaded filename)</small></small>
            </h4>
            <h4><span class="gly gly-cloud"></span> <small>{{ $depslp->filename }} <small>(filename on server)</small></small></small></h4>

            <div class="row">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="gly gly-money"></span> Amount
                  </span>
                  <input type="text" class="form-control" id="amount" name="amount" required style="text-align: right;" value="{{ number_format($depslp->amount,2) }}">
                </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
              <div class="input-group">
                <span class="input-group-addon" id="basic-addon1">Deposit Type</span>
                 <select id="type" name="type" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>
                  <option value="" disabled selected>-- select deposit type --</option>
                  <option value="1" <?=$depslp->type==1?'selected':''?> >Cash</option>
                  <option value="2" <?=$depslp->type==2?'selected':''?> >Cheque</option>
                </select>
              </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group date-toggle">
                  <span class="input-group-addon" id="basic-addon1">Deposit Date</span>
                  <input type="text" class="form-control" id="date" name="date" required="" value="{{$depslp->date->format('Y-m-d')}}" maxlength="8">
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </span>
                </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group time-toogle">
                  <span class="input-group-addon" id="basic-addon1">Deposit Time</span>
                  <input type="text" class="form-control" id="time" name="time" required value="{{$depslp->time}}" maxlength="8">
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-time"></span>
                  </span>
                </div>
              </div>
            </div>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="glyphicon glyphicon-user"></span> Cashier
                  </span>
                  <input type="text" class="form-control" id="cashier" name="cashier" value="{{$depslp->cashier}}" required placeholder="Anna (cashier's name only, required)" maxlength="20" title="Please put cashiers name only">
                </div><!-- /input-group -->
              </div><!-- /.col-lg-10 -->
            </div>

            <div class="row container-note" style="margin-top: 20px;">
              <div class="col-lg-12">
                <textarea class="form-control" id="notes" name="notes" placeholder="Notes: (optional)" maxlength="300">{{ $depslp->remarks }}</textarea>
              </div><!-- /.col-lg-12 -->
            </div>
            
            <h6><span class="glyphicon glyphicon-cloud-upload"></span> {{ $depslp->created_at->format('D M j h:i:s A') }} <small>{{ diffForHumans($depslp->created_at) }}</small></h6>
          </div>
          <div class="panel-footer">
            @if($depslp->verified || $depslp->matched)
              <a href="/{{brcode()}}/depslp/log" class="btn btn-link"><span class="gly gly-unshare"></span> Back</a>
            @else
              <input type="hidden" name="id" value="{{$depslp->id}}">
              <button type="submit" id="btn-save" data-loading-text="Saving..." class="btn btn-primary" autocomplete="off">Save</button>
              <a href="/{{brcode()}}/depslp/{{$depslp->lid()}}" class="btn btn-link">Cancel</a>
            @endif
          </div>
        </div><!-- end: .panel -->
        {!! Form::close() !!}
      </div>
      <div class="col-md-6">
        <?php
          $src = '/'.brcode().'/images/depslp/'.$depslp->lid().'.'.strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION));
        ?>
        @if(strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION))==='pdf')
          <iframe style="width: 100%; height: 500px;" src="/{{brcode()}}/images/depslp/{{$depslp->lid()}}.{{strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION))}}"></iframe>
        @else
        <a href="{{$src}}" target="_blank">
          <img class="img-responsive" src="/{{brcode()}}/images/depslp/{{$depslp->lid()}}.{{strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION))}}">
        </a>
        @endif
        <a href="{{$src}}" target="_blank" style="text-decoration:none;"><span class="fa fa-clone"></span> <small>view</small></a>
      
      </div>
    </div>

   
     
  </div>

 @if(app()->environment()==='production')
  <div class="row">
    <div class="col-sm-6">
      <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9897737241100378" data-ad-slot="4574225996" data-ad-format="auto"></ins>
    </div>
   </div>
</div><!-- end container-fluid -->
@endif

@endsection






@section('js-external')
  @parent


  <script type="text/javascript">

  $(document).ready(function(){

    if ($('#date')[0]!==undefined) {
      $('#date').datetimepicker({
      //$('.date-toggle').datetimepicker({
        format: 'YYYY-MM-DD',
        ignoreReadonly: true
      });
    }

    if ($('#time')[0]!==undefined) {
      $('#time').datetimepicker({
        format: 'HH:mm:ss A',
        ignoreReadonly: true,
      });
    }

    $('#btn-save').on('click', function () {
      var $btn = $(this).button('loading')
    })
  });
  </script>

<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>

@endsection
