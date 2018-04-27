<?php
//http://en.allmetsat.com/metar-taf/nicaragua-costa-rica-panama.php?icao=MNCH
$station_name="Chinandega, Nicaragua";
$timezone=-6;
$timezonedesc='CST';
$station_url='http://www.aviationweather.gov/adds/dataserver_current/httpparam?dataSource=metars&requestType=retrieve&format=xml&stationString=MNCH&hoursBeforeNow=10';

$weatherxml = simplexml_load_file($station_url);

foreach ($weatherxml->data as $weatherinfo):
		$station_id=$weatherinfo->METAR[0]->station_id;
        $temp_c=$weatherinfo->METAR[0]->temp_c;
		$wind_speed_kt=$weatherinfo->METAR[0]->wind_speed_kt;
		$wind_dir_degrees=$weatherinfo->METAR[0]->wind_dir_degrees;
		$sky_condition=$weatherinfo->METAR[0]->sky_condition['sky_cover'];
		$observation_time=$weatherinfo->METAR[0]->observation_time;
    endforeach;

$rawdate=substr($observation_time,0,10);
$rawtime=substr($observation_time,11,8); 
list($curyear, $curmonth, $curday) = preg_split('/[\/.-]/', $rawdate);
//list($curhour, $curmin, $cursec) = split('[:]', $rawtime);
//$obsvtime = date('g:i:s A',strtotime($rawtime)+(3600*$timezone));
$obsvtime = date('g A',strtotime($rawtime)+(3600*$timezone));
$obsvdate = date('j M Y',strtotime($curmonth.'/'.$curday.'/'.$curyear));
					
//WIND CARDINAL DIRECTION	
if ($wind_dir_degrees >= 1 && $wind_dir_degrees <= 23) {
	$wind_dir_quad='N';
}elseif($wind_dir_degrees >= 24 && $wind_dir_degrees <= 67){
	$wind_dir_quad='NE';
}elseif($wind_dir_degrees >= 68 && $wind_dir_degrees <= 112){
	$wind_dir_quad='E';
}elseif($wind_dir_degrees >= 113 && $wind_dir_degrees <= 157){
	$wind_dir_quad='SE';
}elseif($wind_dir_degrees >= 158 && $wind_dir_degrees <= 202){
	$wind_dir_quad='S';
}elseif($wind_dir_degrees >= 203 && $wind_dir_degrees <= 247){
	$wind_dir_quad='SW';
}elseif($wind_dir_degrees >= 248 && $wind_dir_degrees <= 292){
	$wind_dir_quad='W';
}elseif($wind_dir_degrees >= 293 && $wind_dir_degrees <= 337){
	$wind_dir_quad='NW';
}elseif($wind_dir_degrees >= 338 && $wind_dir_degrees <= 360){
	$wind_dir_quad='N';
}else{
	//0
	$wind_dir_quad='VARIABLE';
	
};

//SKY DESCRIPTION SKC|CLR|CAVOK|FEW|SCT|BKN|OVC|OVX 
if ($sky_condition=='SKC') {
	$sky_desc='clear skies';
}elseif($sky_condition=='CLR'){
	$sky_desc='clear skies';
}elseif($sky_condition=='CAVOK'){
	//$sky_desc='a few clouds';
	$sky_desc='scattered clouds';
}elseif($sky_condition=='FEW'){
	//$sky_desc='a few clouds';
	$sky_desc='scattered clouds';
}elseif($sky_condition=='SCT'){
	$sky_desc='scattered clouds';
}elseif($sky_condition=='BKN'){
	$sky_desc='broken clouds';
}elseif($sky_condition=='OVC'){
	$sky_desc='overcast';
}elseif($sky_condition=='OVX'){
	$sky_desc='overcast';
}else{
	$sky_desc='n/a';
};

$wind_speed_mph=round($wind_speed_kt*1.15078);
$temp_f=$temp_c*1.8+32;

echo $station_name."<br/>";
echo $temp_c."&deg;C/".$temp_f."&deg;F<br/>";
echo 'wind dir: '.$wind_dir_degrees."<br/>";
echo 'wind is out of the '.$wind_dir_quad." at ".$wind_speed_mph."mph<br/>";
echo 'wind is '.$wind_dir_quad." at ".$wind_speed_mph."mph<br/>";
echo $sky_desc." | ".$sky_condition."<br/>"; 
echo $sky_desc.'<br>';
echo 'observed at '.$obsvtime." ".$timezonedesc." on ".$obsvdate."<br/>"; 
echo $temp_c."&deg;C/".$temp_f."&deg;F at ".$obsvtime." ".$timezonedesc." on ".$obsvdate."<br/>";

echo "<span id='metartemp'>".$temp_c."&deg;C/".$temp_f."&deg;F</span> <span id='metartime'>@ ".$obsvtime." ".$timezonedesc."</span>";

?>
