@extends('master')

@section('title', ' - Dashboard')

@section('navbar-2')
<ul class="nav navbar-nav navbar-right"> 
  <li class="dropdown">
    <a href="#" class="dropdown-toggle" data-toggle="dropdown">
      <span class="glyphicon glyphicon-cog"></span>
      <b class="caret"></b>
    </a>
    <ul class="dropdown-menu">
    	<li><a href="/settings"><span class="glyphicon glyphicon-cloud-upload"></span> Settings</a></li>
      <li><a href="/logout"><span class="glyphicon glyphicon-log-out"></span> Log Out</a></li>     
    </ul>
  </li>
</ul>
<p class="navbar-text navbar-right">{{ $name }}</p>
@endsection


@section('container-body')
<div class="container-fluid">
	
  <ol class="breadcrumb">
    <li><a href="/"><span class="gly gly-shop"></span></a></li>
    <li class="active">Dashboard</li>
  </ol>
	
  
    <a href="/dashboard?date={{ $date->copy()->subDay()->format('Y-m-d') }}" class="btn btn-default">
      {{ $date->copy()->subDay()->format('Y-m-d') }}
    </a>
    <button class="btn btn-success">{{ $date->format('D, M j, Y') }}</button>
    <a href="/dashboard?date={{ $date->copy()->addDay()->format('Y-m-d') }}" class="btn btn-default">
      {{ $date->copy()->addDay()->format('Y-m-d') }}
    </a>

    <table class="table">
      <thead>
        <tr>
          <th>Branch</th>
          <th>Sales</th>
          <th>Customer</th>
          <th>Head Spend</th>
          <th>Tips</th>
          <th>Tips %</th>
          <th>Emp Count</th>
          <th>Manpower %</th>
          <th>Cost of Food</th>
          <th>Cost of Food %</th>
        </tr>
      </thead>
      <tbody>
        @foreach($dailysales as $key => $ds) 
  			<tr>
  				<td>{{ $key }} - {{ $ds['br']->descriptor }}</td>
          @if(is_null($ds['ds']))
            <td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td><td>-</td>
          @else
            <td>{{ number_format($ds['ds']->sales,2) }}</td>
            <td>{{ number_format($ds['ds']->custcount,0) }}</td>
            <td>{{ $ds['ds']->custcount==0 ? 0:number_format($ds['ds']->sales/$ds['ds']->custcount, 2) }}</td>
            <td>{{ number_format($ds['ds']->tips,2) }}</td>
            <td>{{ $ds['ds']->custcount==0 || $ds['ds']->tips=='0.00' ? 0:number_format(($ds['ds']->sales/$ds['ds']->custcount)/$ds['ds']->tips, 3) }}</td>
            <td>{{ $ds['ds']->empcount }}</td>
            <td>{{ $ds['ds']->sales=='0.00' ? 0:number_format(($ds['br']->mancost*$ds['ds']->empcount)/$ds['ds']->sales,2) }}</td>
            <td>-</td>
            <td>-</td>
          @endif
          
  			</tr>
  			@endforeach
  		</tbody>
		</table>
  



</div>
@endsection














@section('js-external')
  
 	<script src="/js/vendors-common.min.js"></script>

  
@endsection