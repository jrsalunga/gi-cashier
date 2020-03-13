@extends('index')

@section('title', '- Backups History')

@section('body-class', 'generate-dtr')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/backups">Backups</a></li>
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

    <div class="table-responsive">
    <table class="table table-striped table-hover">
      <thead>
        <tr>
          @if($all)
            <th>Br Code</th>
          @endif
          <th>Filename</th>
          <th>Uploaded</th>
          <th>Cashier</th>
          <th class="text-center">Status</th>
          <th class="text-center">Kitchen Log</th>
          <th>Remarks</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        @foreach($backups as $backup)
        <tr>
          @if($all)
            <td title="{{ $backup->branch->descriptor }}">{{ $backup->branch->code }}</td>
          @endif
          <td>{{ $backup->filename }} </td>
          <td title="{{ $backup->uploaddate->format('D m/d/Y h:i A') }}">
            <span class="hidden-xs">
              @if($backup->uploaddate->format('Y-m-d')==now())
                {{ $backup->uploaddate->format('h:i A') }}
              @else
                {{ $backup->uploaddate->format('D M j') }}
              @endif
            </span> 
            <em>
              <small class="text-muted">
              {{ diffForHumans($backup->uploaddate) }}
              </small>
            </em>
          </td>
          <td>{{ $backup->cashier }} </td>
          <td class="text-center">
            @if($backup->processed=='0')
              <span class="glyphicon glyphicon-remove"></span>
            @elseif($backup->processed=='1')
              <span class="glyphicon glyphicon-ok"></span>
            @elseif($backup->processed=='2')
              <span class="fa fa-envelope-o" title="Sent to HR" data-toggle="tooltip"></span>
            @else

            @endif
          </td>
          <td class="text-center">
            @if($backup->kitlog=='0')
              <span class="glyphicon glyphicon-remove"></span>
            @elseif($backup->kitlog=='1')
              <span class="glyphicon glyphicon-ok"></span>
            @else

            @endif
          </td>
          <?php  $x = explode(':', $backup->remarks) ?>
          <td>
            @if($backup->remarks)
              {{ $backup->remarks }} 
            @else

              @if($backup->lat == '1')
                <span class="fa fa-file-archive-o" title="POS Backup"></span>
                POS Backup
              @endif

              @if($backup->long == '1')
                <span class="gly gly-address-book" title="Payroll Backup"></span>
                Payroll Backup
              @endif

              @if($backup->long == '2')
                <span class="fa fa-file-powerpoint-o" title="GI PAY Payroll Backup"></span>
                GI PAY Payroll Backup
              @endif
            @endif
          </td>
          <td>
            {{ $backup->terminal }} 
            <!--
            <a href="https://www.google.com/maps/search/{{$backup->lat}},{{$backup->long}}/{{urldecode('%40')}}{{$backup->lat}},{{$backup->long}},18z" target="_blank"></a>
            -->
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    
    {!! $backups->render() !!}
     
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


<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
<!-- gi- -->

<script>
(adsbygoogle = window.adsbygoogle || []).push({});
</script>
@endsection
