<?php
error_reporting(E_ALL | E_STRICT);
ini_set('display_errors', 1);
ini_set('display_startup_errors', TRUE);
ini_set("log_errors",true);
ini_set("html_errors",false);
ini_set("error_log","/var/log/php_error_log");
include 'private.php';
?>

<!DOCTYPE html>
<head>
  <title>Video Store Database</title>
</head>
  <body>
  	<p><h3>Add Video</h3><p>
  	  <form action="videoDB.php" method="post">
  	  	Name: <br><input type="text" name="name" required="required"><br>
  	  	Category: <br><input type="text" name="category"><br>
  	  	Length in minutes: <br><input type="number" name="length" min="0"><br>
  	  	<input type="submit" value="Add">
  	  </form>
  	
<?php
//Code taken from php.net documentation and lecture videos (lines 6 -)
//Create new connection to MySQL server
$mysqli = new mysqli("oniddb.cws.oregonstate.edu", "burrellp-db", $myPassword, "burrellp-db");
//Test for successful connection
if(!$mysqli || $mysqli->connect_errno) {
 	echo "Failed to connect to MySQL: (". $mysqli->errno . ")" . $mysqli->connect_error;
 }
//Set up prepared statement for displaying videos and test for success
if(!$stmt = $mysqli->prepare("SELECT name, category, length, rented FROM videos")) {
	echo "Prepare failed: (" . $stmt->errno . ")" . $stmt->error;
}
//Bind results and test for success
if(!$stmt->bind_result($name, $category, $length, $status)) {
	echo "Binding results failed: (" . $stmt->errno . ")" . $stmt->error;
}
//Execute statment and test for success
if(!$stmt->execute()) {
	echo "Execute failed: (" . $stmt->errno . ")" . $stmt->error;
}
?>

<p><h3>Video Inventory</h3></p>
  	<table border="1">
      <tr>
      	<td>Name</td>
      	<td>Category</td>
      	<td>Length</td>
      	<td>Status</td>

<?php
	//Array storing different category entries
	$catArr = array();
	//While loop to populate table while there is video information available
	while($stmt->fetch()) {
		//Add movie information from table row by row
		//First see if filter has been applied to results
		if(isset($_POST['filter'])){
			//Only add videos with category matching filter
			if($category == $_POST['filter'] || $_POST['filter'] == 'all' ) {
				echo "<tr><td>".$name."</td>";
				echo "<td>".$category."</td>";
				echo "<td>".$length."</td>";
				//Set status to either Available or checked out
				if($status == 0){
					$displayStatus = "Available";
				} else {
					$displayStatus = "Checked Out";
				}
				//Create button to toggle status back and forth
				echo "<td><form action = 'videoDB.php' method='post' id='".$name."'><button type='submit' name='status' value='".$name."'>".$displayStatus."</button></form></td>";
				//Create button to delete
				echo "<td><form action = 'videoDB.php' method='post' id='".$name."'><button type='submit' name='delete' value='".$name."'>Delete</button></form></td>";
			}
			
		}
	 else {
		echo "<tr><td>".$name."</td>";
		echo "<td>".$category."</td>";
		echo "<td>".$length."</td>";
		//Set status to either Available or checked out
		if($status == 0) { 
			$displayStatus = "Available";
		} else {
			$displayStatus = "Checked out";
		}
		//Create button to toggle status back and forth
		echo "<td><form action = 'videoDB.php' method='post' id='".$name."'><button type='submit' name='status' value='".$name."'>".$displayStatus."</button></form></td>";
		//Create button to delete
		echo "<td><form action = 'videoDB.php' method='post' id='".$name."'><button type='submit' name='delete' value='".$name."'>Delete</button></form></td>";

	}
		//in_array function found at http://php.net/manual/en/function.in-array.php
		//Add category to array if it has been entered by user and is unique
		if($category != "" && !in_array($category, $catArr)) {
			array_push($catArr, $category);
		}
	}
?>
<tr><td><form action='https://web.engr.oregonstate.edu/~burrellp/videoDB.php' method='post'>
	<button type='submit' name='deleteAll' method='post'>Delete All</button></td></tr></form>
</table>

<p><h3>Filter by Category</h3></p>
<form action="videoDB.php" method="post">
  <select name="filter">
  	<option value='all'>All Categories</option>
  	<?php
  	//Add filter option for each existing category
  	foreach($catArr as $value) {
  		echo "<option value='".$value."'>'".$value."'</option>";
  	}
  	unset($value);
  	?>
  </select>
  <button type="submit">Filter Results</button>
</form>


<?php
//If somebody has attempted to add a movie by entering a name
if(isset($_POST['name'])) {
	//Assign name, length, category, and rented variables
	$newName = $_POST['name'];
	if(isset($_POST['length'])) {
		$newLength = $_POST['length'];
	} else {
		$newLength = 0;
	}
	if(isset($_POST['category'])) {
		$newCategory = $_POST['category'];
	} else {
		$newCategory = "other";	
	}
	$newRented = 0;

	//Insert code taken from http://www.tutorialspoint.com/mysql/mysql-insert-query.htm
	$addMovie = "INSERT INTO videos ".
	"(name, category, length, rented)".
	"VALUES ".
	"('$newName', '$newCategory', '$newLength', '$newRented')";

	if($mysqli->query($addMovie) === TRUE) {
		echo "<br><br>'".$newName."' successfully added";	
	} else {
		echo "Error: " . $addMovie . $mysqli->error;
	}
	//Method for refreshing page found at http://stackoverflow.com/questions/12383371/refresh-a-page-using-php
	echo '<meta http-equiv="refresh" content="0"; url="videoDB.php">';

	mysql_close();
}

//Condition where movie's status button has been selected
if(isset($_POST['status'])) {
	$changeMovie = $_POST['status'];
	$updateStatus = "UPDATE videos SET rented = !rented WHERE name = '$changeMovie'";

	if($mysqli->query($updateStatus) === TRUE) {
		echo "<br><br>".$changeMovie."successfully updated";
	} else {
		echo "Error: " . $updateStatus . $mysqli->error;
	}
	//Method for refreshing page found at http://stackoverflow.com/questions/12383371/refresh-a-page-using-php
	echo "<meta http-equiv='refresh' content='0'; url='videoDB.php'>";

	mysql_close();
}

//Condition where movie has been selected for deletion
if(isset($_POST['delete'])) {
	$goneMovie = $_POST['delete'];
	$deleteMovie = "DELETE FROM videos WHERE name = '$goneMovie'";

	if($mysqli->query($deleteMovie) === TRUE) {
		echo "<br><br>".$goneMovie."successfully deleted";
	} else {
		echo "Error: " . $deleteMovie . $mysqli->error;
	}
	//Method for refreshing page found at http://stackoverflow.com/questions/12383371/refresh-a-page-using-php
	echo "<meta http-equiv='refresh' content='0'; url='videoDB.php'>";

	mysql_close();
}

//Condition where user selects Delete All
if(isset($_POST['deleteAll'])) {
	$deleteAll = "DELETE FROM videos";

	if($mysqli->query($deleteAll) === TRUE) {
		echo "<br><br>Inventory successfully deleted";
	} else {
		echo "Error: " . $deleteAll . $mysqli->error;
	}
	//Method for refreshing page found at http://stackoverflow.com/questions/12383371/refresh-a-page-using-php
	echo "<meta http-equiv='refresh' content='0'; url='videoDB.php'>";

	mysql_close();
}

?>
  </body>
</html>