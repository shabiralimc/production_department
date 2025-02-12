<?php
include_once('../../include/php/connect.php');
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '2') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;

if (isset($_GET['po_number'] )) {
    $po_number = $_GET['po_number'];
    $_SESSION['po_number'] = $po_number;

    
    $sql_main_query = "SELECT * FROM production_jc_po_main WHERE po_number ='$po_number'";
    $sql_main = mysqli_query($conn, $sql_main_query);
    if (!$sql_main) {
        die("Error: " . mysqli_error($conn));
    }else{
        $main=mysqli_fetch_assoc($sql_main);
    }

    $sql_pro_invo_query = "SELECT * FROM production_invoice_main WHERE po_number='$po_number'";
    $sql_pro_invo = mysqli_query($conn, $sql_pro_invo_query);
    if (!$sql_pro_invo) {
      die ("Error: " . mysqli_error($conn));
    } else {
      $sql_pro_invo_items=mysqli_fetch_assoc($sql_pro_invo);
    }

    $vandor = $main['vandor_name'];
    $sql_vandor_mas_query = "SELECT * FROM vandor_masters WHERE vandor_name = '$vandor'";
    $sql_vandor_mas_list = mysqli_query($conn, $sql_vandor_mas_query);
    if (!$sql_vandor_mas_list) {
      die ("Error: " . mysqli_error($conn));
    } else {
      $sql_vandor_mas_lists = mysqli_fetch_assoc($sql_vandor_mas_list);
    }

    $sql_items = mysqli_query($conn,"SELECT s_description, no_of_copy, estimated_amount FROM production_jc_po_items WHERE po_number='$po_number'");

}

?>

<!-- Include Header File -->
<?php include_once ('../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../include/php/sidebar-production.php') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper" style="background-color:white;">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">Dashboard</h1>
        </div>
      </div>
      <!-- /.row -->
    </div>
    <!-- /.container-fluid -->
  </div>
  <!-- /.content-header -->
  <!-- Main content -->
  <section class="content">
    <div class="container-fluid">
      <!-- Main row -->
      <div class="row">
        <div class="col-12">
          <!-- solid sales graph -->
          <div class="card card-info card-outline" style="margin:25px;">

<div id="printableArea">
            <div class="card-body">
              <div class="row">

                <div class="col-sm-6">
                  <img src="../../dist/img/logo-full.png" style="width:30%;">
                </div>
                <div class="col-sm-6">
                  <p style="padding:10px; background-color: grey; color: white; font-size: 25pt; font-weight: 900; text-align: center;">PURCHASE ORDER</p>
                </div>

              </div>

              <div class="row" style="margin-top:25px;">

                <div class="col-sm-6">
                  <p>3rd Floor, Chakra Towers, Vanross Junction, Trivandrum - 695 001<br>
                    GST No.: 32AABFC6512C1Z9<br>
                  PAN No.: AABFC6512C</p>
                </div>
                <div class="col-sm-6">
                  <table class="table table-bordered">
                    <tr>
                      <td style="font-weight: bold;">PO No.</td>
                      <td><?php echo $main['po_number']; ?></td>
                      <td style="font-weight: bold;">PO Date</td>
                      <td><?php echo date("d-m-Y", strtotime($main['po_date'])); ?></td>
                    </tr>
                  </table>
                </div>

              </div>

                <div class="row" style="margin-top:25px;">
                  <div class="col-sm-6">
                  <div class="col-sm-12" style="background-color:grey; color: white; font-weight: 400; font-size:18pt;">VENDOR DETAILS</div>
                  <?php if ($sql_vandor_mas_lists > 0) {
                    echo '

                  <p style="text-transform:uppercase; font-size: 16pt; margin-top:15px; font-weight:bold;">'.$sql_vandor_mas_lists['vandor_name'].'</p>
                  <p>'.$sql_vandor_mas_lists['s_address'].'<br>
                    '.$sql_vandor_mas_lists['gst_no'].'<br>
                  '.$sql_vandor_mas_lists['phone'].'</p>
                    ';
                  } else {
                    
                  }
                  ?>

                </div>

                
                <div class="col-sm-6">
                  <div class="col-sm-12" style="background-color:grey; color: white; font-weight: 400; font-size:18pt;">OTHER DETAILS</div>
                  <table style="margin-top:25px;" class="table table-bordered">
                    <tr>
                      <td style="font-weight: bold;">Ref. JC Number</td>
                      <td><?php echo $main['jc_number']; ?></td>
                      <td style="font-weight: bold;">Delivery Date</td>
                      <td><?php echo date("d-m-Y", strtotime($main['delivery_deadline'])); ?></td>
                    </tr>
                    <tr>
                      <td style="font-weight: bold;">Delivery Type</td>
                      <td><?php echo $main['shipping']; ?></td>
                      <td style="font-weight: bold;">Sample Required</td>
                      <td><?php echo $main['sample_required']; ?></td>
                    </tr>
                  </table>
                </div>

              </div>

              <div class="row" style="margin-top:25px;">

                <div class="col-sm-12">
                  <div class="col-sm-12" style="background-color:grey; color: white; font-weight: 400; font-size:18pt;">PURCHASE ITEM DETAILS</div>

                  <table class="table table-bordered table-striped" style="margin-top:25px;">
                    
                    <thead>
                      <tr style="text-align:center;">
                        <th style="width:10%;">Sl.No</th>
                        <th style="width:60%;">Description</th>
                        <th style="width:15%;">No Of Copy</th>
                        <th style="width:15%;">Estimated Amount</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php
                      $siNo = 1; // Initialize Si. No
                      while ($items = mysqli_fetch_assoc($sql_items)) {

                          $siNo;
                      ?>

                      <tr>
                        <td style="text-align:center;"><?php echo $siNo++; ?></td>
                        <td><?php echo $items['s_description']; ?></td>
                        <td style="text-align:center;"><?php echo $items['no_of_copy']; ?></td>
                        <td style="text-align:center;"><?php echo $items['estimated_amount']; ?></td>
                      </tr>
                      <?php
                        }
                        ?>
                    </tbody>
                  </table>

                </div>

              </div>

              <?php if ($sql_pro_invo_items > 0) {
                        echo '
              <div class="row" style="margin-top:25px;">

                <div class="col-sm-12">
                  <div class="col-sm-12" style="background-color:grey; color: white; font-weight: 400; font-size:18pt;">EXPENCES DETAILS</div>

                  <table class="table table-bordered table-striped" style="margin-top:25px;">
                    
<thead>
                      <tr style="text-align:center;">
                        <th>Invoice Number</th>
                        <th>Invoice Date</th>
                        <th>Amount</th>
                        <th>Freight Charges</th>
                        <th>Adddl Expences</th>
                        <th>Total Expences</th>
                        <th>Notes</th>
                      </tr>
                    </thead>

                    <tbody>
                      
                        <tr>
                          <td style="text-align:center;">'.$sql_pro_invo_items['invoice_number'].'</td>
                          <td style="text-align:center;">'.date("d-m-Y", strtotime($sql_pro_invo_items['invoice_date'])).'</td>
                          <td style="text-align:center;">'.$sql_pro_invo_items['amount'].'</td>
                          <td style="text-align:center;">'.$sql_pro_invo_items['freight'].'</td>
                          <td style="text-align:center;">'.$sql_pro_invo_items['addl_expences'].'</td>
                          <td style="text-align:center;">'.$sql_pro_invo_items['total_expences'].'</td>
                          <td style="text-align:center;">'.$sql_pro_invo_items['s_descriptions'].'</td>
                        </tr>
                        
                      
                    </tbody>
                  </table>

                </div>

              </div>
              ';
                      }   ?>

            </div>
            </div>

            <div class="card-footer">
              <button class="btn btn-success" type="button" onclick="printDiv('printableArea')"><i class="fa fa-print"></i> PRINT PO</button>
              <button class="btn btn-info" name="close"><a href="production-jc-view.php?jc_number=<?php echo $main['jc_number']; ?>" style="text-decoration: none; color: white;">Close (without save)</a></button>
            </div>


          </div>
        </div>
      </div>
    </div>
  </section>



</div>




<?php include_once ('../../include/php/footer.php') ?>

<script type="text/javascript">
  function printDiv(divName) {
     var printContents = document.getElementById(divName).innerHTML;
     var originalContents = document.body.innerHTML;

     document.body.innerHTML = printContents;

     window.print();

     document.body.innerHTML = originalContents;
}

</script>
</body>
</html>