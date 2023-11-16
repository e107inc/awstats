<?php
	/**
	 * e107 website system
	 *
	 * Copyright (C) 2008-2016 e107 Inc (e107.org)
	 * Released under the terms and conditions of the
	 * GNU General Public License (http://www.gnu.org/licenses/gpl.txt)
	 *
	 */




	class awstats
	{

		private $fh        = false;
		public  $lastError = false;
		public  $data      = array();
		private $path      = '';
		private $month     = '';
		private $year      = '';
		private $domain    = '';

		private $ignoreSections =  array('MAP', 'MISC',  'ORIGIN',  'UNKNOWNREFERERBROWSER',  "SEARCHWORDS", "VISITOR", "KEYWORDS", "UNKNOWNREFERER", "SIDER_404");

		function __construct()
		{
			$this->path = $this->findPath();
		}

		public function setPath($path)
		{
			$this->path = $path;
		}

		public function getPath()
		{
			return $this->path;
		}

		private function findPath()
		{
			$i = 0;
			$path = __DIR__.'/';

			while (!is_dir("{$path}tmp/awstats"))
			{
				$path .= "../";
				$i++;

				if($i == 10)
				{
					return false;
				}

			}

			return $path.'tmp/awstats/';

		}


		public function setDomain($domain,$ssl=false)
		{
			$this->domain = $domain;

			if(!empty($ssl) && substr($this->path,-4) !== 'ssl/')
			{
				$this->path .= "ssl/";
			}

			return $this;
		}

		/**
		 * @param $year
		 * @return object $this
		 */
		public function load($year)
		{
			$this->data[$year] = array();

			for($a = 1; $a <= 12; $a++)
			{
				$this->processFile($a,$year);
			}

			return $this;
		}



		public function getDays($month)
		{
			$total = cal_days_in_month(CAL_GREGORIAN, $month, $this->year);
			$arr = array();

			for($a = 1; $a <= $total; $a++)
			{
				$m = str_pad($month,2,'0',STR_PAD_LEFT);
				$day = str_pad($a,2,'0',STR_PAD_LEFT);
				$date = $this->year.'-'.$m.'-'.$day;
				$arr[$date] = 1;

				if(isset($this->data[$this->year][$month]['DAY'][$date]))
				{
					$arr[$date] = $this->data[$this->year][$month]['DAY'][$date];
				}
				else
				{
					$arr[$date] = '';
				}
			}

			return $arr; //  $this->data[$this->year][$month]['DAY'];

		}

		public function getYears()
		{
			$res = !empty($this->path) ? scandir($this->path) : [];

			$arr = array();
			foreach($res as $val)
			{


				if(isset($arr[$val]) || $val == '.' || empty($val) || strpos($val,$this->domain)=== false)
				{
					continue;
				}

				$val = filter_var($val,FILTER_SANITIZE_NUMBER_INT);



				if(!empty($val))
				{
					$val = substr($val,2,4);

					if(strlen($val) != 4)
					{
						continue;
					}
					$arr[$val] = $val;
				}

			}

			rsort($arr);

			return $arr;


		}


		/**
		 * @return array
		 */
		public function getMonths($section = 'GENERAL')
		{
			$arr = array();

			for($a = 1; $a <= 12; $a++)
			{
				$arr[$a] = !empty($this->data[$this->year][$a][$section]) ? $this->data[$this->year][$a][$section] : array();
			}

			return $arr;

		}




		public function getLastError()
		{
			return $this->lastError;
		}


		public function processFile($month, $year)
		{
			$this->year = $year;
			$this->month = $month;



			$filename = $this->path . 'awstats' . str_pad($month, 2, '0', STR_PAD_LEFT). $year . '.' . $this->domain . '.txt';


			if(!file_exists($filename))
			{
				$this->lastError .= 'File does not exist: '.$filename."<br />";

				return false;
			}

			$this->fh = fopen($filename, 'r');
			if($this->fh === false)
			{
				$this->lastError = 'File cannot be opened: '.$filename;;

				return false;
			}

			$this->parse();


			$this->data[$this->year][$this->month]['GENERAL']['TotalPages'] = $this->data[$this->year][$this->month]['TIME']['pagesTotal'];
			$this->data[$this->year][$this->month]['GENERAL']['TotalHits'] = $this->data[$this->year][$this->month]['TIME']['hitsTotal'];
			$this->data[$this->year][$this->month]['GENERAL']['TotalBandwidth'] = $this->data[$this->year][$this->month]['TIME']['bwTotal'];
		}




		/* Checks if line is a comment */
		private function comment($line)
		{
			if(isset($line[0]) && $line[0] == '#')
			{
				return true;
			}

			return false;
		}

		/* Builds an array based on a section */
		private function section()
		{
			$in_section = false;
			$section_name = '';
			$section_lines = 0;
			$on_line = 0;
			$section_content = array();

			if($this->fh === false)
			{
				return false;
			}

			while(($line = fgets($this->fh)) !== false)
			{
				$line = trim($line);
				if($this->comment($line))
				{
					continue;
				}

				if($section_name && in_array($section_name,$this->ignoreSections))
				{
					$in_section = false;
				}

				if($in_section)
				{



					if(strpos($line, 'END_' . $section_name) === 0 )
					{
						if($section_name && in_array($section_name,$this->ignoreSections))
						{
							continue;
						}

						return array(
							'name'    => $section_name,
							'lines'   => $section_lines,
							'content' => $section_content
						);
					}
					else if($on_line <= $section_lines)
					{

						array_push($section_content, $line);
					//	$section_content[] = $line;
						$on_line++;
						continue;
					}
					else
					{
						$this->lastError = 'Section Can Not Find Ending:'.$on_line." - ".$section_lines;

						return false;
					}
				}

				if(strpos($line, 'BEGIN_') === 0)
				{
					$in_section = true;

					preg_match('/BEGIN_(\w*)[^\d]*([\d]*)$/',$line, $m);

					$section_name = $m[1];
					$section_lines = intval($m[2]);




					$on_line = 0;
					$section_content = array();
					continue;
				}
			}

			return false;
		}

		/* Parses the sections array and uses that data for whatever it needs it for */
		private function parse()
		{
			if($this->fh === false)
			{
				return false;
			}

			while($section = $this->section())
			{
				/*
					Here you would place extra parsing code based on what you want
					to do with the data. But since this is only an example, the
					data is placed into an array with just the section name and
					the data for each line (untouched). Will have to split by [space]
				*/



				/* You can add specific rules based on the section here */

				$name = $section['name'] ;

				switch($section['name'])
				{

					case 'DAY':
						foreach($section['content'] as $row)
						{
							list($label,$value) = explode(" ",$row,2);

							$year = substr($label,0,4);
							$month = substr($label,4,2);
							$day = substr($label,6,2);

							$key = $year."-".$month."-".$day;

							$section[$key] =  $value;
						}

						unset($section['content']);

					break;



					case 'GENERAL':
					case 'DOMAIN':
					case 'TIME':
							foreach($section['content'] as $row)
							{
								list($label,$value) = explode(" ",$row,2);

								$section[$label] = $value;

							}


							if($section['name'] == 'TIME')
							{
								$pagesT = 0;
								$hitsT = 0;
								$bwT = 0;

								foreach($section['content'] as $row)
								{
									list($c, $pages, $hits, $bw, $bla, $tmp, $bw2) = explode(" ",$row);

									$pagesT += $pages;
									$hitsT += $hits;
									$bwT += $bw2;

								}

								$section['pagesTotal'] = $pagesT;
								$section['hitsTotal'] = $hitsT;
								$section['bwTotal'] = str_replace("&nbsp;","",e107::getFile()->file_size_encode($bwT));
							}


							unset($section['content']);


						break;

					case 'SESSION':
					case 'SEREFERRALS':

						$var = array();
						foreach($section['content'] as $row)
						{
							list($label,$value) = explode(" ",$row,2);
							$var[$label] = (int) $value;
						}

						$section = $var;

					break;

					case 'OS':


							$nameLimit = $section['name'] == 'OS' ? 10 : 5;

							foreach($section['content'] as $row)
							{
								list($label,$value) = explode(" ",$row,2);

								$key = substr($label,0,$nameLimit);

								if(!isset($section[$key]))
								{
									$section[$key] = 0;
								}

								$section[$key] += $value;

							/*	if(!isset($section[$key]))
								{
									$section[$key][0] = 0;
									$section[$key][1] = 0;
								}

								$section[$key][0] += $val1;
								$section[$key][1] += $val2;*/


							}
							unset($section['content']);
						break;

					case 'BROWSER':



							foreach($section['content'] as $row)
							{
								list($browser, $hits,$pages) = explode(" ",$row,3);

								$key = substr($browser,0,4);

								$hits = intval($hits);
								$pages = intval($pages);

								switch($key)
								{
									case "fire":
										$section['firefox']['hits'] += $hits;
										$section['firefox']['pages'] += $pages;
										break;

									case "safa":
										$section['safari']['hits'] += $hits;
										$section['safari']['pages'] += $pages;
										break;

									case "msie":
										$section['ie']['hits'] += $hits;
										$section['ie']['pages'] += $pages;
										break;

									case "chro":
										$section['chrome']['hits'] += $hits;
										$section['chrome']['pages'] += $pages;
										break;

									case "oper":
										$section['opera']['hits'] += $hits;
										$section['opera']['pages'] += $pages;
										break;


									default:
										$section['other']['hits'] += $hits;
										$section['other']['pages'] += $pages;
								}



							/*	if(!isset($section[$key]))
								{
									$section[$key][0] = 0;
									$section[$key][1] = 0;
								}

								$section[$key][0] += $val1;
								$section[$key][1] += $val2;*/


							}
							unset($section['content']);
						break;


					case 'ROBOT':


					default:
						$section = $section['content'];
						break;
					/* Add the rest of the section cases */
				}


				unset($section['name'],$section['lines']);

				$this->data[$this->year][$this->month][$name] = $section;

			//	array_push($this->data, $section);

			}
		}



		/**
		 * Unused
		 * @param $section
		 * @return mixed
		 */
		public function getData($section, $month=null)
		{
			if($month === null)
			{
				$month = date('n');
			}

			return $this->data[$this->year][$month][$section];
		}

		public function getSearchStats($year)
		{

			$stats = $this->load($year)->getMonths('SEREFERRALS');
			$ret = [];

			foreach($stats as $month => $arr)
			{
				foreach($arr as $k => $v)
				{
					if(strpos($k, 'google') !== false)
					{
						$ret[$month]['google'] += (int) $v;
					}
					elseif(strpos($k, 'yahoo') !== false)
					{
						$ret[$month]['yahoo'] += (int) $v;
					}
					elseif(strpos($k, 'bing') !== false)
					{
						$ret[$month]['bing'] += (int) $v;
					}
					/*	elseif(strpos($k,'facebook')!==false)
						{
							$ret[$month]['facebook'] += (int) $v;
						}
						elseif(strpos($k,'instagram')!==false)
						{
							$ret[$month]['instagram'] += (int) $v;
						}*/
					else
					{
						$ret[$month]['other'] += (int) $v;
					}
				}

			}

			$stats = $this->load($year)->getMonths('PAGEREFS');

			$search = awstats::getReferrerKeywords();

			foreach($stats as $month => $arr)
			{
				foreach($arr as $line)
				{
					list($url,$page,$hit) = explode(' ',$line,3);
					foreach($search as $domain)
					{
						if(strpos($url, $domain) !== false)
						{
							$ret[$month][$domain] += (int) $hit;
						}
					}


				}

			}


			return $ret;

		}


		public static function getReferrerKeywords()
		{
			$ret = [];

			if($pref = e107::pref('awstats', 'chart_referrers', false))
			{
				if($rows = explode("\n", $pref))
				{
					foreach($rows as $line)
					{

						$ret[] = trim($line);

					}

				}


			}

			return $ret;

		}


	}