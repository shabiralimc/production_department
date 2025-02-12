<?php
include_once('../../include/php/connect.php');
session_start();
error_reporting(E_ALL);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '2') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

// Calculate the remaining time
$sessionStart = $_SESSION['session_start'];
$sessionLifetime = $_SESSION['session_lifetime'];
$currentTime = time();
$remainingTime = ($sessionStart + $sessionLifetime) - $currentTime;

if (isset($_GET['jc_number'] )) {
    $newUserId = $_GET['jc_number'];
    $_SESSION['new'] = $newUserId;

    // Use prepared statements to fetch data based on jc_number for jobcard_main
    $sql_main = "SELECT jm.jc_number, jm.jc_date, jm.client, jm.involvements, pm.pro_main_status 
                 FROM jobcard_main jm
                 LEFT JOIN production_main pm ON jm.jc_number = pm.jc_number
                 WHERE jm.jc_number = ?";
    $stmt_main= mysqli_prepare($conn, $sql_main);

    if ($stmt_main) {
        mysqli_stmt_bind_param($stmt_main, "s", $newUserId);
        mysqli_stmt_execute($stmt_main);
        $result_main = mysqli_stmt_get_result($stmt_main);

        // Check if the query returned any rows
        if ($result_main && mysqli_num_rows($result_main) > 0) {
            $row_main = mysqli_fetch_assoc($result_main);
        } else {
            // Handle the case when no rows are returned (jc_number not found)
            echo "<script>alert('JC Number not found - Please check the JC Number entered...');";
            // You might want to redirect or display an appropriate message.
            exit();
        }

        mysqli_stmt_close($stmt_main);

    }
    $sql_items = mysqli_query($conn, "SELECT s_description, width, height, unit, qty FROM jobcard_items WHERE jc_number='$newUserId'");
    $result = mysqli_query($conn, "SELECT po_number, po_date, vandor_name, s_status FROM production_jc_po_main WHERE jc_number='$newUserId'");
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $newUserId = $_SESSION['new'];

    if (isset($_POST['pro_main_status'])) {
        $current_status = $_POST['pro_main_status'];

        // Check if jc_number already exists in production_main
        $check_sql = "SELECT jc_number FROM production_main WHERE jc_number = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("s", $newUserId);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            // jc_number exists, perform an update
            $update_sql = "UPDATE production_main SET pro_main_status = ? WHERE jc_number = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("ss", $current_status, $newUserId);

            if ($update_stmt->execute()) {
                $_SESSION['pro_main_status'][$newUserId] = $current_status; // Store the status in a session variable with jc_number as the key                
                echo "<script>alert('The Status of Job Card Updated...'); window.location.href = '$app_url/user/production/production-jc.php';</script>";
            } else {
                echo "Error: " . $update_stmt->error;
            }

            $update_stmt->close();
        } else {
            // jc_number does not exist, perform an insert
            $insert_sql = "INSERT INTO production_main (jc_number, pro_main_status) VALUES (?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ss", $newUserId, $current_status);

            if ($insert_stmt->execute()) {
                $_SESSION['pro_main_status'][$newUserId] = $current_status; // Store the status in a session variable with jc_number as the key
                echo "<script>alert('The Status of Job Card Updated...'); window.location.href = '$app_url/user/production/production-jc.php';</script>";
            } else {
                echo "Error: " . $insert_stmt->error;
            }

            $insert_stmt->close();
        }

        $check_stmt->close();
    }
}
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
        <section class="col-12">
          <!-- solid sales graph -->
          <div class="card" style="background-color: #f4f4f4;">
            <div class="card-header border-0">
              
              <div class="card-tools">
                <button type="button" class="btn btn-sm" style="background-color:#9f9f9f; color: white;" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
                </button>
                <button type="button" class="btn btn-sm" style="background-color:#9f9f9f; color: white;" data-card-widget="remove">
                <i class="fas fa-times"></i>
                </button>
              </div>
            </div>
            <div class="card-body">

              <!-- YOUR CONTENT GOES HERE -->
              <div class="row">
                  <div class="col-md-6">
                    <div class="row">
                      <div class="col-md-4">
                        <div class="small-box bg-default">
                          <div class="inner">
                            <h3><?php echo $row_main['jc_number'];?></h3>
                            <p><?php echo date('d-m-Y', strtotime($row_main['jc_date']));?></p>
                          </div>
                          <div class="icon">
                            <i class="ion ion-ios-paper"></i>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-8">
                        <div class="small-box bg-default">
                          <div class="inner">
                            <h3><?php echo $row_main['client'];?></h3>
                            <p>Client Name</p>
                          </div>
                          <div class="icon">
                            <i class="fa fa-user"></i>
                          </div>
                        </div>
                      </div>
                      <div class="col-md-12">
                        <div class="small-box bg-default">
                          <div class="inner">
                            <h3>Involvements</h3>
                            <p><?php echo $row_main['involvements'];?></p>
                          </div>
                          <div class="icon">
                            <i class="fa fa-print"></i>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label>Work Details</label>
                    <table class="table table-bordered table-striped" style="width:100%;">
                      <thead>
                        <tr>
                          <th>Sl.No.</th>
                          <th>Description</th>
                          <th>Width</th>
                          <th>Height</th>
                          <th>Unit</th>
                          <th>Qty</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php
                $sino=0;
                while ($row = mysqli_fetch_assoc($sql_items)) {
                $sino++;
                ?>
                <tr>
                    <td><?php echo $sino; ?></td>
                    <td><?php echo $row['s_description']; ?></td>
                    <td><?php echo $row['width'];?></td>
                    <td><?php echo $row['height']; ?></td>
                    <td><?php echo $row['unit']; ?></td>
                    <td><?php echo $row['qty']; ?></td>
                </tr>
                    <?php
                    }
                    ?>
                      </tbody>
                    </table>
                  </div>
                  <?php 

                  if ($row_main['pro_main_status'] == '') {
                    echo '
                    <div class="col-md-6">
                    <form action=""method="POST">
                      <div class="input-group mb-3">
                        <select class="form-control" name="pro_main_status" id="pro_main_status">
                          <option value="" disabled selected>-- Select Status --</option>
                          <option value="WIP">Work In Progress</option>
                          <option value="Completed">Completed</option>
                        </select>
                        <div class="input-group-append">
                          <input class="btn btn-primary" type="submit" name="submit" id="submit" value="CHANGE STATUS">
                        </div>
                      </div>
                    </form>
                  </div>
                  ';
                  } elseif ($row_main['pro_main_status'] == 'WIP') {
                    echo '
                    <div class="col-md-6">
                    <form action=""method="POST">
                      <div class="input-group mb-3">
                        <select class="form-control" name="pro_main_status" id="pro_main_status">
                          <option value="" disabled>-- Select Status --</option>
                          <option value="WIP" selected>Work In Progress</option>
                          <option value="Completed">Completed</option>
                        </select>
                        <div class="input-group-append">
                          <input class="btn btn-primary" type="submit" name="submit" id="submit" value="CHANGE STATUS">
                        </div>
                      </div>
                    </form>
                  </div>
                  ';
                } else {

                  echo "";
                }
                ?>
              </div>
              <div class="row">
                <div class="col-md-6">

<?php
    // Assuming $row_main['current_status'] contains the current status
    $currentStatus = isset($row_main['pro_main_status']) ? $row_main['pro_main_status'] : '';

    // Check if the current status is 'Work In Progress' to display or disable the button
    if ($currentStatus == 'WIP') {
        echo '<a href="production-po-create.php?jc_number=' . $row_main['jc_number'] . '" class="btn btn-primary">ISSUE PURCHASE ORDER</a>';
    } else {
        echo '<button class="btn btn-primary" disabled>CREATE PURCHASE ORDER</button>';
    }
    ?>

                </div>
              </div>
              <div class="row">
                <div class="col-md-12">
                  <div style="margin-top:25px;" class="card card-info card-outline">
                    <div class="card-header">PO Generated</div>
                    <div class="card-body">
                      <table class="table table-bordered table-striped" id="purchase" style="width:100%;">
                        <thead>
                            <tr style="text-align:center;">
                                <th style="width:5%;">Sl. No.</th>
                                <th>PO Number</th>
                                <th>PO Date</th>
                                <th>Vendor Name</th>
                                <th>Status</th>
                                <th style="width:10%;">View PO</th>
                                <th style="width:10%;">Edit PO</th>
                                <th style="width:10%;">Expences</th>
                            </tr>
                        </thead>
                        <tbody>
                          <?php
                          $siNo=0;
                          while ($row1 = mysqli_fetch_assoc($result)) {
                            // Check if invoice data has been inserted for the current po_number
                            $po_number = $row1['po_number'];
                            $sql_check_invoice = "SELECT COUNT(*) as count FROM production_invoice_main WHERE po_number = ?";
                            $stmt_check_invoice = mysqli_prepare($conn, $sql_check_invoice);

                            if ($stmt_check_invoice) {
                              mysqli_stmt_bind_param($stmt_check_invoice, "s", $po_number);
                              mysqli_stmt_execute($stmt_check_invoice);
                              $result_check_invoice = mysqli_stmt_get_result($stmt_check_invoice);
                              $row_check_invoice = mysqli_fetch_assoc($result_check_invoice);
                              mysqli_stmt_close($stmt_check_invoice);

                              // Check if there is data in the invoice_main table for the current po_number
                              $invoiceDataExists = $row_check_invoice['count'] > 0;

                              // Store the invoice data status in a session variable
                              $_SESSION['invoiceDataExists'] = $invoiceDataExists;
                            } else {
                              $_SESSION['invoiceDataExists'] = false;
                            }
                            $siNo++;
                            ?>
                            <tr>
                              <td><?php echo $siNo; ?></td>
                              <td><?php echo $row1['po_number']; ?></td>
                              <td><?php echo date('d-m-Y', strtotime($row1['po_date'])); ?></td>
                              <td><?php echo $row1['vandor_name']; ?></td>
                              <td><?php echo $row1['s_status']; ?></td>
                              <td><a href="production_po_view.php?po_number=<?php echo $row1['po_number']; ?>"class="btn btn-info"><i class="fa fa-search"></i> VIEW PO</a></td>
                              
                              <td>
                                <?php
                                if ($row1['s_status'] === 'Completed') {
                                  // If Status is "Completed," generate a disabled button in the "Edit PO" column
                                  echo '<button class="btn btn-success" disabled><i class="fa fa-plus"></i> EDIT PO</button>';
                                } else {
                                  // If Status is not "Completed," generate a regular button in the "Edit PO" column
                                  echo '<a href="production_po_edit.php?po_number=' . $row1['po_number'] . '" class="btn btn-success"><i class="fa fa-plus"></i> EDIT PO</a>';
                                }
                                ?>
                              </td>

                              <td>
                                <?php
                                if ($row1['s_status'] == 'Completed') {
                                  // Only display the "Expenses" button if the status is not "Completed" and invoice data doesn't exist
                                  if (!$invoiceDataExists) {
                                    // If no invoice data exists, generate a regular button
                                    echo '<a href="production_po_expences.php?po_number=' . $row1['po_number'] . '" class="btn btn-success"><i class="fa fa-plus"></i> EXPENCES</a>';
                                  }else{
                                    echo 'Expences filled';
                                  }
                                }
                                ?>
                              </td>

                            </tr>
                            <?php
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <!-- YOUR CONTENT ENDS HERE -->                
            </div>
            <!-- /.card-body -->
            <div class="card-footer">
              <a class="btn btn-info" href="production-jc.php">Close (without save)</a>
            </div>
          </div>
          <!-- /.card -->
        </section>
      </div>
      <!-- /.row (main row) -->
    </div>
    <!-- /.container-fluid -->
  </section>
  <!-- /.content -->
</div>




<!-- Include Footer File -->
<?php include_once ('../../include/php/footer.php') ?>


</div>
<!-- ./wrapper -->

<!-- Page Specific Script -->
<script>
$(document).ready(function() {
    $('#purchase').DataTable({
      "responsive": true,
    });
});
</script>

<script>
function navCreateClient() {
  let text = 'Press OK to take yout to Client Creation Page! Or Press Cancel to stay on this page.';
  if (confirm(text) == true) {
    window.location.href = '/production-jc.php';
  } 
}
</script>



</body>
</html>