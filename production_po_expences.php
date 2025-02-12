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

    // Use prepared statements to fetch data based on jc_number for jobcard_main
    $sql_main = "SELECT po_number,jc_number,po_date, vandor_name FROM production_jc_po_main WHERE po_number = ?";
    $stmt_main= mysqli_prepare($conn, $sql_main);

    if ($stmt_main) {
        mysqli_stmt_bind_param($stmt_main, "s", $po_number);
        mysqli_stmt_execute($stmt_main);
        $result_main = mysqli_stmt_get_result($stmt_main);
        $row_main = mysqli_fetch_assoc($result_main);
        mysqli_stmt_close($stmt_main);
        $jc_number=$row_main['jc_number'];
        $_SESSION['jc_number']=$jc_number;

    }
    $sql_items = mysqli_query($conn,"SELECT s_description, no_of_copy, estimated_amount FROM production_jc_po_items WHERE po_number='$po_number'");
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ok"])) {
    // Retrieve form data
    $invoice_number = $_POST['invoice_number'];
    $invoice_date = $_POST['invoice_date'];
    $amount = $_POST['amount'];
    $freight = $_POST['freight'];
    $addl_expences = $_POST['addl_expences'];
    $total_expences = $_POST['total_expences'];
    $description = $_POST['s_descriptions'];
    
    // Prepare and execute an SQL query to insert data into the invoice_main table
    $insert_sql = "INSERT INTO production_invoice_main (po_number, jc_number, invoice_number, invoice_date, amount, freight, addl_expences, total_expences, s_descriptions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insert_sql);
    $stmt->bind_param("ssssdddds", $_SESSION['po_number'], $_SESSION['jc_number'], $invoice_number, $invoice_date, $amount, $freight, $addl_expences, $total_expences, $description);

        if ($stmt->execute()) {
        echo "<script>alert('The Expences Saved Successfully...'); window.location.href = '$app_url/user/production/production-jc-view.php?jc_number=$jc_number';</script>";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
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
        <div class="col-12">
          <!-- solid sales graph -->
          <div class="card card-info card-outline">
            <div class="card-body">
              
              <div class="row">
                
                <div class="col-md-4">
                  
                  <form>

                  <div class="form-group">
                    <label for="jc_number" class="form-label">Purchase Order Number</label><br>
                    <input type="text"class="form-control" value="<?php echo $row_main['po_number']; ?>"readonly></td>
                  </div>

                  <div class="form-group">
                    <label for="po_date" class="form-label">Purchase Date</label><br>
                    <input type="date"name="po_date"id="po_date"class="form-control" value="<?php echo $row_main['po_date']; ?>"readonly></td>
                  </div>

                  <div class="form-group">
                    <label for="jc_number" class="form-label">JC Number</label><br>
                    <input type="text" class="form-control" value="<?php echo $row_main['jc_number']; ?>" readonly></td>
                  </div>

                  <div class="form-group">
                    <label for="vandor_name" class="form-label">Vandor Name</label><br>
                    <input type="text"name="vandor_name"id="vandor_name"class="form-control" value="<?php echo $row_main['vandor_name']; ?>"readonly></td>
                  </div>

                </div>

                <div class="col-md-8">
                  
                  <div class="card card-info card-outline">
                    <div class="card-header">
                      PURCHASE ORDER ITEM DETAILS
                    </div>
                    <div class="card-body">
                      <table class="table table-bordered table-striped" style="width:100%;">
                        <thead>
                          <tr style="text-align:center;">
                            <th style="width:5%">Sl.No</th>
                            <th style="width:65%">Description</th>
                            <th style="width:15%">No Of Copy</th>
                            <th style="width:15%">Estimated Amount</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          $siNo = 0; // Initialize Si. No
                          while ($items = mysqli_fetch_assoc($sql_items)) {
                            $siNo++;
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
                  </form>
                  </div>

                </div>

              </div>

              <div class="row">
                <div class="col-md-12">
                  <div class="card card-info card-outline">
                    <div class="card-header">
                      VENDOR INVOICE DETAILS AGAINST PO
                    </div>
                    <div class="card-body">
                      <form action=""method="POST">
                        <table class="table table-bordered table-striped" style="width:100%;">
                          <thead>
                            <tr>
                              <th>Invoice Number<span class="text-danger">*</span></th>
                              <th>Invoice Date<span class="text-danger">*</span></th>
                              <th>Amount<span class="text-danger">*</span></th>
                              <th>Freight</th>
                              <th>Addl.Expences</th>
                              <th>Total Expences</th>
                              <th>Notes</th>
                            </tr>
                          </thead>
                          <tbody>
                            <tr>
                              <td><input type="text"name="invoice_number"id="invoice_number"class="form-control" required></td>
                              <td><input type="date"name="invoice_date"id="invoice_date"class="form-control" required></td>
                              <td><input type="number"name="amount"id="amount"class="form-control" step="0.01" placeholder="0.00" required></td>
                              <td><input type="number"name="freight"id="freight"class="form-control" step="0.01" placeholder="0.00"></td>
                              <td><input type="number"name="addl_expences"id="addl_expences"class="form-control" step="0.01" placeholder="0.00"></td>
                              <td><input type="number"name="total_expences"id="total_expences"class="form-control" readonly step="0.01" placeholder="0.00"></td>
                              <td><input type="text"name="s_descriptions"id="s_descriptions"class="form-control"></td>
                            </tr>
                          </tbody>
                        </table>
                    </div>
                    <div class="card-footer">
                      <button type="submit" class="btn btn-success"name="ok">Save and Finish</button>
                      <a href="production-jc-view.php?jc_number=<?php echo $jc_number ?>" class="btn btn-danger">Close (Without Save)</a>
                      </form>
                    </div>
                  </div>
                </div>
              </div>

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
    // Function to calculate the total amount
    function calculateTotalAmount() {
        var amount = parseFloat(document.getElementById("amount").value) || 0; // Parse amount value as a float, default to 0 if not a valid number
        var addl_expences = parseFloat(document.getElementById("addl_expences").value) || 0; // Parse addl_expences value as a float, default to 0 if not a valid number
        var freight = parseFloat(document.getElementById("freight").value) || 0; // Parse addl_expences value as a float, default to 0 if not a valid number

        var total_expences = amount +freight+ addl_expences;
        document.getElementById("total_expences").value = total_expences.toFixed(2); // Display the total amount with 2 decimal places
    }

    // Attach an event listener to the "amount" and "addl_expences" input fields to trigger the calculation
    document.getElementById("amount").addEventListener("input", calculateTotalAmount);
    document.getElementById("addl_expences").addEventListener("input", calculateTotalAmount);
    document.getElementById("freight").addEventListener("input",calculateTotalAmount);
    // Calculate and display the initial total amount when the page loads
    window.addEventListener("load", calculateTotalAmount);
</script>

</body>
</html>