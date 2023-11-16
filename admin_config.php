<?php

// Generated e107 Plugin Admin Area 

require_once('../../class2.php');
if (!getperms('P')) 
{
	e107::redirect('admin');
	exit;
}

// e107::lan('awstats',true);


class awstats_adminArea extends e_admin_dispatcher
{

	protected $modes = array(	
	
		'main'	=> array(
			'controller' 	=> 'awstats_ui',
			'path' 			=> null,
			'ui' 			=> 'awstats_form_ui',
			'uipath' 		=> null
		),
		

	);	
	
	
	protected $adminMenu = array(

	//	'main/custom'		=> array('caption'=> "Stats", 'perm' => 'P'),



	);

	protected $adminMenuAliases = array(
		'main/edit'	=> 'main/list'				
	);	
	
	protected $menuTitle = 'AwStats';


	function init()
	{


		$pref  = e107::pref('awstats');

		if(!empty($pref['domain']))
		{
			$aw  = e107::getSingleton('AWStats', e_PLUGIN."awstats/awstats.class.php");
			$aw->setDomain($pref['domain'], $pref['ssl']);

			$years = $aw->getYears();

			$y = $this->getRequest()->getQuery('year',date('Y'));
			$m = $this->getRequest()->getQuery('month',date('n'));


			if(!empty($years))
			{

				foreach($years as $yr)
				{

					$sel = ($y == $yr && ($this->getRequest()->getAction() != 'prefs')) ? true : false;


					$this->adminMenu['main/year'.$yr] = array('caption'=> $yr, 'perm' => 'P', 'selected'=>$sel, 'uri'=>e_PLUGIN.'awstats/admin_config.php?mode=main&action=custom&year='.$yr.'&month='.$m);
				}

				$this->adminMenu['main/div2'] = array('divider'=>1);
				$this->defaultAction = 'custom';
			}

		}


		$this->adminMenu['main/prefs'] 	= array('caption'=> LAN_PREFS, 'perm' => 'P');


	}
}




				
class awstats_ui extends e_admin_ui
{
			
		protected $pluginTitle		= 'AwStats';
		protected $pluginName		= 'awstats';
	//	protected $eventName		= 'awstats-'; // remove comment to enable event triggers in admin. 		
		protected $table			= '';
		protected $pid				= '';
		protected $perPage			= 10; 
		protected $batchDelete		= true;
		protected $batchExport     = true;
		protected $batchCopy		= true;

	//	protected $sortField		= 'somefield_order';
	//	protected $sortParent      = 'somefield_parent';
	//	protected $treePrefix      = 'somefield_title';

	//	protected $tabs				= array('Tabl 1','Tab 2'); // Use 'tab'=>0  OR 'tab'=>1 in the $fields below to enable. 
		
	//	protected $listQry      	= "SELECT * FROM `#tableName` WHERE field != '' "; // Example Custom Query. LEFT JOINS allowed. Should be without any Order or Limit.
	
		protected $listOrder		= ' DESC';
	
		protected $fields 		= NULL;		
		
		protected $fieldpref = array();
		

	//	protected $preftabs        = array('General', 'Other' );
		protected $prefs = array(
			'domain'		=> array('title'=> 'Domain', 'tab'=>0, 'type'=>'text', 'data' => 'str', 'help'=>''),
			'ssl'		=> array('title'=> 'SSL', 'tab'=>0, 'type'=>'boolean', 'data' => 'str', 'help'=>''),
			'3D'		=> array('title'=> '3D', 'tab'=>0, 'type'=>'boolean', 'data' => 'str', 'help'=>''),
			'chart_referrers'		=> array('title'=> 'Referrer Charts', 'tab'=>0, 'type'=>'textarea', 'data' => 'str', 'help'=>'Enter url search terms, one per line, and a monthly chart will be generated for it.', 'writeParms'=>['placeholder'=>"eg. facebook.com\ninstagram.com\notherwebsite.com"]),

		); 



		protected $awstatsDomain = '';

		protected $awstatsSSL = false;

		protected $awStatsPref = array();

	
		public function init()
		{





			//e107::getMessage()->addDebug("Found Awstats Path: ".$this->awstatsPath);



		}


		public function renderHelp()
		{
			$months = e107::getDate()->terms('month-short');

		//	$text = "<ul>";
			$text = '';

			$m = $this->getQuery('month', date('n'));
			$y = $this->getQuery('year', date('Y'));

			foreach($months as $k=>$v)
			{
				$type = ((int) $m === (int) $k)  ? 'btn-primary' : 'btn-default';

				$href = e_REQUEST_SELF."?mode=main&action=custom&year=".$y."&month=".$k;
				$text .= "<a href='".$href."' class='col-md-2 btn btn-xs ".$type."'>".$v."</a> ";

			}
		//	$text .= "</ul>";

			return array('caption'=>'Month', 'text'=>$text);

		}

		
		// ------- Customize Create --------
		
		public function beforeCreate($new_data,$old_data)
		{
			return $new_data;
		}
	
		public function afterCreate($new_data, $old_data, $id)
		{
			// do something
		}

		public function onCreateError($new_data, $old_data)
		{
			// do something		
		}		
		
		
		// ------- Customize Update --------
		
		public function beforeUpdate($new_data, $old_data, $id)
		{
			return $new_data;
		}

		public function afterUpdate($new_data, $old_data, $id)
		{
			// do something	
		}
		
		public function onUpdateError($new_data, $old_data, $id)
		{
			// do something		
		}		
		
			

		// optional - a custom page.  
		public function customPage()
		{
			$domain = e107::pref('awstats','domain');
			if(empty($domain))
			{
				e107::getMessage()->addWarning("No Domain set");
			}

			$dash = e107::getAddon('awstats','e_dashboard');

			$year = $this->getQuery('year', date('Y'));
			$month = $this->getQuery('month', date('n'));

			$dash->setYear($year);
			$this->addTitle($year);

			$dash->setMonth($month);
			$months = e107::getDate()->terms('month');

			$mon = intval($month);
			$this->addTitle($months[$mon]);

			$text = $this->render("Monthly Visitors", $dash->months());
			$text .= $this->render("Daily Visitors", $dash->days());


			if($referrerCharts = awstats::getReferrerKeywords())
			{
				foreach($referrerCharts as $search)
				{
					$text .= $this->render(ucfirst($search),$dash->searchMonths($search));
				}

			}

		//	$text .= $this->render("Visitors by Country", $dash->map());
			$text .= $this->render("Session Average",$dash->sessions());
			$text .= $dash->pagerefs();
			$text .= $dash->sider();
			$text .= $dash->browser();

			if(deftrue('e_DEBUG'))
			{
				$text .= "<div class='panel panel-default'><div class='panel-body'>".$dash->raw()."</div></div>";

			}
			return $text;


/*
			$tab2       = '';

			$tabs = array(
				'months' => array('caption'=>'New Customers', 'text'=>$monthChart),
				'activity' => array('caption'=>'Admin Activity', 'text'=> $tab2),
			);


			return e107::getForm()->tabs($tabs);*/

		}

		private function render($caption,$text)
		{
			return "<div class='panel panel-default'>
			<div class='panel-heading'>".$caption."</div>
			<div class='panel-body'>".$text."</div></div>";


		}

			
}
				


class awstats_form_ui extends e_admin_form_ui
{

}		
		
		
new awstats_adminArea();

require_once(e_ADMIN."auth.php");
e107::getAdminUI()->runPage();

require_once(e_ADMIN."footer.php");
exit;

