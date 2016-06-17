<div>
	<form action='/pepsi' method='GET'> 
		<select name="year">
			<option value="2016">2016</option>
			<option value="2015">2015</option>
			<option value="2014">2014</option>
			<option value="2013">2013</option>
			<option value="2012">2012</option>
			<option value="2011">2011</option>
	</select>
	<select name="branchid">
		@foreach($branches as $branch)
			@if($_GET['branchid']==$branch->lid())
				<option selected value="{{ $branch->lid() }}">{{ $branch->code }}</option>
			@else
				<option value="{{ $branch->lid() }}">{{ $branch->code }}</option>
			@endif
		@endforeach
	</select>
	<button>submit</submit>
	</form>
</div>

<div>

</div>