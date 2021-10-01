<?php
	require_once("./indexer/tools.php");
	$uploadpath = currentHttpPath()."upload.php";	
?>	

<!DOCTYPE html>
<html>
<head>
	<title>Stiigo indexer</title>	
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/jquery-ui.css" rel="stylesheet">
	<link href="css/dropzone.css" rel="stylesheet">
	<!-- <script src="js/jquery-3.3.1.slim.min.js"></script>	 -->
	<script src="js/jquery.min.js"></script>	
	<!-- <script src="js/popper.min.js"></script>	 -->
	<script src="js/jquery-ui.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/dropzone.js"></script>	
</head>
<body>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
		<a class="nav-item nav-link active" id="nav-home-tab" data-toggle="tab" href="#nav-home" role="tab" aria-controls="nav-home" aria-selected="true">File</a>   
		<a class="nav-item nav-link" id="nav-profile-tab" data-toggle="tab" href="#nav-profile" role="tab" aria-controls="nav-profile" aria-selected="false">Profile</a>   
  </div>
</nav>

<div class="tab-content" id="nav-tabContent">
	
	<div class="tab-pane fade show active" id="nav-home" role="tabpanel" aria-labelledby="nav-home-tab" style="margin: 14px">
		<div id="fileuploadcont" class="dropzone" style="min-height: 250px">
		</div>
	</div>	
	
    <div class="tab-pane fade" id="nav-profile" role="tabpanel" aria-labelledby="nav-profile-tab" style="margin: 14px">
	Profile
	</div>
	
</div>


<script>
$(function () { 

/*
	$("div#fileuploadcont").dropzone(
		{ 		
			url: "<?php echo $uploadpath; ?>",
			maxFilesize: 10,
            autoProcessQueue: false,
            maxFiles: 8,
            parallelUploads: 8,
            uploadMultiple: true
					
		});
*/	
	
	var dz = new Dropzone("#fileuploadcont", 
		{ 	url: "<?php echo $uploadpath; ?>",
			acceptedFiles: "application/pdf",
			maxFilesize: 10,
            autoProcessQueue: true,
            maxFiles: 8,
            parallelUploads: 8,
            uploadMultiple: true,
			paramName: "indexfiles" 
		}); 


		dz.on("error", function(file, message) {
			console.log(message);
		});
		
		
		dz.on("uploadprogress", function(file, progress, bytesSent) {
			console.log(bytesSent.toString());
		});
		
		dz.on("complete", function (file) {
			// dz.removeFile(file);              
        })
		
		dz.on("addedfile", function(file) {
		});
		
});
</script>
</body>
</html>

