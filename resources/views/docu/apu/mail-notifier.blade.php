<p>{{ $user }}: {{ $action }}</p>
<p></p>

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

<p>Changes of fields: {{ $remarks }}</p>

<p>&nbsp;</p>
<p>
  <a href="http://boss.giligansrestaurant.com/ap/{{ $model->lid() }}?src=email" style="display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    border: 1px solid transparent;
    border-radius: 4px;
    vertical-align: middle;
    color: #fff;
    background-color: #5cb85c;
    text-decoration: none;
    border-color: #4cae4c;" title="View Document">
    View Document
  </a>
</p>

<p>&nbsp;</p>


