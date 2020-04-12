<!DOCTYPE html>
<html>
<head>
  <title>{{ $subject }}</title>
</head>
<body>
<div style="background-color:#F8F9FA;font-size:14px;">
  <div style=";width:65%;margin-left:auto;margin-right: auto;margin-top:0px;margin-bottom:30px;">
    <div style="padding: 10px 0 10px 0;background-color:#F8F9FA;">
      <img src="{{ $logo }}" style="background-color:#F8F9FA;">
    </div>
    
    <div style="border: 1px solid #E8EAED;background-color:#fff;">
      <div style="margin:20px;">
      <!-- <h2>Welcome</h2> -->
      <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Good day! The export request transfer of employee <em><strong><a href="{{ $href }}" style="text-decoration: none; color: #000; cursor: pointer;">{{ $fullname }}</a></strong></em> was send by <strong>{{ $cashier }}</strong> at <strong>{{ $brcode }}</strong> branch.</p>
      <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Kindly review the attached Employee Transfer Request Form (ETRF) and confirm the transfer on Area Module by clicking the <strong>View Request</strong> link below.</p>

      <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;"><a href="{{ $link }}" style="font-family:Roboto,Helvetica,Arial,sans-serif;font-size:14px;color:#fff;background-color:#007bff;border-color:#007bff;text-decoration:none;display:inline-block;font-weight:500;text-align:center;vertical-align:middle;-webkit-user-select:none;-moz-user-select:none;-ms-user-select:none;user-select:none;border:1px solid transparent;padding:.375rem .75rem;line-height:1.5;border-radius:.25rem;transition:background-color .15s ease-out">View Request</a></p>

      <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Thank you!</p>

      </div>
    </div>

    @if(!is_null($notes))
    <div style="border: 1px solid #E8EAED;background-color:#fff; margin-top: 10px">
      <div style="margin:20px;">
        <div style="margin-bottom:15px;font-size:13px;color:gray;font-family:Roboto,Helvetica,Arial,sans-serif;">Note:</div>

        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;"><em>"{{ $notes }}"</em></p>
        <p style="padding:0;margin:20px 0 15px 0;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">{{ $cashier }}</p>
      </div>
    </div>
    @endif

    <div style="padding: 10px 0 10px 0;background-color:#F8F9FA;">
     <small style="font-size:11px;color:gray;font-family:Roboto,Helvetica,Arial,sans-serif;"><em>Note: This is a system generated email.</em></small>
    </div>
  </div>
</div>
</table>
</body>
</html>

