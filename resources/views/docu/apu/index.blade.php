@extends('index')

@section('title', '- AP Upload Logs')

@section('body-class', 'ap-logs')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li><a href="/{{brcode()}}/apu/log">Payables</a></li>
    <li class="active">Upload Logs</li>
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

    <div>
      <!-- Nav tabs -->
      <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
          <a href="/{{brcode()}}/apu/log" aria-controls="pos" role="tab">
            Uploads
          </a>
        </li>
        <li role="presentation">
          <a href="/{{brcode()}}/ap/log" aria-controls="pos" role="tab">
            Downloads
          </a>
        </li>
      </ul>
      <!-- Tab panes -->
      <div class="file-explorer tab-content">
        <div role="tabpanel" class="tab-pane active">

          <div class="table-responsives">
            <table class="table table-striped table-hover" style="margin-top: 0;">
              <thead>
                <tr>
                  <th>Uploaded</th>
                  <!-- <th></th>           -->
                  <th>Supplier</th>          
                  <th>Invoice #</th>          
                  <th>Total Amt</th>          
                  <th></th>   
                  <th>Doc Type</th>          
                  <th>Billing Date</th>          
                  <th>Cashier</th>          
                  <th></th>   
                  <th>Uploaded</th>
                  <th>IP Address</th>       
                </tr>
              </thead>
              <tbody>
              @foreach($apus as $ap)
              <tr>
                <td>
                  <a href="/{{brcode()}}/apu/{{$ap->lid()}}" data-toggle="tooltip" title="{{ $ap->fileUpload->filename }}">
                    <span>
                    {{ short_filename($ap->fileUpload->filename) }}
                    </span>
                  </a>
                  @if($ap->verified and $ap->matched)
                    <span class="glyphicon glyphicon-ok-sign text-success" data-toggle="tooltip" title="Matched by {{ $ap->user->name }}"></span>
                  @elseif($ap->verified and !$ap->matched)
                    <span class="gly gly-ok" data-toggle="tooltip" title="Verified by {{ $ap->user->name }}"></span>
                  @else

                  @endif
                </td>
                <!-- <td style="padding: 8px 0 8px 0;">
                  @if($ap->verified and $ap->matched)

                  @elseif($ap->verified and !$ap->matched)

                  @else
                  
                  @endif
                </td> -->
                <td>
                  <span data-toggle="tooltip" title="{{ $ap->supplier->code }} - {{ $ap->supplier->descriptor }}">{{ $ap->supplier->descriptor }}</span>
                </td>
                <td>
                  {{ $ap->refno }}
                </td>
                <td class="text-right">
                  {{ number_format($ap->amount,2) }}
                </td>
                <td>
                  @if($ap->type==1)
                    <span class="label label-success" title="Cash" style="cursor: help;"><small>C</small></span>
                  @elseif($ap->type==2)
                    <span class="label label-info" title="Cheque" style="cursor: help;"><small>K</small></span>
                  @else

                  @endif
                </td>
                <td>
                  <?php
                    $code = empty($ap->doctype->code) ? $ap->doctype->descriptor : $ap->doctype->code;
                  ?>
                  <span data-toggle="tooltip" title="{{ $ap->doctype->descriptor }}">{{ $code }}</span>
                </td>
                <td>
                  <span class="hidden-xs" data-toggle="tooltip" title="{{ $ap->date->format('D m/d/Y') }} - {{ diffForHumans($ap->date) }}">
                    {{ $ap->date->format('M j, Y') }}
                  </span> 
                </td>
                <td>{{ $ap->cashier }}</td>
                <td>
                  @if(!empty($ap->remarks))
                  <a data-toggle="popover" title="Notes" data-content="{{ $ap->remarks }}" data-trigger="focus" tabindex="0" data-container="body" data-placement="left" style="cursor: pointer;">
                    <span class="gly gly-tag"></span>
                  </a>
                  @endif
                </td>
                <td>
                <div data-toggle="tooltip" title="{{ $ap->created_at->format('m/d/Y h:i A') }} - {{ diffForHumans($ap->created_at) }}">
                <span class="hidden-xs">
                  @if($ap->created_at->format('Y-m-d')==now())
                    {{ $ap->created_at->format('h:i A') }}
                  @else
                    {{ $ap->created_at->format('D M j') }}
                  @endif
                </span> 
                <em>
                  <!-- <small class="text-muted">
                  {{ diffForHumans($ap->created_at) }}
                  </small> -->
                </em>
                </div>
              </td>
              <td>{{ $ap->fileUpload->terminal }}</td>
              </tr>
              @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div><!-- end: tab-content -->
      {!! $apus->render() !!}
    </div>
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
