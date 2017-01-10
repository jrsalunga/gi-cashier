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
				<td>7-Up</td>
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
				<td>7-Up Light</td>
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
				<td>7-UP 500ml</td>
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
				<td>Mirinda</td>
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
				<td>Mount Dew</td>
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
				<td>Mug</td>
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

	<table border="0" cellpadding="6" cellspacing="0" style="margin-right: 20px; float: left;">
		<tbody>
			<tr>
				<td>Blue</td>
			</tr>
			@foreach($data[6] as $key => $purchase)
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
				<td>Grapes</td>
			</tr>
			@foreach($data[7] as $key => $purchase)
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
				<td>Lemon</td>
			</tr>
			@foreach($data[8] as $key => $purchase)
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
				<td>Orange</td>
			</tr>
			@foreach($data[9] as $key => $purchase)
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
				<td>Pink</td>
			</tr>
			@foreach($data[10] as $key => $purchase)
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
				<td>Tropical</td>
			</tr>
			@foreach($data[11] as $key => $purchase)
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
				<td>Pep Reg</td>
			</tr>
			@foreach($data[12] as $key => $purchase)
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
				<td>Pep Max</td>
			</tr>
			@foreach($data[13] as $key => $purchase)
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
				<td>Pep Light</td>
			</tr>
			@foreach($data[14] as $key => $purchase)
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
				<td>Pep Zero</td>
			</tr>
			@foreach($data[15] as $key => $purchase)
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
			@foreach($data[16] as $key => $purchase)
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
				<td>Tea</td>
			</tr>
			@foreach($data[17] as $key => $purchase)
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