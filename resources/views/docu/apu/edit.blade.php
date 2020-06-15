@extends('index')

@section('title', '- Account Payable Upload Slip')

@section('body-class', 'apu-edit')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/apu/log">Payables</a></li>
    <li><a href="/{{brcode()}}/apu/{{$apu->lid()}}">{{ short_filename($apu->fileUpload->filename) }}</a></li>
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
        {!! Form::open(['method'=>'PUT', 'url'=>'put/apu', 'id'=>'form-file', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
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
            <h4><span class="gly gly-cloud"></span> <small>{{ $apu->filename }} <small>(filename on server)</small></small></small></h4>

            <div class="row" style="margin-top: 20px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">Document Type</span>
                  <input type="text" class="form-control" id="search-doctype" name="doctype" value="{{ $apu->doctype->descriptor }}" required>
                </div>
                <input type="hidden" id="doctypeid" name="doctypeid" value="{{ $apu->doctype_id }}">
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">Supplier Ref / Invoice No</span>
                  <input type="text" class="form-control" id="refno" name="refno" value="{{ $apu->refno }}" required>
                </div>
              </div>
            </div>

            <!-- <div class="row"style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="gly gly-money"></span> Amount
                  </span>
                  <input type="text" class="form-control" id="amount" name="amount" required style="text-align: right;" value="{{ number_format($apu->amount,2) }}">
                </div>
              </div>
            </div> -->

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
              <div class="input-group">
                <span class="input-group-addon" id="basic-addon1">Pay Type</span>
                 <select id="type" name="type" class="form-control" style="width: 100%; border-left: 1px solid #ccc;" required>
                  <option value="" disabled selected>-- select deposit type --</option>
                  <option value="1" <?=$apu->type==1?'selected':''?> >Cash (C)</option>
                  <option value="2" <?=$apu->type==2?'selected':''?> >Cheque (K)</option>
                </select>
              </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group date-toggle">
                  <span class="input-group-addon" id="basic-addon1">Date</span>
                  <input type="text" class="form-control" id="date" name="date" required="" value="{{$apu->date->format('Y-m-d')}}" maxlength="8">
                  <span class="input-group-addon">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </span>
                </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">Total Amount</span>
                  <input type="text" class="form-control text-right" id="amount" name="amount" value="{{ number_format($apu->amount,2) }}" required>
                </div>
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">Supplier</span>
                  <input type="text" class="form-control" id="search-supplier" name="supplier" value="{{ $apu->supplier->descriptor }}" required>
                </div>
                <input type="hidden" id="supplierid" name="supplierid" value="{{ $apu->supplier_id }}">
              </div>
            </div>

            <div class="row" style="margin-top: 15px;">
              <div class="col-lg-12">
                <div class="input-group">
                  <span class="input-group-addon" id="basic-addon1">
                    <span class="glyphicon glyphicon-user"></span> Cashier
                  </span>
                  <input type="text" class="form-control" id="cashier" name="cashier" value="{{$apu->cashier}}" required placeholder="Anna (cashier's name only, required)" maxlength="20" title="Please put cashiers name only">
                </div><!-- /input-group -->
              </div><!-- /.col-lg-10 -->
            </div>

            <div class="row container-note" style="margin-top: 15px;">
              <div class="col-lg-12">
                <textarea class="form-control" id="notes" name="notes" placeholder="Notes: (optional)" maxlength="300">{{ $apu->remarks }}</textarea>
              </div><!-- /.col-lg-12 -->
            </div>
            
            <h6><span class="glyphicon glyphicon-cloud-upload"></span> {{ $apu->created_at->format('D M j h:i:s A') }} <small>{{ diffForHumans($apu->created_at) }}</small></h6>
          </div>
          <div class="panel-footer">
            @if($apu->verified || $apu->matched)
              <a href="/{{brcode()}}/apu/log" class="btn btn-link"><span class="gly gly-unshare"></span> Back</a>
            @else
              <input type="hidden" name="id" value="{{$apu->id}}">
              <button type="submit" id="btn-save" data-loading-text="Saving..." class="btn btn-primary" autocomplete="off">Save</button>
              <a href="/{{brcode()}}/apu/{{$apu->lid()}}" class="btn btn-link">Cancel</a>
            @endif
          </div>
        </div><!-- end: .panel -->
        {!! Form::close() !!}
      </div>
      <div class="col-md-6">
        <?php
          $src = '/'.brcode().'/images/apu/'.$apu->lid().'.'.strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION));
        ?>
        @if(strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION))==='pdf')
          <iframe style="width: 100%; height: 500px;" src="/{{brcode()}}/images/apu/{{$apu->lid()}}.{{strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION))}}"></iframe>
        @else
        <a href="{{$src}}" target="_blank">
          <img class="img-responsive" src="/{{brcode()}}/images/apu/{{$apu->lid()}}.{{strtolower(pathinfo($apu->filename, PATHINFO_EXTENSION))}}">
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

    $('#btn-save').on('click', function () {
      var $btn = $(this).button('loading')
    })

    $('#search-doctype').autocomplete({
      source: function(request, response) {
        $("#doctypeid").val('');
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
      minLength: 2,
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
      console.log('fdsafafasdf');
      //setTimeout(submitForm, 1000);
    });

    $('#search-supplier').autocomplete({
      source: function(request, response) {
        $("#supplierid").val('');
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
      minLength: 2,
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
  </script>

<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>

@endsection
