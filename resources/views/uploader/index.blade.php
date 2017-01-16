@extends('index')

@section('title', '- Uploader')

@section('body-class', 'uploader')

@section('container-body')
<div class="backdrop" style="display:none;"></div>
<div class="loader" style="display:none;"><img src="/images/spinner_google.gif"></div>
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li class="active">File Uploader</li>
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
        </div> <!-- end btn-grp -->
        <div class="btn-group" role="group">
          <button type="button" class="btn btn-default active">
            <span class="glyphicon glyphicon-cloud-upload"></span>
            <span class="hidden-xs hidden-sm">DropBox</span>
          </button>
        </div>

        <div class="btn-group pull-right" role="group">
          <a href="/backups/upload" class="btn btn-link">
            <span class="gly gly-retweet"></span>
            <span>Go to old DropBox</span>
          </a>
        </div>
      </div>
    </div>
  </nav>

  @include('_partials.alerts')
  

  <div class="row"> 
    <div class="col-sm-7 col-md-6">
      <div id="panel-tasks" class="panel panel-success">
        <div class="panel-heading">
          <h3 class="panel-title"><span class="glyphicon glyphicon-cloud-uploads"></span> 
            File Information
          </h3>
        </div>
        <div class="panel-body">
          {!! Form::open(['method'=>'PUT', 'url'=>'uploader/postfile', 'id'=>'form-file', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
          <div>

            <div class="row" style="margin-top: 5px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="glyphicon glyphicon-file"></span> Document Type
                  </span>
                  <select id="filetype" name="filetype" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>
                    <option value="">-- select document type --</option>
                    <option value="backup">Backup File</option>
                    <option value="depslp">Depost Slip</option>
                  </select>
                </div><!-- /.col-lg-12 -->
              </div><!-- /.col-lg-12 -->
            </div>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="input-group">
                    <button id="attached" class="btn btn-default" type="button" title="Attach file">
                      <span class="glyphicon glyphicon-paperclip"></span> Attach File
                    </button>
                    <input type="hidden"  name="year" id="year" value="{{ now('Y') }}">
                    <input type="hidden"  name="month" id="month" value="{{ pad(now('M'),2) }}">
                    <input type="file" id="file_upload" style="display: none" />
                </div><!-- /input-group -->
              </div><!-- /.col-lg-6 -->
            </div>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="glyphicon glyphicon-pencil"></span> Filename
                  </span>
                  <input type="text" class="form-control" id="filename" name="filename" readonly required>
                </div><!-- /input-group -->
              </div><!-- /.col-lg-6 -->
            </div>

            

            <div class="filetype-result" style="margin: 0 15px;">
            <!--
            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                Choose Backup Type
                <div class="radio">
                  <label>
                    <input type="radio" name="backup_type" id="backup_pos" value="pos" required>
                    <strong>Regular</strong> <small><em>(For Daily Transactions, EoD purposes)</em></small>
                  </label>
                </div>
                <div class="radio">
                  <label>
                    <input type="radio" name="backup_type" id="backup_payroll" value="payroll">
                    <strong>Payroll</strong> <small><em>(For Payroll purposes)</em></small>
                  </label>
                </div>
              </div>
            </div>
            -->
            </div><!-- end: .filetype-result -->

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
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
            </div>

            <div class="row container-note" style="margin-top: 20px;">
              <div class="col-lg-12">
                <textarea class="form-control" id="notes" name="notes" placeholder="Notes: (optional)" style="max-width:100%;" maxlength="300"></textarea>
              </div><!-- /.col-lg-12 -->
            </div>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="process-btn-container">
                  <button id="btn-upload" class="btn btn-primary" type="submit" disabled="disabled">Upload File</button>
                  <a class="btn btn-link" href="/uploader">Cancel</a>  
                </div>
                <div class="progress-container hide">
                  <img src="/images/spinner_google.gif">
                  <span style="padding-left: 10px; font-weight: bold;"> Uploading file... please wait.</span>
                </div>
              </div><!-- /.col-lg-12 -->
            </div>
          </div>
          {!! Form::close() !!}
        </div>
      </div>
    </div><!-- end: .col-sm-7.col-md-6 -->
    <div class="col-sm-5 col-md-6">
      
      <div class="dropbox-container">
        <div id="dropbox" class="prod-image" disabled style="cursor: pointer;">
        
          <span class="message">You can 'Drag and Drop' or 'Click' here to attach your file. <br />
          <i>(they will only be visible to you)</i>
          </span>
          
        </div>
        
      </div>
    </div><!-- end: .col-sm-7.col-md-6 -->
    <div class="hidden-md hidden-lg" style="margin-bottom:20px;"></div>

    
      
  
  </div><!-- end: .row -->
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  <script src="/js/vendors/jquery.filedrop-0.1.0.js"></script>
  <script src="/js/filedrop-common.js"></script>

  <script type="text/javascript">

  $('#attached').on('click', function(){
    //console.log($('.lbl-file_upload'));
    $('#file_upload').click();
  });

  $(document).ready(function(){

    $('.toggle-note').on('click', function(){
      $('.container-note').toggle();
    })

    $('#cashier').on('blur', function(e){
      e.preventDefault();
      if($.trim($(this).val()).length===0) {
        $(this)[0].value='';
      }
    });

    $('#filetype').on('change', function(e){
      e.preventDefault();
      console.log('change: ' +$(this).val());
      alertRemove();
      var html = '';
      if ($(this).val()==='depslp') {
        html += '<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group date-toggle">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'Deposit Date</span>'
              +'<input type="text" class="form-control" id="date" name="date" required placeholder="YYYY-MM-DD" maxlength="8">'
            +'<span class="input-group-addon">'
            +'<span class="glyphicon glyphicon-calendar"></span>'
            +'</span>'
            +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group time-toogle">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'Deposit Time</span>'
              +'<input type="text" class="form-control" id="time" name="time" required placeholder="HH:MM:SS" maxlength="8">'
            +'<span class="input-group-addon">'
            +'<span class="glyphicon glyphicon-time"></span>'
            +'</span>'
            +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'<span class="gly gly-money"></span> Amount</span>'
              +'<input type="text" class="form-control" id="amount" name="amount" required style="text-align: right;" placeholder="0.00">'
            +'</div></div>';
        alertMessage($('#nav-action'), 'warning', '<b>Tips:</b> <ol><li>Before attaching the scanned Bank Deposit Slip please follow the standard file naming convention.'
          +' Sample "<b>DEPSLP MOA 20170102</b>"</b> where <b>DEPSLP</b> - is the document type code, <b>MOA</b> - is the 3 char branch code and '
          +'<b>20170102</b> - is the Deposit Date in YYYYMMDD format. For multiple deposit on the same day, the filename should be <b>DEPSLP MOA 20170102-2</b>,'
          +' <b>DEPSLP MOA 20170102-3</b>, etc.</li><li> Make sure that the <b>Date/Time</b> encoded is the <b>Deposit Date/Time</b> on the Deposit Slip.</li>'
          +'<li>Please don\'t upload other documents like DCCR, etc. <b>Scanned Deposit Slip</b> only. =)</li></ol>'
          +'<small><em class="text-muted">For more questions / clarifications email us at <a href="mailto:jefferson.salunga@gmail.com">jefferson.salunga@gmail.com</a></em></small>');
      } else if ($(this).val()==='backup') {
        html += '<div class="row" style="margin-top: 20px;"><span class="title">Choose Backup Type</span>'
            +'<div class="radio">'
              +'<label>'
                +'<input type="radio" name="backup_type" id="backup_payroll" value="payroll">'
                +'<strong><span class="gly gly-address-book"></span> Payroll Backup</strong> <small><em>(for Payroll Backup purposes)</em></small>'
              +'</label>'
            +'</div>'
            +'<div class="radio">'
              +'<label>'
                +'<input type="radio" name="backup_type" id="backup_pos" value="pos">'
                +'<strong><span class="fa fa-file-archive-o"></span> POS Backup</strong> <small><em>(for End of Day Backup purposes)</em></small>'
              +'</label>'
            +'</div></div>';
            
      } else {
        html +='';
      }

      $('.filetype-result').html(html);

      if ($('#date')[0]!==undefined) {
        $('#date').datetimepicker({
        //$('.date-toggle').datetimepicker({
          format: 'YYYY-MM-DD',
          ignoreReadonly: true
        });
      }

      if ($('#time')[0]!==undefined) {
        $('.time-toogle').datetimepicker({
          format: 'HH:mm:ss',
          ignoreReadonly: true,
        });
      }
    });

    var validateRadio = function() {
      var radios = $('[name="backup_type"]');
      var formValid = false;

      var i = 0;
      while (!formValid && i < radios.length) {
          if (radios[i].checked) formValid = true;
          i++;        
      }
      //if (!formValid) alert("Must check some option!");
      return formValid;
    }

    $('.filetype-result').on('click', '[name="backup_type"]', function() {
      $.inArray($(this).val(), ['pos', 'payroll'])
        $('.filetype-result > div').removeClass('form-required');
    });


    $('form#form-file').submit(function(e) { 

      if ($('#filetype').val()==='backup') {
        
        if(!validateRadio()) {
          $('.filetype-result > div').addClass('form-required');
          return false;
        } else {
          $('.filetype-result > div').removeClass('form-required');
        }
      } else {


      }

      $('.progress-container').removeClass('hide');
      $('.process-btn-container').addClass('hide');

      loader();
      //console.info('loader');
      return true;
      //e.preventDefault(); 
    });
    
  });
  </script>

  <style type="text/css">
  .form-required {
    border: 1px solid red;
    border-radius: 4px;
    padding: 10px;
  }

  .bg-danger {
    border-radius: 4px;
  }

  .form-required .title{
    color: red;
    font-weight: bold;
  }
  </style>
@endsection
