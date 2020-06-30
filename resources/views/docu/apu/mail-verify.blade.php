<p>Accounts payable document ({{ $model->filename }}) has been uploaded on server.</p>
<p></p>

<ul style="margin-top: 0;">
	<li title="Uploaded Filename">{{ $model->fileUpload->filename }}</li>
  <li title="Document Type">{{ $model->doctype->descriptor }}</li>
  <li title="Supplier">{{ $model->supplier->descriptor }}</li>
  <li title="No.">{{ $model->refno }}</li>
	<li title="Transmittal Date">{{ $model->date->format('D M j, Y') }}</li>
	<li title="Amount">{{ number_format($model->amount, 2) }}</li>
</ul>

<p>{{ $model->remarks }}</p>
<p>- {{ $model->cashier }}</p>
<p>&nbsp;</p>

<p>
  <a href="{{ $link }}?src=email" style="display: inline-block;
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
    border-color: #4cae4c;" title="Verify Document">
    Verify Document
  </a>
</p>

<p>&nbsp;</p>
<small><em>Note: this is a system generated email.</em></small>


