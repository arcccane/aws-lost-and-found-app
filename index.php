<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
    </link>
</head>

<body>
    <div class="container">
        <div class="well well-sm">
            <div class="link-container">
                <a href="add-item-form.html">
                    <h4>Add Item</h4>
                </a>
                <br>
                <a href="add-notif-form.html">
                    <h4>Create Notification</h4>
                </a>
            </div>
            <h2 class="main-title"><strong>Lost and Found</strong></h2>
        </div>

        <input id="searchInput" class="search" type="text" onkeyup="filterFunction()" placeholder="Search for items ...">

        <div id="products" class="row list-group">

            <?php

            // Establish a database connection
            $host = "lost-and-found-db.cfwaf6fovbdu.us-east-1.rds.amazonaws.com";
            $user = "admin";
            $password = "password";
            $db = "lost_and_found_db";

            $conn = mysqli_connect($host, $user, $password, $db);

            // Check if the connection was successful
            if (!$conn) {
                die("Connection failed: " . mysqli_connect_error());
            }

            mysqli_set_charset($conn, "utf8");

            // Select all records from the table
            $sql = "SELECT * FROM lost_and_found";
            $result = mysqli_query($conn, $sql);

            // Check if the query was successful
            if (mysqli_num_rows($result) > 0) {

                // Output the data from each row
                while ($row = mysqli_fetch_assoc($result)) {
                    echo "<div class='item col-xs-4 col-lg-4'>";
                    echo " <div class='thumbnail'>";
                    echo "  <img class='group list-group-image' src='" . $row["image"] . "'width='400' height='300' alt='image' title='image'/>";
                    echo "  <div class='caption'>";
                    echo "   <h3 class='group inner list-group-item-heading'>" . $row["description"] . "</h3>";
                    echo "   <br>";
                    echo "   <h5 class='group inner list-group-item-heading'>" . "Category: " . $row["category"] . "</h5>";
                    echo "   <br>";
                    echo "   <div class='row'>";
                    echo "    <div class='col-xs-12 col-md-6'>";
                    echo "     <a class='btn btn-success' href=''>" . "Claim" . "</a>";
                    echo "    </div>";
                    echo "   </div>";
                    echo "  </div>";
                    echo " </div>";
                    echo "</div>";
                }

            } else {
                echo "No records found.";
            }

            // Close the database connection
            mysqli_close($conn);

            ?>
        </div>
    </div>
</body>

</html>

<script>
    function filterFunction() {
      var input, filter, products, item, description, category, i;
      input = document.getElementById("searchInput");
      filter = input.value.toLowerCase();
      products = document.getElementById("products");
      item = products.getElementsByClassName("item");
      for (i = 0; i < item.length; i++) {
        description = item[i].getElementsByTagName("h3")[0];
        category = item[i].getElementsByTagName("h5")[0];
        if (description.innerHTML.toLowerCase().indexOf(filter) > -1 || category.innerHTML.toLowerCase().indexOf(filter) > -1) {
          item[i].style.display = "";
        } else {
          item[i].style.display = "none";
        }
      } 
    }
</script>