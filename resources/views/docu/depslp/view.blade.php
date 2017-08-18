@extends('index')

@section('title', '- View Deposit Slip')

@section('body-class', 'depslp-view')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/depslp/log">Deposit Slip</a></li>
    <li class="active">{{ $depslp->fileUpload->filename }}</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          <div class="btn-group" role="group">
            <a href="{{ URL::previous() }}" class="btn btn-default" title="Back">
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
            
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/{{brcode()}}/uploader" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span>
              <span class="hidden-xs hidden-sm">DropBox</span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    <div class="row">
      <div class="col-md-6">
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
            <h4><span class="gly gly-cloud"></span> <small>{{ $depslp->filename }} <small>(filename on server)</small></small> </h4>
            <h4>
              <span class="peso">₱</span> {{ number_format($depslp->amount,2) }}  
              <small>
              @if($depslp->type==1)
                (Cash)
              @elseif($depslp->type==2)
                (Cheque)
              @else
                (?)
              @endif
              </small>
            </h4>
            <h5><span class="gly gly-history"></span> {{ $depslp->deposit_date->format('D M j h:i:s A') }} <small>{{ diffForHumans($depslp->deposit_date) }}</small></h5>
            <h5><span class="gly gly-user"></span> {{ $depslp->cashier }}</h5>
            <h6><span class="gly gly-pencil"></span> {{ $depslp->remarks }}</h6>
            
            <h6><span class="glyphicon glyphicon-cloud-upload"></span> {{ $depslp->created_at->format('D M j h:i:s A') }} <small>{{ diffForHumans($depslp->created_at) }}</small></h6>
          </div>
          <div class="panel-footer">
            @if($depslp->verified || $depslp->matched)
              <a href="/{{brcode()}}/depslp/log" class="btn btn-link"><span class="gly gly-unshare"></span> Back</a>
            @else
              <a href="/{{brcode()}}/depslp/{{$depslp->lid()}}/edit" class="btn btn-primary">Edit</a>
              <button class="btn btn-default" data-toggle="modal" data-target=".mdl-delete">Delete</button>
              <a href="/{{brcode()}}/depslp/log" class="btn btn-link">Cancel</a>
            @endif
          </div>
        </div><!-- end: .panel -->
      </div>
      <div class="col-md-6">
        <?php
          $src = '/'.brcode().'/images/depslp/'.$depslp->lid().'.'.strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION));
        ?>
        @if(strtolower(pathinfo($depslp->filename, PATHINFO_EXTENSION))==='pdf')
            <iframe style="width: 100%; height: 500px;" src="{{$src}}"></iframe>
        @else
          <a href="{{$src}}" target="_blank">
            <img class="img-responsive" src="{{$src}}">
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
@endif
</div><!-- end container-fluid -->



<div class="modal fade mdl-delete" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel">
  {!! Form::open(['method'=>'POST', 'url'=>'delete/depslp', 'id'=>'form-file', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="myModalLabel"><span class="fa fa-trash"></span> Delete Deposit Slip</h4>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete <strong>{{ $depslp->fileUpload->filename }}</strong>? This this is irreversible transaction. Please be careful on deleting records. </p>
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
          <input type="hidden" name="id" value="{{ $depslp->id }}">
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

  @if($depslp->isDeletable())
    $('.peso').on('dblclick', function(e){
      e.preventDefault();
      document.location.href='{{request()->fullUrl()}}?verified=true';
    });
  @endif

  });
</script>

@endsection
