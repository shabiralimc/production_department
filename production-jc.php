<?php
session_start(); // Start the session (make sure you have this at the beginning of your PHP script)

// Check if the 'user' session variable is set
if (isset($_SESSION['username'])) {
    $username = $_SESSION['username']; // Get the value of 'user' from the session
} else {
    echo "User is not logged in."; // Print a message if the session variable is not set
}
error_reporting(E_ALL);
ini_set('display_errors', 1);
// Check if the user is not logged in
if (!isset($_SESSION['user']) || $_SESSION['role'] !== '2') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;

include_once('../../include/php/connect.php');

// $result = mysqli_query($conn, "SELECT jc_number, jc_date, client,instructed_by,involvements FROM jobcard_main WHERE involvements LIKE '%production%'");

//$result = mysqli_query($conn, "SELECT jobcard_main.jc_number, jobcard_main.jc_date, jobcard_main.client,jobcard_main.instructed_by,jobcard_main.involvements, production_main.jc_number, production_main.pro_main_status FROM jobcard_main LEFT JOIN production_main ON jobcard_main.jc_number = production_main.jc_number WHERE jobcard_main.involvements LIKE '%production%'");

$result = mysqli_query($conn, "SELECT jm.jc_number, jm.jc_date, jm.client, jm.instructed_by, jm.involvements, pm.pro_main_status
                                FROM jobcard_main jm
                                LEFT JOIN production_main pm ON jm.jc_number = pm.jc_number
                                WHERE jm.involvements LIKE '%production%'");

$result_wip = mysqli_query($conn, "SELECT jm.jc_number, jm.jc_date, jm.client, jm.instructed_by, jm.involvements, pm.pro_main_status
                                FROM jobcard_main jm
                                LEFT JOIN production_main pm ON jm.jc_number = pm.jc_number
                                WHERE jm.involvements LIKE '%production%' && pm.pro_main_status LIKE '%WIP%' ");

$result_com = mysqli_query($conn, "SELECT jm.jc_number, jm.jc_date, jm.client, jm.instructed_by, jm.involvements, pm.pro_main_status
                                FROM jobcard_main jm
                                LEFT JOIN production_main pm ON jm.jc_number = pm.jc_number
                                WHERE jm.involvements LIKE '%production%' && pm.pro_main_status LIKE '%Completed%' ");

$result_pend = mysqli_query($conn, "SELECT jm.jc_number, jm.jc_date, jm.client, jm.instructed_by, jm.involvements, pm.pro_main_status
                                FROM jobcard_main jm
                                LEFT JOIN production_main pm ON jm.jc_number = pm.jc_number
                                WHERE jm.involvements LIKE '%production%' AND (SELECT COUNT(*) FROM production_main WHERE jc_number = jm.jc_number) = 0");

?>  
  
<!-- Include Header File -->
<?php include_once ('../../include/php/header.php') ?>

<!-- Include Sidebar File -->
<?php include_once ('../../include/php/sidebar-production.php') ?>

<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
  <!-- Content Header (Page header) -->
  <div class="content-header">
    <div class="container-fluid">
      <div class="row mb-2">
        <div class="col-sm-6">
          <h1 class="m-0">JOBCARD DASHBOARD</h1>
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
          <!-- ./row -->
          <div class="row">
            <div class="col-12 col-sm-12">
              <div class="card card-primary card-tabs">
                <div class="card-header p-0 pt-1">
                  <ul class="nav nav-tabs" id="custom-tabs-one-tab" role="tablist">
                    <li class="nav-item">
                      <a class="nav-link active" id="custom-tabs-one-pending-tab" data-toggle="pill" href="#custom-tabs-one-pending" role="tab" aria-controls="custom-tabs-one-pending" aria-selected="true">NEW/PENDING JOBS</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="custom-tabs-one-messages-tab" data-toggle="pill" href="#custom-tabs-one-messages" role="tab" aria-controls="custom-tabs-one-messages" aria-selected="false">WIP JOBS</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="custom-tabs-one-settings-tab" data-toggle="pill" href="#custom-tabs-one-settings" role="tab" aria-controls="custom-tabs-one-settings" aria-selected="false">COMPLETED JOBS</a>
                    </li>
                    <li class="nav-item">
                      <a class="nav-link" id="custom-tabs-one-home-tab" data-toggle="pill" href="#custom-tabs-one-home" role="tab" aria-controls="custom-tabs-one-home" aria-selected="true">ALL JOBS</a>
                    </li>
                  </ul>
                </div>
                <div class="card-body">
                  <div class="tab-content" id="custom-tabs-one-tabContent">

                    <div class="tab-pane fade show active" id="custom-tabs-one-pending" role="tabpanel" aria-labelledby="custom-tabs-one-pending-tab">
                      <table style="width:100%;" id="productionTablePend" class="table table-bordered table-striped">
                        <thead>
                          <tr style="text-align:center;">
                            <th style="width:5%;">Sl_No</th>
                            <th>JC Number</th>
                            <th>JC Date</th>
                            <th>Client Name</th> <!-- Fix the closing tag for <th> -->
                            <th>Instructed By</th>
                            <th>Involvements</th>
                            <th>Create PO</th>
                            <th>View JC</th>
                          </tr>
                        </thead>

                        <tbody>
                          <?php
                          $slno = 0;
                          while ($row = mysqli_fetch_assoc($result_pend)) {
                          $slno++;
                          ?>
                          <tr>
                            <td style="text-align:center;"><?php echo $slno; ?></td>
                            <td style="text-align:center;"><?php echo $row['jc_number'] ?></td>
                            <td style="text-align:center;"><?php echo date('d-m-Y', strtotime($row['jc_date'])) ?></td>
                            <td><?php echo $row['client'] ?></td>
                            <td style="text-align:center;"><?php echo $row['instructed_by'] ?></td>
                            <td style="text-align:center;"><?php echo $row['involvements'] ?></td>

        <td style="text-align:center;"><?php
            // Check if the session variable is set for creating PO and if the jc_number exists in the session array
            if ($row['pro_main_status'] == 'WIP') {
                echo '<a href="production-po-create.php?jc_number=' . $row['jc_number'] . '" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> CREATE PO</a>';
            } elseif ($row['pro_main_status'] == 'Completed') {
                echo '<button class="btn btn-sm btn-danger" disabled>COMPLETED</button>';
            } else {
                echo '<button class="btn btn-sm btn-primary" disabled><i class="fa fa-plus"></i> CREATE PO</button>';
            }

            
            ?></td>
                            <td style="text-align:center;"><a class="btn-sm btn-success" href="production-jc-view.php?jc_number=<?php echo $row['jc_number'];?>"><i class="fa fa-search"></i> VIEW JC</a></td>
                          </tr>
                            <?php
                              }
                              ?>
                        </tbody>
                      </table>
                    </div>

                    <div class="tab-pane fade" id="custom-tabs-one-home" role="tabpanel" aria-labelledby="custom-tabs-one-home-tab">
                      <table style="width:100%;" id="productionTable" class="table table-bordered table-striped">
                        <thead>
                          <tr style="text-align:center;">
                            <th style="width:5%;">Sl_No</th>
                            <th>JC Number</th>
                            <th>JC Date</th>
                            <th>Client Name</th> <!-- Fix the closing tag for <th> -->
                            <th>Instructed By</th>
                            <th>Involvements</th>
                            <th>Create PO</th>
                            <th>View JC</th>
                          </tr>
                        </thead>

                        <tbody>
                          <?php
                          $slno = 0;
                          while ($row = mysqli_fetch_assoc($result)) {
                          $slno++;
                          ?>
                          <tr>
                            <td style="text-align:center;"><?php echo $slno; ?></td>
                            <td style="text-align:center;"><?php echo $row['jc_number'] ?></td>
                            <td style="text-align:center;"><?php echo date('d-m-Y', strtotime($row['jc_date'])) ?></td>
                            <td><?php echo $row['client'] ?></td>
                            <td style="text-align:center;"><?php echo $row['instructed_by'] ?></td>
                            <td style="text-align:center;"><?php echo $row['involvements'] ?></td>

        <td style="text-align:center;"><?php
            // Check if the session variable is set for creating PO and if the jc_number exists in the session array
            if ($row['pro_main_status'] == 'WIP') {
                echo '<a href="production-po-create.php?jc_number=' . $row['jc_number'] . '" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> CREATE PO</a>';
            } elseif ($row['pro_main_status'] == 'Completed') {
                echo '<button class="btn btn-sm btn-danger" disabled>COMPLETED</button>';
            } else {
                echo '<button class="btn btn-sm btn-primary" disabled><i class="fa fa-plus"></i> CREATE PO</button>';
            }

            
            ?></td>
                            <td style="text-align:center;"><a class="btn-sm btn-success" href="production-jc-view.php?jc_number=<?php echo $row['jc_number'];?>"><i class="fa fa-search"></i> VIEW JC</a></td>
                          </tr>
                            <?php
                              }
                              ?>
                        </tbody>
                      </table>
                    </div>
                    <div class="tab-pane fade" id="custom-tabs-one-messages" role="tabpanel" aria-labelledby="custom-tabs-one-messages-tab">
              <table style="width:100%;" id="productionTableWIP" class="table table-bordered table-striped">
                <thead>
                  <tr style="text-align:center;">
                    <th style="width:5%;">Sl_No</th>
                    <th>JC Number</th>
                    <th>JC Date</th>
                    <th>Client Name</th> <!-- Fix the closing tag for <th> -->
                    <th>Instructed By</th>
                    <th>Involvements</th>
                    <th>Create PO</th>
                    <th>View JC</th>
                  </tr>
                </thead>

                <tbody>
                  <?php
                  $slno = 0;
                  while ($row_wip = mysqli_fetch_assoc($result_wip)) {
                  $slno++;
                  ?>
                  <tr>
                    <td style="text-align:center;"><?php echo $slno; ?></td>
                    <td style="text-align:center;"><?php echo $row_wip['jc_number'] ?></td>
                    <td style="text-align:center;"><?php echo date('d-m-Y', strtotime($row_wip['jc_date'])) ?></td>
                    <td><?php echo $row_wip['client'] ?></td>
                    <td style="text-align:center;"><?php echo $row_wip['instructed_by'] ?></td>
                    <td style="text-align:center;"><?php echo $row_wip['involvements'] ?></td>

<td style="text-align:center;"><?php
    // Check if the session variable is set for creating PO and if the jc_number exists in the session array
    if ($row_wip['pro_main_status'] == 'WIP') {
        echo '<a href="production-po-create.php?jc_number=' . $row_wip['jc_number'] . '" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> CREATE PO</a>';
    } elseif ($row_wip['pro_main_status'] == 'Completed') {
        echo '<button class="btn btn-sm btn-danger" disabled>COMPLETED</button>';
    } else {
        echo '<button class="btn btn-sm btn-primary" disabled><i class="fa fa-plus"></i> CREATE PO</button>';
    }

    
    ?></td>
                    <td style="text-align:center;"><a class="btn-sm btn-success" href="production-jc-view.php?jc_number=<?php echo $row_wip['jc_number'];?>"><i class="fa fa-search"></i> VIEW JC</a></td>
                  </tr>
                    <?php
                      }
                      ?>
                </tbody>
              </table>
                    </div>
                    <div class="tab-pane fade" id="custom-tabs-one-settings" role="tabpanel" aria-labelledby="custom-tabs-one-settings-tab">
               <table style="width:100%;" id="productionTableCOM" class="table table-bordered table-striped">
                <thead>
                  <tr style="text-align:center;">
                    <th style="width:5%;">Sl_No</th>
                    <th>JC Number</th>
                    <th>JC Date</th>
                    <th>Client Name</th> <!-- Fix the closing tag for <th> -->
                    <th>Instructed By</th>
                    <th>Involvements</th>
                    <th>Create PO</th>
                    <th>View JC</th>
                  </tr>
                </thead>

                <tbody>
                  <?php
                  $slno = 0;
                  while ($row_com = mysqli_fetch_assoc($result_com)) {
                  $slno++;
                  ?>
                  <tr>
                    <td style="text-align:center;"><?php echo $slno; ?></td>
                    <td style="text-align:center;"><?php echo $row_com['jc_number'] ?></td>
                    <td style="text-align:center;"><?php echo date('d-m-Y', strtotime($row_com['jc_date'])) ?></td>
                    <td><?php echo $row_com['client'] ?></td>
                    <td style="text-align:center;"><?php echo $row_com['instructed_by'] ?></td>
                    <td style="text-align:center;"><?php echo $row_com['involvements'] ?></td>

<td style="text-align:center;"><?php
    // Check if the session variable is set for creating PO and if the jc_number exists in the session array
    if ($row_com['pro_main_status'] == 'WIP') {
        echo '<a href="production-po-create.php?jc_number=' . $row_com['jc_number'] . '" class="btn btn-sm btn-primary"><i class="fa fa-plus"></i> CREATE PO</a>';
    } elseif ($row_com['pro_main_status'] == 'Completed') {
        echo '<button class="btn btn-sm btn-danger" disabled>COMPLETED</button>';
    } else {
        echo '<button class="btn btn-sm btn-primary" disabled><i class="fa fa-plus"></i> CREATE PO</button>';
    }

    
    ?></td>
                    <td style="text-align:center;"><a class="btn-sm btn-success" href="production-jc-view.php?jc_number=<?php echo $row_com['jc_number'];?>"><i class="fa fa-search"></i> VIEW JC</a></td>
                  </tr>
                    <?php
                      }
                      ?>
                </tbody>
              </table>
                    </div>
                  </div>
                </div>
                <!-- /.card -->
              </div>
            </div>


          
        </div>
      </div>
    </div>
  </section>
</div>


<!-- Include Footer File -->
<?php include_once ('../../include/php/footer.php') ?>


<script>
$(document).ready(function() {
    $('#productionTable').DataTable({
      "responsive": true,
    });
});
</script>

<script>
$(document).ready(function() {
    $('#productionTableWIP').DataTable({
      "responsive": true,
    });
});
</script>

<script>
$(document).ready(function() {
    $('#productionTableCOM').DataTable({
      "responsive": true,
    });
});
</script>

<script>
$(document).ready(function() {
    $('#productionTablePend').DataTable({
      "responsive": true,
    });
});
</script>

</body>
</html>
