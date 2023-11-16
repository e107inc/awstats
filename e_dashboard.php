<?php
if (!defined('e107_INIT')) { exit; }

require_once(e_PLUGIN."awstats/awstats.class.php");
class awstats_dashboard // include plugin-folder in the name.
{
	
	private $title; // dynamic title.

	/** @var awstats */
	private $awObj;

	private $options;
	private $year;
	private $month;

	function __construct()
	{
		$this->year = date('Y');
		$this->month = date('n');

	}

	function chart()
	{
		$config = array();


		$config[0] = array(
			0   =>  array('text'	=> $this->days(),	    'caption'	=> "Daily"),
			1   => array('text'		=> $this->months(),   'caption'	=> "Yearly"),
			2   => array('text'		=> $this->browser(),    'caption'	=> "Browser"),
			3   => array('text'		=> $this->sessions(),   'caption'	=> "Sessions"),
			4   => array('text'     => $this->pagerefs(), 'caption'=>"Referrers"),
			5   => array('text'     => $this->sider(), 'caption'=>"Pages"),
			6   => array('text'     => $this->browser(), 'caption'=>"Browsers"),
			7   => array('text'     => $this->searchMonths('google'), 'caption'=>'Google Search'),
			8   => array('text'     => $this->searchMonths('yahoo'), 'caption'=>'Yahoo Search'),
			9   => array('text'     => $this->searchMonths('bing'), 'caption'=>'Bing Search'),
			10   => array('text'     => $this->searchMonths('_all_'), 'caption'=>'All Search'),

		);

		if($referrerCharts = awstats::getReferrerKeywords())
		{
			foreach($referrerCharts as $search)
			{
				$config[0][] = array('text' => $this->searchMonths($search), 'caption'=>ucfirst($search));
			}

		}




/*
		$config[] = array(
				'text'	=> $this->map(),	    'caption'	=> "Visitors by Country"

		);*/

		$config[] = array(


		);


		return $config;
	}

	function setYear($year)
	{
		$this->year = intval($year);
	}

	function setMonth($m)
	{
		$this->month = intval($m);
	}


	function initChart()
	{
		$pref      = e107::pref('awstats');

		$this->awObj  = e107::getSingleton('awstats', e_PLUGIN."awstats/awstats.class.php");

		// $this->awObj->checkPath();

		if(!is_object($this->awObj))
		{
			e107::getDebug()->log("unable to initiate awstats class");
			return false;
		}
		$this->awObj->setDomain($pref['domain'], $pref['ssl']);

		$this->options =  array(
			'chartArea'	=> array('left'=>'60', 'right'=>20, 'width'=>'100%', 'top'=>'30'),
			'legend'	=> array('position'=> 'top', 'alignment'=>'center', 'textStyle' => array('fontSize' => 12, 'color' => '#ccc')),
			'vAxis'		=> array('title'=>null, 'minValue'=>0, 'maxValue'=>10, 'titleFontSize'=>12, 'titleTextStyle'=>array('color' => '#ccc'), 'gridlines'=>array('color'=>'#696969', 'count'=>5), 'format'=>'', 'textStyle'=>array('color' => '#ccc') ),
			'hAxis'		=> array('title'=>'default label', 'slantedText'=>false, 'slantedTextAngle'=>60, 'ticks'=>'', 'titleFontSize'=>14, 'titleTextStyle'=>array('color' => '#ccc'), 'gridlines' => array('color'=>'transparent'), 'textStyle'=>array('color' => '#ccc') ),
			'colors'	=> array('#f3f300', '#f3f300','#66f0ff', '#DC493C', '#3B5999'),
			'animation'	=> array('duration'=>1000, 'easing' => 'out'),
			'areaOpacity'	=> 0.8,
			'isStacked' => false,
			'backgroundColor' => array('fill' => 'transparent' ),
			'is3D'      => varset($pref['3D'],false)
		);

	}


	function sessions()
	{
		$this->initChart();


		$stats = $this->awObj->load($this->year)->getData('SESSION', $this->month);

		if(empty($stats))
		{
			return "No data";
		}

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}


		$cht = e107::getChart();
		$cht->setProvider('google');


		$id             = __CLASS__."_".__FUNCTION__; // >'_blank_activity_chart';

		$width          = '100%';
		$height         = 450;

		$label          = "Visitors This Month";

		$data = array();
		$data[0]  = array('Time', 'Number');

		$var = array();
		foreach($stats as $label=>$val)
		{
			$var[$label] = array($label,$val);
		}

		$data[1] = $var['0s-30s'];
		$data[2] = $var['30s-2mn'];
		$data[3] = $var['2mn-5mn'];
		$data[4] = $var['5mn-15mn'];
		$data[5] = $var['15mn-30mn'];
		$data[6] = $var['30mn-1h'];
		$data[7] = $var['1h+'];
/*
		foreach($stats as $c=>$info)
		{
			list($time,$amt) = explode(" ",$info);
			$c=$c+1;
			$data[$c][0] = (string) $time;
			$data[$c][1] = intval($amt);
		}*/

		$options = $this->options;

		$options['hAxis']['title'] = $label;

		$options['legend']['position'] = 'right';// 	=> array('position'=> 'top',

		unset($options['colors']);

		$cht->setType('pie');
		$cht->setOptions($options);
		$cht->setData($data);
	//	$cht->debug(true);



		return "<div>".$cht->render($id, $width, $height)."</div>";


	}


	function map()
	{
		$this->initChart();

		$stats = $this->awObj->load($this->year)->getData('DOMAIN',$this->month);

		if(empty($stats))
		{
			return "No Data";
		}

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}

		$cht = e107::getChart();
		$cht->setProvider('google');


		$id             = __CLASS__."_".__FUNCTION__; // >'_blank_activity_chart';

		$width          = '100%';
		$height         = 450;

		$label          = "Visitors This Month";

		$data = array();
		$data[0]  = array('Country', 'Amount');

		$frm = e107::getForm();

		foreach($stats as $key=>$val)
		{
			list($pages, $hits, $bw) = explode(" ",$val);
		//	$iso = strtoupper($key);
			$iso = $frm->getCountry($key);
			$data[] = array($iso, (int) $hits);
		}

		$options = $this->options;
		$options['hAxis']['title'] = $label;
		$options['displayMode'] = 'region';
		$options['colorAxis']['colors'] = array('84b786', '#02d10c');
    //    $options['backgroundColor'] = '#81d4fa';
        $options['datalessRegionColor'] = '#eeeeee';
        $options['defaultColor'] =  '#f5f5f5';
        $options['legend.textStyle'] = array('color'=>'#cccccc', 'fontSize'=>'14px', 'bold'=>false, 'italic'=>false);



	//	$options['legend']['position'] = 'right';// 	=> array('position'=> 'top',

		unset($options['colors']);

		$cht->setType('geo');
		$cht->setOptions($options);
		$cht->setData($data);
	//	$cht->debug(true);



		return "<div>".$cht->render($id, $width, $height)."</div>";


	}




	function days()
	{
		$this->initChart();


		$stats = $this->awObj->load($this->year)->getDays($this->month);

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}


		$cht = e107::getChart();
		$cht->setProvider('google');


		$id             = __CLASS__."_".__FUNCTION__; // >'_blank_activity_chart';

		$width          = '100%';
		$height         = 450;

		$months = e107::getDate()->terms('month');

		$label          = "Visitors: ".$months[$this->month]." ".$this->year;



		$data = array();
		$data[0]  = array('Day', 'Visits');


		foreach($stats as $date=>$info)
		{
			list($y,$m,$d) = explode("-",$date);

			$info = trim($info);

			if(empty($info))
			{
				continue;
			}

			list($visits,$pages,$hits,$bw) = explode(" ",$info);

			$d = intval($d);
			$data[$d][0] = (string) $d;
			$data[$d][1] = intval($visits);
		//	$data[$d][2] = intval($hits);

		}


	//	$sum = array_sum($amt);

	//	$this->title = 'Referrals ('.$sum.')';

		$options = $this->options;
		$options['colors'] = array('#f3f300', '#f3f300','#66f0ff', '#DC493C', '#3B5999');
		$options['hAxis']['title'] = $label;

	//		unset($options['colors']);

		$cht->setType('column');
		$cht->setOptions($options);
		$cht->setData($data);
	//	$cht->debug(true);



		return "<div>".$cht->render($id, $width, $height)."</div>";


	}



	function months()
	{
		$this->initChart();

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}

		$statsLY = $this->awObj->load($this->year -1)->getMonths();
		$stats = $this->awObj->load($this->year)->getMonths();

		$cht = e107::getChart();
		$cht->setProvider('google');


		$id             = __CLASS__."_".__FUNCTION__;

		$amt            = array();
		$width          = '100%';
		$height         = 450;

		$label          = "Vistors (". ($this->year -1) ." - ".$this->year.')';

		$monthName =  e107::getDate()->terms('month-short');

		$lastYearDiz = substr($this->year -1,2,2);
		$thisYearDiz = substr($this->year,2,2);

		$data = array();
		$data[0]  = array('Month', 'Unique', 'Visits');



		for ($m = 1; $m <= 12; $m++)
		{
			$unique     = isset($statsLY[$m]['TotalUnique']) ? (int) $statsLY[$m]['TotalUnique'] : 0;
			$visits     = isset($statsLY[$m]['TotalVisits']) ? (int) $statsLY[$m]['TotalVisits'] : 0;

			$data[$m][0] = $monthName[$m]." '".$lastYearDiz;
			$data[$m][1] = $unique;
			$data[$m][2] = $visits;
		//	$data[$m][3] = $hits;
		}

		for ($m = 1; $m <= 12; $m++)
		{
			$unique     = isset($stats[$m]['TotalUnique']) ? (int) $stats[$m]['TotalUnique'] : 0;
			$visits     = isset($stats[$m]['TotalVisits']) ? (int) $stats[$m]['TotalVisits'] : 0;
		//	$hits       = (int) $stats[$m]['TotalHits'];

			$key = $m + 12;
			$data[$key][0] = $monthName[$m]." '".$thisYearDiz;
			$data[$key][1] = $unique;
			$data[$key][2] = $visits;
		//	$data[$m][3] = $hits;
		}

		$sum = array_sum($amt);

		$this->title = 'Referrals ('.$sum.')';

		$options = $this->options;
		$options['colors'] = array('#ff9933', '#f3f300','#66f0ff', '#DC493C', '#3B5999');
		$options['hAxis']['title'] = $label;
		$options['hAxis']['slantedText'] = true;
		$options['hAxis']['slantedTextAngle'] = 60;
		//	unset($options['colors']);

		$cht->setType('column');
		$cht->setOptions($options);
		$cht->setData($data);
	//	$cht->debug(true);

		$text = $cht->render($id, $width, $height);
		// $text .= $cht->renderTable();


		return "<div>".$text."</div>";


	}


	function searchMonths($type = 'google')
	{
		$this->initChart();

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}


		$statsLY = $this->awObj->getSearchStats($this->year - 1) ;
		$stats = $this->awObj->getSearchStats($this->year) ;

		// e107::getDebug()->log($statsLY);
// e107::getDebug()->log($stats);

		$cht = e107::getChart();
		$cht->setProvider('google');


		$id             = __CLASS__."_".__FUNCTION__.'_'.$type;

		$amt            = array();
		$width          = '100%';
		$height         = 450;

		$label          = "Search Engine Referrer (".(string) ($this->year -1) .' - '.$this->year.")";

		$monthName =  e107::getDate()->terms('month-short');

		$data = array();
		$data[0]  = array('Month');

		if($type === '_all_')
		{
			$data[0][] = 'Google';
			$data[0][] = 'Yahoo';
			$data[0][] = 'Bing';
			$data[0][] = 'Other';
		}
		else
		{
			$data[0][] = ucfirst($type);
		}


		$data[0][] = array('type'=>'string', 'label'=>'Total', 'role'=>'annotation', 'p'=> array('html'=>true));


		$lastYearDiz = substr($this->year -1,2,2);
		$thisYearDiz = substr($this->year,2,2);

		for ($m = 1; $m <= 12; $m++)
		{

			$data[$m][0] = $monthName[$m]." '".$lastYearDiz;
			if($type === '_all_')
			{
				$data[$m][1] = isset($statsLY[$m]['google']) ? (int) $statsLY[$m]['google'] : 0;
				$data[$m][2] = isset($statsLY[$m]['yahoo']) ? (int) $statsLY[$m]['yahoo'] : 0;
				$data[$m][3] = isset($statsLY[$m]['bing']) ? (int) $statsLY[$m]['bing'] : 0;
				$data[$m][4] = isset($statsLY[$m]['other']) ? (int) $statsLY[$m]['other'] : 0;
			}
			else
			{
				$data[$m][1] = isset($statsLY[$m][$type]) ? (int) $statsLY[$m][$type] : 0;
			}


			$data[$m][] = isset($statsLY[$m][$type]) ? (string) $statsLY[$m][$type] : '';
		//
		}

		for ($m = 1; $m <= 12; $m++)
		{
			$key = $m + 12; 
			$data[$key][0] = $monthName[$m]." '".$thisYearDiz;;
			if($type === '_all_')
			{
				$data[$key][1] = isset($stats[$m]['google']) ? (int) $stats[$m]['google'] : 0;
				$data[$key][2] = isset($stats[$m]['yahoo']) ? (int) $stats[$m]['yahoo'] : 0;
				$data[$key][3] = isset($stats[$m]['bing']) ? (int) $stats[$m]['bing'] : 0;
				$data[$key][4] = isset($stats[$m]['other']) ? (int) $stats[$m]['other'] : 0;
			}
			else
			{
				$data[$key][1] = isset($stats[$m][$type]) ? (int) $stats[$m][$type] : 0;
			}

			$data[$key][] = isset($stats[$m][$type]) ? (string) $stats[$m][$type] : '';


		}

		$sum = array_sum($amt);

		$this->title = 'Search Engine Referrals ('.$sum.')';

		$options = $this->options;
		$options['colors'] = array('#ff9933', '#f3f300','#66f0ff', '#DC493C', '#3B5999');
		$options['hAxis']['title'] = $label;
		$options['hAxis']['slantedText'] = true;
		$options['hAxis']['slantedTextAngle'] = 60;

		$options['annotations'] = array('textStyle'=> array('color'=>'black', 'fontSize' => 9), 'format'=>'number', 'alwaysOutside' => true, 'stem' => array('color' => 'transparent'));


		//	unset($options['colors']);

		$cht->setType('column');
		$cht->setOptions($options);
		$cht->setData($data);
	//	$cht->debug(true);

		$text = $cht->render($id, $width, $height);
		// $text .= $cht->renderTable();


		return "<div>".$text."</div>";


	}


	function pagerefs()
	{
		// $this->initChart();

		$stats = $this->awObj->load($this->year)->getData('PAGEREFS',$this->month);

		$text = "<table class='table table-striped table-bordered'>";
		$text .= "<tr><th>Top 25 Links from External Sites</th>
		<th class='text-right'>Amount</th></tr>";

		foreach($stats as  $k=>$va)
		{
			if($k == 26)
			{
				break;
			}

			list($ref,$val,$val2) = explode(" ",$va);
			$text .= "<tr>
				<td><a rel='external' href='".$ref."'>".$ref."</a></td>
				<td class='text-right'>".intval($val)."</td>
				</tr>";

		}

		$text .= "</table>";

		return $text;

	}

	function sider()
	{
		// $this->initChart();

		$stats = $this->awObj->load($this->year)->getData('SIDER',$this->month);

		$text = "<table class='table table-striped table-bordered'>";
		$text .= "<tr>
		<th>URL</th>
		<th class='text-right'>Pages</th>
		<th class='text-right'>Bandwidth</th>
		<th class='text-right'>Entry</th>
		<th class='text-right'>Exit</th>

		</tr>";

		foreach($stats as $k=>$va)
		{
			if($k == 26)
			{
				break;
			}

			list($url,$page,$bw,$entry,$exit) = explode(" ",$va);

			$text .= "<tr>
				<td><a rel='external' href='".rtrim(SITEURL,'/').$url."'>".$url."</a></td>
				<td class='text-right'>".$page."</td>
				<td class='text-right'>".intval($bw)."</td>
				<td class='text-right'>".intval($entry)."</td>
				<td class='text-right'>".intval($exit)."</td>
				</tr>";

		}

		$text .= "</table>";

		return $text;

	}


	function browser()
	{

		$stats = $this->awObj->load($this->year)->getData('BROWSER',$this->month);

		if(empty($stats))
		{
			return "No Data";
		}

		$text = "<table class='table table-striped table-bordered'>";
		$text .= "<tr><th>Browser</th>
		<th class='text-right'>Amount</th></tr>";


		foreach($stats as $name=>$val)
		{

			$text .= "<tr>
				<td>".$name."</td>
				<td class='text-right'>".$val['pages']."</td>
				</tr>";

		}

		$text .= "</table>";

		return $text;

	}


	function raw()
	{

		$this->initChart();

		if($error = $this->awObj->getLastError())
		{
		//	return $error;
		}

		$months = $this->awObj->load(2017)->getMonths();

		// $text = print_a($months, true);
			//	$times = $p->getData('TIME');
		$text = print_a($this->awObj->data, true);

		return $text;
	}


	
	
	function status() // Status Panel in the admin area
	{

		/*$var[0]['icon'] 	= "<img src='".e_PLUGIN."awstats/images/awstats_16.png' alt='' />";
		$var[0]['title'] 	= "My Title";
		$var[0]['url']		= e_PLUGIN_ABS."awstats/awstats.php";
		$var[0]['total'] 	= 10;

		return $var;*/
	}	
	
	
	function latest() // Latest panel in the admin area.
	{
		/*$var[0]['icon'] 	= "<img src='".e_PLUGIN."awstats/images/awstats_16.png' alt='' />";
		$var[0]['title'] 	= "My Title";
		$var[0]['url']		= e_PLUGIN_ABS."awstats/awstats.php";
		$var[0]['total'] 	= 10;

		return $var;
		*/
	}
	
	
}
