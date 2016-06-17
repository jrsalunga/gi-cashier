<div>
	<form action='/pepsi' method='GET'> 
		<select name="year">
			<?php
			function i($year){
				return isset($_GET['year']) && $_GET['year']==$year
				? 'selected'
				: '';
			}
			?>

			<option <?=i(2016)?> value="2016">2016</option>
			<option <?=i(2015)?> value="2015">2015</option>
			<option <?=i(2014)?> value="2014">2014</option>
			<option <?=i(2013)?> value="2013">2013</option>
			<option <?=i(2012)?> value="2012">2012</option>
			<option <?=i(2011)?> value="2011">2011</option>
	</select>
	<select name="branchid">
		@foreach($branches as $branch)
			@if(isset($_GET['branchid']) && $_GET['branchid']==$branch->lid())
				<option selected value="{{ $branch->lid() }}">{{ $branch->code }}</option>
			@else
				<option value="{{ $branch->lid() }}">{{ $branch->code }}</option>
			@endif
		@endforeach
	</select>
	<button>submit</submit>
	</form>
</div>

@if(count($data)>0)
<div>
	<table border="1" cellpadding="5" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Month</td>
			</tr>
			@foreach($data[0] as $key => $purchase)
			<tr>
				<td>{{ $key }}</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Pepsi Reg</td>
			</tr>
			@foreach($data[0] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Pepsi Max</td>
			</tr>
			@foreach($data[1] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Pepsi Light</td>
			</tr>
			@foreach($data[2] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Pepsi 500ml</td>
			</tr>
			@foreach($data[3] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Iced Tea</td>
			</tr>
			@foreach($data[4] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Hot Tea</td>
			</tr>
			@foreach($data[5] as $key => $purchase)
			<tr>
				<td>
				@if(is_null($purchase))
					&nbsp;
				@else
					{{ $purchase->qty }}
				@endif
			</td>
			</tr>
			@endforeach
		</tbody>
	</table>

</div>
@endif