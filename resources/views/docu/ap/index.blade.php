@extends('index')

@section('title', '- AP Logs')

@section('body-class', 'ap-logs')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/ap/log">Payables</a></li>
    <li class="active">Logs</li>
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

    <div class="table-responsives">
    <table class="table table-striped table-hover" style="margin-top: 0;">
      <thead>
        <tr>
          <th>Date</th>          
          <th>Filename</th>          
          <th>File Count</th>          
        </tr>
      </thead>
      <tbody>
      @foreach($aps as $ap)
        <tr>
          <td>
            {{ $ap->uploaddate->format('D M j, Y') }}
          </td>
          <td style="min-width: 5%;">
            
            <div class="btn-group">
              <a href="/{{brcode()}}/ap/{{$ap->uploaddate->format('Y/m/d')}}">
                {{ $ap->filename }}*
              </a>
              <a class="dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="box-shadow: none; cursor: pointer;">
                <span class="caret"></span>
              </a>
              <?php
                $files = explode(',', $ap->system_remarks);
              ?>
              <ul class="dropdown-menu">
                @foreach($files as $file)
                <li>
                  <a href="/dl/ap/AP/{{$ap->uploaddate->format('Y')}}/{{strtoupper(brcode())}}/{{$ap->uploaddate->format('m/d')}}/{{ $ap->filename }}{{ $file }}?m=log">{{ $ap->filename }}{{ $file }} <span class="glyphicon glyphicon-download-alt pull-right"></span></a>
                </li>
                @endforeach
              </ul>
            </div>
          
          
          </td>
          <td>{{ $ap->size }}</td>
        </tr>
      @endforeach
      </tbody>
    </table>
    </div>
   
    
    {!! $aps->render() !!}
     
  </div>
</div><!-- end container-fluid -->
 
 @if(app()->environment()==='production')
 <div class="row">
  <div class="col-sm-6">
    <ins class="adsbygoogle" style="display:block" data-ad-client="ca-pub-9897737241100378" data-ad-slot="4574225996" data-ad-format="auto"></ins>
  </div>
 </div>
@endif

@endsection






@section('js-external')
  @parent

   <script type="text/javascript">
     $(function () {
      $('[data-toggle="tooltip"]').tooltip();
    });
   </script>

<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- gi- -->

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>

@endsection
