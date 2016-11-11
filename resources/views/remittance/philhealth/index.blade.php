@extends('index')

@section('title', ' - Settings')


@section('container-body')
<div class="container-fluid">
	
  <ol class="breadcrumb">
    <li><span class="gly gly-shop"></span> <a href="/">{{ $branch }}</a></li>
    <li>Remittance</li>
    <li class="active">Philhealth</li>
  </ol>

  <ul class="nav nav-tabs">
    <li role="presentation"><a href="#">SSS</a></li>
    <li role="presentation" class="active"><a href="/remittance/philhealth">PhilHealth</a></li>
    <li role="presentation"><a href="#">Pag Ibig</a></li>
  </ul>



  <div class="row">
    <div class="col-md-6">
      <div style="margin-top:10px;">
        @include('_partials.alerts')

        @if(session()->has('dl'))
          <div class="alert alert-success alert-important">
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
              <span aria-hidden="true">&times;</span>
            </button>
            Success! <a href="/dl/{{ session('dl') }}"><strong>{{ session('dl') }}</strong></a>
          </div>
        @endif

        <form action="/remittance/upload" method="POST" enctype="multipart/form-data">
          {{ csrf_field() }}
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="file">Attach File:</label>
                <input type="file" id="file" name="file" required>
              </div>
            </div>
          </div><!-- end: .row -->
          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label for="company_id">Company</label>
                <select type="text" class="form-control" id="company_id" name="company_id" required>
                  <option value="">Select Company</option>
                  @foreach($companies as $company)
                    <option value="{{ $company->id }}">{{ $company->descriptor }}</option>
                  @endforeach
                </select>
              </div>
            </div>
          </div><!-- end: .row -->
          <div class="row">
            <div class="col-md-4">
              <div class="form-group">
                <label for="file">Month:</label>
                <div class="btn-group dp-container" role="group">
                  <label class="btn btn-default" for="dp-date">
                    <span class="glyphicon glyphicon-calendar"></span>
                  </label>
                  <input readonly="" type="text" class="btn btn-default dp" id="dp-date" style="max-width: 110px;">
                  <input type="hidden" id="date" name="date">
                </div>
              </div>
            </div>
          </div><!-- end: .row -->
          <div class="row">
            <div class="col-md-4">
              <button type="submit" id="btn-submit" class="btn btn-primary">
                <span class="gly gly-ok"></span> Submit
              </button>
            </div>
          </div><!-- end: .row -->

          
        </form>
      </div>
    </div>
    <div class="col-md-6">
      
    </div>
  </div>
  



</div>
@endsection



@section('js-external')
  <script src="/js/vendors-common.min.js"></script>

<script type="text/javascript">
  
  $('#date').val(moment().format('YYYY-MM-DD'));
  
  $('#dp-date').datetimepicker({
    defaultDate: moment(),
    format: 'MM/YYYY',
    showTodayButton: true,
    ignoreReadonly: true,
    viewMode: 'months'
  }).on('dp.change', function(e){
    var date = e.date.format('YYYY-MM-DD');
    console.log(date);
    $('#date').val(date);
  });

</script>
@endsection










