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