<!doctype html>
<html lang="en">
<head>
  
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/> 


  <title>{{ session('user.branchcode') }} Timesheet {{ $dr->date->format('D, M j, Y') }}</title>

  <link rel="shortcut icon" type="image/x-icon" href="/images/g.png" />
  <link rel="stylesheet" href="/css/styles-all.min.css">
</head>
<body style="padding-top: 20px;">

	<div class="hidden-print" style="padding-bottom: 30px;">
		<nav>
		  <ul class="pager">
		    <li class="previous">
		    	<a href="/timesheet/print?date={{ $dr->date->copy()->subDay()->format('Y-m-d') }}">
		    		<span aria-hidden="true">&larr;</span> 
		    		{{ $dr->date->copy()->subDay()->format('D, M j, Y') }}
		    </a>
		    </li>
		    <li class="next">
		    	<a href="/timesheet/print?date={{ $dr->date->copy()->addDay()->format('Y-m-d') }}">
		    		{{ $dr->date->copy()->addDay()->format('D, M j, Y') }} 
		    		<span aria-hidden="true">&rarr;</span>
		    	</a>
		    </li>
		  </ul>
		</nav>
	</div>
	
	<h4>{{ session('user.branchcode') }} Timesheet - {{ $dr->date->format('D, M j, Y') }}</h4>
	@if(count($data[0])>0)
    <table class="table table-hover table-bordered table-condensed">
      <thead>
        <tr>
          <th>Employee</th>
          <th class="text-right">Time In</th>
          <th class="text-right">Break In</th>
          <th class="text-right">Break Out</th>
          <th class="text-right">Time Out</th>
          <!--
          <th class="text-right">Timelog Count</th>
          -->
        </tr>
      </thead>
      <tbody>
        @foreach($data[0] as $key => $e)
        <tr>
          <td>
            {{ $key+1}}. {{ $e['employee']->lastname }}, {{ $e['employee']->firstname }}
            
          </td>
            @foreach($e['timelogs'] as $key => $t)
              @if(is_null($t))
                <td class="text-right">-</td>
              @else
                <td class="text-right">
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
          
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
      No data
    @endif

</body>
</html>