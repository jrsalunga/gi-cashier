@extends('master')

@section('title', '- Depslip Storage')

@section('body-class', 'depslp-storage')

@section('navbar-2')
<ul class="nav navbar-nav navbar-right"> 
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
      <span class="glyphicon glyphicon-menu-hamburger"></span>
    </a>
    <ul class="dropdown-menu">
      <li><a href="/settings"><span class="glyphicon glyphicon-cog"></span> Settings</a></li>
      <li><a href="/logout"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>     
    </ul>
  </li>
</ul>
<p class="navbar-text navbar-right">{{ $name }}</p>
@endsection

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    
    @if(count($data['breadcrumbs'])>0)
      <?php 
        $temp = $data['breadcrumbs'];
        array_shift($temp) 
      ?>
     
      @foreach($temp as $path => $folder)
        <li><a href="/{{brcode()}}{{ strtolower($path) }}">{{ $folder }}</a></li>
      @endforeach
      <li class="active">{{ $data['folderName'] }}</li>
    @else 
      <li class="active">Filing System</li>
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
            <div class="btn-group">
              <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="fa fa-archive"></span> 
                <span class="hidden-xs hidden-sm">Filing System</span>
                <span class="caret"></span>
              </button>
              <ul class="dropdown-menu">
                <li><a href="/backups"><span class="fa fa-file-archive-o"></span> Backup</a></li>
                <li><a href="/{{brcode()}}/depslp"><span class="fa fa-bank"></span> Deposit Slip</a></li>
              </ul>
            </div>
            
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
          <!--
          <div class="btn-group" role="group">
            <a href="/backups/upload" class="btn btn-default">
              <span class="glyphicon glyphicon-cloud-upload"></span> DropBox
            </a>
          </div>
        -->
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    
    <div>
    <!-- Nav tabs -->
    <ul class="nav nav-tabs" role="tablist">
      <li role="presentation" class="active">
        <a href="/{{brcode()}}/depslp" aria-controls="pos" role="tab">
          Deposit Slip Archive
        </a>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="file-explorer tab-content">
      <div role="tabpanel" class="tab-pane active" >
          

        <div style="height: 10px;"></div>
        <div class="navbar-form"  style="padding:0; margin: 8px 15px; border-top: 0;">
        @if(count($data['breadcrumbs'])>1)
        <a href="/{{brcode()}}{{ strtolower(endKey($data['breadcrumbs'])) }}" class="btn btn-default" title="Back">
          <span class="gly gly-unshare"></span>
          <span class="gly gly-hdd"></span>{{ endKey($data['breadcrumbs']) }}
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

        <table id="tb-backups" class="table table-hover">
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
              <td colspan="4"><a href="/{{brcode()}}{{ strtolower($data['folder']) }}/{{ strtolower($folder) }}"><span class="fa fa-folder-o"></span> {{ $folder }}</a></td>
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
                @elseif($file['type']=='file')
                  <span class="fa fa-file-pdf-o"></span>
                @else
                  <span class="fa file-o"></span>

                @endif 

                {{ $file['name'] }}</td>
                
                
                
                <td>{{ human_filesize($file['size']) }}</td>
                <td class="hidden-xs hidden-sm">{{ $file['type'] or 'Unknown' }}</td>
                <td class="hidden-xs">{{ $file['modified']->format('D, M j, Y g:i A') }}</td>
            </tr>
            @endforeach
          @endif
          </tbody>
        </table>
      </div>
    </div>

  </div>   

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  <script src="/js/vendors-common.min.js"></script>
  
  <script>
  
    
 
  </script>
@endsection
