<?php
	
	//IMPORTANT - set this value below before proceeding
	$api_key = 'e4cc229d88e4ac868b7d'; //should be a string - get this from My Account >> API Key
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">	
<html>
	<head>
	</head>
	<body>
		
<h1>Weight Tracker</h1>
<p>This app demonstrates a very simple use case for StatsMix. It is a form that allows you to enter your weight and store it in StatsMix. StatsMix automatically generates a time-series chart of the results.</p>
	<?php
	
	
	
	if ($_POST) {
		require 'StatsMix.php';
		StatsMix::set_api_key($api_key);
		StatsMix::track('Daily Weight',$_POST['weight']);
		//set the timestamp to the current UTC date (this is the default, so you can leave it blank)
		$stat->generated_at = gmdate('Y-m-d H:i:s'); //
		if(!StatsMix::get_error()){
			echo "<p>Successfully logged weight.</p>";
			echo '<pre>';
			print_r(htmlentities(StatsMix::get_response()));
			echo '</pre>';
		} else {
			echo "<p>Failed to log weight. Error message is: <strong>" . StatsMix::get_error() . "</strong></p>";
		}
	
	}
	
	?>
	<div>
	<form method="post">
		<label>Enter your weight:<input name="weight" type="text" size = "3" value="<?php intval(@$_POST['weight'])?>"/></label>
		<input type="submit" value="submit" />
	</form>
	</div>


	</body>
</html>
