@extends('index')

@section('title', '- Upload Backup')

@section('body-class', 'generate-dtr')

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
            </a> 
            <a href="/backups/log" class="btn btn-default">
              <span class="glyphicon glyphicon-th-list"></span>
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
            </button>
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
            {!! Form::open(['method'=>'PUT', 'url'=>'upload/postfile', 'class'=>'form-horizontal', 'enctype'=>'multipart/form-data']) !!}
            <div class="dropbox-container">
              <div id="dropbox" class="prod-image">
                <span class="message">Drop images here to upload. <br />
                <i>(they will only be visible to you)</i>
                </span>
              </div>
              <!--
              <label for="file_upload" class="lbl-file_upload">Upload</label> 
              -->
              <input type="file" id="file_upload" style="display: none" />

              <div class="row">
                <div class="col-lg-12">
                  <div class="input-group">
                    <span class="input-group-btn">
                      <input type="hidden"  name="year" id="year" value="{{ now('Y') }}">
                      <input type="hidden"  name="month" id="month" value="{{ pad(now('M'),2) }}">
                      
                      <input type="hidden"  name="lat" id="lat">
                      <input type="hidden"  name="lng" id="lng">
                      <button id="attached" class="btn btn-default" type="button" title="Attach file">
                        <span class="glyphicon glyphicon-paperclip"></span>
                      </button>
                    
                    </span>
                    <input type="text" class="form-control" id="filename" name="filename" readonly required>
                  </div><!-- /input-group -->
                </div><!-- /.col-lg-6 -->
              </div>
              <div class="row" style="margin-top: 10px;">
                <div class="col-lg-12">
                  
                  <textarea class="form-control" id="notes" name="notes" placeholder="Notes: 2016-03-23 backup file submitted by cashier Anna" style="max-width:100%;"></textarea>
                </div><!-- /.col-lg-6 -->
              </div>
              <div class="row" style="margin-top: 10px;">
                <div class="col-lg-12">
                  
                  <button id="btn-upload" class="btn btn-primary" type="submit" disabled="disabled">Submit</button>
                  <a class="btn btn-default" href="/backups/upload">Cancel</a>  
                </div><!-- /.col-lg-6 -->
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
  <script async defer src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBjNiaRtUU5cE7G2IcIYVGm5vxyNDzh6ws&signed_in=true&callback=findMyGeo"></script>

  <script type="text/javascript">

  var findMyGeo = function() {

    var response = {};
    var output = document.getElementsByClassName('geo-callback-message')[0];

    if (!navigator.geolocation){
      output.innerHTML = '<div class="alert alert-danger alert-important" role="alert">Geolocation is not supported by your browser. Please use Google Chrome</div>';
      return;
    }

    function success(position) {
      var latitude  = position.coords.latitude;
      var longitude = position.coords.longitude;
      $('#lat').val(latitude);
      $('#lng').val(longitude);
      //output.innerHTML = 'Latitude is ' + latitude + '° <br>Longitude is ' + longitude + '°';
      //output.innerHTML = '<div class="alert alert-success alert-important" role="alert">Latitude is ' + latitude + '° <br>Longitude is ' + longitude + '°</div>';
      output.innerHTML = '';
    };

    function error() {
      output.innerHTML = '<div class="alert alert-warning alert-important" role="alert">Unable to retrieve your location. Kindly refresh and allow the browser\'s location notification.</div>';
    };

    //output.innerHTML = '<div class="alert alert-warning alert-important" role="alert">Loading...</div>';                                  

    var opt = {
      //enableHighAccuracy: true,
      //timeout: 5000,
      //maximumAge: 0
    }

    navigator.geolocation.getCurrentPosition(success, error, opt);
  }



  $('#attached').on('click', function(){
      //console.log($('.lbl-file_upload'));
      $('#file_upload').click();
    });
  $(document).ready(function(){
    
  });
  </script>
@endsection
