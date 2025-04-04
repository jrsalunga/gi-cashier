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
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Your expense record <em><strong>(PURCHASE.NEW)</strong></em> with {{ count($data) }} invoce(s) from the Head Office is ready.</p>
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Kindly follow the instructions below to update your POS expense record (6,1).</p>
        <p style="padding:0;margin:0;margin-bottom:15px;line-height:24px;font-family:Roboto,Helvetica,Arial,sans-serif;">Thank you!</p>
      </div>
    </div>

    @foreach($data as $rcpt)

      <div style="border: 1px solid green ;background-color:#fff; margin-top: 10px; font-family: Noto Sans Mono, monospace; width: 600px;"  >
      <div style="margin:20px 20px 5px;">
      <p style="padding:0;margin:0;">SUPPLIER: {{ $rcpt['suppcode'] }} - {{ $rcpt['supplier'] }}</p>
      <p style="padding:0;margin:0;">DOC #: {{ $rcpt['inv'] }}</p>
      <p style="padding:0;margin:0;">DATE: {{ $rcpt['date'] }}</p>
      <p style="padding:0;margin:0;">TERMS: {{ $rcpt['terms'] }}</p>
      <p style="padding:0;margin:0;">AMOUNT: {{ nf($rcpt['total']) }}</p>
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
    @endforeach

    <div style="border: 1px solid #E8EAED;background-color:#fff; margin-top: 10px">
      <div style="margin:20px;">
        <div style="margin-bottom:15px;font-size:15px;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong>Instructions:</strong></div>

        <ol style="font-family: Roboto,Helvetica,Arial,sans-serif;">
          <li style="margin-top: 10px">Download the attached file <strong>PURCHASE.NEW</strong> and make sure to delete the previous file to prevent the appearance of number on the filename. <em><small>(e.g. <strong>PURCHASE (1).NEW</strong>, <strong>PURCHASE (2).NEW</strong>)</small></em></li>
          <li style="margin-top: 10px"><strong>"Cut/Copy"</strong> and <strong>"Paste"</strong> the file in the <strong>C:\GI_GLO</strong> folder.</li>
          <li style="margin-top: 10px">Re-run the POS Program</li>
          <li style="margin-top: 10px">A confirmation screen will appear. <em><small>(see the sample picture below)</small></em></li>
          <li style="margin-top: 10px">Press <strong>"1"</strong> then <strong>&#60;Enter&#62;</strong> to allow the program to update your record.</li>
          <li style="margin-top: 10px">You may view the new records when you run (6,1).</li>
          <!--
          <li style="margin-top: 10px">Lastly, <strong>print</strong> and <strong>scan</strong> the Stock Purchase Transmittal slip and make sure to <strong>upload</strong> it on Cashier's Module.</li>
          -->
        </ol>

      </div>
    </div>

    <div style="border: 1px solid #E8EAED;background-color:#fff; margin-top: 10px">
      <div style="margin:20px;">
        <div style="margin-bottom:15px;font-size:15px;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong>Notes:</strong></div>

        <ul style="font-family: Roboto,Helvetica,Arial,sans-serif;">
          <li style="margin-top: 10px">Please <strong>double check</strong> your recently encoded expenses on 6-1; the update will <strong style="color: red;">delete/override</strong> encoded expenses on the same day. You can re-encode the deleted expenses records.</li>
        </ul>
      </div>
    </div>

    <div style="padding: 10px 0 10px 0;background-color:#F8F9FA;">
     <small style="font-size:11px;color:gray;font-family:Roboto,Helvetica,Arial,sans-serif;"><em>Note: This is a system generated email.</em></small>
    </div>

    <p>&nbsp;</p>
    <p style="padding: 0;margin: 20px 0 0 0;font-family:Roboto,Helvetica,Arial,sans-serif;"><strong><small>Confirmation Screen</small></strong></p>
    <img src="http://cashier.giligansrestaurant.com/images/uploads/AllowUpdate.PNG" style="background-color:#F8F9FA;">
  </div>
</div>
</table>
</body>
</html>