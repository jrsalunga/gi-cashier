$(function(){

	/*
	var re = /\b(GC)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d\d)(\.ZIP)/gi; 
	var str = 'GC071416.ZIP';
	
	console.log(re.exec(str));
	console.log(str.match(re));
	*/
	var resetForm =  function () {
		$('#form-file').trigger("reset");
		$('#btn-upload').prop('disabled', true);
	}

	var backupVerifyFilename = function(filename){
		if(filename.length!==12) {
			return false;
		}
		var re = /\b(GC)(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(\d\d)(\.ZIP)/gi; 
		if(!filename.match(re)) {
			return false;
		} 
		return true;
	}

	var dropbox = $('#dropbox'),
			message = $('.message', dropbox);
	
	dropbox.filedrop({
		// The name of the $_FILES entry:
		fallback_id: 'file_upload',
		paramname: 'pic', //['pic','year', 'month'],
		data: {
			'year': $('#year')[0].value,
			'month': $('#month')[0].value,
		},
		maxfiles: 5,
    maxfilesize: 10, // max file size in MBs
		url: '/upload/postfile',
		withCredentials: true, 
		headers: {          // Send additional request headers
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    },
		
		uploadFinished:function(i,file,response){
			// response is the JSON object that post_file.php returns
			console.log('done uploading!')
			console.log(response);
			console.log($('#month')[0].value);
			if(response.status === 'success'){
				$.data(file).addClass('done');
				$('#filename').val(file.name);
				$('#btn-upload')[0].disabled = false;
			} else {
				//$('#filename').val('Ooops! something went wrong while uploading....');
				alertMessage($('#nav-action'), 'danger', '<b>Ooops!</b> Something went wrong while uploading. Try refreshing your browser.');
			}
		},
  	
  	error: function(err, file) {
		 switch(err) {
          case 'BrowserNotSupported':
          		console.log('browser does not support HTML5 drag and drop')
              //alert('browser does not support HTML5 drag and drop')
              break;
          case 'TooManyFiles':
          	alert('Too Many Files')
              // user uploaded more than 'maxfiles'
              break;
          case 'FileTooLarge':
          	alert('File Too Large')
              // program encountered a file whose size is greater than 'maxfilesize'
              // FileTooLarge also has access to the file which was too large
              // use file.name to reference the filename of the culprit file
              break;
          case 'FileTypeNotAllowed':
          	alertMessage($('#nav-action'), 'warning', 'File type not allowed. Please save your file as .jpg, .png, .pdf or .zip');
          	//alert('File Type Not Allowed')
              // The file type is not in the specified list 'allowedfiletypes'
              break;
          case 'FileExtensionNotAllowed':
          	alertMessage($('#nav-action'), 'warning', 'File extension not allowed. Please save your file as .jpg, .png, .pdf or .zip');
          	//alert('File Extension Not Allowed')
              // The file extension is not in the specified list 'allowedfileextensions'
              break;
          default:
              break;
      }
		},
		//allowedfiletypes: ['image/jpg', 'image/jpeg','image/png','image/gif', 'application/zip'],   // filetypes allowed by Content-Type.  Empty array means no restrictions
    //allowedfileextensions: ['.ZIP','.zip'], // file extensions allowed. Empty array means no restrictions
    allowedfileextensions: ['.ZIP','.zip','.PNG','.png','.JPG','.jpg', '.PDF','.pdf'], // file extensions allowed. Empty array means no restrictions
    
		// Called before each upload is started
		beforeEach: function(file){
			console.log(file);
			var ext = file.name.replace(/^.*\./, '').toLowerCase();
			console.log(ext);
			
			if (ext === 'pdf') {
				$('#filetype').val('depslp').trigger('change');
				console.log('pdf');
				return true;
			}
				
			
			if (!file.type.match(/^image\//)) {
				console.log('file is not an image!');
				
				if (ext !=='zip'){
					console.log('File not supported!');
					return false;
				}
				console.log('but a zip file!');
				// Returning false will cause the
				// file to be rejected

				if(!backupVerifyFilename(file.name)) {
					alertMessage($('#nav-action'), 'danger', '<b>Ooops! '+ file.name +'</b> invalid backup! Kindly check the file.');
					$('#filename').val(file.name);
					console.log($(this));
					console.log(dropbox);
					$('#dropbox').html('<span class="message">You can \'Drag and Drop\' or \'Click\' here to attach your file.  <br>'
                +'<i>(they will only be visible to you)</i>'
                +'</span>');
					resetForm();
					return false;
				} else {
					console.log($('#filetype').val());
					$('#filetype').val('backup').trigger('change');
					return true;
				}
			}
			
			console.log('Continue on pics');
			
			$('#filetype').val('depslp').trigger('change');
			console.log($('#filetype'));
		},
		
		uploadStarted:function(i, file, len){
			console.log('started!');
			$('#filename').val('attaching file...');
			//$('#attached > span').removeClass('')
			createImage(file);
			//alertRemove();
		},
		
		progressUpdated: function(i, file, progress) {
			console.log('update progress:'+ progress);
			$.data(file).find('.progress').width(progress);
		},
		globalProgressUpdated: function(progress) {
			console.log('progress:'+ progress);
        // progress for all the files uploaded on the current instance (percentage)
        // ex: $('#progress div').width(progress+"%");
    },
		speedUpdated: function(i, file, speed) {
        console.log('speed:'+ speed);
    },
    	 
	});
	
	var template = '<div class="preview">'+
						'<span class="imageHolder">'+
							'<img />'+
							'<span class="uploaded"></span>'+
						'</span>'+
						'<div class="progressHolder">'+
							'<div class="progress"></div>'+
						'</div>'+
					'</div>'; 
	
	
	function createImage(file){
		ext = file.name.replace(/^.*\./, '');
		var preview = $(template), 
			image = $('img', preview);
			
		var reader = new FileReader();
		
		image.width = 100;
		image.height = 100;
		
		reader.onload = function(e){
			console.log(ext);
			// e.target.result holds the DataURL which
			// can be used as a source of the image:
			if (ext.toLowerCase() === 'zip') {

				var s = '/images/Zip-File.png';
			}
			else if (ext.toLowerCase() === 'pdf') {

				var s = '/images/Pdf-File.png';
			} 
			else {

				var s = e.target.result;
			}
			image.attr('src', s);
		};
		
		// Reading the file as a DataURL. When finished,
		// this will trigger the onload function above:
		reader.readAsDataURL(file);
		
		/*
		reader.addEventListener("loadend", function() {
      // send the file over web sockets
      //ws.send(fr.result);
      console.log('reader');
      //console.log(reader.result);
    });

    // load the file into an array buffer
    //reader.readAsArrayBuffer(file);
		*/

		message.hide();
		//preview.appendTo(dropbox);
		preview.appendTo(dropbox).prev(preview).remove();
		
		
		// Associating a preview container
		// with the file, using jQuery's $.data():
		
		$.data(file,preview);
	}

	function showMessage(msg){
		message.html(msg);
	}
	
	
	
	
	
	var file_select = $('#file_upload');
	
	file_select.on('change', function(){
		var oFile = document.getElementById('file_upload').files[0];
		console.log(oFile.name);
	});
	
	
	
	
	
	

});