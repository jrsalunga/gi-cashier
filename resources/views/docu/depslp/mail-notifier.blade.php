<p>{{ $user }}: {{ $action }}</p>

<ul>
	<li>{{ $depslp->fileUpload->filename }}</li>
	<li>{{ $depslp->filename }}</li>
	<li>{{ number_format($depslp->amount, 2) }}</li>

	<li>{{ $depslp->deposit_date->format('D M j h:i:s A') }} <small>{{ diffForHumans($depslp->deposit_date) }}</small></li>
	<li>{{ $depslp->cashier }}</li>
	<li>{{ $depslp->remarks }}</li>

</ul>

<p>Remarks: {{ $remarks }}</p>


