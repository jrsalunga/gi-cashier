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
          <div class="btn-group" role="group">
            <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span>
              <span class="hidden-xs hidden-sm">Back</span>
            </a> 
            <a href="/backups" class="btn btn-default">
              <span class="fa fa-archive"></span>
              <span class="hidden-xs hidden-sm">Filing System</span>
            </a>
            <a href="/backups/checklist" class="btn btn-default">
              <span class="fa fa-calendar-check-o"></span>
              <span class="hidden-xs hidden-sm">Checklist</span>
            </a>
            <button type="button" class="btn btn-default active">
              <span class="glyphicon glyphicon-th-list"></span>
              <span class="hidden-xs hidden-sm">Logs</span>
            </button>
            
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/uploader" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span>
              <span class="hidden-xs hidden-sm">DropBox</span>
            </a>
          </div>
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
          <th class="">Cashier</th>
          <th class="">Processed</th>
          <th class="hidden-xs hidden-sm">Remarks</th>
          <th class="hidden-xs hidden-sm">IP Address</th>
        </tr>
      </thead>
      <tbody>
        @foreach($backups as $backup)
        <tr>
          @if($all)
            <td title="{{ $backup->branch->descriptor }}">{{ $backup->branch->code }}</td>
          @endif
          <td>{{ $backup->filename }} </td>
          <td>
            <span class="hidden-xs">
              @if($backup->uploaddate->format('Y-m-d')==now())
                {{ $backup->uploaddate->format('h:i A') }}
              @else
                {{ $backup->uploaddate->format('m/d/Y h:i A') }}
              @endif
            </span> 
            <em>
              <small title="{{ $backup->uploaddate->format('m/d/Y h:i A') }}">
              {{ diffForHumans($backup->uploaddate) }}
              </small>
            </em>
          </td>
          <td>{{ $backup->cashier }} </td>
          <td class="text-center"><span class="glyphicon glyphicon-{{ $backup->processed == '1' ? 'ok':'remove' }}"></span></td>
          <?php  $x = explode(':', $backup->remarks) ?>
          <td class="hidden-xs hidden-sm">{{ $backup->remarks }} </td>
          <td class="hidden-xs hidden-sm">
              {{ $backup->terminal }} 
              @if($backup->lat == '1')
                <span class="gly gly-certificate"></span>
              @endif

              @if($backup->long == '1')
                <span class="gly gly-address-book"></span>
              @endif
            <!--
            <a href="https://www.google.com/maps/search/{{$backup->lat}},{{$backup->long}}/{{urldecode('%40')}}{{$backup->lat}},{{$backup->long}},18z" target="_blank">
            </a>
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
@endsection


@section('js-external')
  @parent
  
  <script>
  
    
 
  </script>
@endsection
