<?php

require_once("db_management.php");

$path = 'src/20121106_2166_cdc_grezze_Domestico.csv';
// $path = '20121106_2162_cdc_grezze_Produttore.csv';



// PARSE SMART METER


 /*
function stringToTime($str){
	$str = (int)end(explode('_',$str));
	$hour = floor($str*15/60);	// hard coded by now
	$min = ($str*15)%60;		// hard coded by now
	return array($hour,$min);
}
*/

function stringToTime($mode,$str,$timeArray=array(0,0)){
	switch($mode){
		case 'date':
			// print_r($timeArray);
			$date = explode('/',$str);
			$date = date(mktime($timeArray[0],$timeArray[1],0,(int)$date[1],(int)$date[0],(int)$date[2]));
			//$date = ($formatted)? date('d/M/Y',$date) : $date;
			if ($timeArray==array(0,0)){
				$date = date('d/M/Y',$date);
			}else{
				$date = date('Y-m-d H:i:s',$date);
			}
			return $date;
		case 'time':
			$tmp = explode('_',$str);
			$str = (int)(end($tmp));
			$hour = ($str*15/60);		// hard coded by now
			$min = ($str*15)%60;		// hard coded by now
			return array($hour,$min);
		default:
			return $str.' : default';
	}
	return $str;
}


function convertSmData($key,$str){
	
	//convert smart meter data
	$eType = array(
				'146'=>'Attiva Prelevata',		//	'Prelevata (at)'
				'147'=>'Attiva Immessa',		//	'Immessa (at)'
				'148'=>'Reattiva 1� Quadrante'	//	'I� Quadrante (re)'
			); 
	
	switch ($key) {
		case 'GIORNO_CDC':
			//var_dump($date);
			return stringToTime('date',$str);
		//case 'PROGRESSIVO':
		//	return $prog[$str];
		case 'ID_OBIS':
			return $eType[$str];
		case 'ID_PUNTO_MISURA':
			return (int)$str;
		default:
			print $key;
			return $str;
	}
	
}


function parseData($path,$type=null){

	if($type==null){
	
		$dailyRecs = 96;
		$data = array();
		
		//file open
		$file = fopen($path,"r");
		
		$header = true;
		
		while (!feof($file)) {
			$line = fgets($file);
			$rawData = explode(';', $line);
			// if(count($rawData)!=196) print count($rawData).' :: ';
			//var_dump($rawData);
			if ($header){
				$keys = explode(';',$line);
				$header = false;
			}else{			
				for ($i=0; $i<$dailyRecs; $i++){
				
					$pointer = 4 + $i * 2;
					$id = (int)$rawData[3];
					
					$type = convertSmData('ID_OBIS',$rawData[2]);
					
					$nRec = $keys[$pointer];
					$hourMin = stringToTime('time',$nRec);
					
					$datetime = stringToTime('date',$rawData[0],$hourMin);
					/*if($rawData[0]=="13/03/2011"){
						print "ERROR --";
						print $datetime ." -- ";
						print $rawData[0] ." -- ";
						print_r($hourMin);
						print "<br/>";
						//print "<br/>";
					}*/
					//print $datetime.'<br/>';
					
					$val = (float)str_replace(',','.',$rawData[$pointer]);
					$cState = (int)$rawData[$pointer+1];
					
					$parsedData = array(
						'id'=>$id,
						'time'=>$datetime,
						'type'=>$type,
						'value'=>$val,
						'cState'=>$cState
					);
					
					array_push($data,$parsedData);
				}
				// if ($break) break;
			}
		}
		fclose($file);
		return $data;
	}
}

function random_data($start_id, $data){

	$rows = array();
	foreach($data as $d){
		$range = mt_rand(1,5)/100;
		$row = $d;
		$row['id'] = $start_id;
		$row['value'] =  abs($d['value'] + (rand(0, 2) - 1) * $range); 
		$rows[] = $row;
	}
	return $rows;
}
function other_user($data){
	$start_id = 34672255;
	$query = "";
	for($i = 1; $i <= 5; $i++){
		$rows = random_data($start_id+$i, $data);
		$query .= populateDb($rows);
	}
	return $query;
}


// TEST EXECUTION

?>

<html>
	<body>
		<form action="#" method="post">
			<input type="submit" name="clear_db"  value="clear db" />
			<input type="submit" name="fill_db"  value="fill db" />
			<input type="submit" name="other"  value="other users" />
			
		</form>
	</body>
</html>

<?php

if(isset($_POST['clear_db'])){
	$query = "DELETE FROM record WHERE 1";
	print $query.'<hr/>';
	dbQuery($query);
}else if(isset($_POST['fill_db'])){
	$data = parseData($path);
	//print 'populating...<hr/>';
	print populateDb($data);
}else if(isset($_POST['other'])){
	$data = parseData($path);
	$query = other_user($data);
	//print_r($data2);
	//print 'populating...<hr/>';
	//$query = populateDb($data2);
	print $query;
}


?>