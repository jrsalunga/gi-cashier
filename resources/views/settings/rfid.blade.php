@extends('index')

@section('title', ' - Password')

@section('css-external')
<link rel="stylesheet" href="/css/jquery-ui.css">
@endsection

@section('container-body')
<div class="container-fluid">
	
  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li class="active">Settings</li>
  </ol>
	<hr>
  <div class="row">
  	<div class="col-sm-3">
  		<ul class="nav nav-pills nav-stacked">
  			<li role="presentation"><a href="/settings">Profile</a></li>
        <li role="presentation"><a href="/settings/password">Change Password</a></li>
			  <li role="presentation" class="active"><a href="/settings/rfid">RFID Assignment</a></li>
			</ul>
  	</div>
  	<div class="col-sm-9">

  		<h4>RFID Assignment</h4>
      <hr>

      @include('_partials.alerts')
      
      {!! Form::open() !!}
        <div class="form-group">
          <label for="search-employee">Search Employee</label>
          <input type="text" class="form-control" id="search-employee" placeholder="Search Employee" maxlength="50">
          <input type="hidden" id="employeeid" name="employeeid">
        </div>
        <div class="log">
        </div>
        
        
      {!! Form::close()  !!} 
  	</div>

  </div>



</div>
@endsection

@section('js-external')
  @parent

  <script>

  function getHtmlRfid(){
    return '<div class="form-group">'
          +'<label for="rfid">RFID</label>'
          +'<input type="text" class="form-control" id="rfid" name="rfid" placeholder="RFID" maxlength="15" required>'
          +'</div><button type="submit" class="btn btn-primary">Submit</button><a href="#" id="btn-discard" class="btn btn-link">Discard</a>';
  }

  function employeeSearch(){
   
    $("#search-employee").autocomplete({
      
      source: function( request, response ) {
        $('.log').html('');
        $.when(
          $.ajax({
              type: 'GET',
              url: "/api/search/employee",
              dataType: "json",
              data: {
                maxRows: 20,
                q: request.term
              },
              success: function( data ) {
                response( $.map( data, function( item ) {
                  return {
                    label: item.code + ' - ' + item.lastname+ ', ' + item.firstname,
                    value: item.lastname+ ', ' + item.firstname,
                    id: item.id,
                    rfid: item.rfid
                  }
                }));
              }
          })
        ).then(function(data){
          console.log(data);
        });
      },
      minLength: 2,
      select: function(e, ui) {     
        $('#employeeid').val(ui.item.id); /* set the selected id */

        var html;
        if (ui.item.rfid==null) {
          html = getHtmlRfid;
        } else {
          html = '<p class="bg-warning"> Current RFID: <strong>'
              +ui.item.rfid
              +'</strong> <small><a href="#" class="lnk-change">change</a></small>'
              +'</p>';
        }
        $('.log').html(html); /* set the selected id */

      },
      open: function() {
        $(this).removeClass('ui-corner-all').addClass('ui-corner-top');
        $('#employeeid').val('');  /* remove the id when change item */
        $('.log').html(''); /* set the selected id */
      },
      close: function() {
        $(this).removeClass('ui-corner-top').addClass('ui-corner-all');
      },
      focus: function (e, ui) {
        $('.ui-helper-hidden-accessible').hide();
      },
      change: function( event, ui ) {

      },
      messages: {
        noResults: '',
        results: function() {}
      }
      
    });
  }

  $(document).ready(function(){
    employeeSearch();

    $(document).delegate('.lnk-change', 'click', function(e) { 
      e.preventDefault();
      $('.log').html(getHtmlRfid);
      $('#rfid').focus();
    });

    $(document).delegate('#btn-discard', 'click', function(e) { 
      e.preventDefault();
      $('form').trigger('reset');
      $('.log').html('');
      $('#search-employee').focus();
    });
    
  });


  </script>



  

@endsection














