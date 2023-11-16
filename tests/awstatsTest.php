<?php


use PHPUnit\Framework\TestCase;

class awstatsTest extends TestCase
{
	private $aw;

	public function setUp(): void
	{
		require_once(__DIR__.'/../../../class2.php');
		require_once(__DIR__.'/../awstats.class.php');
		$this->aw = new \awstats;
		$this->aw->setPath(__DIR__.DIRECTORY_SEPARATOR."tmp".DIRECTORY_SEPARATOR."awstats".DIRECTORY_SEPARATOR);
		$this->aw->setDomain('example.com');
	}

	public function testGetPath()
	{
		$result = $this->aw->getPath();
		$this->assertNotEmpty($result);
	}


	/*public function testSetDomain()
	{

	}

	public function testGetMonths()
	{

	}

	public function testProcessFile()
	{

	}

	public function testGetData()
	{

	}

	public function testGetLastError()
	{

	}*/

	public function testGetSearchStats()
	{
		$result = $this->aw->getSearchStats(2005);
		$this->assertNotEmpty($result[1]);

		$res = $result[1]; // ie. January.

		$this->assertSame(28, $res['google']);
		$this->assertSame(335, $res['facebook']);


	}

/*	public function testGetYears()
	{

	}

	public function test__construct()
	{

	}

	public function testGetDays()
	{

	}

	public function testLoad()
	{

	}*/
}
