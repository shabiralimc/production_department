<?php
include_once('../../include/php/connect.php');
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["selectedIds"])) {
    $selectedIds = explode(",", $_POST["selectedIds"]);
    
    // Delete records from the database
    foreach ($selectedIds as $id) {
        $delete_items_sql = "DELETE FROM production_jc_po_items WHERE id=?";
        $stmt_delete_items = $conn->prepare($delete_items_sql);
        $stmt_delete_items->bind_param("i", $id);
        $stmt_delete_items->execute();

    }
    

    echo"Selected rows deleted successfully.";

}
?>
