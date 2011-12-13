var fileList;
var classCompView;
var globalVarView;
var globalFunctionView;
var inheritance;

$(function(){
  
  var graph = new NodeGraph();
  //var canvas = document.getElementsByTagName('canvas');
  //canvas.setAttribute('width', 10000); 
  //canvas.setAttribute('height', 10000);  
  
  /* consider moving to NodeGraph
  $("#canvas").mouseup(function(e){
     if (openWin.css("display") == "none"){
       var children = $(e.target).children();
       if (children.length > 0){
         var type = children[0].tagName;
         if (type == "desc" || type == "SPAN"){
           graph.addNodeAtMouse();
         }
       }
     }
  });  */
  
  // ui code
  var openWin = $("#openWin");
  openWin.hide();
 
  $(".btn").mouseenter(function(){
    $(this).animate({"backgroundColor" : "white"}, 200);
  }).mouseleave(function(){
    $(this).animate({"backgroundColor" : "#efefef"});
  });
  
  $("#clear").click(clear);
   $("#clearAll").click(clear);
   function clear(){
    	graph.clearAll();
		$("#canvas").show();
		$("#scViewDiv").hide();
		$("#codeList").hide();
	}
  $("#help").click(function(){
    window.open("http://www.zreference.com/znode", "_blank");
  });
  
  $("#save").click(function(){
  		$("#canvas").show();
  	 	$("#scViewDiv").hide();
		$("#codeList").hide(); 
	 	 $("#saveForm").show();	
  		saveFile;
  	});
  
  function saveFile(){
    var name = filename.val();
    if (name == "" || name == nameMessage){
      alert("Please Name Your File");
      filename[0].focus();
      return;
    }
    $.post("json/save.php", {data:graph.toJSON(), name:name}, function(data){
      alert("Your file was saved.");
    });
  }
  
  $("#canvas").mousedown(function(){
    openWin.fadeOut();
  });
  
  $("#open").click(function(){
  	$("#canvas").show();
	$("#scViewDiv").hide(); 
    $("#codeList").hide();
    var fileList =  $("#files");
    fileList.html("<div>loading...<\/div>");
    openWin.fadeIn();
    fileList.load("json/files.php?"+Math.random()*1000000);
  });
  
  var nameMessage = "Enter your file name";
  var filename = $("#filename").val(nameMessage);

  filename.focus(function(){
    if ($(this).val() == nameMessage){
      $(this).val("");
    }
  }).blur(function(){
    if ($(this).val() == ""){
      $(this).val(nameMessage);
    }
  });
  
  $("#nameForm").submit(function(e){
    e.preventDefault();
    $("#saveForm").hide();
    $("#newFeature").hide();
    saveFile();
  });
  
  $(".file").live('click', function() {
    var name = $(this).text();
    $.getJSON("files/" + name + ".json", {n:Math.random()}, function(data){
       	$("#canvas").show();
		$("#scViewDiv").hide();
	   graph.fromJSON(data);
       filename.val(name);
	   //**********************get all files and other parameter in JSON object
		fileList = null;
		classCompView = null; 
		globalVarView = null;
		viewType = null;
		inheritance = null;
	   if(data.files !=null)
			fileList = data.files;
		if(data.classCompView != null)
			classCompView = data.classCompView;
		if(data.globalVarView != null)
			globalVarView = data.globalVarView;
		if(data.globalFunctionView != null)
			globalFunctionView = data.globalFunctionView;
		if(data.inheritance)
			inheritance = data.inheritance;
		//*****************************************************
		openWin.fadeOut();
	});
  }).live('mouseover', function(){
    $(this).css({"background-color": "#ededed"});
  }).live("mouseout", function(){
    $(this).css({"background-color": "white"});
  });
  
   //source code view
$("#scView").click(function(){

	if(fileList != null)
	{
		openWin.fadeOut();
		$("#saveForm").hide();
         $("#newFeature").hide();
		$("#canvas").hide(); 
		//Q
		$('#codes').html('');
		$('#scViewDiv').html('');
		$("#codeList").fadeIn(); 
	    $("#scViewDiv").show(); 
		for(var i=0; i<fileList.length ; i++)
		{
			var fileObj = fileList[i];
			var fileName = fileObj.name;
			 $('#codes').append('<a href="#" title="' + i +'" class="file codeName">'+fileName+'</a><br>');
		}
		
		 /* 
		
		$("#scViewDiv").show();
		if ( $("#scViewDiv").length > 0 ) {
			jQuery('#scViewDiv').html('');
		}
		jQuery.ajaxSetup({async:false});//make ajax syncronus so that each file get correct name
		for(var i=0; i<fileList.length ; i++)
		{
			var fileObj = fileList[i];
			var fileName = fileObj.name;
			
			$.post('php/loadFile.php',{path:fileObj.path} ,function(data) {
				$('#scViewDiv').append('<div style="position:relative; float:left;"><h5 style="margin-bottom:2px;">'+fileName+'</h5><textarea rows="20" cols="50">'+data+'</textarea ></div>');
			});
		}
		jQuery.ajaxSetup({async:true});//reset it to default  */
	}
	else
		alert("No source code found.");  
		
  });
   $(".codeName").live('click',function(){
		 
		var fileId = $(this).attr("title");
		
		var i = Number(fileId);
		var fileObj = fileList[i];
		var fileName = fileObj.name; 
	
		/*if ( $("#scViewDiv").length > 0 ) {
			jQuery('#scViewDiv').html('');
		}*/
		
		jQuery.ajaxSetup({async:false});
		$.post('php/loadFile.php',{path:fileObj.path} ,function(data) {
				$('#scViewDiv').append('<div class="ui-widget-content draggable" ><h5 style="margin-bottom:2px;">'+fileName+'</h5><textarea rows="20" cols="50">'+data+'</textarea ></div>');  
			});
		jQuery.ajaxSetup({async:true}); 
  });
  
  $("#submitBttn").click(function(){
  var jsonName = $("#filename").val();
  if(jsonName == '' || jsonName == 'Enter your file name')
		alert('Please enter file name.');
	else
	{
		$("#jsonName").val(jsonName);
		$("#uploadFile").submit();
		//alert("success");
	}
  
  });
  
  //when click upload button
  $("#upload").click(function(){
  	$("#canvas").show();
  	 $("#scViewDiv").hide();
		$("#codeList").hide();
	  $("#newFeature").show();
	  $("#saveForm").show();
	  });
  
  
//composite view button click 
 $("#classCompView").click(function(){
	if(classCompView != null)
	{
		$("#canvas").show();
		$("#scViewDiv").hide();
		$("#codeList").hide();
		graph.fromJSON(classCompView);
	}
	else
		alert("No composite view found");
	});
//global variable view button click	
  $("#globalVarView").click(function(){
	if(globalVarView != null)
	{
		$("#canvas").show();
		$("#scViewDiv").hide();
		$("#codeList").hide();
		graph.fromJSON(globalVarView);
	}
	else
		alert("No Global Variable found.");
	});
	
//global function view button click	
  $("#globalfunctView").click(function(){
	if(globalFunctionView != null)
	{
		$("#canvas").show();
		$("#scViewDiv").hide();
		$("#codeList").hide();
		graph.fromJSON(globalFunctionView);
	}
	else
		alert("No Global Function found.");
	});
 
  $("#inheritance").click(function(){
		if(inheritance != null)
		{
			$("#canvas").show();
			$("#scViewDiv").hide();
			$("#codeList").hide();
			graph.fromJSON(inheritance);
		}
		else
			alert("No Inheritance View found.");
  });
	
	
	$( ".draggable" ).live('mouseover',function(){
		$(this).draggable();
	});
	
	
	
});