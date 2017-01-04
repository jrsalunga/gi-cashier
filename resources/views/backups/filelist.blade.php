@extends('index')

@section('title', '- Backups')

@section('body-class', 'generate-dtr')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    
    @if(count($data['breadcrumbs'])>0)
      <?php 
        $temp = $data['breadcrumbs'];
        array_shift($temp) 
      ?>
      <li><a href="/backups">Backups</a></li>
      @foreach($temp as $path => $folder)
        <li><a href="/backups{{ $path }}">{{ $folder }}</a></li>
      @endforeach
      <li class="active">{{ $data['folderName'] }}</li>
    @else 
      <li class="active">Backups</li>
    @endif
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
            <button type="button" class="btn btn-default active">
              <span class="fa fa-archive"></span>
              <span class="hidden-xs hidden-sm">Filing System</span>
            </button>
            <a href="/backups/checklist" class="btn btn-default">
              <span class="fa fa-calendar-check-o"></span>
              <span class="hidden-xs hidden-sm">Checklist</span>
            </a>
            <a href="/backups/log" class="btn btn-default">
              <span class="glyphicon glyphicon-th-list"></span>
              <span class="hidden-xs hidden-sm">Logs</span>
            </a> 
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

    
    <div>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation" class="<?=($tab==='pos')?'active':''?>">
        <a href="/backups" aria-controls="pos" role="tab">
          POS Backup Archive
        </a>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="file-explorer tab-content">
      <div role="tabpanel" class="tab-pane active" >
          

        <div>&nbsp</div>
        <div class="navbar-form">
        @if(count($data['breadcrumbs'])>0)
        <a href="/backups{{ endKey($data['breadcrumbs']) }}" class="btn btn-default" title="Back">
          <span class="gly gly-unshare"></span>
          backups{{ endKey($data['breadcrumbs']) }}
        </a>
        @else

        <!--
        <button class="btn btn-default" type="button">
          <span class="glyphicon glyphicon-cloud"></span>
          backups
        </button> 
        -->
        @endif
        </div>

        <div class="table-responsive">
        <table id="tb-backups" class="table">
          <!--
          <thead>
            <tr>
              <th>File/Folder</th><th>Size</th><th>Type</th><th>Date Modified</th>
            </tr>
          </thead>
        -->
          <tbody>
          @if(count($data['subfolders'])>0)
            @foreach($data['subfolders'] as $path => $folder)
            <tr>
              <td colspan="4"><a href="/backups{{ $path }}"><span class="fa fa-folder-o"></span> {{ $folder }}</a></td>
            </tr>
            @endforeach
          @endif


          @if(count($data['files'])>0)
            @foreach($data['files'] as $path => $file)
            <tr>
              <td>
                @if($file['type']=='zip')
                  <span class="fa fa-file-archive-o"></span>
                @elseif($file['type']=='img')
                  <span class="fa fa-file-image-o"></span>
                @else
                  <span class="fa file-o"></span>

                @endif 
                <span id="{{$file['name']}}">{{ $file['name'] }}</span>
                </td>
                {{-- 
                <td><a href="/download/{{$tab}}/{{ $file['fullPath'] }}" target="_blank"><span class="glyphicon glyphicon-download-alt"></span></a></td>
                --}}
                <td>{{ human_filesize($file['size']) }}</td>
                <td>{{ $file['mimeType'] or 'Unknown' }}</td>
                <td>{{ $file['modified']->format('j-M-y g:ia') }}</td>
            </tr>
            @endforeach
          @endif
          </tbody>
        </table>
      </div>
      </div>
    </div>

  </div>   

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  
  <script>
  
    
 
  </script>
@endsection
