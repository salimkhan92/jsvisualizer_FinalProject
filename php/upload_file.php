<?php
  
	$files = array();//list of files
	$functionList = array();//list of functions
	$classList = array();//list of classes
	$globalVariable = array();//list of global variable
	
	$connectionObj = '"connections":[';
	
	
	$node_id = 0;
	$node_x = 100;
	$node_y = 100;
  
	foreach ($_FILES['file']['name'] as $i => $name) {
   
        if ($_FILES['file']['error'][$i] == 4) {
            continue; 
        }
       
        if ($_FILES['file']['error'][$i] == 0) {
           
 			//$files[] = $_FILES["file"]["tmp_name"][$i];
			$files[$_FILES["file"]["name"][$i]] = $_FILES["file"]["tmp_name"][$i];
        }
   }
  
	foreach ($files as $file) {
      readFiles($file);
   }
   

	createJSONObject();

	
function readFiles($file)
{
	global $functionList;
	global $classList;
	global $globalVariable;
	$funct = NULL;
	$nbr_funct = 0;  //number of function found
	$fh = fopen($file, 'r');
	while(!feof($fh))
	{
		$line = fgets($fh);
		$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
		

		//search for function in $line
		//if(preg_match('/(?<=function)(\s+.+\(.*\)\s*)(?=\{)/',$line,$matches)!= 0)
		if(preg_match('/(?<=function)(\s+.+\(.*\)\s*)((?=\{)|(\n*))/',$line,$matches)!= 0)
		{
			$matches[1] = trim($matches[1]);//remove line break
			if(is_null($funct))
			{
				$funct = $matches[1];
			}
			else
			{
				if(substr_count($funct,'CLASS: ')==0)
				{
					$funct = "CLASS: ".$funct."\\n-------------------\\n";
				}
				$funct .= $matches[1]."\\n";
			}
	
		}
		
		//*****check class base on this.anyValue = function(....
		if(preg_match('/this\.\w+\s*=\s*function\(.*\)/',$line,$matches)!= 0)
		{
			if($funct != NULL && substr_count($funct,'CLASS: ')==0)
				$funct = "CLASS: ".$funct."\\n-------------------\\n";
		}
		//************************************************************
		
		if(preg_match('/{/',$line,$matches)!= 0)
		{
			$nbr_funct += substr_count($line,'{') ;
		}
		if(preg_match('/}/',$line,$matches)!= 0)
		{
			$nbr_funct -= substr_count($line,'}');

			if($nbr_funct == 0)
			{
				if(substr_count($funct,'CLASS: ')==1)//if it is class then add to classList
				{
					array_push($classList,$funct);
				}
				else// if it is just function add to function list
				{
					array_push($functionList,$funct);
				}
				$funct = NULL;
			}
		}
		
		//for global variable
		if(preg_match('/var\s+.+(?=\;)/',$line,$matches)!= 0 && $nbr_funct == 0)
		{
			array_push($globalVariable,$matches[0]);
		}
	}
	fclose($fh);
}

function findClassComposition($jsonObj)
{
	global $classList;
	if(sizeOf($classList) > 0)
	{
		for($i=0; $i<sizeOf($classList); $i++)
		{
			//preg_match('/(?<=CLASS:\s)(.+)(?=\()/',$classList[$i],$matches);
			preg_match('/(?<=CLASS:\s)(.+?)(?=\()/',$classList[$i],$matches);
			$className = $matches[1];
			
			//***************************
			preg_match('/(?<=:Node_Id:)\d+/',$classList[$i],$matches);
			$class_Id = $matches[0];
			//**************************
			
			$jsonObj = searchForComposition($jsonObj, $className, $class_Id);
		}
	
	}
	
	return $jsonObj;
}



function createJSONObject()
{
	global $functionList;
	global $classList;
	global $globalVariable;
	
	global $node_id;
	global $node_x;
	global $node_y;
	
	$jsonObj = '{' ;
	if(sizeof($classList) > 0)
	{
		$jsonObj .= createClassCompositionView($jsonObj);
	}
	
	if(sizeof($globalVariable) > 0)
	{
		$jsonObj .= globalVariableView($jsonObj);
	}
	
	if(sizeof($functionList) > 0)
	{
		$jsonObj .= globalFunction($jsonObj);
	}
	
	
	$jsonObj .= inheritanceView($jsonObj);

	//before closing main json object add json array containing all .js file path
	$fileJsonObj = storeJsFiles();
	$jsonObj .= $fileJsonObj;
	
	//closing main json object
	$jsonObj .= '}';
	
	//save json object to file
	$file = fopen("../files/" . $_POST["jsonName"] . ".json", "w") or die("error");
	fwrite($file, $jsonObj);
	fclose($file);
	//echo "saved";
	//redirect to same page 
	$mssg = $_POST["jsonName"].' project has been saved';
	header('Location: /jsvisualizer?mssg='.$mssg);
}

function createClassCompositionView($jsonObj)
{
	global $files;
	global $classList;
	
	global $node_id;
	$node_x = 100;
	$node_y = 100;
	
	$ccvObj;
	if($jsonObj != '{')
		$ccvObj = ',"classCompView":{';
	else
		$ccvObj = '"classCompView":{';
	
	$nodeObj = '"nodes" : [';
	$connObj = ',"connections" : [';
	
	foreach ($classList as $className) 
	{
		if($nodeObj != '"nodes" : [')
			$nodeObj .= ',';
		$nodeObj .= '{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":136, "txt":"'.$className.'"}';
		$class_Id = $node_id;
		$node_id++;
		
		preg_match('/(?<=CLASS:\s)(.+?)(?=\()/',$className,$matches);
		$className = $matches[1];
		$className = trim($className);
		$regex = '/new\s+'.$className.'/';  //regex to find class name in file
		
		$fileKey = array_keys($files);
		
		$foundClassInAllFile = ''; //text for class composition node
		foreach ($fileKey as $key) 
		{
			$file = $files[$key];
			$fh = fopen($file, 'r');
			$line_nbr = 0;
			$foundClass = '';
			while(!feof($fh))
			{
				$line_nbr++;
				$line = fgets($fh);
				$line = changeIllegalChar($line);
				$line = trim($line);  //remove line break
				$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
				if(preg_match($regex,$line,$matches)!=0)
				{
					if($foundClass =='' && $foundClassInAllFile != '')
					$foundClassInAllFile.='\\n-------------------------------\\n';
					if($foundClass =='')
						$foundClass .= $className.' in '.$key.'\\n--------------------------\\n';
					else
						$foundClass.= '\\n';
					$foundClass .= 'at line '.$line_nbr.': '.$line;
				}
			}
		
			$foundClassInAllFile.= $foundClass;
			fclose($fh);
		}
	
		//create node
		if($foundClassInAllFile != '')
		{

			$node_y += 300;
			
			if($nodeObj != '"nodes" : [')
				$nodeObj.=',';
			$nodeObj.='{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":136, "txt":"'.$foundClassInAllFile.'"}';
			
			//create connection
			if($connObj != ',"connections" : [')
				$connObj.= ',';
			$connObj.= '{"nodeA":'.$class_Id.', "nodeB":'.$node_id.',"conA":"bottom","conB":"top"}';
			$node_id++;
			
		}
		$node_x += 200;
		$node_y = 100;
	}
	
	$nodeObj .= ']';
	$connObj .= ']';
	
	$ccvObj .= $nodeObj;
	$ccvObj .= $connObj;
	$ccvObj .= '}';
	return $ccvObj;
}

//global variable view
function globalVariableView($jsonObj)
{
	global $files;
	global $globalVariable;
	
	global $node_id;
	$node_x = 20;
	$node_y = 80;
	
	$gvvObj = '';
	if($jsonObj != '{')
		$gvvObj .= ',';
	$gvvObj .= '"globalVarView":{';
	
	$nodeObj = '"nodes" : [';
	$connObj = ',"connections" : [';
	
	//create one node to list all global variable
	$nodeObj.='{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":260, "height":200, "txt":"Global Variable List:\\n--------------------------------------------------------------\\n';
	foreach ($globalVariable as $variable) 
	{
		$nodeObj.= $variable."\\n";
	}
	unset($variable);
	$nodeObj.= '"}';
	$node_id++;
	$node_x += 350;
	$node_y = 100;
	
	//create view**********************
	foreach ($globalVariable as $var) 
	{
		//preg_match('/(?<=var\s).+(?==)/',$var,$matches);
		//$var = $matches[0];
		if(preg_match('/(?<=var\s).+(?==)/',$var,$matches)!=0)
			$var = $matches[0];
		elseif (preg_match('/(?<=var\s).+(?=;)/',$var,$matches)!=0)
			$var = $matches[0];
		
		$var = trim($var);
		
		if($nodeObj != '"nodes" : [')
			$nodeObj .= ',';
		$nodeObj .= '{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":40, "txt":" var '.$var.'"}';
		$var_Id = $node_id;
		$node_id++;
		
		$regex = '/'.$var.'/';  //regex to find variable name in file
		
		$fileKey = array_keys($files);
		
		$foundVariableInAllFile = ''; //text for variable node
		foreach ($fileKey as $key) 
		{
			$file = $files[$key];
			$fh = fopen($file, 'r');
			$line_nbr = 0;
			$foundVariable = '';
			while(!feof($fh))
			{
				$line_nbr++;
				$line = fgets($fh);
				$line = changeIllegalChar($line);
				$line = trim($line);  //remove line break
				$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
				if(preg_match($regex,$line,$matches)!=0)
				{
					if($foundVariable =='' && $foundVariableInAllFile != '')
					$foundVariableInAllFile.='\\n-------------------------------\\n';
					if($foundVariable =='')
						$foundVariable .= 'var '.$var.' in '.$key.'\\n-----------------------------\\n';
					else
						$foundVariable.= '\\n';
					
					$line = str_replace('"','\\"',$line);//replace all " with \"
					$foundVariable .= 'at line '.$line_nbr.': '.$line;
				}
			}
		
			$foundVariableInAllFile .= $foundVariable;
			fclose($fh);
		}
	
		//create node
		if($foundVariableInAllFile != '')
		{
			$node_y += 150;
			
			if($nodeObj != '"nodes" : [')
				$nodeObj.=',';
			$nodeObj.='{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":136, "txt":"'.$foundVariableInAllFile.'"}';
			
			//create connection
			if($connObj != ',"connections" : [')
				$connObj.= ',';
			$connObj.= '{"nodeA":'.$var_Id.', "nodeB":'.$node_id.',"conA":"bottom","conB":"top"}';
			$node_id++;
			
		}
		
		$node_x += 200;
		$node_y = 100;
	}
	
	//**********************
	$nodeObj .= ']';
	$connObj .= ']';
	
	$gvvObj .= $nodeObj;
	$gvvObj .= $connObj;
	$gvvObj .= '}';
	
	return $gvvObj;
}

//create global function json object
function globalFunction($jsonObj)
{
	global $files;
	global $functionList;
	global $node_id;
	
	$node_x = 20;
	$node_y = 80;
	
	$gfunctObj = '';
	if($jsonObj != '{')
		$gfunctObj .= ',';
	$gfunctObj .= '"globalFunctionView":{';
	
	$nodeObj = '"nodes" : [';
	$connObj = ',"connections" : [';
	
	//create one node to list all global function
	$nodeObj.='{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":260, "height":200, "txt":"Global Function List:\\n--------------------------------------------------------------\\n';
	foreach ($functionList as $funct) 
	{
		$nodeObj.= $funct."\\n";
	}
	unset($funct);
	$nodeObj.= '"}';
	$node_id++;
	$node_x += 350;
	$node_y = 100;
	
	//***********create function calling view
	foreach ($functionList as $funct) 
	{
		$funct = trim($funct);
		if($funct == '');
			continue;
		if($nodeObj != '"nodes" : [')
			$nodeObj .= ',';
		$nodeObj .= '{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":40, "txt":" function '.$funct.'"}';
		$funct_Id = $node_id;
		$node_id++;
		
		//create regular expression to find function with parameter
		preg_match('/.+(?=\()/',$funct,$matches);
		$regex = '/'.$matches[0].'\(';
		if(preg_match('/(?<=\().+(?=\))/',$funct,$matches)!=0)
		{
			$regex .= '.+';
			$nbr_param = substr_count($matches[0],',');
			for($i=0; $i<=$nbr_param; $i++)
			{
				if($i==0)
					continue;
				else
				$regex .= ',.+';
			}
		}
		$regex .= '\)/';
		//regex close
		
		$fileKey = array_keys($files);
		
		$foundFunctionInAllFile = ''; //text for variable node
		foreach ($fileKey as $key) 
		{
			$file = $files[$key];
			$fh = fopen($file, 'r');
			$line_nbr = 0;
			$foundFunction = '';
			while(!feof($fh))
			{
				$line_nbr++;
				$line = fgets($fh);
				$line = changeIllegalChar($line);
				$line = trim($line);  //remove line break
				$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
				if(preg_match($regex,$line,$matches)!=0)
				{
					if($foundFunction =='' && $foundFunctionInAllFile != '')
					$foundFunctionInAllFile.='\\n-------------------------------\\n';
					if($foundFunction =='')
						$foundFunction .= 'function '.$funct.' in '.$key.'\\n-----------------------------\\n';
					else
						$foundFunction.= '\\n';
					
					$line = str_replace('"','\\"',$line);//replace all " with \"
					$foundFunction .= 'at line '.$line_nbr.': '.$line;
				}
			}
		
			$foundFunctionInAllFile .= $foundFunction;
			fclose($fh);
		}
	
		//create node
		if($foundFunctionInAllFile != '')
		{
			$node_y += 150;
			
			if($nodeObj != '"nodes" : [')
				$nodeObj.=',';
			$nodeObj.='{"id":'.$node_id.', "x":'.$node_x.',"y":'.$node_y.',"width":145, "height":136, "txt":"'.$foundFunctionInAllFile.'"}';
			
			//create connection
			if($connObj != ',"connections" : [')
				$connObj.= ',';
			$connObj.= '{"nodeA":'.$funct_Id.', "nodeB":'.$node_id.',"conA":"bottom","conB":"top"}';
			$node_id++;
			
		}
		
		$node_x += 200;
		$node_y = 100;
	}
	
	
	//*************close function calling view
	
	//closing all json object
	$nodeObj .= ']';
	$connObj .= ']';
	
	$gfunctObj .= $nodeObj;
	$gfunctObj .= $connObj;
	$gfunctObj .= '}';
	
	return $gfunctObj;
}

//inheritance view
function inheritanceView($jsonObj)
{
	global $files;
	global $node_id;
	
	$node_x = 250;
	$node_y = 150;
	
	$classInfo = array();// array to store class name as key and node ID as value
	
	$inheritanceObj = '';
	if($jsonObj != '{')
		$inheritanceObj .= ',';
	$inheritanceObj .= '"inheritance":{';
	
	$nodeObj = '"nodes" : [';
	$connObj = ',"connections" : [';
	
	$nbr_node = 0;
	$kids = 0;
	$fileKey = array_keys($files);
		
	foreach ($fileKey as $key) 
	{
		$file = $files[$key];
		$fh = fopen($file, 'r');
		while(!feof($fh))
		{
			$line_nbr++;
			$line = fgets($fh);
			$line = changeIllegalChar($line);
			$line = trim($line);  //remove line break
			$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
			if((bool)strchr($line,"prototype") && (bool)strchr($line,"new"))
			{
				$tempLine = $line;  
				$tempLine = str_replace(" ","",$tempLine);  //remove all white space
				$tempLine = str_replace(array("\r", "\r\n", "\n"),"",$tempLine); //remove line breaks
				$tempLine = str_replace("prototype=new","",$tempLine); //remove 'prototype=new'
				$tempLine = str_replace(";","",$tempLine);  //remove ';'
				$tempLine = str_replace("(","",$tempLine);  //remove '('
				$tempLine = str_replace(")","",$tempLine);  //remove ')'
				$pair = explode(".",$tempLine);  // make subclass and superclass as an array
                $superClass = $pair[1];
				$subClass = $pair[0];
				$superClass_Id;
				$subClass_Id;
				$super_xPos;
				$super_yPos;
				$sub_xPos;
				$sub_yPos;
				//for super class
				if (array_key_exists($superClass, $classInfo))
				{
					$info = $classInfo[$superClass];
					$infoArr = explode(":",$info);
					$superClass_Id = $infoArr[0];
					$super_xPos = $infoArr[1];
					$super_yPos = $infoArr[2];
					$kids = $infoArr[3]; 
				}
				else
				{
					
					$superClass_Id = $node_id;
					$node_id++; 
					//$super_xPos = $node_x +($nbr_node * 100); 
					$super_xPos = $node_x;
					$super_yPos = $node_y;
					$node_x += 250;
					//$nbr_node++; 
					 
					//create node for super class
					if($nodeObj != '"nodes" : [')
						$nodeObj.=',';
					$nodeObj .= '{"id":'.$superClass_Id.', "x":'.$super_xPos.',"y":'.$super_yPos.',"width":145, "height":40, "txt":" Class: '.$superClass.'"}';
				}
				//for sub class
				if (array_key_exists($subClass, $classInfo))
				{
					$info = $classInfo[$subClass];
					$infoArr = explode(":",$info);
					$subClass_Id = $infoArr[0];
					$sub_xPos = $infoArr[1];
					$sub_yPos = $infoArr[2]; 
				}
				else
				{
					if (array_key_exists($superClass, $classInfo))
					{
						$kids++;
						$classInfo[$superClass] = $superClass_Id.':'.$super_xPos.':'.$super_yPos.':'.$kids;
						$sub_xPos = $super_xPos+(250 * $kids);
						$sub_yPos = $super_yPos+100; 
					}
					else
					{
						$sub_xPos = $super_xPos;
						$sub_yPos = $super_yPos+100;
						$kids = 1;
						$classInfo[$superClass] = $superClass_Id.':'.$super_xPos.':'.$super_yPos.':'.$kids;
						//$node_x += 250;
					}
					$kids = 0;
					$classInfo[$subClass] = $node_id.':'.$sub_xPos.':'.$sub_yPos.':'.$kids;

					$subClass_Id = $node_id;
					$node_id++;
					//$nbr_node++;
					
					//create node for sub class
					if($nodeObj != '"nodes" : [')
						$nodeObj.=',';
					$nodeObj .= '{"id":'.$subClass_Id.', "x":'.$sub_xPos.',"y":'.$sub_yPos.',"width":145, "height":40, "txt":"Class: '.$subClass.'"}';
				}
			//create connection between super class and sub class
			if($connObj != ',"connections" : [')
				$connObj.= ',';
			$connObj.= '{"nodeA":'.$superClass_Id.', "nodeB":'.$subClass_Id.',"conA":"bottom","conB":"top"}';
			
			}//if loop close
		}//while loop close
		fclose($fh);
	} //file foreach clos
	
	$nodeObj .= ']';
	$connObj .= ']';
	
	$inheritanceObj .= $nodeObj;
	$inheritanceObj .= $connObj;
	$inheritanceObj .= '}';
	
	return $inheritanceObj;

}

//store file in fileuploaded folder from temp folder
function storeJsFiles()
{
	global $files;
	$fileJsonObj = ',"files":[';
	$fileKey = array_keys($files);
	foreach ($fileKey as $key) 
	{
		$oldFile = dirname($files[$key])."/".basename($files[$key]);//get the path and file name 
		$oldFile = str_replace("\\","/",$oldFile);//replace all '\' with '/'

		$uploads_dir = '../uploaded-files/';
		preg_match('/.+(?=\.js)/',$key,$matches);
		$newFile = $uploads_dir.$matches[0]."_T".date("d-m-Y_H-i-s", time()).".txt";//create new file name add timestamp to avoid conflict of file name

		$fjs = fopen($newFile, 'w') or die('Cannot open file:  '.$newFile); //create file
		copy($oldFile, $newFile);//copy file
		fclose($fjs);
		//echo $newFile;
		//add string to json object
		if($fileJsonObj != ',"files":[')
			$fileJsonObj .= ',';
		$fileJsonObj .= '{"name":"'.$key.'", "path":"'.$newFile.'"}';
	}
	$fileJsonObj .= ']';
	return $fileJsonObj;
}

function changeIllegalChar($line)
{
	$line = trim($line);  //remove line break
	$line = preg_replace('/\/{2}.*\n*/',' ',$line);//remove comment
	$line = str_replace("\\","\\\\",$line);  //remove ']'
	return $line;
}

	
 
?> 