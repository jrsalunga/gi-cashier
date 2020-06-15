<p>{{ $user }}: {{ $action }}</p>
<p></p>

<p style="margin-bottom: 0;">Old Record:</p>
<ul style="margin-top: 0;">
	<li>{{ $model->fileUpload->filename }}</li>
	<li>{{ $model->filename }}</li>
  <li>{{ $model->doctype->descriptor }}</li>
	<li>{{ number_format($model->amount, 2) }}</li>
	<li>{{ $model->date->format('D M j, Y') }}</li>
  <li>{{ $model->refno }}</li>
  <li>{{ $model->supplier->descriptor }}</li>
	<li>{{ $model->cashier }}</li>
	<li>{{ $model->remarks }}</li>
</ul>

<p>Updated Fields: {{ $remarks }}</p>

<p>&nbsp;</p>
<small><em>Note: this is a system generated email.</em></small>


