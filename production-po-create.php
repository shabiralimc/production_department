<?php
// Include Database Connection
include_once "../../include/php/connect.php";

// Start the session (make sure you have this at the beginning of your PHP script)
session_start(); 
error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION['user']) || $_SESSION['role'] !== '2') {
    echo "<script>alert('You are not authorised to view the URL - Please login using your username and password before accessing URL...'); window.location = '$app_url';</script>";
    exit();
}

  function generateUserId($conn) {
      // Query the database to get the current maximum user ID
      $query = "SELECT MAX(po_number) AS max_po_number FROM production_jc_po_main";
      $result = mysqli_query($conn, $query);
      if ($result && mysqli_num_rows($result) > 0) {
          $row = mysqli_fetch_assoc($result);
          $maxId = $row['max_po_number'];
          // Generate the next user ID by incrementing the current maximum
          // and adding a prefix or formatting as needed
          if ($maxId === null) {
              // If no users exist yet, start from a specific value
              $nextId = 'P00001';
          } else {
              // Increment the numeric part and pad with zeros
              $nextId = 'P' . str_pad((int)substr($maxId, 1) + 1, 5, '0', STR_PAD_LEFT);
          }
          return $nextId;
      } else {
          // Handle errors or initial case when no users exist
          return 'P00001';
      }
  }
  
  if (isset($_GET['jc_number'])) {
      $jc_number=$_GET['jc_number'];
  // Use prepared statements to fetch data based on jc_number for jobcard_main
  $sql_main1 = "SELECT jc_number FROM jobcard_main WHERE jc_number = ?";
  $stmt_main1 = mysqli_prepare($conn, $sql_main1);
  
  if ($stmt_main1) {
      mysqli_stmt_bind_param($stmt_main1, "s", $newUserId);
      mysqli_stmt_execute($stmt_main1);
      $result_main1 = mysqli_stmt_get_result($stmt_main1);
      $row_items = mysqli_fetch_assoc($result_main1);
      mysqli_stmt_close($stmt_main1);
  }
  
  }
  if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ok"])) {
      $newUserId = generateUserId($conn); 
      $_SESSION['new']=$newUserId;
  
  
      if (isset($_POST['jc_number'], $_POST['shipping'],$_POST['po_date'], $_POST['vandor_name'], $_POST['sample_required'],$_POST['s_status'], $_POST['delivery_deadline'])) {
          $jc_number = $_POST['jc_number'];
  
          $shipping = $_POST['shipping'];
          $po_date = $_POST['po_date'];
          $vandor_name = $_POST['vandor_name'];
          $sample = $_POST['sample_required'];
          $delivery_deadline = $_POST['delivery_deadline'];
          $status = $_POST['s_status'];
          $pname=$_SESSION['user'];
  
          // Insert a new record with the incremented id
          $insert_sql = "INSERT INTO production_jc_po_main(po_number,jc_number,shipping,po_date,vandor_name,sample_required,delivery_deadline,s_status,user) VALUES (?,?,?,?,?,?,?,?,?)";
          $stmt1 = $conn->prepare($insert_sql);
          $stmt1->bind_param("sssssssss", $newUserId, $jc_number, $shipping,$po_date, $vandor_name, $sample,$delivery_deadline,$status,$pname);
          if ($stmt1->execute()) {

          }else{
              echo "Error: " . $stmt1->error;
          }
          $stmt1->close();
      }
  
      if(isset($_POST['s_description'], $_POST['no_of_copy'], $_POST['estimated_amount'])) {
  
          $descriptions = $_POST['s_description'];
          $no_of_copys = $_POST['no_of_copy'];
          $estimated_amounts = $_POST['estimated_amount'];
  
           // Check if these variables are arrays before using count()
      if (is_array($descriptions) && is_array($no_of_copys) && is_array($estimated_amounts)) {
          for ($i = 0; $i < count($descriptions); $i++) {
              // Extract values for the current row
              $description = $descriptions[$i];
              $no_of_copy = $no_of_copys[$i];
              $estimated_amount = $estimated_amounts[$i];
  
  
           $sql = "INSERT INTO production_jc_po_items (po_number,jc_number,s_description, no_of_copy, estimated_amount) VALUES (?,?,?,?,?)";
          $stmt2 = $conn->prepare($sql);
          $stmt2->bind_param("sssii", $newUserId,$jc_number,$description, $no_of_copy, $estimated_amount);
          if ($stmt2->execute()) {
              echo "<script>alert('The PO Created Successfully...'); window.location.href = '$app_url/user/production/production-jc-view.php?jc_number=$jc_number';</script>";
          }else{
              echo "Error: " . $stmt2->error;
          }
          $stmt2->close();
  }
  }
  }
  }
  $newUserId = generateUserId($conn);

$sql_vandor = "SELECT DISTINCT vandor_name FROM vandor_masters ORDER BY vandor_name ASC";
$vandor_result = mysqli_query($conn, $sql_vandor);

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
              <form action=""method="POST">
                <input type="hidden" name="jc_number" value="<?php echo $jc_number; ?>">
                <input type="hidden" name="s_status" id="s_status" value="ongoing">
                <div class="row">
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="po_number"class="form-label"id="po_number">Purchase Order Number</label>
                      <input type="text"class="form-control" value="<?php echo $newUserId; ?>"readonly>
                    </div>
                    <div class="form-group">
                      <label for="po_date"class="form-label">Purchase Order Date</label><span class="text-danger">*</span>
                      <input type="date"name="po_date"id="po1"class="form-control" value="<?php echo date("Y-m-d"); ?>" readonly required>
                    </div>
                    <div class="form-group">
                      <label for="jc_number"class="form-label"id="jc_number">JC Number</label>
                      <input type="text" class="form-control" value="<?php echo $jc_number; ?>" readonly>
                    </div>
                  </div>
                  <div class="col-sm-6">
                    <div class="form-group">
                      <label for="vandor"class="form-label">Vendor Name</label><span class="text-danger">*</span>
                      <select name="vandor_name" id="vandor_name"class="form-control select2bs4" required>
                        <option disabled value="" selected hidden>--Select Option--</option>
                        <?php while ($vandor_row = mysqli_fetch_array($vandor_result)) {
                        $vandor_name_from_data = $vandor_row['vandor_name'];
                        echo '<option value="'.$vandor_name_from_data.'">'.$vandor_name_from_data.'</option>';
                      }
                        ?>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="shipping"class="form-label">Shipping Method</label><span class="text-danger">*</span>
                      <select name="shipping" id="shipping"class="form-control" required>
                        <option disabled value="" selected hidden>--Select Option--</option>
                        <option value="pickup">Pick Up</option>
                        <option value="delivery">Delivery</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="sample"class="form-label">Sample Required</label><span class="text-danger">*</span>
                      <select name="sample_required" id="sample_required"class="form-control" required>
                        <option disabled value="" selected hidden>--Select Option--</option>
                        <option value="Yes">Yes</option>
                        <option value="No">No</option>
                      </select>
                    </div>
                    <div class="form-group">
                      <label for="delivery_deadline"class="form-label">Delivery Deadline</label><span class="text-danger">*</span>
                      <input type="date" class="form-control" name="delivery_deadline" id="delivery_deadline">
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-sm-12">
                    <div class="card card-info card-outline">
                      <div class="card-header">
                        PURCHASE ORDER ITEMS DETAILS
                      </div>
                      <div class="card-body">
                        <table class="table table-bordered table-striped" border="2" id="dataTable">
                          <thead>
                            <tr>
                              <th></th>
                              <th>Sl.No</th>
                              <th>Description</th>
                              <th>No Of Copy</th>
                              <th>Estimated Amount</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php
                              $siNo = 0; // Initialize Si. No
                              $index = 0;
                              
                                  $index++;
                              ?>
                            <tr id="row<?php echo $index; ?>">
                              <td><input type="checkbox" name="delete[]" id="delete<?php echo $index; ?>" class="delete-checkbox" style="width: 20px; height: 20px;"> </td>
                              <td><?php echo $siNo++; ?></td>
                              <td><input type="text" name="s_description[]" id="s_description" class="form-control" required></td>
                              <td><input type="number" name="no_of_copy[]" id="no_of_copy" class="form-control" ></td>
                              <td><input type="number" name="estimated_amount[]" id="estimated_amount" class="form-control" required></td>
                            </tr>
                          </tbody>
                        </table>
                      </div>
                      <div class="card-footer">
                        <button class="btn btn-success" type="button" name="addrow" id="addrow"><i class="fa fa-plus"></i> Add Row</button>
                        <button class="btn btn-danger"  type="button" name="deleterow" id="deleterow"><i class="fa fa-minus"></i> Delete Row</button><br>
                      </div>
                    </div>
                  </div>
                </div>
            </div>
            <div class="card-footer">
            <button class="btn btn-success" type="submit" name="ok">SAVE PURCHASE ORDER</button>
            <button class="btn btn-info" name="close"><a href="production-jc-view.php?jc_number=<?php echo $jc_number; ?>" style="text-decoration: none; color: white;">Close (without save)</a></button>
            </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<!-- Include Footer File -->
<?php include_once ('../../include/php/footer.php') ?>
<script>
  document.addEventListener("DOMContentLoaded", function () {
      var currentRow =0; // Initialize the row counter
  
    // Add a new row when the "Add More" button is clicked
    document.getElementById("addrow").addEventListener("click", function () {
          currentRow++; // Increment the row counter
  
          // Clone the last row of the table
          var table = document.getElementById("dataTable");
          var lastRow = table.rows[table.rows.length - 1];
          var newRow = lastRow.cloneNode(true);
          newRow.id = "row" + currentRow;
  
          // Clear the input fields in the new row
          var inputs = newRow.querySelectorAll("input, select");
          inputs.forEach(function (input) {
              var oldId = input.id;
              var newId = oldId.replace(/\d+/, currentRow); // Update the numeric part of the ID
              input.id = newId;
              input.value = "";
  
              // Update the name attribute as well to make it unique
              var oldName = input.name;
              var newName = oldName.replace(/\d+/, currentRow); // Update the numeric part of the name
              input.name = newName;
  
              input.value = "";
          });
  
          // Increment the Sl.No in the new row
          var slNoCell = newRow.querySelector("td:nth-child(2)");
          slNoCell.textContent = currentRow;
  
          // Append the new row to the table
          table.appendChild(newRow);
      });
  
      // Add a click event listener to the "Delete" button
      document.getElementById("deleterow").addEventListener("click", function () {
          // Get all checkboxes with class "delete-checkbox"
          var checkboxes = document.querySelectorAll(".delete-checkbox");
          var firstRowCheckbox = checkboxes[0]; // Get the checkbox of the first row
          checkboxes.forEach(function (checkbox) {
  
          // Loop through the checkboxes and remove the corresponding row if checked
          for (var i = 1; i < checkboxes.length; i++) {
              if (checkbox.checked) {
                  // Find the parent row and remove it
                  var row = checkbox.closest("tr");
                  row.parentNode.removeChild(row);
              }
          }
          // Uncheck the checkbox of the first row to prevent deletion
          firstRowCheckbox.checked = false;
      });
  });
      });
</script>

<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })
  })

</script>

</body>
</html>