@extends('index')

@section('title', '- DTR')

@section('css-external')
	<link rel="stylesheet" type="text/css" href="//cdnjs.cloudflare.com/ajax/libs/bootstrap-daterangepicker/2.1.24/daterangepicker.min.css">
  <link href="https://fonts.googleapis.com/css?family=Source+Code+Pro" rel="stylesheet">
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
              <img src="http://cashier.giligansrestaurant.com/images/{{$employee->photo?'employees/'.$employee->code.'.jpg':'login-avatar.png'}}" style="margin-right: 5px; width: 100px;" class="img-responsive">
            </td>
            <td>
              <h3>
                {{ $employee->lastname }}, {{ $employee->firstname }}
                <small>{{ $employee->code }}</small>
              </h3>
              <p>{{ $employee->position->descriptor or '' }}</p>
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
              {{ $header->totalWorkedHours }}
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
              {{ $header->totalTardyHours }}
              <span class="small"> Hrs</span>
            </h3>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-sm-12">
        <div class="table-responsive">
          <table class="table table-hover table-condensed" style="font-family: 'Source Code Pro', monospace; margin-top: 0px;">
            <thead>
              <tr>
                <th>Day(s)</th>
                <th>Day Type</th>
                <th class="text-right">Time Start</th>
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
            <?php
              $tot_tardy = 0;
            ?>
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
                  <a href="/{{brcode()}}/timelog/employee/{{$employee->lid()}}?date={{$timesheet['date']->format('Y-m-d')}}">
                  {{ $timesheet['date']->format("D, M j") }}
                  </a>
                </td>
                <td>
                  @if($timesheet['mandtl'])
                    {{ dayDesc($timesheet['mandtl']->daytype) }}
                  @else
                    <span style="color: #bbb;">No Mansked</span>
                  @endif
                </td>
                <td class="text-right">
                  @if($timesheet['mandtl'])
                  {{ empty($timesheet['mandtl']->timestart) || $timesheet['mandtl']->timestart=='off' ? '':date('g A', strtotime($timesheet['mandtl']->timestart)) }}
                  @else
                    
                  @endif
                </td>
                <td class="text-right">
                  <!--
                  @if($timesheet['timelog']->workHours->format('H:i')!=='00:00')
                    <small><em style="color: #aaa;">({{$timesheet['timelog']->workHours->format('H:i')}})</em></small>
                  @endif
                  -->
                  <strong class="help" data-toggle="tooltip" title="{{$timesheet['timelog']->workHours->format('H')}} Hour(s) and {{$timesheet['timelog']->workHours->format('i')}} Minute(s)">
                    {{ $timesheet['timelog']->workedHours or '' }}
                  </strong>
                </td>
                <td class="text-right">
                  @if($timesheet['tardy']>0)
                    {{ $timesheet['tardy'] }}
                  @endif 
                </td>
                 <td class="text-right">
                  {{ $timesheet['timelog']->otedHours or '' }}
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->timein))
                    <span data-toggle="tooltip" title="{{ $timesheet['timelog']->timein->timelog->datetime->format('h:i:s A') }}"
                      @if(!$timesheet['timelog']->is_timein())
                       style="color: #ccc;">
                      @elseif($timesheet['timelog']->timein->timelog->entrytype=='2')
                         class="text-danger">
                      @else
                        >
                      @endif
                      {{ $timesheet['timelog']->timein->timelog->datetime->format('h:i A') }}
                      </span>
                  @else
                    
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->breakin))
                    <span data-toggle="tooltip" title="{{ $timesheet['timelog']->breakin->timelog->datetime->format('h:i:s A') }}"
                      @if(!$timesheet['timelog']->is_breakin())
                       style="color: #ccc;">
                      @elseif($timesheet['timelog']->breakin->timelog->entrytype=='2')
                         class="text-danger">
                      @else
                        >
                      @endif
                      {{ $timesheet['timelog']->breakin->timelog->datetime->format('h:i A') }}
                      </span>
                  @else
                    
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->breakout))
                    <span data-toggle="tooltip" title="{{ $timesheet['timelog']->breakout->timelog->datetime->format('h:i:s A') }}" 
                    @if(!$timesheet['timelog']->is_breakout())
                       style="color: #ccc;">
                    @elseif($timesheet['timelog']->breakout->timelog->entrytype=='2')
                       class="text-danger">
                    @else
                      >
                    @endif
                    {{ $timesheet['timelog']->breakout->timelog->datetime->format('h:i A') }}
                    </span>
                  @else
                    
                  @endif
                </td>
                <td class="text-right">
                  @if(!empty($timesheet['timelog']->timeout))
                    <span data-toggle="tooltip" title="{{ $timesheet['timelog']->timeout->timelog->datetime->format('h:i:s A') }}" 
                      @if(!$timesheet['timelog']->is_timeout())
                       style="color: #ccc;">
                      @elseif($timesheet['timelog']->timeout->timelog->entrytype=='2')
                        <span class="text-danger">
                      @else
                        <span>
                      @endif
                      {{ $timesheet['timelog']->timeout->timelog->datetime->format('h:i A') }}
                    </span>
                  @else
                    
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
  	var url = "/{{brcode()}}/timesheet/{{$employee->lid()}}?fr="+ picker.startDate.format('YYYY-MM-DD') +"&to="+ picker.endDate.format('YYYY-MM-DD');
  	window.location.replace(url);
  	console.log(picker.startDate.format('MM/DD/YYYY'));
  	console.log(picker.endDate.format('MM/DD/YYYY'));
  });

  cb(start, end);


});
</script>

@endsection