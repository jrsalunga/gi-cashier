<!DOCTYPE html>
<html>
<head>
  <title>{{ $subject }}</title>
</head>
<body>

<div style="background-color:#F8F9FA;font-size:14px;">
  <div style=";width:90%;margin-left:auto;margin-right: auto;margin-top:0px;margin-bottom:30px;">
    <div style="padding: 10px 0 10px 0;background-color:#F8F9FA;">
      <img src="http://boss.giligansrestaurant.com/images/giligans-header.png" style="background-color:#F8F9FA;">
    </div>

    <div itemscope itemtype="http://schema.org/EmailMessage">
      <div itemprop="potentialAction" itemscope itemtype="http://schema.org/ConfirmAction">
        <meta itemprop="name" content="Approve Expense"/>
        <div itemprop="handler" itemscope itemtype="http://schema.org/HttpActionHandler">
          <link itemprop="url" href="https://myexpenses.com/approve?expenseId=abc123"/>
        </div>
      </div>
      <meta itemprop="description" content="Approval request for John's $10.13 expense for office supplies"/>
    </div>
    
    <div style="border: 1px solid #E8EAED;background-color:#fff;">
      <div style="margin:20px;">
        <!-- <h2>Welcome</h2> -->
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Dear Cashier,</p>
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Your expense record with {{ count($data) }} invoice{{ count($data)>1?'s':'' }} below from Head Office is ready for manually encoding.</p>
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Kindly follow the instructions below to update your POS expense record (6,1).</p>
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Thank you!</p>
      </div>
    </div>

    <?php $ctr=1; ?> 
    @foreach($data as $rcpt)

      <div style="border-top: 1px solid green;border-left: 1px solid green;border-right: 1px solid green;border-bottom: 0px;background-color:#fff;margin-top: 10px;padding-bottom: 10px;font-family: Noto Sans Mono, monospace;width: 600px;">
        <p style="padding:0;margin:10px 20px 0px 20px;"><b>Delivery Receipt/Invoice # {{ $ctr }}</b></p>
      </div>
      <div style="border: 1px solid green ;background-color:#fff; margin-top: 0px; font-family: Noto Sans Mono, monospace; width: 600px;">
        <div style="margin:20px 20px 5px;">
        <p style="padding:0;margin:0;">SUPPLIER: <b>{{ $rcpt['suppcode'] }} - {{ $rcpt['supplier'] }}</b></p>
        <p style="padding:0;margin:0;">DOC #: <b>{{ $rcpt['inv'] }}</b></p>
        {{-- <p style="padding:0;margin:0;">DATE: {{ $rcpt['date'] }}</p> --}}
        <p style="padding:0;margin:0;">TERMS: <b>{{ $rcpt['terms'] }}</b></p>
        <p style="padding:0;margin:0;">AMOUNT: <b>{{ nf($rcpt['total']) }}</b></p>
        </div>
        <table style="margin:0px 0px 20px 20px; width:92%;">
          <tbody>
            @foreach($rcpt['items'] as $item)
            <tr>
              <td style="width:5%;">{{ $item['pcode'] }}</td>  
              <td style="width:45%;">{{ $item['comp'] }}</td>  
              <td>{{ $item['qty'] }} {{ $item['unit'] }}</td>  
              <td style="text-align: right;">@ {{ nf($item['ucost']) }}</td>  
              <td style="text-align: right;">{{ nf($item['tcost']) }}</td>  
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      <?php $ctr++; ?> 
    @endforeach

    <div style="border: 1px solid #E8EAED;background-color:#fff; margin-top: 10px">
      <div style="margin:20px;">
        <div style="margin-bottom:15px;font-size:15px;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong>Notes:</strong></div>

        <ul style="font-family: Roboto,Helvetica,Arial,sans-serif;">
          <li style="margin-top: 10px">Please note that we removed the attached PURCHASE.NEW file to download.</li>
          <li style="margin-top: 10px">Kindly manually encode the {{ count($data) }} invoice{{ count($data)>1?'s':'' }} on the POS expense record (6,1).</li>
        </ul>
      </div>
    </div>

    <div style="border: 1px solid #E8EAED;background-color:#fff; margin-top: 10px">
      <div style="margin:20px;">
        <div style="margin-bottom:15px;font-size:15px;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong>Instructions:</strong></div>

        <ol style="font-family: Roboto,Helvetica,Arial,sans-serif;">
          <li style="margin-top: 10px">Before encoding the invoice{{count($data)>1?'s':''}}, make sure to check whether it has already been encoded on POS to avoid duplication.</li>
          <li style="margin-top: 10px">If everything is clear, please proceed with encoding of the invoice{{count($data)>1?'s':''}} on the POS expense record (6,1).</li>
          <li style="margin-top: 10px">After encoding, make sure to match the POS printout on the email to ensure that all details are accurate and the same.</li>
          <!--
          <li style="margin-top: 10px">Lastly, <strong>print</strong> and <strong>scan</strong> the Stock Purchase Transmittal slip and make sure to <strong>upload</strong> it on Cashier's Module.</li>
          -->
        </ol>

      </div>
    </div>

    

    <div style="padding: 10px 0 10px 0;background-color:#F8F9FA;">
     <small style="font-size:11px;color:gray;font-family:Roboto,Helvetica,Arial,sans-serif;"><em>Note: This is a system generated email.</em></small>
    </div>

    {{-- <p>&nbsp;</p>
    <p style="padding: 0;margin: 20px 0 0 0;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong><small>Confirmation Screen</small></strong></p>
    <img src="http://cashier.giligansrestaurant.com/images/uploads/AllowUpdate.PNG" style="background-color:#F8F9FA;"> --}}
  </div>
</div>
</table>
</body>
</html>