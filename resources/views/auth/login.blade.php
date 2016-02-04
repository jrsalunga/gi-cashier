<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/> 
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>Giligan's Restaurant @yield('title')</title>

  <link rel="shortcut icon" type="image/x-icon" href="/images/favicon.ico" /> 
  <link rel="stylesheet" href="/css/styles-all.min.css">


</head>
<body class="@yield('body-class')">
<!-- Fixed navbar -->
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      
      
    
      <a class="navbar-brand" href="/">
        <img src="/images/giligans-header.png" class="img-responsive header-logo">
      </a>
      <p class="navbar-text" style="font-size: 20px; margin: 11px 0px 11px -10px; color: #d6e9c6;">
        <em>Beta</em></p>
    </div>
    
  </div>
</nav>

{{--  $errors->first('email') --}}
{{--  $errors->first('username') --}}
  <div class="div-signin">
    <div>
      <img class="center-block img-signin img-circle img-responsive" src="/images/login-avatar.png">
    </div>
    
      {!! Form::open(['url' => 'login', 'accept-charset'=>'utf-8', 'class'=>'form-signin']) !!}
    


      <label class="sr-only" for="inputEmail">Username</label>
      <input id="inputEmail" class="form-control" type="text" 
      <?php 

        echo !empty(old('email')) ? 'value="'.old('email').'"' : 'autofocus=""';

      ?> required="" placeholder="Username" name="email">

      <label class="sr-only" for="inputPassword">Password</label>
      @if($errors->has('email'))
        <div class="has-error">
        <input id="inputPassword" class="form-control" type="password" required="" autofocus="" placeholder="Password" name="password">
        <p class="text-danger">username or password you entered is incorrect.</p>
        </div>
      @else
        <input id="inputPassword" class="form-control" type="password" required="" placeholder="Password" name="password">
      @endif

      <button class="btn btn-lg btn-primary btn-block" type="submit">Sign in</button>
      {!! Form::close() !!}
  </div>



