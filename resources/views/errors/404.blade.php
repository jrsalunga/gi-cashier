@extends('index')

@section('title', '- 404')

@section('body-class', 'error-404')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li class="active">File Not Found!</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          <div class="btn-group" role="group">
            <a href="{{ URL::previous() }}" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span> Back
            </a> 
            <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-shop"></span> Main Menu
            </a> 
          </div> <!-- end btn-grp -->
        </div>
      </div>
    </nav>

    <h1>404: File not found!</h1>
   

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  
@endsection
