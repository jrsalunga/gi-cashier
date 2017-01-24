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
            
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-calendar-check-o"></span>
                <span class="hidden-xs hidden-sm">Checklist</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups/checklist"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp/checklist"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>

            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="glyphicon glyphicon-th-list"></span>
                <span class="hidden-xs hidden-sm">Logs</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups/log"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp/log"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>
            
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/{{brcode()}}/uploader" class="btn btn-default">
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
          <td class="text-center"><span class="glyphicon glyphicon-{{ $backup->processed == '1' ? 'ok':'remove' }}"></span></td>
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
@endsection


@section('js-external')
  @parent

@endsection
