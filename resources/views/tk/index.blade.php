<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/> 
  <meta name="csrf-token" content="{{ csrf_token() }}" />

  <title>Giligan's Restaurant @yield('title')</title>

  <link rel="shortcut icon" type="image/x-icon" href="/images/g.png" />
  <link rel="stylesheet" href="/css/styles-all.min.css">


</head>
<body class="tk" data-branchcode="{{ strtolower(Cookie::get('code')) }}">
<!-- Fixed navbar -->
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </button>
      <a class="navbar-brand" href="/">
        <img src="/images/giligans-header.png" class="img-responsive header-logo">
      </a>
      <p class="navbar-text" style="font-size: 20px; margin: 11px 0px 11px -10px;">
        <em style=" color: #3c763d;">
          <span></span>
          <span style=" color: #d6e9c6;">Bundy Clock - Beta</span>
        </em>
      </p>
    </div>
    <div id="navbar" class="navbar-collapse collapse">
      <ul class="nav navbar-nav navbar-right"> 
        <li>
          <a href="/">
            <span class="glyphicon glyphicon-dashboard"></span>
          </a>
        </li>

      </ul>
      
    </div>
  </div>
</nav>

<div class="container-fluid">
	<div class="tk-block row">
		<div class="l-pane col-sm-5">
      <!--
      <div class="ts-group">
        <div class="ts">{{  strftime('%I:%M:%S', strtotime('now')) }}</div>
        <div class="am">{{  strftime('%p', strtotime('now')) }}</div>
        <div style="clear: both;"></div>
      </div>
      -->
      <div class="ts-group2">
        <div class="tsg-container">

          <div class="tsg ts-hr">
            <div class="tsg-l">
              <img src="/images/tk/0.png" class="tsg-img">
            </div>
            <div class="tsg-r">
              <img src="/images/tk/0.png" class="tsg-img">
            </div>
          </div>

          <div class="tsg ts-min">
            <div class="tsg-l">
              <img src="/images/tk/0.png" class="tsg-img">
            </div>
            <div class="tsg-r">
              <img src="/images/tk/0.png" class="tsg-img">
            </div>
          </div>

          <ul>
            <li>
              <div class="tsg-sec">
                <div class="tsg-l">
                  <img src="/images/tk/0.png" class="tsg-img">
                </div>
                <div class="tsg-r">
                  <img src="/images/tk/0.png" class="tsg-img">
                </div>
              </div>
            </li>
            <li>
              <div class="ts-am">
                <img src="/images/tk/am.png" id="am">
              </div>
            </li>
          </ul>
        </div>
      </div>
      <div class="date-group">
        <div id="date">
          <time>{{  date('M j Y', strtotime('now')) }}</time>
          <span class="day">{{  date('D', strtotime('now')) }}</span>
        </div>
        
      </div>

      <div class="emp-group">
        @if(count($timelogs)>0)
          <div class="img-cont">
            <img  id="emp-img" src="/images/employees/{{ $timelogs[0]->employee->code }}.jpg" height="100%" width="100%">
          </div>
          <div class="emp-cont">
            <p id="emp-code">{{ $timelogs[0]->employee->code }}</p>
            <h1 id="emp-name">{{ $timelogs[0]->employee->lastname }}, {{ $timelogs[0]->employee->firstname }}</h1>
            <p id="emp-pos">{{ isset($timelogs[0]->employee->position) ? $timelogs[0]->employee->position->descriptor : '' }}</p>
          </div>
        @else 
          <div class="img-cont">
          <img  id="emp-img" src="/images/login-avatar.png" height="100%" width="100%">
          </div>
          <div class="emp-cont">
            <p id="emp-code"></p>
            <h1 id="emp-name"></h1>
            <p id="emp-pos"></p>
          </div>
        @endif
        <div style="clear: both;"></div>
      </div>
      
      <div class="message-group"></div>
      
      

		</div>
		<div class="r-pane col-sm-7">
      <div class="container-table">
        <table class="table table-condensed" role="table">
          <thead>
            <tr>
              <th>Emp No</th><th>Name</th><th>Date Time</th><th>Type</th><th>Branch</th>
            </tr>
          </thead>
          <tbody class="emp-tk-list">
          @if(count($timelogs)>0)
            @foreach($timelogs as $timelog)
            <tr class="txncode{{ $timelog->txncode }}">
              <td>{{ $timelog->employee->code }}</td>
              <td>{{ $timelog->employee->lastname }}, {{ $timelog->employee->firstname }}</td>
              <td>
                <span>
                  {{ strftime('%b %d', strtotime($timelog->datetime)) }}
                </span>
                &nbsp;
                {{ strftime('%I:%M:%S %p', strtotime($timelog->datetime)) }}
              </td>
              <td>
                {{ $timelog->getTxnCode() }}   
              </td>
              <td>
                {{ $timelog->employee->branch->code or '' }}
              </td>
            </tr>
            @endforeach
          @else
            <tr>
            </tr>
          @endif
          </tbody>
        </table>
      </div>
		</div>
	</div>	
</div>


<!-- modal ti/to -->	
<div class="modal fade" id="TKModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" data-backdrop="static">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        
        <h4 class="modal-title" id="myModalLabel">Good day!</h4>
      </div>
      <div class="modal-body">
        <div class="emp-group">
        <div class="img-cont">
          <img  id="mdl-emp-img" src="" height="100%" width="100%">
        </div>
        <div class="emp-cont">
          <p id="mdl-emp-code"></p>
          <h1 id="mdl-emp-name"></h1>
          <p id="mdl-emp-pos"></p>
        </div>
        <div style="clear: both;"></div>
      </div>
      </div>
      <div class="modal-footer">
        <button type="button" id="btn-time-in" class="btn btn-success btn-tk" data-dismiss="modal">
          press <strong>I</strong> for Time In
        </button>
        <button type="button" id="btn-break-start" class="btn btn-info btn-tk" data-dismiss="modal">
          press <strong>B</strong> for Break Start
        </button>
        <button type="button" id="btn-break-end" class="btn btn-warning btn-tk" data-dismiss="modal">
          press <strong>N</strong> for Break End
        </button>
        <button type="button" id="btn-time-out" class="btn btn-primary btn-tk" data-dismiss="modal">
          press <strong>O</strong> for Time Out
        </button>
        
      </div>
        <div class="mdl-f-options">
          <!--
          <p>Options:</p>
          <button type="button" class="btn btn-default btn-xs">press <strong>T</strong> to view timelog for the current month</button>
          -->
        <button type="button" class="btn btn-default btn-xs">press <strong>Esc</strong> to escape</button>
        </div>
    </div>
  </div>
</div>

<script src="/js/vendors-common.min.js"></script>
<script src="/js/tk.js"></script>

@if(app()->environment() == 'production')
<script>
  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

  ga('create', 'UA-68152291-2', 'auto');
  ga('send', 'pageview');

</script>
@endif


</body>
</html>


