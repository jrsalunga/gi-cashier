@extends('index')

@section('title', '- Backups History')

@section('body-class', 'generate-dtr')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li>Backups</li>
    <li class="active">History</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          <div class="btn-group" role="group">
            <a href="/dashboard" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span>
            </a> 
            <!--
            <a href="/backups/history" class="btn btn-default">
              <span class="glyphicon glyphicon-th-list"></span>
            </a>
            -->
            <button type="button" class="btn btn-default active">
              <span class="glyphicon glyphicon-th-list"></span>
            </button>
            
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/backups/upload" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    
   <table class="table">
      <thead>
        <tr>
          @if($all)
            <th>Br Code</th>
          @endif
          <th>Filename</th><th>Upload Date</th>
          <th class="hidden-xs hidden-sm">Processed</th>
          <th class="hidden-xs">Remarks</th>
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
          <td>{{ $backup->uploaddate->format('m/d/Y h:i A') }} <em><small>({{ diffForHumans($backup->uploaddate) }})</small></em></td>
          <td class="text-center hidden-xs hidden-sm"><span class="glyphicon glyphicon-{{ $backup->processed == '1' ? 'ok':'remove' }}"></span></td>
          <?php  $x = explode(':', $backup->remarks) ?>
          <td class="hidden-xs">{{ $x['1'] }} </td>
          <td class="hidden-xs hidden-sm">{{ $backup->terminal }} </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    
   
      {!! $backups->render() !!}
     

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  
  <script>
  
    
 
  </script>
@endsection
