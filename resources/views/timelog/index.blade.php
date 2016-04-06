@extends('index')

@section('title', '- Timelog List')

@section('body-class', 'timelog-list')

@section('container-body')
<div class="container-fluid">

  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li class="active">Timelog</li>
  </ol>

  <div>
    <nav id="nav-action" class="navbar navbar-default">
      <div class="container-fluid">
        <div class="navbar-form">
          <div class="btn-group" role="group">
            <a href="/" class="btn btn-default" title="Back to Main Menu">
              <span class="gly gly-unshare"></span>
              <span class="hidden-xs hidden-sm">Back</span>
            </a> 
            <button type="button" class="btn btn-default active">
              <span class="gly gly-stopwatch"></span>
              <span class="hidden-xs hidden-sm">Timelogs</span>
            </button>
            <a href="/timesheet" class="btn btn-default">
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
        </div>
      </div>
    </nav>

    @include('_partials.alerts')

    {{-- $timelogs->total() --}}

    <table class="table">
      <thead>
        <tr>
          <th>Date</th><th>Time</th><th>Type</th><th>Lastname</th><th>Firstname</th>
        </tr>
      </thead>
      <tbody>
        @foreach($timelogs as $timelog)
        <tr>
          <td>{{ $timelog->datetime->format('m/d/Y') }} </td>
          <td>{{ $timelog->datetime->format('h:i A') }} </td>
          <td>{{ $timelog->getTxnCode() }} </td>
          <td>{{ $timelog->employee->firstname }} </td>
          <td>{{ $timelog->employee->lastname }} </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    
    {!! $timelogs->render() !!}

    
      
  
  </div>
</div><!-- end container-fluid -->
@endsection


@section('js-external')
  @parent
  
  <script>
  
    
 
  </script>
@endsection
