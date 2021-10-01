<?php
	require_once("./indexer/tools.php");
	$searchurl = currentHttpPath()."/indexer/webservice.php?act=details&wordid=";
?>	

<!DOCTYPE html>
<html>
<head>
	<title>PHEW</title>	
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link href="css/bootstrap.min.css" rel="stylesheet">
	<link href="css/jquery-ui.css" rel="stylesheet">
	<!-- <script src="js/jquery-3.3.1.slim.min.js"></script>	 -->
	<script src="js/jquery.min.js"></script>	
	<script src="js/popper.min.js"></script>	
	<script src="js/jquery-ui.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<link rel="stylesheet" href="js/jstree/themes/default/style.css" />
	<script src="js/jstree/jstree.js"></script>
	<style>	
		.ui-autocomplete-loading {
			background: white url("img/ui-anim_basic_16x16.gif") right center no-repeat;
		}	
		div.round {
			background-color:#f6f6f6; border-radius: 5px;
			border: 1px solid #ced4da;
			padding: 10px;
			width: 100%;    
		}
		
.container {
    border: 1px black solid;
    min-width: 150px;
    min-height: 300px;
    overflow: auto;
}

.jstree-default a { 
    white-space:normal !important; height: auto; 
}
.jstree-anchor {
    height: auto !important;
}
.jstree-default li > ins { 
    vertical-align:top; 
}
.jstree-leaf {
    height: auto;
}
.jstree-leaf a{
    height: auto !important;
}		
	</style>	
</head>
<body>

<div id="mainContainer" style="padding:8px;">
<div class="jumbotron">
	<h1 class="display-4">PHEW</h1>
	<p class="lead">
		Begin discovering
			<!-- 	<span class="input-group-text">Begin discovering</span> -->
		
		
<div class="input-group">  
  <input type="text" class="form-control" placeholder="Keyword" id="dgKeywordSrc"> 
  
  <div class="input-group-append">
    <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="margin-left: 3px">Mode</button>
    <div class="dropdown-menu">
		<a class="dropdown-item" href="#">All words</a>      
		<a class="dropdown-item" href="#">Any word</a>      	  
		<a class="dropdown-item" href="#">Exact match</a>      	  		
    </div>
  </div>
</div>
		
		
	</p>
  <hr>
  <p><!-- helptext --></p>  
  <!-- <a class="btn btn-primary btn-lg" href="#" role="button"></a> -->
  <div id="jstree" class="tree round container"></div>
</div>
</div>

<script>
$(function () { 
	function addNode(parent, nodeid, caption, position) {
		//$('#jstree').jstree('create_node', $(parent), { "text":caption, "id":nodeid, "state": {"opened": true}}, position, false, false);	
		$('#jstree').jstree('create_node', '#', { "text":caption, "id":nodeid, "state": {"opened": true}}, position, false, false);	
	}
		
	$('#jstree').jstree({
        'plugins': [],
            'core': {
            'data': [{
					"id": "dgPubYearNode",
                    "text": "Publication Years",
                    "state": {"opened": true},
                    "children": [{
						"text": "Year",
						"state": {"opened": true},
                        "id": "dgYearNode",
						"children": [{
							"text": "File",
							"state": {"opened": true},
							"id": "dgResourceNode",							
							"children": [{
								"text": "Page",
								"state": {"opened": true},
								"id": "dgSectionNode",
								"children": [{
									"text": "Section",									
									"state": {"opened": true},
									"id": "dgSectionText",									
									/*
									"children": [{
										
										"text": "",
										"icon": "img/ui-text.png",																				
										"state": {"opened": true},
										"id": "dgDocText",										
										"text": "Paragraph",
										"icon": "img/ui-text.png",
										"id": "dgParagraph" 
									}] 
									*/
								}]
							}]
						}]
					}]
            }],
                'check_callback': true
	}});

	function loadSearchData(wordid) {
		var srcurl = '<?php echo $searchurl; ?>' + wordid.toString();
		// console.log(srcurl.toString());
		$('#jstree').jstree(true).settings.core.data = {'url': srcurl};
		$('#jstree').jstree(true).refresh();
	}
	
	$('#jstree').on('ready.jstree', function (e, data) {		
		 // loadSearchData(-1);
	});
	
	$( "#dgKeywordSrc" ).autocomplete({
      source: function( request, response ) {
        $.ajax( {
          url: "keywords.php",
          dataType: "jsonp",
          data: {
            term: request.term
          },
          success: function( data ) {
			// console.log( data);  
            response( data );
          }
        } );
	},
      //minLength: 2,
	  minLength: 1,
      select: function( event, ui ) {
		// console.log( "Selected: " + ui.item.value + "  " + ui.item.id );
		loadSearchData(ui.item.id);
      }
    } );


});
</script>

</body>
</html>