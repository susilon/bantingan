<?php
namespace Bantingan;
/*		
    This is the application base controller

    Bantingan Framework v3
    Copyright (C) 2023 by Susilo Nurcahyo
    susilonurcahyo@gmail.com

    Some library are copyright to their respective owners.

	Bantingan Framework is free, open source, and GPL friendly. You can use it for commercial projects, open source projects, or really almost whatever you want.	

	This application is provided to you “as is” without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability, fitness for a particular purpose or non-infringement.
*/
use Bantingan\AppRouter;
use Bantingan\PageGenerator;
use Controllers;

class Controller
{
	public $viewBag;

	public $fileName;
	public $paperSize;
	public $pageOrientation;

	public $isGET = false;
	public $isPOST = false;

	public $baseUrl;
	public $basePath;

	public $namespace;

	public $smartyPlugin = [];

	public function __construct()
	{
		if (!isset($this->viewBag)) {
			$this->viewBag = new \StdClass();			
		}		
		
		$this->baseUrl();
		
		$classFunction = array($this,BANTINGAN_ACTION_NAME);			
		$method = BANTINGAN_ACTION_NAME;	

		if (!method_exists($this, BANTINGAN_ACTION_NAME)) {			
			throw new \Exception('Method does not exists', 404);			
		} else {				
			$findmethod = new \ReflectionMethod($this, BANTINGAN_ACTION_NAME);			
			if ($findmethod->getNumberOfRequiredParameters() > sizeof(BANTINGAN_PARAMETER)) {
				throw new \Exception('Arguments not valid', 404);
			}
		}

		if ($_SERVER['REQUEST_METHOD'] == 'GET') {
			$this->isGET = true;			
		} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
			$this->isPOST = true;			
		}
	}

	public function baseUrl() {
		$this->basePath = __DIR__."/../";
		$this->baseUrl = '//'.$_SERVER['HTTP_HOST'];
		if (!empty(APPLICATION_SETTINGS["BaseUrl"])) {
			$this->baseUrl .= '/'.APPLICATION_SETTINGS["BaseUrl"];
		}
		return $this->baseUrl;
	}

	// flash message, a one read message
	public function flash($key, $value=null)
	{
		if ($value != null) {
			$_SESSION["flashMsg"][$key] = $value;
		} else {			
			$value = isset($_SESSION["flashMsg"][$key])?$_SESSION["flashMsg"][$key]:null;
			$_SESSION["flashMsg"][$key] = null;				
		}		
		return $value;
	}

	// return html
	public function page($viewPathArg=null)
	{
		$page = new PageGenerator;
		$smarty = $page->getSmarty();
		$this->registerPlugin($smarty);

		$page->viewBag = $this->viewBag;
		$page->namespace = $this->namespace;
		return $page->create($viewPathArg);		
	}

	// stream html
	public function view($viewPathArg=null)
	{
		$page = new PageGenerator;
		$smarty = $page->getSmarty();
		$this->registerPlugin($smarty);

		$page->viewBag = $this->viewBag;
		$page->namespace = $this->namespace;		
		$page->render($viewPathArg);

		return $this;		
	}

	// stream pdf
	protected function dompdfView($viewPathArg = null)
	{		
		$html = $this->page($viewPathArg);
			
		// instantiate and use the dompdf class
		$dompdf = new \Dompdf\Dompdf();
		$dompdf->load_html($html);
		$dompdf->set_paper($this->paperSize??'A4', $this->pageOrientation??'Portrait');
		
		$options = $dompdf->getOptions();
		$options->setIsRemoteEnabled(true);
		$options->setIsPhpEnabled(true); 
		$dompdf->setOptions($options);  

		$dompdf->render();

		$dompdf = $this->dompdfInjectPageCount($dompdf);

		$fileName = $this->fileName?$this->fileName:BANTINGAN_ACTION_NAME;

		$dompdf->stream($fileName.".pdf", array("Attachment" => false));		
	}

	// file pdf download
	protected function dompdfFile($viewPathArg = null)
	{		
		$html = $this->page($viewPathArg);
			
		// instantiate and use the dompdf class
		$dompdf = new \Dompdf\Dompdf();
		$dompdf->load_html($html);
		$dompdf->set_paper($this->paperSize??'A4', $this->pageOrientation??'Portrait');
		
		$options = $dompdf->getOptions();
		$options->setIsRemoteEnabled(true);
		$options->setIsPhpEnabled(true); 
		$dompdf->setOptions($options);  

		$dompdf->render();

		$dompdf = $this->dompdfInjectPageCount($dompdf);

		$fileName = $this->fileName?$this->fileName:BANTINGAN_ACTION_NAME;

		$dompdf->stream($fileName.".pdf", array("Attachment" => true));		
	}

	/**
	 * Replace a predefined placeholder DOMPDF_PAGE_COUNT_PLACEHOLDER with the total page count in the whole PDF document
	 *
	 * @param Dompdf $dompdf
	 */
	protected function dompdfInjectPageCount(\Dompdf\Dompdf $dompdf)
	{
	    /** @var CPDF $canvas */
	    $canvas = $dompdf->getCanvas();
	    $pdf = $canvas->get_cpdf();

	    foreach ($pdf->objects as &$o) {
	        if ($o['t'] === 'contents') {
	            $o['c'] = str_replace('DOMPDF_PAGE_COUNT_PLACEHOLDER', $canvas->get_page_count(), $o['c']);
	        }
	    }

	    return $dompdf;			
	}

	// standard Bantingan Json format
	protected function jsonResponse($status, $message, $data, $option=null)
	{
		header('Content-Type: application/json');

		$dataJson = new \StdClass();
        $dataJson->status = $status;
        $dataJson->message = $message;
        $dataJson->data = $data;

		if ($option != null) {
			echo json_encode($dataJson, $option);		
		} else {
			echo json_encode($dataJson);		
		}
	}


	// file xls download
	protected function xlsFile($viewPathArg = null)
	{
		$fileName = $this->fileName?$this->fileName:BANTINGAN_ACTION_NAME;

		$html = $this->page($viewPathArg);
		$reader = new \PhpOffice\PhpSpreadsheet\Reader\Html();
		$spreadsheet = $reader->loadFromString($html);
		unset($reader);

		$writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');		
		header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
		header('Content-Disposition: attachment; filename="'.$fileName.'.xlsx"');
		header('Cache-Control: max-age=0');
		$writer->save("php://output");
	}

	// redirect to other action
	protected function redirectToAction($actionName, $controllerName=null, $objectParameter=null)
	{
		$namespacepath = "";
		if ($this->namespace != "") {
			$namespacepath = strtolower($this->namespace)."/";
		}
		$newUrl =  $this->baseUrl()."/".$namespacepath.BANTINGAN_CONTROLLER_NAME."/".$actionName;
		if (isset($controllerName)) {
			// override controller name if exists in parameter
			$newUrl =  $this->baseUrl()."/".$namespacepath.$controllerName."/".$actionName;
			if (isset($objectParameter)) {
				$newUrl =  $this->baseUrl()."/".$namespacepath.$controllerName."/".$actionName."/".$objectParameter;
			}
		}
		header("Location: ".$newUrl);
		exit;
	}

	protected function redirectToURL($url)
	{
		header("Location: ".$url); 
		exit;
	}

	// return as  json
	protected function json($data, $option = 0)
	{
		header('Content-Type: application/json');
		echo json_encode($data, $option);
	}

	protected function jsonGz($data, $option=null)
	{		
		ob_start('ob_gzhandler');
		header('Content-Type: text/plain');
		header('Content-Encoding: gzip');
		echo gzencode(json_encode($data, $option));
	}

	protected function csvView($data, $withheader, $delimiter=null, $enclosure=null)
	{
		$file = fopen('php://output', 'w');
		
		if ($withheader) {
			$keys = array_keys($data[0]);
			fputcsv($file, $keys);
		}

		foreach ($data as $row) 
		{
		  fputcsv($file, $row);
		}
		
		fclose($file);
	}

	protected function csvFile($data, $withheader, $filename, $delimiter=null, $enclosure=null)
	{		
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename="'.$filename.'.csv"');
		
		return $this->CSVView($data, $withheader, $delimiter, $enclosure);
	}

	private function registerPlugin($smarty)
	{		
		if (count($this->smartyPlugin)>0) {
			foreach($this->smartyPlugin as $plugin) {
				$smarty->registerPlugin($plugin->modifier, $plugin->name, $plugin->name);
			}
		}
	}
}