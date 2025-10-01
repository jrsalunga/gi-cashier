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
        @include('_partials.menu.logs')
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
                    <option value="" disabled selected>-- Select document type --</option>
                    <option value="backup">Backup File</option>
                    <option value="depslp">Depost Slip (DEPSLP)</option>
                    <option value="setslp">Card Settlement Slip (SETSLP)</option>
                    <option value="ap">Accounts Payable Documents (AP)</option>
                    <!--
                    <option value="exportreq-etrf">Export Request (EX*.REQ) + Emp Transfer Request Form (ETRF)</option>
                    -->
                  </select>
                </div><!-- /.col-lg-12 -->
              </div><!-- /.col-lg-12 -->
            </div>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="input-group">
                    <button id="attached" class="btn btn-default" style="background-color: #188038; color: #fff;" type="button" title="Attach file">
                      <span class="glyphicon glyphicon-paperclip"></span> Attach File
                    </button>
                    <input type="hidden"  name="year" id="year" value="{{ now('Y') }}">
                    <input type="hidden"  name="month" id="month" value="{{ pad(now('M'),2) }}">
                    <input type="file" id="file_upload" data-input-value="filename" style="display: none" />
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
                    value="Jeff"
                    <?=app()->environment()==='local'?'value="Jeff"':''?>
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
                  @if(request()->has('eod') && request()->input('eod')=='false')
                    <input type="hidden" name="_eod" value="false">
                  @endif
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

 @if(app()->environment()==='productions')
 <div class="row" style="margin-top: 10px;">
  <div class="col-sm-6">
    <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9897737241100378" data-ad-slot="4574225996" data-ad-format="auto"></ins>
  </div>
 </div>
@endif
@endsection


@section('js-external')
  @parent
  <link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
  <script src="/js/vendors/jquery.filedrop-0.1.0.js"></script>
  <script src="/js/filedrop-common.js?v=0.5"></script>
  <script src="//cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>


  <script type="text/javascript">

  $('#attached').on('click', function(){
    $('#file_upload').data('input-value', 'filename');
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

    $('#filetype').on('change', function(e){
      e.preventDefault();
      console.log('change: ' +$(this).val());
      alertRemove();
      var html = '';
      if ($(this).val()==='depslp') {
          html += '<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Deposit Type</span>'
                +'<select id="type" name="type" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>'
                  +'<option value="" disabled selected>-- select deposit type --</option>'
                  +'<option value="1">Cash</option>'
                  +'<option value="2">Cheque</option>'
                +'</select>'
              +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
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
          +'<b>20170102</b> - is the Deposit Date in YYYYMMDD format. '
          +'</li><li> Make sure that the <b>Date/Time</b> encoded is the <b>Deposit Date/Time</b> on the Deposit Slip.</li>'
          +'<li><div style="color:red;"">Please be reminded that from now on the <b>Deposit Slip</b> should be <b>one is to one (1:1)</b>. One scanned deposit slip for every upload.</div>'
          +'</li>'
          +'<li>'
          +'For branch that has multiple deposit on the same day, the filename should be <b>DEPSLP MOA 20170102 Cash</b>, <b>DEPSLP MOA 20170102 Check</b>, <b>DEPSLP MOA 20170102 Cash_2</b>, etc.'
          +'</li>'
          +'<li>Please don\'t upload other documents like DCCR, etc. <b>Scanned Deposit Slip</b> only. =)</li>'
          +'</ol>'
          +'<small><em class="text-muted">For more questions / clarifications / need assistance you can call us at <a href="tel:+639993330386">0999 333 0386</a></em></small>');
      } else if ($(this).val()==='setslp') {

          html += '<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Credit Card Terminal</span>'
                +'<select id="terminal_id" name="terminal_id" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>'
                  +'<option value="" disabled selected>-- Select credit card terminal --</option>'
                  +'<option value="1">BDO</option>'
                  +'<option value="2">RCBC</option>'
                  +'<option value="3">HSBC</option>'
                  +'<option value="4">MAYA</option>'
                +'</select>'
              +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group date-toggle">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'Settlement Date</span>'
              +'<input type="text" class="form-control" id="date" name="date" required placeholder="YYYY-MM-DD" maxlength="8">'
            +'<span class="input-group-addon">'
            +'<span class="glyphicon glyphicon-calendar"></span>'
            +'</span>'
            +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group time-toogle">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'Settlement Time</span>'
              +'<input type="text" class="form-control" id="time2" name="time" required placeholder="HH:MM:SS" maxlength="8">'
            +'<span class="input-group-addon">'
            +'<span class="glyphicon glyphicon-time"></span>'
            +'</span>'
            +'</div>'
            +'</div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'<span class="gly gly-money"></span> Grand Total</span>'
              +'<input type="text" class="form-control" id="amount" name="amount" required style="text-align: right;" placeholder="0.00">'
            +'</div></div>';
        alertMessage($('#nav-action'), 'warning', '<b>Tips:</b> <ol>'
          +'<li>This facility is intended only to those branches with credit card terminal, and must start uploading on September 1, 2018.</li>'
          +'<li>Scan and save your Card Settlement Slip in this following filename format '
          +' "<b style="color:red;">SETSLP SGD 20180901 BDO</b>" where <b>SETSLP</b> - is the document code, <b>SGD</b> - is the 3 char branch code, '
          +'<b>20180901</b> - is the Settlement Date in YYYYMMDD format and <b>BDO</b> - is the credit card teminal.</li>'
          +'<li> Make sure that the <b>Settlement Date/Time</b> encoded is the <b style="color:red;">Date/Time on the Card Settlement Slip</b> and not the current or computer\'s date and time.</li>'
          +'<li>For those branches that have <b>multiple terminal or multiple terminal with the same bank/provider</b>, the file naming should be  <ul><li><b>SETSLP SGD 20180901 BDO</b> or</li><li> <b>SETSLP SGD 20180901 BDO CREDIT</b> or</li><li> <b>SETSLP SGD 20180901 BDO DEBIT</b> or</li><li><b>SETSLP SGD 20180901 RCBC</b> or</li><li><b>SETSLP SGD 20180901 HSBC</b></li></ul> and <b style="color:red;">must scan and uploaded separately (one-to-one 1:1)</b>.'
          +'</li>'
          +'<li>Please scan only the <b>actual size</b> of each Card Settlement Slip to reduce the file size.</li>'
          +'</ol>'
          +'<small><em class="text-muted">For more questions / clarifications / need assistance you can call us at <a href="tel:+639993330386">0999 333 0386</a></em></small>');

      } else if ($(this).val()==='backup') {
        html += '<div class="row" style="margin-top: 20px;"><span class="title">Choose Backup Type</span>'
            +'<div class="radio">'
              +'<label>'
                +'<input type="radio" name="backup_type" id="backup_pos" value="pos" <?=app()->environment()==='local'?'checked':'checked'?>>'
                +'<strong><span class="fa fa-file-archive-o"></span> POS EoD Backup</strong> <small><em>(This will be saved on server and will (<span class="glyphicon glyphicon-ok"></span>) process all transactions.)</em></small>'
              +'</label>'
            +'</div>'
            /*
            +'<div class="radio">'
              +'<label>'
                +'<input type="radio" name="backup_type" id="backup_hr" value="hr">'
                +'<strong><span class="gly gly-address-book"></span> POS Payroll Backup</strong> <small><em>(This will be emailed to HR but will (<span class="glyphicon glyphicon-remove"></span>) not process transactions.)</em></small>'
              +'</label>'
            +'</div>'
            +'<div class="radio">'
              +'<label>'
                +'<input type="radio" name="backup_type" id="backup_payroll" value="payroll">'
                +'<strong><span class="fa fa-file-powerpoint-o"></span> GI PAY Payroll Backup</strong> <small><em>(Backup generated from GI PAY. e.g. PR031517.ZIP)</em></small>'
              +'</label>'
            +'</div>'; */
            +'</div>';
      } else if ($(this).val()==='exportreq-etrf') {
        html +='<div class="row" style="margin-top: 20px;">'
                +'<div class="input-group">'
                  +'<button id="attach-etrf" class="btn btn-default" style="background-color: #188038; color: #fff;" type="button" title="Attach Employee Transfer Request Form">'
                    +'<span class="glyphicon glyphicon-paperclip"></span> Attach Employee Transfer Request Form'
                   +'</button></div></div>'
                  +'<div class="row" style="margin-top: 20px;">'
                    +'<div class="input-group">'
                      +'<span class="input-group-addon" id="basic-addon1">'
                        +'<span class="glyphicon glyphicon-file"></span> ERTF Filename</span>'
                      +'<input type="text" class="form-control" id="etrf-filename" name="etrf-filename" readonly="" required="">'
                    +'</div></div>';
      } else if ($(this).val()==='ap') {
        html += '' 
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Document Type</span>'
                +'<input type="text" class="form-control" id="search-doctype" name="doctype" placeholder="example: Delivery Receipt, Sales Invoice, Stock Transfer" required>'
                +'<input type="hidden" id="doctypeid" name="doctypeid">'
                +'<span class="input-group-addon">'
                +'<span class="glyphicon glyphicon-search"></span>'
                +'</span>'
              +'</div></div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Invoice or Reference No.</span>'
                +'<input type="text" class="form-control" id="refno" name="refno" required>'
              +'</div></div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Pay Type</span>'
                +'<select id="ap-paytype" name="type" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>'
                  +'<option value="" disabled selected>-- select deposit type --</option>'
                  +'<option value="1">Cash (C)</option>'
                  +'<option value="2">Cheque (K)</option>'
                  +'<option value="3">Unpaid (U)</option>'
                  +'<option value="4">Head Office (H)</option>'
                +'</select>'
              +'</div></div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group date-toggle">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'<span id="ap-datetype"></span>Transmittal Date</span>'
                +'<input type="text" class="form-control" id="date" name="date" required placeholder="YYYY-MM-DD" maxlength="8">'
                +'<span class="input-group-addon">'
                +'<span class="glyphicon glyphicon-calendar"></span>'
                +'</span>'
              +'</div></div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
                +'<span class="input-group-addon" id="basic-addon1">'
                +'Supplier</span>'
                +'<input type="text" class="form-control" id="search-supplier" name="supplier" placeholder="Search for Supplier" required>'
                +'<input type="hidden" id="supplierid" name="supplierid">'
                +'<span class="input-group-addon">'
                +'<span class="glyphicon glyphicon-search"></span>'
                +'</span>'
              +'</div></div>'
            +'<div class="row" style="margin-top: 20px;">'
              +'<div class="input-group">'
              +'<span class="input-group-addon" id="basic-addon1">'
                +'Total Amount</span>'
              +'<input type="text" class="form-control" id="amount" name="amount" required style="text-align: right;" placeholder="0.00">'
            +'</div></div>';

            alertMessage($('#nav-action'), 'warning', '<b>Tips:</b> <ol><li>When encoding of expenses on GI POS (6 1), please separate each receipt upon encoding.'
          +' One (1) invoice or (1) delivery receipt = one (1) entry. Make sure to sum up each receipt.'
          +'</li><li> Make sure that the scanned <b>Sales Invoice (INV), Delivery Receipt (DR), Stock Transfer (STOCK), Statement of Accounts (SOA), Space Leased (SLEASED), </b>etc.'
          +' must scan alongside with the <b>Stock Purchase - Transmittal Slip</b>. Do not include the Receiving Reports (RR).</li>'
          +'<li><span style="color:red;">The scanned page or pages must save into one (1) PDF file.</span> (Smallest file size but readable)</li>'
          +'<li>'
          +'<div>Here are the sample scanned documents. (Click each filename to view sample scanned documents)</div>'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/DR 007 20200611 K 0025219.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b>DR 007 20200611 K 0025219.pdf, </b></a> &nbsp;&nbsp;'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/DR RDE 20200627 K 37757.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b>DR RDE 20200627 K 37757.pdf, </b></a> &nbsp;&nbsp;'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/INV 687 20200626 K 0020271.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b>INV 687 20200626 K 0020271.pdf, </b></a> &nbsp;&nbsp;'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/INV NEO 20200624 K 0000159.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b>INV NEO 20200624 K 0000159.pdf, </b></a> &nbsp;&nbsp;'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/SOA 100 20200612 K 5240.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b>SOA 100 20200612 K 5240.pdf, </b></a> &nbsp;&nbsp;'
          +'<a href="javascript:void(0)" target="popup" onclick="window.open(\'http://cashier.giligansrestaurant.com/images/ap-samples/STRANS NPC 20200603 K ST060120.png\', \'_blank\', \'width=auto,height=auto\'); return false"><b> STRANS NPC 20200603 K ST060120.pdf </b></a> &nbsp;&nbsp;'
          +'</li>'
          +'</ol>'
          +'<small><em class="text-muted">For more questions / clarifications / need assistance you can call us at <a href="tel:+639993330386">0999 333 0386</a></em></small>');
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
       //$('.time-toogle').datetimepicker({
        $('#time').datetimepicker({
          format: 'hh:mm:ss A',
          ignoreReadonly: true,
        }).on('dp.show', function(e) {
          $('#time').val(e.date.format('hh:mm:ss'));
        })
        .on('dp.hide', function(e) {
          $('#time').val(e.date.format('HH:mm:ss'));
        });
      }

      if ($('#time2')[0]!==undefined) {
       //$('.time-toogle').datetimepicker({
        $('#time2').datetimepicker({
          format: 'hh:mm:ss',
          ignoreReadonly: true,
        })
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
    };

    $('.filetype-result').on('click', '[name="backup_type"]', function() {
      $.inArray($(this).val(), ['pos', 'payroll']);
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


    $('.filetype-result').on('click', '#attach-etrf', function() {
      $('#file_upload').data('input-value', 'etrf-filename');
      $('#file_upload').click();
    });

    // $('.filetype-result').on('change', '#ap-paytype', function(e){
    //   if($(this).val()==1) 
    //     $('#ap-datetype').text('Transaction ');
    //   else if($(this).val()==2)
    //     $('#ap-datetype').text('Billing\\Statement ');
    //   else
    //     $('#ap-datetype').text('');
    // });

    $('.filetype-result').on('keypress', '#search-doctype', function() {
      $(this).autocomplete({
        source: function(request, response) {
          console.log(request);
          $.ajax({
            type: 'GET',
            url: "/api/s/doctype",
            dataType: "json",
            data: {
              maxRows: 25,
              q: request.term
            },
            success: function(data) {
              response($.map(data, function(item) {
                console.log(item);
                return {
                  label: item.code+' - '+item.descriptor,
                  value: item.descriptor,
                  id: item.id
                }
              }));
            }
          });
        },
        minLength: 1,
        select: function(event, ui) {
          //console.log(ui);
          //log( ui.item ? "Selected: " + ui.item.label : "Nothing selected, input was " + this.value);
          $("#doctypeid").val(ui.item.id); /* set the selected id */
        },
        open: function() {
          $( this ).removeClass("ui-corner-all").addClass("ui-corner-top");
          $("#doctypeid").val(''); /* set the selected id */
        },
        close: function() {
            $( this ).removeClass("ui-corner-top").addClass("ui-corner-all");
        },
        messages: {
          noResults: '',
          results: function() {}
        }
      }).on('blur', function(e){
        if ($(this).val().length==0) {
          $( this ).removeClass("ui-corner-all").addClass("ui-corner-top");
          $("#doctypeid").val(''); /* set the selected id */
        }
        //setTimeout(submitForm, 1000);
      });
    });

    $('.filetype-result').on('keypress', '#search-supplier', function() {
      $(this).autocomplete({
        source: function(request, response) {
          console.log(request);
          $.ajax({
            type: 'GET',
            url: "/api/s/supplier",
            dataType: "json",
            data: {
              maxRows: 25,
              q: request.term
            },
            success: function(data) {
              response($.map(data, function(item) {
                console.log(item);
                return {
                  label: item.code+' - '+item.descriptor,
                  value: item.descriptor,
                  id: item.id
                }
              }));
            }
          });
        },
        minLength: 1,
        select: function(event, ui) {
          $("#supplierid").val(ui.item.id); /* set the selected id */
        },
        open: function() {
          $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
          $("#supplierid").val(''); /* set the selected id */
        },
        close: function() {
          $(this).removeClass("ui-corner-top").addClass("ui-corner-all");
        },
        messages: {
          noResults: '',
          results: function() {}
        }
      }).on('blur', function(e){
        if ($(this).val().length==0) {
          $(this).removeClass("ui-corner-all").addClass("ui-corner-top");
          $("#supplierid").val(''); /* set the selected id */
        }
        //setTimeout(submitForm, 1000);
      });
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

 @if(app()->environment()==='productions')
<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
@endif
@endsection
