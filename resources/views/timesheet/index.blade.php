@extends('index')

@section('title', '- Timesheet')

@section('body-class', 'timesheet-index')

@section('container-body')
<div class="container-fluid">
	<ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/dashboard">{{ $branch }}</a></li>
    <li>Timesheet</li>
    <li class="active">{{ $dr->date->format('D, M j, Y') }}</li>
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
            <a href="/timelog" class="btn btn-default">
              <span class="gly gly-stopwatch"></span>
              <span class="hidden-xs hidden-sm">Timelogs</span>
            </a>
            <button type="button" class="btn btn-default active">
              <span class="glyphicon glyphicon-th-list"></span>
              <span class="hidden-xs hidden-sm">Timesheet</span>
            </button>
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/timelog/add?ref=timesheet" class="btn btn-default" title="Back to Main Menu">
              <span class="glyphicon glyphicon-plus"></span>
              <span class="hidden-xs hidden-sm">Add Timelog</span>
            </a> 
          </div>
          <div class="btn-group pull-right clearfix" role="group">
            <a href="/timesheet?date={{ $dr->date->copy()->subDay()->format('Y-m-d') }}" class="btn btn-default" title="{{ $dr->date->copy()->subDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-left"></span>
            </a>
            <input type="text" class="btn btn-default" id="dp-date" value="{{ $dr->date->format('m/d/Y') }}" style="max-width: 110px;" readonly>
            <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
            <a href="/timesheet?date={{ $dr->date->copy()->addDay()->format('Y-m-d') }}" class="btn btn-default" title="{{ $dr->date->copy()->addDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-right"></span>
            </a>
          </div>
          
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    @if(count($data[1])>0)
      <div class="alert alert-important alert-warning">
        <p>There is other employee timelog from other store. Please contact system administrator</p>
      <ul>
      @foreach($data[1] as $key => $f)
        <?php $f->load('employee.branch'); ?>
        <li>{{ $f->employee->lastname }}, {{ $f->employee->firstname }} of {{ $f->employee->branch->code }} - {{ $f->entrytype==2?'Manual':'Punched' }} {{ $f->getTxnCode() }} - 
          {{ $f->datetime->format('D, M j, Y h:i:s A') }} created at {{ $f->createdate->format('D, M j, Y h:i:s A') }}</li>
      @endforeach
    </ul>
      </div>
    @endif



    @if(isset($data[0]) && count($data[0])>0)
    <div class="table-responsive">
    <table class="table table-hover table-bordered">
      <thead>
        <tr>
          <th>Employee</th>
          <th class="text-right">Work Hours</th>
          <th class="text-right">Time In</th>
          <th class="text-right">Break In</th>
          <th class="text-right">Break Out</th>
          <th class="text-right">Time Out</th>
          <!--
          <th class="text-right">Txn Count</th>
          -->
        </tr>
      </thead>
      <tbody>
        @foreach($data[0] as $key => $e)
        <tr>
          <td <?=$e['onbr']?'':'class="bg-danger"'?> >
            {{ $key+1}}. 

            <a href="/timesheet/{{$e['employee']->lid()}}?fr={{$dr->date->copy()->startOfMonth()->format('Y-m-d')}}&amp;to={{$dr->date->copy()->endOfMonth()->format('Y-m-d')}}">
              {{ $e['employee']->lastname or '-' }}, {{ $e['employee']->firstname or '-' }}
            </a>
            <span class="label label-default pull-right" title="{{ $e['employee']->position->descriptor or '-' }}">{{ $e['employee']->position->code or '-' }}</span>
          </td>
          <td class="text-right">
            <!--
            @if(count($e['raw'])>0)
              <span class="label label-default pull-left" title="Transaction count">
                {{ count($e['raw']) }}
              </span>
            @else
              
            @endif  
            -->
            @if($e['timesheet']->workHours->format('H:i')==='00:00')
              -
            @else
              <small class="text-muted"><em>
                ({{ $e['timesheet']->workHours->format('H:i') }})</em> 
              </small>
              <strong>
                {{ $e['timesheet']->workedHours }}
              </strong>
            @endif
          </td>
          @foreach($e['timelogs'] as $key => $t)
            @if(is_null($t))
              <td class="text-right">-</td>
            @else
              <td class="text-right {{ $t['entrytype']=='2'?'bg-warning':'bg-success' }}" 
              title="{{ $t['datetime']->format('D, M j, Y h:i:s A') }} @ {{ $t['createdate']->format('D, M j, Y h:i:s A') }}">
                {{ $t['datetime']->format('h:i A') }}
              </td>
            @endif
          @endforeach

            <!--
            <td class="text-right">
              @if(count($e['raw'])>0)
                {{ count($e['raw']) }}
              @else
                -
              @endif 
            </td>
            -->
            <!--
          <td class="text-right">
            @if(count($e['raw'])>0)
              {{ count($e['raw']) }}
            @else
              -
            @endif 
          </td>
        -->
          
        </tr>
        @endforeach
      </tbody>
    </table>
    </div>
    @else
      No data
    @endif

</div><!-- end .container-fluid -->
@endsection




@section('js-external')
  @parent

  @include('_partials.js-vendor-highcharts')

<script>
  $('document').ready(function(){

  	$('#dp-date').datetimepicker({
      defaultDate: "{{ $dr->date->format('Y-m-d') }}",
      format: 'MM/DD/YYYY',
      showTodayButton: true,
      ignoreReadonly: true,
      calendarWeeks: true
    }).on('dp.change', function(e){
      //var date = e.date.format('YYYY-MM-DD');
      //console.log(date);
      //document.location.href = '/timesheet?date='+e.date.format('YYYY-MM-DD');
      document.location.replace('/timesheet?date='+e.date.format('YYYY-MM-DD'));
    });


      



   
  });
</script>
@endsection