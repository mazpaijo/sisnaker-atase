
<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once("lib/nusoap.php");

class ServicesAPI extends CI_Controller {

  public function __construct()
  {
    parent::__construct();
  }

  function insert_jo()
  {
    $jobid = $this->input->post('jobid', TRUE);

    $r = new stdClass;
    $r->status = 0;


    // cek cekal pptkis di BNP2TKI
    require_once 'xmlrpc-func.php';

    $xmlrpc_server_host     = 'siskotkln.bnp2tki.go.id';
    $xml_rpc_server_path    = '/xmlrpc/siskotkln_ws/index.php';

    define(XMLRPC_DEBUG, 1);


    $sql = "	SELECT
    *, DATE_FORMAT(jobtglawal, '%Y%m%d') as jobtglawal2, DATE_FORMAT(jobtglakhir, '%Y%m%d') as jobtglakhir2
    FROM jo
    WHERE
    jo.jobid = $jobid";
    $result = mysql_query($sql) or die($messages['err_query']);
    $tmp = array();
    if ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
      $tmp = $row;
    }

    $USER_ID            = "ws_twn";
    $USER_PASS          = "ws_twn";
    $SERVICE_NAME       = "exec.insert_jo";
    $SERVICE_PARAM	  = $tmp['jobno']."|".$tmp['jobtglawal2']."|".$tmp['jobtglakhir2']."|".$tmp['ppkode']."|".$tmp['agid']."|";

    $sql = "	SELECT
    *
    FROM jodetail
    JOIN jenispekerjaan ON jenispekerjaan.jpid = jodetail.jpid
    WHERE
    jodetail.jobid = $jobid";
    $result = mysql_query($sql) or die($messages['err_query']);
    $tmp = array();
    $n = mysql_num_rows($result);
    $i = 0;
    while ($row = mysql_fetch_array($result,MYSQL_ASSOC)) {
      $SERVICE_PARAM .= $row['idpekerjaan_bnp2tki']."[,]".$row['jobdl']."[,]".$row['jobdp']."[,]".$row['jobdc']."[,]".(int)$row['jpgaji'];
      if ($i < ($n - 1)) {
        $SERVICE_PARAM .= "[.]";
      }
      $i++;
    }

    $inputArray['USER_ID']          = $USER_ID;
    $inputArray['USER_PASS']        = $USER_PASS;
    $inputArray['SERVICE_NAME']     = $SERVICE_NAME;
    $inputArray['SERVICE_PARAM']    = $SERVICE_PARAM;

    $result = XMLRPC_request($xmlrpc_server_host,  $xml_rpc_server_path ,"siskotkln_ws" , array( XMLRPC_prepare( $inputArray ) ) );
    if ($result[0] == 1) {
      $r->response = $result[1];

      $sql = "UPDATE jo SET jobispushed = 1, jopushtimestamp = NOW() WHERE jobid = $jobid";
      $result = mysql_query($sql) or die($messages['err_query']);

      $r->status = 1;
    }

    echo json_encode($r);
  }

}
?>