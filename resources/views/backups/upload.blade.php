@extends('index')

@section('title', '- Upload Backup')

@section('body-class', 'backup-upload')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/backups">Backups</a></li>
    <li class="active">Upload</li>
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
            <a href="/backups/checklist" class="btn btn-default">
              <span class="fa fa-calendar-check-o"></span>
              <span class="hidden-xs hidden-sm">Checklist</span>
            </a>
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
            <button type="button" class="btn btn-default active">
              <span class="glyphicon glyphicon-cloud-upload"></span>
              <span class="hidden-xs hidden-sm">DropBox</span>
            </button>
          </div>
          <div class="btn-group pull-right" role="group">
            <a href="/{{brcode()}}/uploader" class="btn btn-link">
              <span class="gly gly-retweet"></span>
              <span>Try new DropBox</span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    @include('_partials.alerts')
    <div class="geo-callback-message"></div>

    <div class="row row-centered dtr-daterange">
      <div class="col-sm-7 col-md-6 col-centered">
        <div id="panel-tasks" class="panel panel-success">
          <div class="panel-heading">
            <h3 class="panel-title"><span class="glyphicon glyphicon-cloud-upload"></span> DropBox</h3>
          </div>
          <div class="panel-body">
            {!! Form::open(['method'=>'PUT', 'url'=>'upload/postfile', 'id'=>'form-backup', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
            <div class="dropbox-container">
              <div id="dropbox" class="prod-image" disabled>
              
                <span class="message">Drag and Drop your backup here. <br />
                <i>(they will only be visible to you)</i>
                </span>
                
              </div>
              <!--
              <label for="file_upload" class="lbl-file_upload">Upload</label> 
              -->
              <input type="file" id="file_upload" style="display: none" />

              <div class="row" style="margin-top: 5px;">
                <div class="col-lg-12">
                  <div class="input-group">
                    <span class="input-group-btn">
                      <button id="attached" class="btn btn-default" type="button" title="Attach file">
                        <span class="glyphicon glyphicon-paperclip"></span> Backup
                      </button>
                      <input type="hidden"  name="year" id="year" value="{{ now('Y') }}">
                      <input type="hidden"  name="month" id="month" value="{{ pad(now('M'),2) }}">
                      
                      <input type="hidden"  name="lat" id="lat">
                      <input type="hidden"  name="lng" id="lng">
                    
                    </span>
                    <input type="text" class="form-control" id="filename" name="filename" readonly required>
                  </div><!-- /input-group -->
                </div><!-- /.col-lg-6 -->
              </div>

              <div class="row" style="margin-top: 10px;">
                <div class="col-xs-11">
                  <div class="input-group">
                    <span class="input-group-addon" id="basic-addon1">
                      <span class="glyphicon glyphicon-user"></span> Cashier
                    </span>
                    <input type="text" class="form-control" id="cashier" name="cashier" required  
                      placeholder="Anna (cashier's name only, required)"  
                      maxlength="20"                         
                      title="Please put cashiers name only"
                    >
                    <!--
                    pattern="[a-zA-Z\s-().]+"
                      oninvalid="setCustomValidity('Please put cashier\'s name only ')"
                      onchange="try{setCustomValidity('')}catch(e){}"
                    -->
                  </div><!-- /input-group -->
                </div><!-- /.col-lg-10 -->

                <div class="col-xs-1">
                  <div class="input-group pull-right">
                    <button type="button" class="btn btn-default toggle-note" title="Show/Hide note">
                      <span class="glyphicon glyphicon-option-vertical"></span>
                    </button>
                  </div><!-- /input-group -->
                </div><!-- /.col-lg-2 -->
              </div>

              <div class="row container-note" style="margin-top: 10px;">
                <div class="col-lg-12">
                  <textarea class="form-control" id="notes" name="notes" placeholder="Notes: (optional)" style="max-width:100%;" maxlength="300"></textarea>
                </div><!-- /.col-lg-12 -->
              </div>

              <div class="row" style="margin-top: 10px;">
                <div class="col-lg-12">
                  <div class="process-btn-container">
                    <button id="btn-upload" class="btn btn-primary" type="submit" disabled="disabled">Process Backup</button>
                    <a class="btn btn-link" href="/backups/upload">Cancel</a>  
                  </div>
                  <div class="progress-container hide">
                    <img src="/images/spinner_google.gif">
                    <span style="padding-left: 10px; font-weight: bold;"> Processing backup... please wait.</span>
                  </div>
                </div><!-- /.col-lg-12 -->
              </div>
            {!! Form::close() !!}
          </div>
        </div>
      </div>
    </div>
   

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  <script src="/js/vendors/jquery.filedrop-0.1.0.js"></script>
  <script src="/js/filedrop.js"></script>

  <script type="text/javascript">

  $('#attached').on('click', function(){
    //console.log($('.lbl-file_upload'));
    $('#file_upload').click();
  });
  
  $(document).ready(function(){

  $('.toggle-note').on('click', function(){
    $('.container-note').toggle();
  });


  $('#cashier').on('blur', function(e){
    e.preventDefault();
    if($.trim($(this).val()).length===0) {
      $(this)[0].value='';
    }
  });


  $('form#form-backup').submit(function(e) { 
    $('.process-btn-container').addClass('hide');
    $('.progress-container').removeClass('hide');
    //e.preventDefault(); 
  });
    
  });
  </script>
@endsection
