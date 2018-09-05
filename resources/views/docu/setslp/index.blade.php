@extends('index')

@section('title', '- Card Settlement Slip Logs')

@section('body-class', 'setslp-logs')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/setslp/log">Card Settlement Slip</a></li>
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

    @if(session('alert-success') || session('setslp.delete')) 
      
    @else
      
    @endif

    <div class="table-responsive">
    <table class="table table-striped table-hover" style="margin-top: 0;">
      <thead>
        <tr>
          
          <th>Uploaded Filename</th>
          <th></th>
          <th class="text-right">Amount</th>
          <th></th>
          <th>Settlement Date/Time</th>
          <th>Cashier</th>
          <th>Remarks</th>
          <th>Uploaded</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        @foreach($setslps as $setslp)
        <tr>
          <td>
            <a href="/{{brcode()}}/setslp/{{$setslp->lid()}}">{{ $setslp->fileUpload->filename }}</a>
            @if($setslp->verified and $setslp->matched)
              <span class="glyphicon glyphicon-ok-sign text-success" data-toggle="tooltip" title="Matched by {{ $setslp->user->name }}"></span>
            @elseif($setslp->verified and !$setslp->matched)
              <span class="gly gly-ok" data-toggle="tooltip" title="Verified by {{ $setslp->user->name }}"></span>
            @else

            @endif
          </td>  
          <td style="padding: 8px 0 8px 0;">
            @if($setslp->verified and $setslp->matched)

            @elseif($setslp->verified and !$setslp->matched)

            @else
            <!--
            <span class="pull-right">
              <a href="/{{brcode()}}/setslp/{{$setslp->lid()}}" class="btn btn-primary btn-xs" title="View Card Settlement Slip Information">
                <span class="gly gly-eye-open"></span> view
              </a>
            </span>
          -->
            @endif
          </td>
          <td class="text-right">{{ number_format($setslp->amount,2) }}</td>
          <td>
            @if($setslp->terminal_id==1)
              <span class="label label-primary"><small>BDO</small></span>
            @elseif($setslp->terminal_id==2)
              <span class="label label-default"><small>RCBC</small></span>
            @elseif($setslp->terminal_id==3)
              <span class="label label-warning"><small>HSBC</small></span>
            @else

            @endif
          </td>
          <td>
            
            <span class="hidden-xs" data-toggle="tooltip" title="{{ $setslp->getTransDate()->format('D m/d/Y h:i A') }}">
              @if($setslp->getTransDate()->format('Y-m-d')==now())
                {{ $setslp->getTransDate()->format('h:i A') }}
              @else
                {{ $setslp->getTransDate()->format('D M j') }}
              @endif
            </span> 
            <em>
              <small class="text-muted">
              {{ diffForHumans($setslp->getTransDate()) }}
              </small>
            </em>


          </td>
          <td>{{ $setslp->cashier }}</td>
          <td>{{ $setslp->remarks }}</td>
          <td>
            <div data-toggle="tooltip" title="{{ $setslp->created_at->format('m/d/Y h:i A') }}">
            <span class="hidden-xs">
              @if($setslp->created_at->format('Y-m-d')==now())
                {{ $setslp->created_at->format('h:i A') }}
              @else
                {{ $setslp->created_at->format('D M j') }}
              @endif
            </span> 
            <em>
              <small class="text-muted">
              {{ diffForHumans($setslp->created_at) }}
              </small>
            </em>
            </div>
          </td>
          <td>{{ $setslp->fileUpload->terminal }}</td>
          
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    
    {!! $setslps->render() !!}
     
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
