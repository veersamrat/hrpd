<?php

session_start();
date_default_timezone_set("Asia/Kolkata");

include_once('DAO/ReportDAO.php');

//Creating objects
$ReportDAO = new ReportDAO();

$disabled = "";
$last_months = date('m');

if ($_REQUEST['month'] != '') {
    $last_months = $_REQUEST['month'];
}

$year = date('Y');
if ($_REQUEST['year'] != '') {
    $year = $_REQUEST['year'];
}

if ($_SESSION['area_id'] > 0) {
    $district_id = $_SESSION['area_id'];
    $disabled = "disabled = disabled";
} else {
    $district_id = 0;
}
if (isset($_REQUEST['district_id']) && $_REQUEST['district_id'] != '') {
    $district_id = $_REQUEST['district_id'];
}

$divisionName = $ReportDAO->getDivisionNameByID($district_id);
$monthName = date("F", mktime(0, 0, 0, $last_months, 10));

function getDays() {
    $arrDaysNo = array();
    $last_months = date('m');
    if (isset($_REQUEST['month']) && $_REQUEST['month'] != '') {
        $last_months = $_REQUEST['month'];
    }
    $year = date('Y');
    if (isset($_REQUEST['year']) && $_REQUEST['year'] != '') {
        $year = $_REQUEST['year'];
    }
    $number = cal_days_in_month(CAL_GREGORIAN, $last_months, $year);
    for ($i = 1; $i <= $number; $i++) {
        $arrDaysNo[$i] = 0;
    }
    return $arrDaysNo;
}

$division_id = 5;
$selectedMonth = 1;
if ($selectedMonth > 0) {
    $month = "MONTH(NOW()- INTERVAL " . $selectedMonth . " MONTH)";
} else {
    $month = "MONTH(NOW())";
}


error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Asia/Kolkata');

if (PHP_SAPI == 'cli')
    die('This page should only be run from a Web Browser');

/** Include PHPExcel */
require_once dirname(__FILE__) . '/libraries/PHPExcel.php';


// Create new PHPExcel object
$objPHPExcel = new PHPExcel();

// Set document properties
$objPHPExcel->getProperties()->setCreator("HRPDSS")
        ->setLastModifiedBy("HRPDSS")
        ->setTitle("Summary of Monthly Complaint Report")
        ->setSubject("Division " . $divisionName . " For the Month of " . $monthName)
        ->setDescription("Summary of Monthly Complaint Report Division " . $divisionName . " For the Month of " . $monthName)
        ->setKeywords("HRPDSS Complaint Reports")
        ->setCategory("HRPDSS Complaint Reports");



//For Total Complaint Register according to Day and Categorised Excel Section----------------------------


$j = 0;
$setData = '';
$setExcelName = $divisionName."_".$year."_".$monthName."_".date('d-m-Y');

$objPHPExcel->getDefaultStyle()->getAlignment()
        ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);

$objPHPExcel->setActiveSheetIndex(0)->getStyle('B')->getAlignment()->setHorizontal(
        PHPExcel_Style_Alignment::HORIZONTAL_LEFT
);

$objPHPExcel->getActiveSheet()->getStyle("A1:AH1")->getFont()->setBold(true)->setSize(16);
$objPHPExcel->getActiveSheet()->getStyle("A2:AH2")->getFont()->setBold(true)->setSize(14);
$objPHPExcel->getActiveSheet()->getStyle("A3:AH3")->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(50);
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A1:AH1');
$objPHPExcel->setActiveSheetIndex(0)->mergeCells('A2:AH2');
$objPHPExcel->setActiveSheetIndex(0)
        ->setCellValue('A1', "Summary of Monthly Complaint Report")
        ->setCellValue('A2', "Division " . $divisionName . " For the Month of " . $monthName)
        ->setCellValue('A3', "Code")
        ->setCellValue('B3', "Category of Complaint");


$rowNumber = 3;
$col = 'C';
$number = cal_days_in_month(CAL_GREGORIAN, $last_months, $year);
for ($num = 1; $num <= $number; $num++) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $num);
    $col++;
    $nextCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($nextCol . $rowNumber, "Total");

$setRec = $ReportDAO->getComplaintCategory();
$rowNumber = 4;
foreach ($setRec as $key => $value) {
    $arrDaysNo = getDays();
    $complain_category_id = $value['complain_category_id'];

    $results = $ReportDAO->getComplaintNumberByDate($last_months, $year, $district_id, $complain_category_id);
    $total = 0;
    foreach ($results as $key => $result) {
        $arrDaysNo[$result['dayNo']] = $result['counted'];
        $total = $total + $result['counted'];
    }
    //$setData .= $value['complain_category_id'] . "\t" . $value['Category'] . "\t";
    $objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, $value['complain_category_id']);
    $objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, $value['Category']);
    $col = 'C';
    //$rowNumber = 4;
    foreach ($arrDaysNo as $key => $val) {
        $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val);
        $col++;
        $lastCol = $col;
    }
    $objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total);
    $rowNumber++;
}


$rowNumber = $rowNumber;
//For Total Complaint Register Excel Section----------------------------
$results = $ReportDAO->getComplaintNumberByDate($last_months, $year, $district_id, 0);
$total = 0;
foreach ($results as $key => $result) {
    $arrDaysNo[$result['dayNo']] = $result['counted'];
    $total = $total + $result['counted'];
}
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber . ":".$lastCol . $rowNumber)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Total Number of Complaints");
$col = 'C';
foreach ($arrDaysNo as $key => $val) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val);
    $col++;
    $lastCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total);

$rowNumber = $rowNumber+5;

$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Response Time");
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber)->getFont()->setBold(true);

//Get Complaint resolved within an hour
$rowNumber = $rowNumber+1;
$arrDaysNo1 = getDays();
$results1 = $ReportDAO->getResolvedComplaintNumberByDateAndHour($last_months, $year, $district_id, 1);
$total1 = 0;
foreach ($results1 as $key => $result1) {
    $arrDaysNo1[$result1['dayNo']] = $result1['counted'];
    $total1 = $total1 + $result1['counted'];
}
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in less than 1 - hr");
$col = 'C';
foreach ($arrDaysNo1 as $key => $val1) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val1);
    $col++;
    $lastCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total1);

//Get Complaint resolved within an hour
$arrDaysNo2 = getDays();
$rowNumber = $rowNumber+1;
$results2 = $ReportDAO->getResolvedComplaintNumberByDateAndHour($last_months, $year, $district_id, 2);
$total2 = 0;
foreach ($results2 as $key => $result2) {
    $arrDaysNo2[$result2['dayNo']] = $result2['counted'];
    $total2 = $total2 + $result2['counted'];
}
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in 1 to 2 hrs");
$col = 'C';
foreach ($arrDaysNo2 as $key => $val2) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val2);
    $col++;
    $lastCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total2);

//Get Complaint resolved within an hour
$arrDaysNo3 = getDays();
$rowNumber = $rowNumber+1;
$results3 = $ReportDAO->getResolvedComplaintNumberByDateAndHour($last_months, $year, $district_id, 3);
$total3 = 0;
foreach ($results3 as $key => $result3) {
    $arrDaysNo3[$result3['dayNo']] = $result3['counted'];
    $total3 = $total3 + $result3['counted'];
}
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in 2 to 4 hrs");
$col = 'C';
foreach ($arrDaysNo3 as $key => $val3) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val3);
    $col++;
    $lastCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total3);

//Get Complaint resolved within an hour
$arrDaysNo4 = getDays();
$rowNumber = $rowNumber+1;
$results4 = $ReportDAO->getResolvedComplaintNumberByDateAndHour($last_months, $year, $district_id, 4);
$total4 = 0;
foreach ($results4 as $key => $result4) {
    $arrDaysNo4[$result4['dayNo']] = $result4['counted'];
    $total4 = $total4 + $result4['counted'];
}
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in more than 4 - hrs");
$col = 'C';
foreach ($arrDaysNo4 as $key => $val4) {
    $objPHPExcel->getActiveSheet()->setCellValue($col . $rowNumber, $val4);
    $col++;
    $lastCol = $col;
}
$objPHPExcel->getActiveSheet()->setCellValue($lastCol . $rowNumber, $total4);


$rowNumber = $rowNumber+4;

$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in less than 1 - hr");
$objPHPExcel->getActiveSheet()->setCellValue("C" . $rowNumber, $total1);
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("C" . $rowNumber)->getFont()->setBold(true);

$rowNumber = $rowNumber+1;
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in 1 to 2 hrs");
$objPHPExcel->getActiveSheet()->setCellValue("C" . $rowNumber, $total2);
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("C" . $rowNumber)->getFont()->setBold(true);

$rowNumber = $rowNumber+1;
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in 2 to 4 hrs");
$objPHPExcel->getActiveSheet()->setCellValue("C" . $rowNumber, $total3);
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("C" . $rowNumber)->getFont()->setBold(true);

$rowNumber = $rowNumber+1;
$objPHPExcel->getActiveSheet()->setCellValue("A" . $rowNumber, '');
$objPHPExcel->getActiveSheet()->setCellValue("B" . $rowNumber, "Attended in more than 4 - hrs");
$objPHPExcel->getActiveSheet()->setCellValue("C" . $rowNumber, $total4);
$objPHPExcel->getActiveSheet()->getStyle("B" . $rowNumber)->getFont()->setBold(true);
$objPHPExcel->getActiveSheet()->getStyle("C" . $rowNumber)->getFont()->setBold(true);

// Rename worksheet
$objPHPExcel->getActiveSheet()->setTitle('Simple');

$styleArray = array(
  'borders' => array(
    'allborders' => array(
      'style' => PHPExcel_Style_Border::BORDER_THIN
    )
  )
);

$objPHPExcel->getActiveSheet()->getStyle('A1:AH'.$rowNumber)->applyFromArray($styleArray);

$nCols = 34; //set the number of columns

    foreach (range(2, $nCols) as $col) {
        $objPHPExcel->getActiveSheet()->getColumnDimensionByColumn($col)->setWidth(5);            
    }
// Set active sheet index to the first sheet, so Excel opens this as the first sheet
$objPHPExcel->setActiveSheetIndex(0);


// Redirect output to a client’s web browser (Excel2007)
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="'.$setExcelName.'".xlsx"');
header('Cache-Control: max-age=0');
// If you're serving to IE 9, then the following may be needed
header('Cache-Control: max-age=1');

// If you're serving to IE over SSL, then the following may be needed
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
header('Pragma: public'); // HTTP/1.0

$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
$objWriter->save('php://output');
exit;
