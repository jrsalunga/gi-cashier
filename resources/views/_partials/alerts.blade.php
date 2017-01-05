@if (count($errors) > 0)
    <div class="alert alert-danger" role="alert">
      <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
      <ul>
      @foreach($errors->all() as $message) 
        <li>{{ $message }}</li>
      @endforeach
    </ul>
    
    </div>
 
@endif


@if(session()->has('alert-success'))
  <div class="alert alert-success {{ session()->has('alert-important') ? 'alert-important':'' }}">
    {{ session('alert-success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

@if(session()->has('alert-error'))
  <div class="alert alert-danger {{ session()->has('alert-important') ? 'alert-important':'' }}">
    {{ session('alert-error') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

@if(session()->has('alert-warning'))
  <div class="alert alert-warning {{ session()->has('alert-important') ? 'alert-important':'' }}">
    {{ session('alert-warning') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
@endif

@if(session()->has('backup-success'))
  <div class="alert alert-success">
    {{ session('backup-success') }} 
    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
      <span aria-hidden="true">&times;</span>
    </button>
  </div>
  <div class="alert alert-important text-primary" style="margin: -25px 0 -5px 0;">
    <?php
      if(strpos(request()->url(), '.com'))
        $url = 'https://goo.gl/gO9X3G';
      else if(strpos(request()->url(), '.net'))
        $url = 'https://goo.gl/OcbBKs';
      else
        $url = '/backups/checklist';

    ?>
    <b><span class="glyphicon glyphicon-alert"></span> Reminders:</b> Always check if you have complete End of Day POS Backup by checking on <a href="{{ $url }}" class="btn btn-default"><span class="fa fa-calendar-check-o"></span> Checklist</a>
  </div>
@endif