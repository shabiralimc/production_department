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

if (isset($_GET['po_number'])) {
    $po_number = $_GET['po_number'];
    $_SESSION['po_number']=$po_number;
   // Use prepared statements to fetch data based on po_number for production_jc_po_main
   $sql_main1 = "SELECT po_number,jc_number,shipping,po_date, vandor_name, sample_required,s_status FROM production_jc_po_main WHERE po_number = ?";
   $stmt_main1 = mysqli_prepare($conn, $sql_main1);

   if ($stmt_main1) {
       mysqli_stmt_bind_param($stmt_main1, "s", $po_number);
       mysqli_stmt_execute($stmt_main1);
       $result_main1 = mysqli_stmt_get_result($stmt_main1);
       $row_main1 = mysqli_fetch_assoc($result_main1);
       mysqli_stmt_close($stmt_main1);
   }
   // Use prepared statements to fetch all rows of data based on po_number for jobcard_items
   $sql_items1 = "SELECT s_description, no_of_copy, estimated_amount FROM production_jc_po_items WHERE po_number = ?";
   $stmt_items1 = mysqli_prepare($conn, $sql_items1);

   if ($stmt_items1) {
       mysqli_stmt_bind_param($stmt_items1, "s", $po_number);
       mysqli_stmt_execute($stmt_items1);
       $result_items1 = mysqli_stmt_get_result($stmt_items1);
}
// Check if the record with the given po_number exists in the production_jc_po_main table
$check_sql = "SELECT po_number FROM production_jc_po_main WHERE po_number = ?";
$stmt_check = mysqli_prepare($conn, $check_sql);

if ($stmt_check) {
    mysqli_stmt_bind_param($stmt_check, "s", $po_number);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

     // If the record exists in production_jc_po_main fetch and display existing data
     if ($row_check = mysqli_fetch_assoc($result_check)){
        $isUpdateMain = true;

        $sql_main = "SELECT po_number,jc_number, shipping, po_date, vandor_name, sample_required, delivery_deadline, s_status FROM production_jc_po_main WHERE po_number = ?";
        $stmt_main = mysqli_prepare($conn, $sql_main);

        if ($stmt_main) {
            mysqli_stmt_bind_param($stmt_main, "s", $po_number);
            mysqli_stmt_execute($stmt_main);
            $result_main = mysqli_stmt_get_result($stmt_main);

            // Fetch data from the result
            if ($row_main = mysqli_fetch_assoc($result_main)) {
                // Now you have data in $row_main that you can use to populate your form fields
                $jc_number = $row_main['jc_number'];
                $shipping = $row_main['shipping'];
                $po_date = $row_main['po_date'];
                $vandor_name = $row_main['vandor_name'];
                $simple_required = $row_main['sample_required'];
                $delivery_deadline = $row_main['delivery_deadline'];
                $status = $row_main['s_status'];
            }
        }
    } else {
        $isUpdateMain = false;
    }

    mysqli_stmt_close($stmt_check);
} else {
    $isUpdateMain = false;
}

$existingItems = []; // Initialize as an empty array
// Check if the record with the given po_number exists in the production_jc_po_items table
$check_items_sql = "SELECT po_number FROM production_jc_po_items WHERE po_number = ?";
$stmt_check_items = mysqli_prepare($conn, $check_items_sql);

if ($stmt_check_items) {
    mysqli_stmt_bind_param($stmt_check_items, "s", $po_number);
    mysqli_stmt_execute($stmt_check_items);
    $result_check_items = mysqli_stmt_get_result($stmt_check_items);

     // If the record exists in creative_items, fetch and display existing data
     if (mysqli_num_rows($result_check_items) > 0) {
        $isUpdateItems = true;
        $sql_items = "SELECT id, s_description, no_of_copy, estimated_amount FROM production_jc_po_items WHERE po_number = ?";
        $stmt_items = mysqli_prepare($conn, $sql_items);

        if ($stmt_items) {
            mysqli_stmt_bind_param($stmt_items, "s", $po_number);
            mysqli_stmt_execute($stmt_items);
            $result_items = mysqli_stmt_get_result($stmt_items);

             // Initialize $row_item
             $row_item = null;

             // Loop through the results if needed
             while ($row_item = mysqli_fetch_assoc($result_items)) {
                 // Store data for each row in the array
                 $existingItems[] = $row_item;
                 
             }

              // Close the $stmt_items after the loop
            mysqli_stmt_close($stmt_items);
        }
    } else {
        $isUpdateItems = false;
    }

    mysqli_stmt_close($stmt_check_items);
} else {
    $isUpdateItems = false;
}
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["ok"])) {
    if (isset($_POST['jc_number'], $_POST['shipping'], $_POST['po_date'], $_POST['vandor_name'],$_POST['simple_required'], $_POST['s_status'])) {
        $_SESSION['po_number']=$po_number;
        $jc_number = $_POST['jc_number'];
        $shipping = $_POST['shipping'];
        $po_date = $_POST['po_date'];
        $vandor_name = $_POST['vandor_name'];
        $simple_required = $_POST['simple_required'];
        $delivery_deadline = $_POST['delivery_deadline'];
        $status = $_POST['s_status'];

       // Handle creative_main table
       if ($isUpdateMain) {
        $sql_update_main = "UPDATE production_jc_po_main SET jc_number=?, shipping=?, po_date=?, vandor_name=?, sample_required= ?, delivery_deadline=?, s_status=? WHERE  po_number=?";
        $stmt_main2= $conn->prepare($sql_update_main);
        $stmt_main2->bind_param("ssssssss", $jc_number, $shipping, $po_date, $vandor_name, $simple_required, $delivery_deadline, $status, $po_number);
    }
    if ($stmt_main2->execute()) {
        
    } else {
        echo "Error: " . $stmt_main2->error;
    }
    }

    
    // Handle creative_items table
    if (isset($_POST['s_description'], $_POST['no_of_copy'], $_POST['estimated_amount'])) {
        $descriptions = $_POST['s_description'];
        $no_of_copys = $_POST['no_of_copy'];
        $estimated_amounts = $_POST['estimated_amount'];
        $itemIds = $_POST['item_id']; // Add item_id field to identify existing rows

        // Loop through the arrays and insert/update each row separately
        for ($i = 0; $i < count($descriptions); $i++) {
            $description = $descriptions[$i];
            $no_of_copy = $no_of_copys[$i];
            $estimated_amount = $estimated_amounts[$i];
            $itemId = $itemIds[$i]; // Get the item_id for this row
// Check if this row should be updated or inserted
if (!empty($itemId)) {
    // Update the existing record in the production_jc_po_items table
    $update_items_sql = "UPDATE production_jc_po_items SET s_description=?, no_of_copy=?, estimated_amount=? WHERE id=?";
    $stmt_items2 = $conn->prepare($update_items_sql);
    $stmt_items2->bind_param("siii", $description, $no_of_copy, $estimated_amount, $itemId);
} else {
    // Insert a new record into the creative_items table
    $insert_items_sql = "INSERT INTO production_jc_po_items (po_number,jc_number, s_description, no_of_copy, estimated_amount) VALUES (?, ?, ?, ?, ? )";
    $stmt_items2 = $conn->prepare($insert_items_sql);
    $stmt_items2->bind_param("sssii", $po_number,$jc_number, $description, $no_of_copy, $estimated_amount);
}

// Bind parameters and execute the statement
if ($stmt_items2->execute()) {
    echo "<script>alert('The PO Edits Saved Successfully...'); window.location.href = '$app_url/user/production/production-jc-view.php?jc_number=$jc_number';</script>";
} else {
    echo "Error: " . $stmt_items2->error;
}
}
}

}

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
                    
                <div class="row">

                  <div class="col-md-6">

                    <div class="form-group">
                      <label for="po_number" class="form-label" id="po_number">Purchase Order Number</label>
                      <input type="text" class="form-control" name="po_number" value="<?php echo isset($row_main1['po_number']) ? $row_main1['po_number'] : ''; ?>"readonly >
                    </div>

                    <div class="form-group">
                      <label for="po_date" class="form-label">Purchase Order Date</label>
                      <input type="date" name="po_date" id="po1" class="form-control" value="<?php echo $row_main1['po_date']; ?>" readonly>
                    </div>

                    <div class="form-group">
                      <label for="jc_number" class="form-label" id="jc_number">JC Number</label>
                      <input type="text" class="form-control" name="jc_number"value="<?php echo $row_main1['jc_number']; ?>"readonly>
                    </div>

                  </div>

                  <div class="col-md-6">

                    <div class="form-group">
                      <label for="vandor" class="form-label">Vendor Name</label><span class="text-danger">*</span>
                      <select name="vandor_name" id="vandor_name" class="form-control select2bs4">
                        <?php while ($vandor_row = mysqli_fetch_array($vandor_result)) {

                        $vandor_name_from_data = $vandor_row['vandor_name'];

                        echo "<option value=\"{$vandor_name_from_data}\"" , ($row_main1['vandor_name'] == $vandor_name_from_data? " selected" : "") , ">{$vandor_name_from_data}</option>";

                      }
                        ?>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="shipping" class="form-label">Shipping Method</label>
                      <select name="shipping" id="shipping" class="form-control">
                        <option value="pickup" <?php echo ($row_main1['shipping'] === 'shabir') ? 'selected' : ''; ?>>Pick Up</option>
                        <option value="delivery"<?php echo ($row_main1['shipping'] === 'delivery')?'selected' : ''; ?>>Delivery</option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="sample" class="form-label">Sample Required</label><span class="text-danger">*</span>
                      <select name="simple_required" id="simple_required" class="form-control">
                        <option value="Yes"<?php echo ($row_main1['sample_required']=== 'Yes')?'selected' : ''; ?>>Yes</option>
                        <option value="No"<?php echo  ($row_main1['sample_required'] === 'No')? 'selected' : ''; ?>>No</option>
                      </select>
                    </div>

                    <div class="form-group">
                      <label for="delivery_deadline"class="form-label">Delivery Deadline</label><span class="text-danger">*</span>
                      <input type="date" class="form-control" name="delivery_deadline" id="delivery_deadline" value="<?php echo $row_main['delivery_deadline']; ?>">
                    </div>

                    <div class="form-group">
                      <label for="s_status" class="form-label">Status</label>
                      <select name="s_status" id="s_status" class="form-control">
                        <option value="ongoing"<?php echo ($row_main1['s_status'] === 'ongoing')?'selected' : ''; ?>>Ongoing</option>
                        <option value="Completed"<?php echo ($row_main1['s_status']=== 'Completed')? 'selected' : ''; ?>>Completed</option>
                      </select>
                    </div>

                  </div>

                </div>

                <div class="row">
                  
                  <div class="col-md-12">
                    
                    <div class="card card-info card-outline">
                      
                      <div class="card-header">
                        DETAILS OF PURCHASE ORDER ITEMS
                      </div>

                      <div class="card-body">
                        
                        <table class="table table-bordered table-striped" style="width:100%;" id="dataTable">
                          <thead>
                            <tr style="text-align:center;">
                              <th style="width:5%"></th>
                              <th style="width:5%">Sl.No</th>
                              <th style="width:60%">Description</th>
                              <th style="width:15%">No Of Copy</th>
                              <th style="width:15%">Estimated Amount</th>
                            </tr>
                          </thead>
                          <tbody>
                            <?php 
                              $index = 0;
                              $siNo = 1;
                              // Loop through the data received from the form and generate rows
                              foreach ($existingItems as $index => $item) {  
                                    $index++;
                            ?>
                            <tr id="row<?php echo $index; ?>">
                              <td style="text-align:center;"><input type="checkbox" name="delete[]" id="delete<?php echo $index; ?>" class="delete-checkbox" style="width: 20px; height: 20px;"></td>
                              <td style="text-align:center;"><?php echo $siNo++; ?></td>
                              <td><input type="text" name="s_description[]" id="s_description<?php echo $index; ?>" class="form-control" value="<?php echo $item['s_description']; ?>"></td>
                              <td><input type="number" name="no_of_copy[]" id="no_of_copy<?php echo $index; ?>" class="form-control" value="<?php echo $item['no_of_copy']; ?>"></td>
                              <td><input type="number" name="estimated_amount[]" id="estimated_amount<?php echo $index; ?>" class="form-control" value="<?php echo $item['estimated_amount']; ?>"></td>
                              <input type="hidden" name="item_id[]" value="<?php echo $item['id']; ?>">
                            </tr>
                            <?php 
                            } ?>
                          </tbody>
                        </table>

                      </div>

                      <div class="card-footer">
                        <button class="btn btn-info" type="button" name="addrow" id="addrow"><i class="fa fa-plus"></i> Add Row</button>
                        <button class="btn btn-danger" type="button" name="deleterow" id="deleterow"><i class="fa fa-minus"></i> Delete</button><br>
                      </div>

                    </div>

                  </div>

                </div>
              
            </div>

            <div class="card-footer">
              <button class="btn btn-success" type="submit" name="ok">Save Completion</button>
              <button class="btn btn-info" name="close"><a style="text-decoration:none; color: white;" href="production-jc-view.php?jc_number=<?php echo $row_main1['jc_number']; ?>">Close (without save)</a></button>             
          </form>
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
document.addEventListener("DOMContentLoaded", function () {
    var currentRow = <?php echo count($existingItems); ?>; // Initialize the row counter

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

    // Add an event listener to the "Delete" button
    document.getElementById("deleterow").addEventListener("click", function () {
        var selectedIds = [];
        var checkboxes = document.querySelectorAll(".delete-checkbox:checked");

        checkboxes.forEach(function (checkbox) {
            // Get the closest row (tr) and remove it from the table
            var row = checkbox.closest("tr");
            row.remove();

            // Get the hidden input field with item_id and add its value to the selectedIds array
            var itemIdInput = row.querySelector("input[name='item_id[]']");
            if (itemIdInput) {
                selectedIds.push(itemIdInput.value);
            }
        });

        // Send the selected IDs to your server using AJAX
        if (selectedIds.length > 0) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "delete_production.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    // Handle the response from the server if needed
                    console.log(xhr.responseText);
                }
            };
            xhr.send("selectedIds=" + selectedIds.join(","));
        }
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