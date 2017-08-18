@extends('index')

@section('title', '- Deposit Slip Logs')

@section('body-class', 'depslp-logs')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/depslp/log">Deposit Slip</a></li>
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

    @if(session('alert-success') || session('depslp.delete')) 
      
    @else
      <div class="alert alert-important alert-info">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <b>Updates:</b>
          <ul>
            <li>You can now <b>View</b> your uploaded Deposit Slip by clicking on the filename (<b>DEPSLP GAL 20170109.jpg</b>) or on the 
              <button class="btn btn-primary btn-xs">
                <span class="gly gly-eye-open"></span> view
              </button> button.

            </li>
            <li>You can also <b>Edit</b> and <b>Delete</b> the Deposit Slip if you have errors on your previous uploads, as long as it's not verified <span class="gly gly-ok"></span>.</li>
          </ul>
          <small><em class="text-muted">For more questions / clarifications email us at <a href="mailto:jefferson.salunga@gmail.com">jefferson.salunga@gmail.com</a></em></small>
      </div>
    @endif

    <div class="table-responsive">
    <table class="table table-striped table-hover" style="margin-top: 0;">
      <thead>
        <tr>
          
          <th>Uploaded Filename</th>
          <th></th>
          <th class="text-right">Amount</th>
          <th></th>
          <th>Deposit Date/Time</th>
          <th>Cashier</th>
          <th>Remarks</th>
          <th>Uploaded</th>
          <th>IP Address</th>
        </tr>
      </thead>
      <tbody>
        @foreach($depslips as $depslip)
        <tr>
          <td>
            <a href="/{{brcode()}}/depslp/{{$depslip->lid()}}">{{ $depslip->fileUpload->filename }}</a>
            @if($depslip->verified and $depslip->matched)
              <span class="glyphicon glyphicon-ok-sign text-success" data-toggle="tooltip" title="Matched by {{ $depslip->user->name }}"></span>
            @elseif($depslip->verified and !$depslip->matched)
              <span class="gly gly-ok" data-toggle="tooltip" title="Verified by {{ $depslip->user->name }}"></span>
            @else

            @endif
          </td>  
          <td style="padding: 8px 0 8px 0;">
            @if($depslip->verified and $depslip->matched)

            @elseif($depslip->verified and !$depslip->matched)

            @else
            <span class="pull-right">
              <a href="/{{brcode()}}/depslp/{{$depslip->lid()}}" class="btn btn-primary btn-xs" title="View Deposit Slip Information">
                <span class="gly gly-eye-open"></span> view
              </a>
            </span>
            @endif
          </td>
          <td class="text-right">{{ number_format($depslip->amount,2) }}</td>
          <td>
            @if($depslip->type==1)
              <span class="label label-success" title="Cash" style="cursor: help;"><small>C</small></span>
            @elseif($depslip->type==2)
              <span class="label label-info" title="Cheque" style="cursor: help;"><small>K</small></span>
            @else

            @endif
          </td>
          <td>
            
            <span class="hidden-xs" data-toggle="tooltip" title="{{ $depslip->deposit_date->format('D m/d/Y h:i A') }}">
              @if($depslip->deposit_date->format('Y-m-d')==now())
                {{ $depslip->deposit_date->format('h:i A') }}
              @else
                {{ $depslip->deposit_date->format('D M j') }}
              @endif
            </span> 
            <em>
              <small class="text-muted">
              {{ diffForHumans($depslip->deposit_date) }}
              </small>
            </em>

          </td>
          <td>{{ $depslip->cashier }}</td>
          <td>{{ $depslip->remarks }}</td>
          <td>
            <div data-toggle="tooltip" title="{{ $depslip->created_at->format('m/d/Y h:i A') }}">
            <span class="hidden-xs">
              @if($depslip->created_at->format('Y-m-d')==now())
                {{ $depslip->created_at->format('h:i A') }}
              @else
                {{ $depslip->created_at->format('D M j') }}
              @endif
            </span> 
            <em>
              <small class="text-muted">
              {{ diffForHumans($depslip->created_at) }}
              </small>
            </em>
            </div>
          </td>
          <td>{{ $depslip->fileUpload->terminal }}</td>
          
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    
    {!! $depslips->render() !!}
     
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
