@extends('index')

@section('title', '- DTR')

@section('css-external')
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/2.1.24/daterangepicker.min.css">
@endsection

@section('body-class', 'timesheet-employee')

@section('container-body')
<div class="container-fluid">
	
	<ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/dashboard">{{ $branch }}</a></li>
    <li>Timesheet</li>
    <li class="active">{{ $employee->code or '' }}</li>
    <!--
    <li class="active">{{ $dr->date->format('D, M j, Y') }}</li>
  	-->
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
            <div id="reportrange" class="btn btn-default">
            	<span class="glyphicon glyphicon-calendar"></span>
            	<span class="p">{{ $dr->fr->format("m/d/Y") }} - {{ $dr->to->format("m/d/Y") }}</span> 
            </div>
            <!--
            <a href="/timesheet/{{$employee->lid()}}?date={{ $dr->date->copy()->subDay()->format('Y-m-d') }}" class="btn btn-default" title="{{ $dr->date->copy()->subDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-left"></span>
            </a>
            <input type="text" class="btn btn-default" id="dp-date" value="{{ $dr->date->format('m/d/Y') }}" style="max-width: 110px;" readonly>
            <label class="btn btn-default" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
            <a href="/timesheet/{{$employee->lid()}}?date={{ $dr->date->copy()->addDay()->format('Y-m-d') }}" class="btn btn-default" title="{{ $dr->date->copy()->addDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-right"></span>
            </a>
            -->
          </div>
          
        </div>
      </div>
    </nav>

    <div class="row">
      <div class="col-sm-6">
        <table>
          <tr>
            <td>
              <img class="img-responsive" src="/images/employees/{{ $employee->code }}.jpg" style="margin-right: 5px; width: 100px;">
            </td>
            <td>
              <h3>
                {{ $employee->lastname }}, {{ $employee->firstname }}
                <small>{{ $employee->code }}</small>
              </h3>
              <p>
                <em>Timesheet for {{ $dr->fr->format("D M j, Y") }} - {{ $dr->to->format("D M j, Y") }}</em>
              </p>
            </td>
          </tr>
        </table>
        
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Total Work Hours</h3>
          </div>
          <div class="panel-body text-right">
            <h3>
              0
              <span class="small"> Hrs</span>
            </h3>
          </div>
        </div>
      </div>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Total Tardy Hours</h3>
          </div>
          <div class="panel-body text-right">
            <h3>
              0
              <span class="small"> Hrs</span>
            </h3>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <div class="table-responsive">
          <table class="table table-hover table-condensed">
            <thead>
              <tr>
                <th>Day(s)</th>
                <th class="text-right">Rendered Hours</th>
                <th class="text-right">Tardy Hours</th>
                <th class="text-right">OT Hours</th>
                <th class="text-right">Time In</th>
                <th class="text-right">Break In</th>
                <th class="text-right">Break Out</th>
                <th class="text-right">Time Out</th>
              </tr>
            </thead>
            <tbody>
            @foreach($timesheets as $timesheet)
              <tr 
                @if($timesheet['date']->isToday())
                  class="bg-success"
                @elseif($timesheet['date']->dayOfWeek==0)
                  class="bg-warning"
                @else

                @endif
              >
                <td>
                  {{-- $timesheet['date']->format('Y-m-d') --}}
                  <a href="/timesheet?date={{$timesheet['date']->format('Y-m-d')}}">
                  {{ $timesheet['date']->format("D, M j") }}
                  </a>
                </td>
                <td class="text-right">
                  @if($timesheet['timelog']->workHours->format('h:i A')!=='00:00:00')
                    <small><em>({{$timesheet['timelog']->workHours->format('H:i:s')}})</em></small>
                  @endif
                  {{ $timesheet['timelog']->workedHours or '' }}
                </td>
                 <td class="text-right">
                  
                </td>
                 <td class="text-right">
                  {{ $timesheet['timelog']->otedHours or '' }}
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->timein))
                    {{ $timesheet['timelog']->timein->timelog->datetime->format('h:i A') }}
                  @else
                    -
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->breakin))
                    {{ $timesheet['timelog']->breakin->timelog->datetime->format('h:i A') }}
                  @else
                    -
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->breakout))
                    {{ $timesheet['timelog']->breakout->timelog->datetime->format('h:i A') }}
                  @else
                    -
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->timeout))
                    {{ $timesheet['timelog']->timeout->timelog->datetime->format('h:i A') }}
                  @else
                    -
                  @endif
                </td>
              </tr>
            @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>



</div>
@endsection




@section('js-external')
  @parent

  <script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/2.1.24/daterangepicker.min.js"></script>

<script type="text/javascript">
$(function() {

	console.log(moment());

	var start = moment('{{$dr->fr->format("Y-m-d")}}');
	console.log(start);
	var end = moment('{{$dr->to->format("Y-m-d")}}');
	console.log(end);

	function cb(start, end) {
      $('#reportrange .p').html(start.format('MM/DD/YYYY') + ' - ' + end.format('MM/DD/YYYY'));
  }

  $('#reportrange').daterangepicker({
    startDate: start,
    endDate: end,
    ranges: {
       'Today': [moment(), moment()],
       'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
       'Last 7 Days': [moment().subtract(6, 'days'), moment()],
       'Last 30 Days': [moment().subtract(29, 'days'), moment()],
       'This Month': [moment().startOf('month'), moment().endOf('month')],
       'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
    }
  }, cb)
  .on('apply.daterangepicker', function(ev, picker) {
  	//console.log(ev);
  	var url = "/timesheet/{{$employee->lid()}}?fr="+ picker.startDate.format('YYYY-MM-DD') +"&to="+ picker.endDate.format('YYYY-MM-DD');
  	window.location.replace(url)
  	console.log(picker.startDate.format('MM/DD/YYYY'));
  	console.log(picker.endDate.format('MM/DD/YYYY'));
  });

  cb(start, end);


});
</script>

@endsection