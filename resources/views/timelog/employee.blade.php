@extends('index')

@section('title', '- Employee Timelog ('.strtoupper(brcode()).')')

@section('body-class', 'emp-timelog')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/{{brcode()}}/dashboard">{{ $branch }}</a></li>
    <li><a href="/timelog">Timelog</a></li>
    <li><a href="/{{brcode()}}/timelog/employee/{{$employee->lid()}}">{{$employee->code}}</a></li>
    <li class="active">{{ $date->format('D, M j, Y') }}</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          <div class="btn-group" role="group">
            <a href="/dashboard" class="btn btn-default">
              <span class="gly gly-unshare"></span>
              <span class="hidden-xs hidden-sm">Back</span>
            </a> 
            <button type="button" class="btn btn-default active">
              <span class="gly gly-stopwatch"></span>
              <span class="hidden-xs hidden-sm">Timelogs</span>
            </button>
            <a href="/{{brcode()}}/timesheet?date={{ $date->format('Y-m-d') }}" class="btn btn-default">
              <span class="glyphicon glyphicon-th-list"></span>
              <span class="hidden-xs hidden-sm">Timesheet</span>
            </a>
          </div> <!-- end btn-grp -->
          <div class="btn-group" role="group">
            <a href="/timelog/add" class="btn btn-default">
              <span class="glyphicon glyphicon-plus"></span>
              <span class="hidden-xs hidden-sm">Add Timelog</span>
            </a>
          </div>

          <div class="btn-group pull-right clearfix" role="group">
            <a href="/{{brcode()}}/timelog/employee/{{$employee->lid()}}?date={{$date->copy()->subDay()->format('Y-m-d')}}" class="btn btn-default" title="{{ $date->copy()->subDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-left"></span>
            </a>
            <input type="text" class="btn btn-default" id="dp-date" value="{{ $date->format('m/d/Y') }}" style="max-width: 110px;" readonly>
            <label class="btn btn-default  hidden-sm hidden-xs" for="dp-date"><span class="glyphicon glyphicon-calendar"></span></label>
            <a href="/{{brcode()}}/timelog/employee/{{$employee->lid()}}?date={{ $date->copy()->addDay()->format('Y-m-d')}}" class="btn btn-default" title="{{ $date->copy()->addDay()->format('Y-m-d') }}">
              <span class="glyphicon glyphicon-chevron-right"></span>
            </a>
          </div>
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    <div class="row">
      <div class="col-sm-6">
        <table>
          <tbody><tr>
            <td>
              <?php 
                $src=$employee->photo?'employees/'.$employee->code.'.jpg':'login-avatar.png';
              ?>
              <img src="http://cashier.giligansrestaurant.com/images/{{$src}}" style="margin-right: 5px; width: 100px;" class="img-responsive">
            </td>
            <td>
              <h3>
                {{ $employee->lastname }}, {{ $employee->firstname }}
                <small>{{ $employee->code }}</small>
              </h3>
              <p>{{ $employee->position->descriptor or '' }}</p>
              <p>
                <em>{{ $date->format('D, M j, Y') }} Timelog</em>
              </p>
            </td>
          </tr>
        </tbody>
        </table>
        <div style="margin: 10px 0;">
          <a class="btn btn-primary" href="/{{brcode()}}/timesheet/{{$employee->lid()}}?fr={{$date->copy()->startOfMonth()->format('Y-m-d')}}&to={{$date->copy()->endOfMonth()->format('Y-m-d')}}">
            View <strong>{{ $date->format('M') }}</strong> Timesheet
          </a>
        </div>
      </div>
      
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Rendered Work Hours</h3>
          </div>
          <div class="panel-body text-right">
            <h3>
              {{ $timesheet->workedHours or '0' }}
              <span class="small"> Hrs</span>
            </h3>
          </div>
        </div>
      </div>
      <?php
        $tardy = 0;
        if ((isset($mandtl->timestart) && $mandtl->timestart!='off') && !is_null($timesheet->timein)) {

          $timein = $timesheet->timein->timelog->datetime;
          $timestart = c($timein->format('Y-m-d').' '.$mandtl->timestart);
          
          $late =$timestart->diffInMinutes($timein, false); 
          $tardy = $late>0 ? number_format(($late/60), 2) : 0;
          //$tardy = 1;
        }?>
      <div class="col-sm-3">
        <div class="panel panel-default">
          <div class="panel-heading">
            <h3 class="panel-title">Tardy Hours</h3>
          </div>
          <div class="panel-body text-right">
            <h3>
              {{ $tardy }}
              <span class="small"> Hrs</span>
            </h3>
          </div>
        </div>
      </div>
       
    </div><!-- end: .row -->

    <div class="row">

      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">Details</div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <td class="bg-default"></td>
                    <td class="bg-default">Time Start/In</td>
                    <td class="bg-default">Break Start/In</td>
                    <td class="bg-default">Break End/Out</td>
                    <td class="bg-default">Time End/Out</td>
                    <td class="bg-default">Work Hours</td>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>
                      Mansked

                      
                      @if($mandtl)
                        &nbsp;
                          <i class="fa fa-calendar"></i>
                          <!--
                        <a href="/task/manday/{{ $mandtl->manskedday->lid() }}?employeeid={{$employee->lid()}}">
                        </a>
                        -->
                      @else
                        &nbsp;
                        <i class="glyphicon glyphicon-remove-circle" data-toggle="tooltip" title="No Mansked"></i>
                      @endif
                    </td>
                    @if($mandtl)
                      <td>{{ (isset($mandtl->timestart) && $mandtl->timestart!='off') ? date('h:i A', strtotime($mandtl->timestart)) : '-' }}</td>
                      <td>{{ (isset($mandtl->breakstart) && $mandtl->breakstart!='off') ? date('h:i A', strtotime($mandtl->breakstart)) : '-' }}</td>
                      <td>{{ (isset($mandtl->breakend) && $mandtl->breakend!='off') ? date('h:i A', strtotime($mandtl->breakend)) : '-' }}</td>
                      <td>{{ (isset($mandtl->timeend) && $mandtl->timeend!='off') ? date('h:i A', strtotime($mandtl->timeend)) : '-' }}</td>
                      <td class="text-right">{{ $mandtl->workhrs>0 ? $mandtl->workhrs:'-' }}</td>
                    @else
                      <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
                    @endif
                  </tr>
                  <tr>
                    <td>Timesheet</td>
                    @if(is_null($timesheet))
                      <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
                    @else
                    <td>{{ is_null($timesheet->timein) ? '-':$timesheet->timein->timelog->datetime->format('h:i A') }}</td>
                    <td>{{ is_null($timesheet->breakin) ? '-':$timesheet->breakin->timelog->datetime->format('h:i A') }}</td>
                    <td>{{ is_null($timesheet->breakout) ? '-':$timesheet->breakout->timelog->datetime->format('h:i A') }}</td>
                    <td>{{ is_null($timesheet->timeout) ? '-':$timesheet->timeout->timelog->datetime->format('h:i A') }}</td>
                    <td class="text-right">{{ $timesheet->workedHours or '-' }}</td>
                    @endif
                  </tr>
                </tbody>
              </table>
              <p></p>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-12">
        <div class="panel panel-default">
          <div class="panel-heading">Raw Timelog Details</div>
          <div class="panel-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <td>Branch</td>
                    <td>Txn Type</td>
                    <td>Time</td>
                    <td>Entry</td>
                    <td>On Timesheet</td>
                    <td>&nbsp;</td>
                  </tr>
                </thead>
                <tbody class="emp-tk-list">
                @if($timelogs)
                  @foreach($timelogs as $timelog)
                    <tr class="txncode{{ $timelog->txncode or '' }}a" data-id="{{ $timelog->id }}">
                      <td>{{ $timelog->branch->code or '' }}</td>
                      <td>
                        <span class="label label-{{ $timelog->txnClass() }}" style="color: #fff;" >
                          {{ $timelog->getTxnCode() }}
                        </span>
                      </td>
                      <td>
                        <spanclass="help" data-toggle="tooltip" title="created @ {{ $timelog->createdate->format('D, M j, Y h:i:s A') }}" style="color: #000;">
                          {{ $timelog->datetime->format('h:i:s A') }}
                        </span>

                        
                      </td>
                      <td>
                        @if($timelog->entrytype==1)
                          RFID
                        @else 
                          Manual
                        @endif
                      </td>
                      <td>
                        <!--
                        @if ($timelog->ignore)
                          
                        @else 
                          <span class="glyphicon glyphicon-ok" style="color: #468847;"></span> 
                        @endif
                        -->
                        <?php
                        switch ($timelog->txncode) {
                          case '1':
                            if (isset($timesheet->timein) && $timesheet->timein->timelog->datetime->eq($timelog->datetime))
                              echo '<span class="glyphicon glyphicon-ok" style="color: #468847;"></span>';
                            break;
                          case '2':
                            if (isset($timesheet->breakin) && $timesheet->breakin->timelog->datetime->eq($timelog->datetime))
                              echo '<span class="glyphicon glyphicon-ok" style="color: #468847;"></span>';
                            break;
                          case '3':
                            if (isset($timesheet->breakout) && $timesheet->breakout->timelog->datetime->eq($timelog->datetime))
                              echo '<span class="glyphicon glyphicon-ok" style="color: #468847;"></span>';
                            break;
                          case '4':
                            if (isset($timesheet->timeout) && $timesheet->timeout->timelog->datetime->eq($timelog->datetime))
                              echo '<span class="glyphicon glyphicon-ok" style="color: #468847;"></span>';
                            break;
                          default:
                            echo '-';
                            break;
                        }?>
                      </td>
                      <td>
                        @if ($timelog->ignore)
                          <i class="glyphicon glyphicon-ban-circle" style="color: #ccc;"></i> 
                        @else
                          <i class="glyphicon glyphicon-ok-circle" style="color: #468847;"></i> 
                        @endif

                        @if(request()->has('action') && request()->input('action')=='ignore')

                          @if(request()->has('txncode') && request()->input('txncode')==$timelog->txncode)
                          <form action="{{ request()->url() }}" method="POST">
                          {{ csrf_field() }}
                          <input type="hidden" name="_method" value="DELETE">
                          <input type="hidden" name="id" value="{{ $timelog->id }}">
                          <input type="hidden" name="employeeid" value="{{ $timelog->employeeid }}">
                          <input type="hidden" name="branchid" value="{{ $timelog->branchid }}">
                          <input type="hidden" name="date" value="{{ $date->format('Y-m-d') }}">
                          <input type="hidden" name="ignore" value="{{ $timelog->ignore }}">
                          
                          <button type="submit" class="btn btn-default btn-sm">
                            @if ($timelog->ignore)
                              <span class="glyphicon glyphicon-ok-circle" style="color: #468847;" title="Un-ignore"></span> 
                            @else
                              <span class="glyphicon glyphicon-ban-circle" style="color: #a94442;" title="Ignore"></span> 
                            @endif
                            
                          </button>
                          </form>
                          @endif

                        @endif
                      
                      
                      </td>
                    </tr>
                  @endforeach
                @else
                  
                @endif
                </tbody>
              </table>
            </div> 
           </div>
        </div>       
      </div> 

    </div><!-- end: .row -->

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  
  <script>
   moment.locale('en', { week : {
      dow : 1 // Monday is the first day of the week.
    }});

  $('document').ready(function(){

    

    $('#dp-date').datetimepicker({
      defaultDate: "{{ $date->format('Y-m-d') }}",
      format: 'MM/DD/YYYY',
      showTodayButton: true,
      ignoreReadonly: true,
    }).on('dp.change', function(e){
      var date = e.date.format('YYYY-MM-DD');
      console.log(date);
      document.location.href = '/{{brcode()}}/timelog/employee/{{stl($employee->id)}}?date='+e.date.format('YYYY-MM-DD');
      
    });
  });
  </script>
@endsection
