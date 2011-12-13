<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Final Project ESD beta1</title>
    <link rel="stylesheet" href="css/style.css" type="text/css" /> 
    <script type="text/javascript" src="js/libs/jquery.min.js"></script>
    <script type="text/javascript" src="js/libs/jquery.color.js"></script>
    <script type="text/javascript" src="js/libs/raphael.js"></script>
    <script type="text/javascript" src="js/znode/nodegraph.js"></script>
    <script type="text/javascript" src="js/znode/ui.js"></script>
	
	
	<script src="jquery.ui.core.js"></script>
	<script src="jquery.ui.widget.js"></script>
	<script src="jquery.ui.mouse.js"></script>
	<script src="jquery.ui.draggable.js"></script>
	
  </head>
  <body>
    <div id="openWin">
      <div id="fileTitle">Projects:</div>
      <div id="files"></div>
    </div>
    <div id="overlay"></div> 
    <nav id = global1>
    <ul>
       <li><a><u><div id="clear">Home</div></u></a></li>
       <li id=services>
       		<a href=#>Project</a>
       			<ul id=subMenu> 
                        <li><div id ="upload" title="Uploads File" >Upload</div></li> 
       					<li><div id="open"   title="Opens File">Open</div></li> 
       					<!--<li><div id="save"   title="Saves file">Create</div></li>  -->
				</ul>
        </li>
        <li id=services>
	    	<a href=#>Views</a>
              	<ul id=subMenu> 
              		<li><div id="scView" title="View Source Code">Source Code View</div></li>
					<li><div id="classCompView" title="View Composition">Composition View</div></li>
					<li><div id="globalVarView"  title="View Global Variables">Global Variable View</div></li>
					<li><div id="globalfunctView"  title="View Global Functions">Global Function</div></li>
            		<li><div id="inheritance" title="View Inheritance" >Inheritance View</div></li>
            		<li><div id="clearAll" title="Clears all Opened Files">Clear All</div></li>
                </ul>
       </li>
    </ul>
</nav>
    <div id="newFeature">
		<form action="php/upload_file.php" method="post" enctype="multipart/form-data" id="uploadFile"> 
            <input type="hidden" name="jsonName" id="jsonName"/>
			<input type="file" value="" name="file[]" multiple/>
            
		</form>
  </div>
    <div id="saveForm">
        <form id="nameForm">
         Project Name :
         <input type="text" name="filename" id="filename" spellcheck="false"/>
         <input class= "button" type="button" id="submitBttn" value="Submit" />
        </form>
</div>  
    <div id="canvas"></div>
    
      <!--<div id="codeTitle">File List:</div> -->
	<div id="codeList" style=" position : absolute;
  top : 54px;
  left : 20px;
  width : 250px; 
  background-color: #eFeFeF;
  z-index : 1;
  display : none;
  border-radius: 10px;
  -moz-border-radius: 10px;
  -webkit-border-radius: 10px;
  border: 1px solid #6e7e93; 
  box-shadow: 0 0 30px #629996;
  -moz-box-shadow: 0 0 30px #629996;
  -webkit-box-shadow: 0 0 30px #629996;">
	  		<div id="codeTitle">Files:</div>
      		<div id="codes"></div>
	  </div>

	<div id="scViewDiv"></div>
	
	
	<!-- display alert message after file saved -->
	<?php 
		$mssg;
		if(isset($_GET['mssg']))
		{
			$mssg = $_GET['mssg'];
	?>
	<script language="javascript">
		alert('<?php echo $mssg ?>');
	</script>
	<?php }?>
	
  </body>
 	
</html>
  