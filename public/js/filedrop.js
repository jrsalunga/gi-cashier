$(function(){
	
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
		maxfiles: 1,
    maxfilesize: 8, // max file size in MBs
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
				$('#filename').val('Ooops! something went wrong while uploading....');
			}
			
		},
  	error: function(err, file) {
		 switch(err) {
          case 'BrowserNotSupported':
              alert('browser does not support HTML5 drag and drop')
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
          	alert('File Type Not Allowed')
              // The file type is not in the specified list 'allowedfiletypes'
              break;
          case 'FileExtensionNotAllowed':
          	alert('File Extension Not Allowed')
              // The file extension is not in the specified list 'allowedfileextensions'
              break;
          default:
              break;
      }
		},
		//allowedfiletypes: ['image/jpeg','image/png','image/gif', 'application/zip'],   // filetypes allowed by Content-Type.  Empty array means no restrictions
    allowedfileextensions: ['.ZIP','.zip'], // file extensions allowed. Empty array means no restrictions
    
		// Called before each upload is started
		beforeEach: function(file){
			console.log(file);
			if(!file.type.match(/^image\//)){
				console.log('file is not an image!');
				
				var ext = file.name.replace(/^.*\./, '');
				console.log(ext);
				if(ext.toLowerCase()!=='zip'){
					console.log('File not supported!');
					return false;
				}
				console.log('but a zip file!');
				// Returning false will cause the
				// file to be rejected
				
			}
		},
		
		uploadStarted:function(i, file, len){
			console.log('started!');
			$('#filename').val('attaching file...');
			//$('#attached > span').removeClass('')
			createImage(file);
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
			var s = (ext.toLowerCase() == 'zip') ? '/images/Zip-File.png' : e.target.result;
			image.attr('src', s);
		};
		
		// Reading the file as a DataURL. When finished,
		// this will trigger the onload function above:
		reader.readAsDataURL(file);
		
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