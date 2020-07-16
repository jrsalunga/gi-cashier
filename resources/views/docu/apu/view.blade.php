@extends('index')

@section('title', '- Account Payable Upload')

@section('body-class', 'apu-view')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/apu/log">Payables</a></li>
    <li class="active">{{ short_filename($apu->fileUpload->filename) }}</li>
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
        <div class="panel panel-success">
          <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-cloud-uploads"></span> 
              Payables Information
              @if($apu->verified || $apu->matched)
                <span class="fa fa-lock"></span>
              @endif

            </h3>
          </div>
          <div class="panel-body">
            <h4>
              <span class="gly gly-file"></span>
              <span data-toggle="tooltip" title="{{ $apu->fileUpload->filename }}">
                {{ short_filename($apu->fileUpload->filename) }} 
              </span> 
              @if($apu->verified and $apu->matched)
                <span class="glyphicon glyphicon-ok-sign text-success" data-toggle="tooltip" title="Matched and verified by {{ $apu->user->name }}"></span>
              @elseif($apu->verified and !$apu->matched)
                <span class="gly gly-ok" data-toggle="tooltip" title="Verified by {{ $apu->user->name }}"></span>
              @else

              @endif
              <small><small>(uploaded filename)</small></small>
            </h4>
            <h4><span class="gly gly-cloud"></span> <small>{{ $apu->filename }} <small>(filename on server)</small></small> </h4>
            <h4>
              <span class="peso">₱</span> {{ number_format($apu->amount,2) }}  
              <small>
              @if($apu->type==1)
                (Cash)
              @elseif($apu->type==2)
                (Cheque)
              @else
                (?)
              @endif
              </small>
            </h4>
            <h4><span class="gly gly-cart-in"></span> 
            @if(!is_null($apu->supplier))
              @if(!empty($apu->supplier->code))
              <small>{{ $apu->supplier->code }} -</small> 
              @endif
              <small>{{ $apu->supplier->descriptor }}</small> 
            @endif
            </h4>
            <h4><span class="gly gly-barcode"></span> <small>@if(!is_null($apu->doctype)) {{ $apu->doctype->descriptor }} @endif</small></h4>
            <h4><b style="font-color:#000; font-size: large;">#</b> <small>{{ $apu->refno }}</small></h4>
            <h5><span class="gly gly-history"></span> {{ $apu->date->format('D M j, Y') }}</h5>
            <h5><span class="gly gly-user"></span> {{ $apu->cashier }}</h5>
            <h6><span class="gly gly-pencil"></span> {{ $apu->remarks }}</h6>
            <h6><span class="glyphicon glyphicon-cloud-upload"></span> {{ $apu->created_at->format('D M j h:i:s A') }} <small>{{ diffForHumans($apu->created_at) }}</small></h6>
          </div>
          <div class="panel-footer">
            @if($apu->verified || $apu->matched)
              <a href="/{{brcode()}}/apu/log" class="btn btn-link"><span class="gly gly-unshare"></span> Back</a>
            @else
              <a href="/{{brcode()}}/apu/{{$apu->lid()}}/edit" class="btn btn-primary">Edit</a>
              <button class="btn btn-default" data-toggle="modal" data-target=".mdl-delete">Delete</button>
              <a href="/{{brcode()}}/apu/log" class="btn btn-link">Cancel</a>
            @endif
          </div>
        </div><!-- end: .panel -->
      </div>
      <div class="col-md-6">
        @if($apu->file_exists())
          <?php
            $src = '/'.brcode().'/images/apu/'.$apu->lid().'.'.strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION));
          ?>
          @if(strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION))==='pdf')
              <iframe style="width: 100%; height: 500px;" src="{{$src}}"></iframe>
          @else
            <a href="{{$src}}" target="_blank">
              <img class="img-responsive" src="{{$src}}">
            </a>
          @endif
          <a href="{{$src}}" target="_blank" style="text-decoration:none;"><span class="fa fa-clone"></span> <small>view</small></a>
          @else
            File not found.
          @endif
      </div>
    </div>

   
     
  </div>

@if(app()->environment()==='production')
  <div class="row">
    <div class="col-sm-6">
      <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9897737241100378" data-ad-slot="4574225996" data-ad-format="auto"></ins>
    </div>
   </div>
@endif
</div><!-- end container-fluid -->



<div class="modal fade mdl-delete" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel">
  {!! Form::open(['method'=>'POST', 'url'=>'delete/apu', 'id'=>'form-file', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><span class="fa fa-trash"></span> Delete Payable  Upload</h4>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete <strong>{{ $apu->fileUpload->filename }}</strong>? This this is irreversible transaction. Please be careful on deleting records. </p>
        <p></p>
        <p class="text-muted"><small>Reasons for deletion:</small></p>
        <p>
          <textarea name="reason" required style="min-width: 100%; max-width: 100%;"></textarea>
        </p>
      </div>
      <div class="modal-footer">
        <div class="pull-right">
        
          <button type="submit" class="btn btn-primary">Yes</button>
          <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
          <input type="hidden" name="id" value="{{ $apu->id }}">
        </div>
      </div>
    </div><!-- end: .modal-content  -->
  </div>
  {!! Form::close() !!}
</div>
@endsection






@section('js-external')
  @parent



<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- gi- -->
<script>
(adsbygoogle = window.adsbygoogle || []).push({});

  $(document).ready(function(){



  });
</script>

@endsection
