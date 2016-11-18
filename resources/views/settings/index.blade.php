@extends('index')

@section('title', ' - Settings')


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
  			<li role="presentation" class="active"><a href="/settings">Profile</a></li>
			  <li role="presentation"><a href="/settings/password">Change Password</a></li>
        <li role="presentation"><a href="/settings/rfid">RFID Assignment</a></li>
			</ul>
  	</div>
  	<div class="col-sm-9">
      
      <h4>Account Information</h4>
      <hr>
      @include('_partials.alerts')
      <form>
        <div class="form-group">
          <label for="username">Fullname</label>
          <input type="text" value="{{ $user->name }}" class="form-control" id="fullname" placeholder="fullname" readonly>
        </div>
        <div class="form-group">
          <label for="email">Email address</label>
          <input type="email" value="{{ $user->email }}" class="form-control" id="email" placeholder="Email" readonly>
        </div>
        <div class="form-group">
          <label for="username">Username</label>
          <input type="text" value="{{ $user->username }}" class="form-control" id="username" placeholder="Username" readonly>
        </div>
        <div class="form-group">
          <label for="username">Branch</label>
          <input type="text" value="{{ $user->branch->descriptor }}" class="form-control" id="branch" placeholder="Branch" readonly>
        </div>
      </form>
  		
  	</div>

  </div>



</div>
@endsection














