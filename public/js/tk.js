
var buildEmployeesTimelogs = function(data){
	/*	
	var tmlgs = getEmployeeTimelogs(data.data.id)
	var html = htmlEmployeeTimelogs2(tmlgs);
	*/
	getEmployeeTimelogs(data.data.id).done(function(data){
		htmlEmployeeTimelogs2(data);
	});
};

var getEmployeeTimelogs = function(id){
	//var aData;
	return $.ajax({
        type: 'GET',
        contentType: 'application/json',
		url: 'api/timelog/employee/'+ id,
        dataType: "json",
        //async: false,
        success: function(data, textStatus, jqXHR){
            //aData = data;
			$('.notify').css('display', 'none');
        },
        error: function(jqXHR, textStatus, errorThrown){
			$('.message-group').html('<div class="alert alert-danger">Could not connect to server!</div>');
            //alert(textStatus + ' Failed on posting data');
        }
    });	
	
	//return aData;
}

var htmlEmployeeTimelogs2 = function(data){
	//console.log(data);
	var len = 15;
	var ctr = 0;
	
	$('#TimelogModal .modal-body').html('');
	
	var html = '<div class="row"><div class="col-md-12">';
	html += '<table class="table table-condensed">';
	html += '<thead><tr><th>Day</th><th>Time In</th><th>Time Out</th></tr></thead><tbody class="tk-tb">';
	html += '</tbody></table></div></div>';
	
	$('#TimelogModal .modal-body').prepend(html);
	
	for(i=0; i<data.length; i++) {
		
		var h = '';
		if(data[i].day == 'Sun'){
			h += '<tr class="warning">';
		} else {
			h += '<tr>';
		}
		
		h += '<td><em>'+ data[i].day +'</em> '+ moment(data[i].date).format("MMMM D, YYYY") +'</td>';
		h += '<td>'+ data[i].ti +'</td>';
		h += '<td>'+ data[i].to +'</td>';
		h += '</tr>';
		
		$('.tk-tb').prepend(h);	
	}
}


var htmlEmployeeTimelogs = function(data){
	console.log(data);
	var len = 15;
	var ctr = 0;
	
	var html = '<div class="row"><div class="col-md-6">';
	html += '<table class="table table-condensed">';
	html += '<thead><tr><th>Day</th><th>Time In</th><th>Time Out</th></tr></thead><tbody>';
	for(i=0; i<data.length; i++) {
		if(i==len){
			html += '</tbody></table></div><div class="col-md-6">';
			html += '<table class="table table-condensed">';
			html += '<thead><tr><th>Day</th><th>Time In</th><th>Time Out</th></tr></thead><tbody>';
		} 
		if(data[i].day == 'Sun'){
			html += '<tr class="warning">';
		} else {
			html += '<tr>';
		}
		
		html += '<td>'+ (i+1) +' <em>('+ data[i].day +')</em></td>';
		html += '<td>'+ data[i].ti +'</td>';
		html += '<td>'+ data[i].to +'</td>';
		html += '</tr>';
		//console.log(data[i].date);
	}
	html += '</tbody></table></div></div>';
	
	
	return html;
}




var appendToTkList = function(data){
	
	var d = moment(data.data.date+' '+data.data.time).format('hh:mm:ss A');
	var c = moment(data.data.date+' '+data.data.time).format('MMM DD');
	
		var html = '<tr class="txncode'+ data.data.txncode +'"><td>'+ data.data.empno +'</td>';
			html += '<td>'+ data.data.lastname +', '+ data.data.firstname +'</td>'
			html += '<td><span> '+ c +' </span>&nbsp; '+ d +' </td>'
			html += '<td>'+ data.data.txnname;
			//html += '<span id="'+ data.data.timelogid +'" ';
			//html += 'class="glyphicon glyphicon-remove-circle pull-right" style="opacity: .5;"></span>';
			html += '<td>'+ data.data.branch +'</td>'
			html += '</td></tr>';
			
		if($('.emp-tk-list tr').length >= 15){
			$('.emp-tk-list tr:last-child').empty();
			$('.emp-tk-list tr:last-child').remove();
		}
	
		$('.emp-tk-list tr:first-child').before(html)
			.prev().find('td').each(function(){
				$(this).effect("highlight", {}, 1000);
			});
}

var updateEmpView = function(data){

	$('#emp-img').attr('src', 'images/employees/'+ data.data.empno +'.jpg');
	$('#emp-code').text(data.data.empno);
	$('#emp-name').text(data.data.lastname +', '+ data.data.firstname);
	$('#emp-pos').text(data.data.position);

}

var updateEmpViewModal = function(data){
	$('#mdl-emp-img').attr('src', 'images/employees/'+ data.data.code +'.jpg');
	$('#mdl-emp-code').text(data.data.code);
	$('#mdl-emp-name').text(data.data.lastname +', '+ data.data.firstname);
	$('#mdl-emp-pos').text(data.data.position.descriptor);
	
}


var updateTK = function(data){
	console.log('updateTK');
	console.log(data.data);
	data = data || {};
		
	var html = '<div class="alert alert-'+ data.status +'">'+ data.message +'</div>';	
	$('.message-group').html(html);
	
	if(data && data.code=='200' || data.code=='201'){
		appendToTkList(data);	
		updateEmpView(data);
		//var loc = $('body').data('location');
		
		//console.log(loc);
		//console.log(loc+'-'+data.data.txncode);
		
		//socket.emit(loc+'-'+data.data.txncode, data.data);

		setInterval( function() {
			$('.message-group div').fadeOut(1600);
		},3000);
	} else {
		console.log('error');
	}
}

var updateTKmodal = function(data){
	//console.log(data);
	data = data || {};
		
	var html = '<div class="alert alert-'+ data.status +'">'+ data.message +'</div>';	
	$('.message-group').html(html);
	
	if(data && data.code=='200'){
		//appendToTkList(data);	
		updateEmpViewModal(data);
		$('#TKModal').modal('show');
		setInterval( function() {
			$('.message-group div').fadeOut(1600);
		},3000);
	} else {
		console.log('error');
	}
}

var isInt = function(n) {	
   return n % 1 === 0;
}

var validateEmpno = function(empno){
	if(empno!=undefined && isInt(empno) && empno.length==10){
	//if(empno!=undefined && empno.length==10){
		return true;
	} else {
		console.log('Error on validate');
		
		var html = '<div class="alert alert-info">Unknown RFID: '+ empno +'</div>';	
		$('.message-group').html(html);
		
		setInterval( function() {
			$('.message-group div').fadeOut(1600);
		},3000);
		return false;
	}
}

var saveEmpTimelog = function(empno, txncode, location){
	if(validateEmpno(empno)){
		postTimelog(preparePostTimelogData(empno, txncode), location)
		.done(function(data){
			updateTK(data);
			$('#TKModal').modal('hide');
		});
	}		
}

// send a curl request 
var replicate = function(data){

	//console.log(data);

	var formData = {
		timelogid : data.data.timelogid
	}
	
	return $.ajax({
        type: 'POST',
        contentType: 'application/x-www-form-urlencoded',
		url: 'api/replicate',
        dataType: "json",
        //async: false,
        data: formData,
        beforeSend: function(jqXHR, obj) {
       		//('.notify .inner').html('Replicating...');
	    	//$('.notify').css('display', 'block');
	    	beforeSync();
  		},
        success: function(data, textStatus, jqXHR){
			//$('.notify').css('display', 'none');
			//$('.notify .inner').html('Done...');
			synced(data);
        },
        error: function(jqXHR, textStatus, errorThrown){
			$('.message-group').html('<div class="alert alert-danger">Could not connect to server!</div>');
            //alert(textStatus + ' Failed on posting data');
        }
    });	
}

var beforeSync = function(){
	el = $('.emp-tk-list tr:first-child td:last-child span');
	el.removeClass('glyphicon-remove-circle');
	el.addClass('rotate');
	el.addClass('glyphicon-refresh');
	delete el;
}

var synced = function(data){
	el = $('.emp-tk-list tr:first-child td:last-child span');
	el.removeClass('glyphicon-refresh');
	el.removeClass('rotate');
	if(data.code == 200){
		el.addClass('glyphicon-cloud');
		el.attr('title', 'synced');
	} else {
		el.addClass('glyphicon-remove-circle');
		el.attr('title', 'not synced');
	}
	el.parent().effect("highlight", {}, 1000);
	delete el;
	console.log('synced!');
}

var preparePostTimelogData = function(empno, tc){

	return {
		rfid : empno,
		datetime: moment().format('YYYY-MM-DD HH:mm:ss'),
		txncode: tc,
		entrytype: '1',
		//terminalid: 'plant' gethostname
	}

}

var postTimelog = function(data, source){
	//var aData;

	source = source || loc;
	
	var formData = data;
	console.log(formData);
	
	return $.ajax({
        type: 'POST',
        contentType: 'application/x-www-form-urlencoded',
				url: 'api/timelog',
        dataType: "json",
        //async: false,
        data: formData,
        beforeSend: function(jqXHR, obj) {
       		$('.notify .inner').html('Saving...');
	    		$('.notify').css('display', 'block');

  		},
        success: function(data, textStatus, jqXHR){
            //aData = data;
			//updateTK(data);
			$('.notify').css('display', 'none');
			$('.notify .inner').html('Loading...');
        },
        error: function(jqXHR, textStatus, errorThrown){
			$('.message-group').html('<div class="alert alert-danger">Could not connect to server!</div>');
            //alert(textStatus + ' Failed on posting data');
            //console.log(textStatus);
            //console.log(errorThrown);
            //console.log(jqXHR);
        }
    });	
}


var getEmployee = function(empno){
	//var aData;
	
	return $.ajax({
        type: 'GET',
        contentType: 'application/json',
				url: 'api/employee/rfid/'+ empno,
        dataType: "json",
        //async: false,
        success: function(data, textStatus, jqXHR){
            //aData = data;
			//updateTKmodal(data);
			//console.log('success..');
			$('.notify').css('display', 'none');
        },
        error: function(jqXHR, textStatus, errorThrown){
			$('.message-group').html('<div class="alert alert-danger">Could not connect to server!</div>');
            //alert(textStatus + ' Failed on posting data');
            //console.log('error..');
        }
    });	
	
	//return aData;
}


var keypressInit = function(){
	
	var data = {};
	var empno = '';
	var posted = false;
	
	
	var endCapture = false;
	var arr = [];
	var last_empno = '';
	var empData = {};


	
	
	$('#TKModal').on('hidden.bs.modal', function (e) {
		//console.log('modal hide');
		endCapture = false;
		arr = [];
		last_empno = '';
		empData = {};
	});

	/****** button functions ******/
	$('#btn-time-in').on('click', function(){
		saveEmpTimelog(empno, 1, 'local');
	});

	$('#btn-break-start').on('click', function(){
		saveEmpTimelog(empno, 2, 'local');
	});

	$('#btn-break-end').on('click', function(){
		saveEmpTimelog(empno, 3, 'local');
	});

	$('#btn-time-out').on('click', function(){
		saveEmpTimelog(empno, 4, 'local');
	});
	
	
	$(this).bind('keypress', function(e){
		var code = e.which || e.keyCode;
		$('.empno').text('');
		//console.log('keypress');
		//console.log(code);		
		
		if(code == 13) { //Enter keycode

			//$('.empno').text(arr.join('',','));
			empno = arr.join('',',');
			//console.log(empno);
			//console.log('Press Enter');
			if(validateEmpno(empno) && last_empno != empno){
				//console.log('Fetching employee: '+ empno);
				
				//empData = getEmployee(empno);
				//updateTKmodal(empData);

				getEmployee(empno).done(function(data){
					updateTKmodal(data);
					empData = data;
				});

				last_empno = empno;
			} else {
				console.log('Same Empno');	
			}
			
			endCapture = true;
			arr = [];
														 // capslock jenn pc
		} else if((code == 105 || code == 102 || code == 70) && endCapture){ // timein    49="1"
				
			if(validateEmpno(empno)){
				//console.log('Time In: '+ empno);
				//postTimelog(empno,'ti');

				postTimelog(preparePostTimelogData(empno,'1'), 'local')
				.done(function(data){
					updateTK(data); //update when socket emit
					console.log('emit');
					//socket.emit(loc+'-'+data.data.txncode, data);
					//socket.emit('timein', data);
					$('#TKModal').modal('hide');
				});

				//socket.emit('ti', preparePostTimelogData(empno,'ti'));
				//$('#TKModal').modal('hide');
			}		
			
			/* on modal hide do this
			endCapture = false;
			arr = [];
			last_empno = '';
			*/               
		} else if((code == 98 || code == 66) && endCapture){ // break start
			if(validateEmpno(empno)){
				postTimelog(preparePostTimelogData(empno,'2'), 'local')
				.done(function(data){
					updateTK(data); //update when socket emit
					$('#TKModal').modal('hide');
				});
			}
		} else if((code == 110 || code == 78) && endCapture){ // break end
			
			if(validateEmpno(empno)){
				postTimelog(preparePostTimelogData(empno,'3'), 'local')
				.done(function(data){
					updateTK(data); //update when socket emit
					$('#TKModal').modal('hide');
				});
			}
			                          // capslock jenn pc
		} else if((code == 111 || code == 106 || code == 74) && endCapture){ // timeout	50="2"	or 48 ="0"
			
			if(validateEmpno(empno)){
				//console.log('Time Out: '+ empno);
				//postTimelog(empno,'to');
				
				postTimelog(preparePostTimelogData(empno,'4'), 'local')
				.done(function(data){
					updateTK(data); //update when socket emit
					console.log('emit');
					//socket.emit(loc+'-'+data.data.txncode, data);
					//socket.emit('timeout', data);
					$('#TKModal').modal('hide');
				});
				

				//socket.emit('to', preparePostTimelogData(empno,'to'));
				//$('#TKModal').modal('hide');
			}
			
			/*
			endCapture = false;
			arr = [];
			last_empno = '';
			*/
		} else if((code == 116 || code == 64 || code == 114 || code == 84) && endCapture){ // press view timelogs
			$('#TKModal').modal('hide');
			if(validateEmpno(empno)){
				console.log('Get Employee Timelog: '+ empno);
				//postTimelog(empno,'to');
				buildEmployeesTimelogs(empData);
				$('#TimelogModal').modal('show');				
			}
			
			endCapture = false;
			arr = [];
			last_empno = '';
		} else {
			
			arr.push(String.fromCharCode(code));
			//console.log(arr.join(''));
			var html = '<div class="alert alert-info">'+ arr.join('') +'</div>';	
			$('.message-group').html(html);
			
				
			
			/*
			if(posted){
				arr = [];
				posted = false;	
			} else {
				
			}
			
			if(code == 105 && endCapture && arr.length == 1){
				console.log('time in')
				posted = true;
				$('#TKModal').modal('hide');
			}
			
			if(code == 111 && endCapture && arr.length == 1){
				console.log('time out')
				posted = true;
				$('#TKModal').modal('hide');
			}
			*/
			
		}	
	});
}


var InitClock = function(){
	
	//var timezone = moment.tz(DateWithTimezone.getTimezone()).format("Z")
	
	
	setInterval( function() {
		//$('.ts').html(moment().format('hh:mm:ss'));
		var sec = moment().format('ss');
		var min = moment().format('mm');
		var hr = moment().format('hh');
		$('.tsg-sec .tsg-l .tsg-img').attr('src', '/images/tk/'+sec[0]+'.png');
		$('.tsg-sec .tsg-r .tsg-img').attr('src', '/images/tk/'+sec[1]+'.png');

		$('.ts-min .tsg-l .tsg-img').attr('src', '/images/tk/'+min[0]+'.png');
		$('.ts-min .tsg-r .tsg-img').attr('src', '/images/tk/'+min[1]+'.png');

		$('.ts-hr .tsg-l .tsg-img').attr('src', '/images/tk/'+hr[0]+'.png');
		$('.ts-hr .tsg-r .tsg-img').attr('src', '/images/tk/'+hr[1]+'.png');
	},1000);
	
	setInterval( function() {
		$('#am').attr('src', '/images/tk/'+moment().format('a')+'.png')  //.html(moment().format('a'));
	},1000);
	//},64000); // 1 min
	
	setInterval( function() {
		$('.day').html(moment().format('ddd'));
		//$('.day').html(moment().format('MMM D'));
		$('#date time').html(moment().format("MMM D YYYY"));
	},1000);
	//},3600000); 
	
	
}










function geoFindMe() {
  var output = document.getElementsByClassName("message-group")[0];

  if (!navigator.geolocation){
    output.innerHTML = "<p>Geolocation is not supported by your browser</p>";
    return;
  }

  function success(position) {
    var latitude  = position.coords.latitude;
    var longitude = position.coords.longitude;

    //console.log(latitude);

    output.innerHTML = '<p>Latitude is ' + latitude + '° <br>Longitude is ' + longitude + '°</p>';

    //var img = new Image();
    //img.src = "https://maps.googleapis.com/maps/api/staticmap?center=" + latitude + "," + longitude + "&zoom=13&size=300x300&sensor=false";

    //output.appendChild(img);
  };

  function error() {
    output.innerHTML = "Unable to retrieve your location";
  };

  output.innerHTML = "<p>Locating…</p>";

  var opt = {
	  //enableHighAccuracy: true,
	  //timeout: 5000,
	  //maximumAge: 0
	}

  navigator.geolocation.getCurrentPosition(success, error, opt);
}


var drawLocation = function(){

  var output = document.getElementsByClassName("img-loc")[0];

  if (!navigator.geolocation){
    output.innerHTML = "<p>Geolocation is not supported by your browser</p>";
    return;
  }

  function success(position) {
    var latitude  = position.coords.latitude;
    var longitude = position.coords.longitude;
    var accuracy = position.coords.accuracy;

    //console.log(latitude);

    output.innerHTML = '<p>Latitude is ' + latitude + '° <br>Longitude is ' + longitude + '°'+ '<br>More or less ' + accuracy + ' meters</p>';

    var img = new Image();
    img.src = "https://maps.googleapis.com/maps/api/staticmap?center=" + latitude + "," + longitude + "&zoom=15&size=400x400&sensor=true&markers=size:mid%7Ccolor:red%7C"+ latitude + "," + longitude;

    output.appendChild(img);

  };

  function error() {
    output.innerHTML = "Unable to retrieve your location";
  };

  output.innerHTML = "<p>Locating…</p>";

  var opt = {
	  enableHighAccuracy: true,
	  timeout: 5000,
	  maximumAge: 0
	}

  navigator.geolocation.getCurrentPosition(success, error, opt);

}




var getLocation = function(){

	var response = {};
  //var output = document.getElementsByClassName("img-loc")[0];

  if (!navigator.geolocation){
    output.innerHTML = "<p>Geolocation is not supported by your browser</p>";
    return;
  }

  function success(position) {
    response.latitude  = position.coords.latitude;
    response.longitude = position.coords.longitude;
    response.accuracy = position.coords.accuracy;

    //console.log(latitude);

    //output.innerHTML = '<p>Latitude is ' + latitude + '° <br>Longitude is ' + longitude + '°'+ '<br>More or less ' + accuracy + ' meters</p>';

    //var img = new Image();
    //img.src = "https://maps.googleapis.com/maps/api/staticmap?center=" + latitude + "," + longitude + "&zoom=15&size=400x400&sensor=true&markers=size:mid%7Ccolor:red%7C"+ latitude + "," + longitude;

    //output.appendChild(img);

    response.status = 'ok';

    return response;

  };

  function error() {
    //output.innerHTML = "Unable to retrieve your location";
    response.status = 'error';
    return response;
  };

  //output.innerHTML = "<p>Locating…</p>";

  var opt = {
	  enableHighAccuracy: true,
	  timeout: 5000,
	  maximumAge: 0
	}

  navigator.geolocation.getCurrentPosition(success, error, opt);

}

var marker, pos, info;

function initMap() {
	var myLatLng = {lat: 14.569964, lng: 121.045599};
	var imgmap = document.getElementById('map');
	
	info = document.getElementsByClassName('loc-info')[0];

  var map = new google.maps.Map(imgmap, {
    center: myLatLng,
    zoom: 16
  });

  marker = new google.maps.Marker({
    position: myLatLng,
    map: map,
    title: 'Your here!',
    draggable: true,
    animation: google.maps.Animation.DROP,
  });

  //markerRender();

  marker.addListener('click', markerClick);
  marker.addListener('drag', markerRender);

 
  

  //var infoWindow = new google.maps.InfoWindow({map: map});

  // Try HTML5 geolocation.
  if (navigator.geolocation) {
    navigator.geolocation.getCurrentPosition(function(position) {
    	
      pos = {
        lat: position.coords.latitude,
        lng: position.coords.longitude,
        acc: position.coords.accuracy
      };

      $('#ilat').val(pos.lat);
			$('#ilong').val(pos.lng);
			$('#lat').val(pos.lat);
			$('#long').val(pos.lng);

      info.innerHTML = '<p>Latitude is ' + pos.lat + '° <br>Longitude is ' + pos.lng + '°'+ '<br>More or less ' + pos.acc + ' meters</p>';
      

      marker.setPosition(pos);
      //marker.setContent('Location found.');
      map.setCenter(pos);
    }, function() {
      handleLocationError(true, infoWindow, map.getCenter());
    });
  } else {
    // Browser doesn't support Geolocation
    handleLocationError(false, infoWindow, map.getCenter());
  }
}




function handleLocationError(browserHasGeolocation, infoWindow, pos) {
  infoWindow.setPosition(pos);
  infoWindow.setContent(browserHasGeolocation ?
                        'Error: The Geolocation service failed.' :
                        'Error: Your browser doesn\'t support geolocation.');
}

function markerClick(){
	marker.setAnimation(google.maps.Animation.DROP);
	console.log('marker click');
}

function markerRender(event){
	console.log('dragging');
	console.log(event);
	pos.lat = event.latLng.lat();
	pos.lng = event.latLng.lng();
	pos.acc = 0;
	//console.log(info);
	$('#lat').val(pos.lat);
	$('#long').val(pos.lng);
	info.innerHTML = '<p>Latitude is ' + pos.lat + '° <br>Longitude is ' + pos.lng + '°'+ '<br>More or less ' + pos.acc + ' meters</p>';
}



$(document).ready(function(){
		
	
	InitClock();

	keypressInit();

	



});